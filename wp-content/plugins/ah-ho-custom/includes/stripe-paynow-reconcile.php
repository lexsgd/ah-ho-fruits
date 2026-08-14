<?php
/**
 * Stripe PayNow Reconciliation Safety Net
 *
 * PayNow via Stripe is async — the order stays "Pending payment" until
 * Stripe delivers `payment_intent.succeeded`. If that webhook is lost
 * (Stripe delivery failure, firewall, etc.), the order gets stuck and
 * the customer can't get their delivery.
 *
 * This cron runs every 15 minutes and:
 *   1. Finds stripe_paynow orders with no recorded payment, aged 5 min–48 hr
 *   2. Reads the stored Stripe PaymentIntent or Source ID from order meta
 *   3. Queries Stripe's API
 *   4. If Stripe says it's paid, marks the order as Processing
 *
 * Also registers an admin notice on the order screen if reconciled,
 * and an AJAX "Reconcile now" button for single-order manual retry.
 *
 * ---------------------------------------------------------------------------
 * Why step 2 alone is not enough (order #5246, 2026-08-04)
 * ---------------------------------------------------------------------------
 * Every checkout submit mints a NEW PaymentIntent and overwrites
 * `_payment_intent_id` on the order. A customer who submits checkout several
 * times gets several PayNow QR codes; if she pays an EARLIER one and then taps
 * through checkout again, the order ends up storing a later intent that will
 * never be paid, while the money sits on an intent the order no longer points
 * at. The stored id is therefore only a hint, not the truth.
 *
 * Stripe stamps `metadata.order_id` on every intent the gateway creates, so
 * that — not the stored id — is the durable link back to the order. When the
 * stored intent is missing or unpaid we search Stripe by `metadata.order_id`
 * and reconcile against whichever intent actually succeeded, then repair the
 * stored id so the order and Stripe agree from then on.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ah_Ho_Stripe_PayNow_Reconcile {

    const CRON_HOOK        = 'ah_ho_stripe_paynow_reconcile';
    const LOG_SOURCE       = 'ah-ho-paynow-reconcile';
    const MIN_AGE_SECONDS  = 300;       // 5 min — give real webhook a chance
    const MAX_AGE_SECONDS  = 172800;    // 48 hr — don't scan ancient orders
    const STRIPE_API_BASE  = 'https://api.stripe.com/v1';
    const LEDGER_MAX_PAGES = 5;         // 500 payments per run, plenty for 48 hr
    const HEARTBEAT_OPTION = 'ah_ho_paynow_last_sweep';
    const ALERTED_OPTION   = 'ah_ho_paynow_alerted_exceptions';
    const STALE_AFTER      = 7200;      // 2 hr with no sweep = the net is down

    public static function init() {
        add_action('init', [__CLASS__, 'schedule_cron']);
        add_action(self::CRON_HOOK, [__CLASS__, 'run']);
        add_filter('cron_schedules', [__CLASS__, 'register_interval']);

        // Never let an unpaid PayNow order be auto-cancelled while the money
        // could still be in flight. This is what the customer sees.
        add_filter('woocommerce_cancel_unpaid_order', [__CLASS__, 'protect_pending_paynow'], 10, 2);

        add_action('admin_notices', [__CLASS__, 'render_heartbeat_notice']);

        add_action('wp_ajax_ah_ho_reconcile_order', [__CLASS__, 'ajax_reconcile_order']);
        add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__, 'render_admin_button']);
        add_action('admin_footer', [__CLASS__, 'render_admin_script']);
    }

    /**
     * WooCommerce cancels unpaid orders once "Hold stock (minutes)" elapses.
     * That rule was written for cards, where payment is instant and an unpaid
     * order really is abandoned. PayNow is asynchronous: the customer is in
     * their banking app, and the money can land minutes later — or the webhook
     * confirming it can be delayed. Cancelling on that timer is what produces
     * "the system indicate payment cancelled" while the bank says settled.
     *
     * So PayNow orders are exempt for the whole reconciliation window. After
     * 48 hr the sweep has had many chances to find the money; if it hasn't,
     * the order genuinely was abandoned and normal cancellation resumes.
     */
    public static function protect_pending_paynow($cancel, $order) {
        if (!$cancel || !$order instanceof WC_Order) {
            return $cancel;
        }
        if ($order->get_payment_method() !== 'stripe_paynow') {
            return $cancel;
        }
        if ($order->get_date_paid('edit')) {
            return false; // paid — never cancel, whatever the timer thinks
        }
        $created = $order->get_date_created();
        if ($created && $created->getTimestamp() > (time() - self::MAX_AGE_SECONDS)) {
            self::log(sprintf(
                'Order %d: blocked hold-stock auto-cancel; PayNow payment may still be in flight.',
                $order->get_id()
            ));
            return false;
        }
        return $cancel;
    }

    public static function register_interval($schedules) {
        if (!isset($schedules['every_fifteen_minutes'])) {
            $schedules['every_fifteen_minutes'] = [
                'interval' => 15 * MINUTE_IN_SECONDS,
                'display'  => __('Every 15 minutes', 'ah-ho-custom'),
            ];
        }
        return $schedules;
    }

    public static function schedule_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'every_fifteen_minutes', self::CRON_HOOK);
        }
        // Seed the heartbeat so the "net is down" notice measures from first
        // scheduling, not from the epoch — otherwise it cries wolf for the few
        // minutes between deploy and the first sweep.
        if (!get_option(self::HEARTBEAT_OPTION)) {
            update_option(self::HEARTBEAT_OPTION, time(), false);
        }
    }

    /**
     * Order statuses worth scanning: everything except the terminal ones.
     *
     * Deliberately wider than pending/on-hold. Staff routinely move a stuck
     * order forward by hand (order #5246 was pushed to Out for Delivery while
     * still unpaid), and those are exactly the ones that would otherwise never
     * get their payment recorded.
     *
     * `cancelled` and `failed` are scanned too, since 2026-08-14. The multi-QR
     * case this whole file exists for ENDS in a cancelled order: the customer
     * pays an earlier PayNow QR, the later intent the order points at is never
     * paid and expires, and the gateway cancels the order — while the money is
     * sitting in Stripe against the earlier intent. Skipping cancelled orders
     * meant the safety net switched itself off in precisely the case it was
     * built for, and the manual button vanished from the screen at the same
     * time. `refunded` stays excluded: there the money has already gone back.
     */
    private static function scannable_statuses() {
        $statuses = array_keys(wc_get_order_statuses());
        $terminal = ['wc-refunded', 'wc-checkout-draft'];
        return array_values(array_diff($statuses, $terminal));
    }

    public static function run() {
        $report = ['reconciled' => [], 'exceptions' => []];

        // Pass 1 — order-first. Walk our own unpaid orders and ask Stripe about
        // each. Fast, and the common case.
        $orders = wc_get_orders([
            'status'         => self::scannable_statuses(),
            'payment_method' => 'stripe_paynow',
            'date_created'   => '>' . (time() - self::MAX_AGE_SECONDS),
            'limit'          => 50,
            'return'         => 'objects',
        ]);

        $cutoff = time() - self::MIN_AGE_SECONDS;
        foreach ($orders as $order) {
            if ($order->get_date_paid('edit')) {
                continue; // payment already recorded — nothing to reconcile
            }
            $created = $order->get_date_created();
            if (!$created || $created->getTimestamp() > $cutoff) {
                continue;
            }
            if (self::reconcile($order)) {
                $report['reconciled'][] = $order->get_id();
            }
        }

        // Pass 2 — money-first. Walk everything Stripe actually took in and
        // prove each payment landed on an order. See sweep_stripe_ledger().
        self::sweep_stripe_ledger($report);

        update_option(self::HEARTBEAT_OPTION, time(), false);

        // An unmatchable payment stays unmatchable until a human deals with it,
        // and this runs every 15 minutes. Without this, one stuck payment would
        // send 192 identical emails over the 48 hr window and train everyone to
        // ignore the alert — so each distinct exception is reported once.
        $report['exceptions'] = self::filter_new_exceptions($report['exceptions']);

        if ($report['reconciled'] || $report['exceptions']) {
            self::send_alert($report);
        }
    }

    /**
     * Drop exceptions already emailed about, and forget ones old enough to have
     * aged out of the window so a genuinely recurring problem can re-alert.
     */
    private static function filter_new_exceptions(array $exceptions) {
        $seen = get_option(self::ALERTED_OPTION, []);
        if (!is_array($seen)) {
            $seen = [];
        }

        $now = time();
        foreach ($seen as $key => $stamp) {
            if (!is_int($stamp) || $stamp < ($now - self::MAX_AGE_SECONDS)) {
                unset($seen[$key]);
            }
        }

        $fresh = [];
        foreach ($exceptions as $exception) {
            $key = md5($exception);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = $now;
            $fresh[]    = $exception;
        }

        update_option(self::ALERTED_OPTION, $seen, false);
        return $fresh;
    }

    /**
     * Money-first reconciliation: start from Stripe, end at our orders.
     *
     * Pass 1 can only find money for orders it already suspects. It cannot
     * catch a payment whose order meta is wrong in a way we did not anticipate,
     * an order deleted or trashed, or a charge that never got stamped onto an
     * order at all — and "some money in Stripe never reached an order" is the
     * failure the shop actually feels.
     *
     * So we invert it. Stripe is the source of truth for money: every succeeded
     * PayNow payment in the window MUST correspond to an order marked paid. Any
     * that does not is either fixed here or raised as an exception nobody has to
     * notice on their own. Nothing can sit unmatched in silence.
     *
     * Uses the list endpoint rather than /search on purpose — search is an index
     * with a lag of up to a minute, and a payment we cannot see is the whole bug.
     */
    private static function sweep_stripe_ledger(array &$report) {
        $secret_key = self::get_secret_key();
        if (!$secret_key) {
            self::log('Ledger sweep: no Stripe secret key available; skipping.');
            return;
        }

        $since = time() - self::MAX_AGE_SECONDS;
        $after = null;

        for ($page = 0; $page < self::LEDGER_MAX_PAGES; $page++) {
            $url = self::STRIPE_API_BASE . '/payment_intents?limit=100'
                 . '&created[gte]=' . $since
                 . '&expand[]=' . rawurlencode('data.latest_charge');
            if ($after) {
                $url .= '&starting_after=' . rawurlencode($after);
            }

            $result = self::stripe_get($url, $secret_key);
            if (is_wp_error($result)) {
                self::log('Ledger sweep: Stripe list failed: ' . $result->get_error_message());
                return;
            }

            $intents = $result['data'] ?? [];
            if (!$intents) {
                return;
            }
            foreach ($intents as $intent) {
                self::inspect_ledger_intent($intent, $report);
            }
            if (empty($result['has_more'])) {
                return;
            }
            $after = $intents[count($intents) - 1]['id'] ?? null;
            if (!$after) {
                return;
            }
        }

        self::log(sprintf(
            'Ledger sweep: hit the %d-page cap; older payments in the window were not inspected this run.',
            self::LEDGER_MAX_PAGES
        ));
    }

    /**
     * One succeeded Stripe payment: prove it landed on an order, or raise it.
     */
    private static function inspect_ledger_intent(array $intent, array &$report) {
        if (!self::is_paid($intent)) {
            return; // unpaid, expired or already refunded — no money to place
        }

        // Same grace period pass 1 gives: a payment that succeeded seconds ago
        // very likely has its webhook already in flight. Racing it risks two
        // processes calling payment_complete() on the same order at once.
        $created = (int) ($intent['created'] ?? 0);
        if ($created && $created > (time() - self::MIN_AGE_SECONDS)) {
            return;
        }

        $intent_id = $intent['id'] ?? '';
        $order_id  = (int) ($intent['metadata']['order_id'] ?? 0);
        $amount    = self::format_minor($intent);

        if (!$order_id) {
            $report['exceptions'][] = sprintf(
                '%s (%s) succeeded but carries no order_id — cannot tell which order it belongs to.',
                $intent_id,
                $amount
            );
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            $report['exceptions'][] = sprintf(
                '%s (%s) is stamped for order #%d, but that order no longer exists.',
                $intent_id,
                $amount,
                $order_id
            );
            return;
        }

        if ($order->get_date_paid('edit')) {
            return; // the normal, healthy outcome for almost every payment
        }

        if (!self::amounts_match($order, $intent)) {
            $report['exceptions'][] = sprintf(
                '%s took %s for order #%d, but that order totals %s — amounts disagree, not touching it.',
                $intent_id,
                $amount,
                $order_id,
                strip_tags($order->get_formatted_order_total())
            );
            return;
        }

        self::apply_match($order, $intent_id, $intent);
        $report['reconciled'][] = $order_id;
        self::log(sprintf('Ledger sweep: order %d RECONCILED from Stripe side via %s.', $order_id, $intent_id));
    }

    private static function format_minor(array $object) {
        $minor    = (int) ($object['amount_received'] ?? $object['amount'] ?? 0);
        $currency = strtoupper($object['currency'] ?? 'sgd');
        return sprintf('%s %s', $currency, number_format($minor / 100, 2));
    }

    public static function reconcile(WC_Order $order) {
        $order_id = $order->get_id();

        if ($order->get_date_paid('edit')) {
            self::log("Order {$order_id}: payment already recorded; nothing to do.");
            return false;
        }

        $secret_key = self::get_secret_key();
        if (!$secret_key) {
            self::log("Order {$order_id}: no Stripe secret key available; aborting.");
            return false;
        }

        $stored_id = self::extract_intent_id($order);
        $matched   = null; // ['id' => ..., 'object' => [...]]

        // 1. Trust the stored intent first — the normal, single-attempt case.
        if ($stored_id) {
            $object = self::fetch_stripe_object($stored_id, $secret_key);
            if (is_wp_error($object)) {
                self::log("Order {$order_id}: Stripe API error on {$stored_id}: " . $object->get_error_message());
            } elseif (self::is_paid($object) && self::amounts_match($order, $object)) {
                $matched = ['id' => $stored_id, 'object' => $object];
            } else {
                self::log(sprintf(
                    'Order %d: stored intent %s status=%s — not a paid match, searching by metadata.order_id.',
                    $order_id,
                    $stored_id,
                    $object['status'] ?? 'unknown'
                ));
            }
        } else {
            self::log("Order {$order_id}: no Stripe intent ID in meta; searching by metadata.order_id.");
        }

        // 2. Fall back to the durable link: whichever intent Stripe stamped with
        //    this order_id and actually succeeded. Catches the multi-QR case
        //    where the order stored a later, never-paid intent.
        if (!$matched) {
            $matched = self::find_paid_intent_by_order_id($order, $secret_key);
        }

        if (!$matched) {
            self::log("Order {$order_id}: no paid Stripe intent found; leaving as-is.");
            return false;
        }

        self::apply_match($order, $matched['id'], $matched['object']);
        return true;
    }

    /**
     * Point the order at the intent that really got paid, then record the money.
     * Shared by both reconciliation directions.
     */
    private static function apply_match(WC_Order $order, $intent_id, array $object) {
        $stored_id = self::extract_intent_id($order);

        // Repair the stored pointer so order and Stripe agree from here on.
        if ($stored_id !== $intent_id) {
            $order->update_meta_data('_payment_intent_id', $intent_id);
            $order->update_meta_data('_ah_ho_paynow_intent_corrected_from', (string) $stored_id);
            $order->add_order_note(sprintf(
                'Payment was made against Stripe intent %s, but this order was pointing at %s '
                . '(checkout was submitted more than once, so a later PayNow QR replaced the paid one). '
                . 'Matched by metadata.order_id and corrected.',
                $intent_id,
                $stored_id ?: 'nothing'
            ));
        }

        self::record_payment($order, $object, $intent_id);

        self::log(sprintf(
            'Order %d: RECONCILED via %s (status=%s, stored was %s).',
            $order->get_id(),
            $intent_id,
            $object['status'] ?? '',
            $stored_id ?: 'empty'
        ));
    }

    /**
     * Ask Stripe which PaymentIntent for this order actually got paid.
     *
     * The gateway stamps `metadata.order_id` on every intent it creates, so a
     * single search returns every attempt for the order. We take the succeeded
     * one whose amount matches.
     */
    private static function find_paid_intent_by_order_id(WC_Order $order, $secret_key) {
        $order_id = $order->get_id();
        $query    = sprintf("metadata['order_id']:'%d'", $order_id);
        $url      = self::STRIPE_API_BASE . '/payment_intents/search?limit=20&expand[]='
                  . rawurlencode('data.latest_charge')
                  . '&query=' . rawurlencode($query);

        $result = self::stripe_get($url, $secret_key);
        if (is_wp_error($result)) {
            self::log("Order {$order_id}: Stripe search failed: " . $result->get_error_message());
            return null;
        }

        $intents = $result['data'] ?? [];
        if (!$intents) {
            self::log("Order {$order_id}: Stripe search returned no intents for metadata.order_id.");
            return null;
        }

        foreach ($intents as $intent) {
            // Defensive: only ever accept an intent explicitly stamped with THIS order.
            if ((string) ($intent['metadata']['order_id'] ?? '') !== (string) $order_id) {
                continue;
            }
            if (!self::is_paid($intent) || !self::amounts_match($order, $intent)) {
                continue;
            }
            return ['id' => $intent['id'], 'object' => $intent];
        }

        self::log(sprintf(
            'Order %d: searched %d intent(s) by metadata.order_id, none succeeded with a matching amount.',
            $order_id,
            count($intents)
        ));
        return null;
    }

    /**
     * Write the payment onto the order.
     *
     * Orders still awaiting payment go through payment_complete() as usual.
     * Orders that staff already moved forward by hand get the money recorded
     * WITHOUT a status change — pushing an out-for-delivery order back to
     * Processing would undo their work and re-fire customer emails.
     *
     * A cancelled or failed order is revived to On hold rather than Processing.
     * The money is real and must be recorded, but the order was taken off the
     * board once already, so a human confirms it before anything ships.
     */
    private static function record_payment(WC_Order $order, array $object, $intent_id) {
        $charge_id = self::extract_charge_id($object) ?: $intent_id;
        $note      = sprintf(
            'PayNow payment reconciled via safety-net cron. Stripe %s: %s. Webhook was likely missed.',
            ($object['object'] ?? 'object'),
            $intent_id
        );

        if (in_array($order->get_status(), ['cancelled', 'failed'], true)) {
            $order->set_transaction_id($charge_id);
            $order->set_date_paid(time());
            $order->update_meta_data('_ah_ho_paynow_revived_from_status', $order->get_status());
            $order->add_order_note(
                $note . sprintf(
                    ' This order was %s, but Stripe shows the customer DID pay %s in full against intent %s.'
                    . ' Moved to On hold for a human to confirm before fulfilment — do not cancel again'
                    . ' without refunding.',
                    $order->get_status(),
                    strip_tags($order->get_formatted_order_total()),
                    $intent_id
                )
            );
            $order->save();
            $order->update_status('on-hold');
            $order->update_meta_data('_ah_ho_paynow_reconciled_at', current_time('mysql'));
            $order->save();
            return;
        }

        if ($order->needs_payment()) {
            $order->payment_complete($charge_id);
        } else {
            $order->set_transaction_id($charge_id);
            $order->set_date_paid(time());
            $note .= sprintf(
                ' Order was already at "%s", so payment was recorded without changing its status.',
                $order->get_status()
            );
        }

        $order->add_order_note($note);
        $order->update_meta_data('_ah_ho_paynow_reconciled_at', current_time('mysql'));
        $order->save();
    }

    private static function is_paid(array $object) {
        if (!in_array($object['status'] ?? '', ['succeeded', 'chargeable', 'consumed'], true)) {
            return false;
        }
        // A refunded intent still reads `succeeded` forever. Now that cancelled
        // orders are scanned, that would revive an order whose money has already
        // gone back to the customer. `latest_charge` is expanded on both the
        // fetch and the search so the refund is visible here.
        return !self::is_refunded($object);
    }

    private static function is_refunded(array $object) {
        if (!empty($object['amount_refunded'])) {
            return true; // charge / legacy source
        }
        $charge = $object['latest_charge'] ?? null;
        if (is_array($charge) && (!empty($charge['amount_refunded']) || !empty($charge['refunded']))) {
            return true;
        }
        return false;
    }

    private static function extract_intent_id(WC_Order $order) {
        // `_payment_intent_id` is what the PaymentPlugins Stripe gateway actually
        // writes — it must stay first. The rest cover the official WooCommerce
        // Stripe gateway and the legacy Sources flow.
        $candidates = [
            '_payment_intent_id',
            '_stripe_intent_id',
            '_pi_id',
            '_stripe_payment_intent',
            '_stripe_source_id',
            '_source_id',
        ];
        foreach ($candidates as $key) {
            $value = $order->get_meta($key, true);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    private static function extract_charge_id(array $object) {
        if (($object['object'] ?? '') !== 'payment_intent') {
            return null;
        }
        // Newer API versions drop `charges` in favour of `latest_charge`, and an
        // expanded `charges.data` can be present but empty — check both, and
        // don't let an empty list short-circuit the fallback.
        if (!empty($object['charges']['data'][0]['id'])) {
            return $object['charges']['data'][0]['id'];
        }
        $latest = $object['latest_charge'] ?? null;
        if (is_string($latest) && $latest !== '') {
            return $latest;
        }
        if (is_array($latest) && !empty($latest['id'])) {
            return $latest['id'];
        }
        return null;
    }

    private static function amounts_match(WC_Order $order, array $object) {
        $expected_minor = (int) round(((float) $order->get_total()) * 100);
        $actual_minor   = (int) ($object['amount_received'] ?? $object['amount'] ?? 0);
        return $expected_minor > 0 && abs($expected_minor - $actual_minor) <= 1;
    }

    private static function fetch_stripe_object($id, $secret_key) {
        $endpoint = str_starts_with($id, 'src_')
            ? self::STRIPE_API_BASE . '/sources/' . rawurlencode($id)
            : self::STRIPE_API_BASE . '/payment_intents/' . rawurlencode($id)
              . '?expand[]=' . rawurlencode('latest_charge');

        return self::stripe_get($endpoint, $secret_key);
    }

    private static function stripe_get($endpoint, $secret_key) {
        $response = wp_remote_get($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Stripe-Version' => '2024-06-20',
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300) {
            $msg = $body['error']['message'] ?? ('HTTP ' . $code);
            return new WP_Error('stripe_api_error', $msg);
        }
        if (!is_array($body)) {
            return new WP_Error('stripe_api_error', 'Non-JSON response from Stripe.');
        }
        return $body;
    }

    /**
     * Locate the Stripe secret key.
     *
     * The store runs the PaymentPlugins gateway, which does not use the same
     * option names as the official WooCommerce Stripe plugin — so rather than
     * guess, fall back to scanning every stripe-ish option for a value that is
     * shaped like a secret/restricted key in the right mode.
     *
     * The key itself is never logged.
     */
    private static function get_secret_key() {
        $mode = self::stripe_mode();

        $option_keys = [
            'woocommerce_stripe_api_settings', // PaymentPlugins
            'wc_stripe_api_settings',
            'wc_stripe_settings',
            'woocommerce_stripe_settings',     // official gateway
        ];
        foreach ($option_keys as $key) {
            $opts = get_option($key);
            if (!is_array($opts)) {
                continue;
            }
            $fields = $mode === 'test'
                ? ['test_secret_key', 'secret_key_test', 'api_secret_test']
                : ['secret_key', 'live_secret_key', 'secret_key_live', 'api_secret_live', 'api_secret'];
            foreach ($fields as $field) {
                if (!empty($opts[$field]) && is_string($opts[$field]) && self::looks_like_key($opts[$field], $mode)) {
                    return $opts[$field];
                }
            }
        }

        if (defined('AH_HO_STRIPE_SECRET_KEY') && AH_HO_STRIPE_SECRET_KEY) {
            return AH_HO_STRIPE_SECRET_KEY;
        }

        return self::scan_options_for_key($mode);
    }

    private static function stripe_mode() {
        foreach (['woocommerce_stripe_api_settings', 'wc_stripe_api_settings', 'woocommerce_stripe_settings'] as $key) {
            $opts = get_option($key);
            if (!is_array($opts)) {
                continue;
            }
            if (!empty($opts['mode']) && is_string($opts['mode'])) {
                return $opts['mode'] === 'test' ? 'test' : 'live';
            }
            if (isset($opts['testmode'])) {
                return $opts['testmode'] === 'yes' ? 'test' : 'live';
            }
        }
        return 'live';
    }

    private static function looks_like_key($value, $mode) {
        $prefixes = $mode === 'test' ? ['sk_test_', 'rk_test_'] : ['sk_live_', 'rk_live_'];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Last resort: look through stripe-related options for a correctly-shaped key.
     */
    private static function scan_options_for_key($mode) {
        global $wpdb;

        $rows = $wpdb->get_col(
            "SELECT option_value FROM {$wpdb->options}
             WHERE option_name LIKE '%stripe%' AND option_value != ''
             LIMIT 100"
        );
        if (!$rows) {
            self::log('No stripe-related options found while looking for a secret key.');
            return null;
        }

        foreach ($rows as $raw) {
            $value = maybe_unserialize($raw);
            $found = self::search_value_for_key($value, $mode);
            if ($found) {
                self::log('Stripe secret key located via option scan (' . $mode . ' mode).');
                return $found;
            }
        }

        self::log('Could not locate a Stripe ' . $mode . ' secret key in any option; reconciliation cannot run.');
        return null;
    }

    private static function search_value_for_key($value, $mode) {
        if (is_string($value)) {
            return self::looks_like_key($value, $mode) ? $value : null;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                $found = self::search_value_for_key($item, $mode);
                if ($found) {
                    return $found;
                }
            }
        }
        return null;
    }

    /**
     * Tell someone. A safety net nobody watches is a safety net that fails
     * quietly until a customer chases the shop on WhatsApp.
     */
    private static function send_alert(array $report) {
        $to = apply_filters(
            'ah_ho_payment_alert_recipients',
            array_filter([
                get_option('admin_email'),
                defined('AH_HO_PAYMENT_ALERT_EMAIL') ? AH_HO_PAYMENT_ALERT_EMAIL : null,
            ])
        );
        if (!$to) {
            return;
        }

        $lines = ['PayNow reconciliation ran on ' . get_bloginfo('name') . '.', ''];

        if ($report['reconciled']) {
            $lines[] = 'Payments recovered — these orders had money in Stripe that the site had missed:';
            foreach (array_unique($report['reconciled']) as $order_id) {
                $order   = wc_get_order($order_id);
                $lines[] = sprintf(
                    '  #%d — %s — now "%s"%s',
                    $order_id,
                    $order ? strip_tags($order->get_formatted_order_total()) : 'unknown total',
                    $order ? wc_get_order_status_name($order->get_status()) : 'unknown',
                    $order ? ' — ' . $order->get_edit_order_url() : ''
                );
            }
            $lines[] = '';
        }

        if ($report['exceptions']) {
            $lines[] = 'NEEDS A HUMAN — money in Stripe that could not be matched to an order:';
            foreach ($report['exceptions'] as $exception) {
                $lines[] = '  ' . $exception;
            }
            $lines[] = '';
        }

        $subject = sprintf(
            '[%s] PayNow: %d payment(s) recovered, %d needing attention',
            get_bloginfo('name'),
            count(array_unique($report['reconciled'])),
            count($report['exceptions'])
        );

        wp_mail($to, $subject, implode("\n", $lines));
    }

    /**
     * If the sweep has not run recently the net is down — most likely WP-Cron
     * is disabled with nothing replacing it. Say so on screen rather than
     * letting it stay silently inert, which is how it failed before.
     */
    public static function render_heartbeat_notice() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !in_array($screen->id, ['shop_order', 'woocommerce_page_wc-orders', 'dashboard'], true)) {
            return;
        }

        $last = (int) get_option(self::HEARTBEAT_OPTION, 0);
        if ($last && $last > (time() - self::STALE_AFTER)) {
            return; // healthy
        }

        printf(
            '<div class="notice notice-error"><p><strong>PayNow payment safety net is not running.</strong> '
            . 'The last check was %s. Until this is fixed, a PayNow payment whose confirmation goes missing '
            . 'will not be picked up automatically. Most likely cause: WP-Cron is disabled and no server cron '
            . 'is calling wp-cron.php.</p></div>',
            $last ? esc_html(human_time_diff($last) . ' ago') : 'never'
        );
    }

    private static function log($message) {
        if (function_exists('wc_get_logger')) {
            wc_get_logger()->info($message, ['source' => self::LOG_SOURCE]);
        } else {
            error_log('[ah-ho-paynow-reconcile] ' . $message);
        }
    }

    public static function render_admin_button($order) {
        if (!$order instanceof WC_Order) {
            return;
        }
        if ($order->get_payment_method() !== 'stripe_paynow') {
            return;
        }
        // Show for anything without a recorded payment, not just pending/on-hold —
        // an order staff already pushed to Out for Delivery is precisely the one
        // someone needs to reconcile by hand.
        if ($order->get_date_paid('edit')) {
            return;
        }
        // Cancelled and failed orders keep the button: "the site says cancelled
        // but the customer has a PayNow receipt" is the single most common
        // reason anyone comes to this screen. Only refunded is hopeless.
        if ($order->get_status() === 'refunded') {
            return;
        }
        $nonce = wp_create_nonce('ah_ho_reconcile_' . $order->get_id());
        printf(
            '<p class="form-field form-field-wide"><button type="button" class="button ah-ho-reconcile-btn" data-order="%d" data-nonce="%s">Reconcile PayNow payment now</button><span class="ah-ho-reconcile-result" style="margin-left:8px;"></span></p>',
            $order->get_id(),
            esc_attr($nonce)
        );
    }

    public static function render_admin_script() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !in_array($screen->id, ['shop_order', 'woocommerce_page_wc-orders'], true)) {
            return;
        }
        ?>
        <script>
        jQuery(function($){
            $(document).on('click', '.ah-ho-reconcile-btn', function(){
                var btn = $(this);
                var result = btn.siblings('.ah-ho-reconcile-result');
                btn.prop('disabled', true);
                result.text('Checking Stripe…');
                $.post(ajaxurl, {
                    action: 'ah_ho_reconcile_order',
                    order_id: btn.data('order'),
                    _nonce: btn.data('nonce')
                }).done(function(res){
                    result.text(res.data && res.data.message ? res.data.message : (res.success ? 'Reconciled. Reload to see new status.' : 'Failed.'));
                    if (res.success) {
                        setTimeout(function(){ location.reload(); }, 1500);
                    }
                }).fail(function(){
                    result.text('Request failed.');
                }).always(function(){
                    btn.prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }

    public static function ajax_reconcile_order() {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Not allowed.']);
        }
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $nonce    = $_POST['_nonce'] ?? '';
        if (!$order_id || !wp_verify_nonce($nonce, 'ah_ho_reconcile_' . $order_id)) {
            wp_send_json_error(['message' => 'Bad nonce.']);
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }
        $was_dead = in_array($order->get_status(), ['cancelled', 'failed'], true);
        $ok       = self::reconcile($order);
        if ($ok) {
            wp_send_json_success(['message' => $was_dead
                ? 'Payment confirmed in Stripe. Order revived to On hold — check it, then move it forward.'
                : 'Payment confirmed. Order marked Processing.']);
        }
        wp_send_json_error(['message' => 'Stripe did not confirm payment (yet). Check log: Tools → Logs → ah-ho-paynow-reconcile.']);
    }
}

Ah_Ho_Stripe_PayNow_Reconcile::init();

register_deactivation_hook(AH_HO_CUSTOM_PLUGIN_DIR . 'ah-ho-custom.php', function() {
    $t = wp_next_scheduled(Ah_Ho_Stripe_PayNow_Reconcile::CRON_HOOK);
    if ($t) {
        wp_unschedule_event($t, Ah_Ho_Stripe_PayNow_Reconcile::CRON_HOOK);
    }
});
