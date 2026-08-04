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
    const SWEEP_LOCK       = 'ah_ho_paynow_sweep_lock';
    const SWEEP_THROTTLE   = 300;       // 5 min between opportunistic sweeps

    public static function init() {
        add_action('init', [__CLASS__, 'schedule_cron']);
        add_action(self::CRON_HOOK, [__CLASS__, 'run']);
        add_filter('cron_schedules', [__CLASS__, 'register_interval']);
        add_action('shutdown', [__CLASS__, 'maybe_sweep'], 99);

        add_action('wp_ajax_ah_ho_reconcile_order', [__CLASS__, 'ajax_reconcile_order']);
        add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__, 'render_admin_button']);
        add_action('admin_footer', [__CLASS__, 'render_admin_script']);
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
    }

    /**
     * Order statuses worth scanning: everything except the terminal ones.
     *
     * Deliberately wider than pending/on-hold. Staff routinely move a stuck
     * order forward by hand (order #5246 was pushed to Out for Delivery while
     * still unpaid), and those are exactly the ones that would otherwise never
     * get their payment recorded.
     */
    private static function scannable_statuses() {
        $statuses = array_keys(wc_get_order_statuses());
        $terminal = ['wc-cancelled', 'wc-refunded', 'wc-failed', 'wc-checkout-draft'];
        return array_values(array_diff($statuses, $terminal));
    }

    /**
     * Opportunistic sweep, throttled to once per SWEEP_THROTTLE seconds.
     *
     * WP-Cron is disabled on this install (DISABLE_WP_CRON), so the scheduled
     * hook never fires by itself. This runs on `shutdown` — after the response
     * has been built, so the visitor never waits on Stripe — and swallows
     * everything: a payment-reconciliation helper must never be able to take
     * the shop down.
     */
    public static function maybe_sweep() {
        if (wp_doing_cron()) {
            return; // the scheduled hook already covers that path
        }
        if (get_transient(self::SWEEP_LOCK)) {
            return;
        }
        // Claim the lock BEFORE working so concurrent requests don't stampede.
        set_transient(self::SWEEP_LOCK, 1, self::SWEEP_THROTTLE);

        try {
            self::run();
        } catch (\Throwable $e) {
            self::log('Opportunistic sweep failed: ' . $e->getMessage());
        }
    }

    public static function run() {
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
            self::reconcile($order);
        }
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

        $intent_id = $matched['id'];
        $object    = $matched['object'];

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
            $order_id,
            $intent_id,
            $object['status'] ?? '',
            $stored_id ?: 'empty'
        ));
        return true;
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
        $url      = self::STRIPE_API_BASE . '/payment_intents/search?limit=20&query=' . rawurlencode($query);

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
     */
    private static function record_payment(WC_Order $order, array $object, $intent_id) {
        $charge_id = self::extract_charge_id($object) ?: $intent_id;
        $note      = sprintf(
            'PayNow payment reconciled via safety-net cron. Stripe %s: %s. Webhook was likely missed.',
            ($object['object'] ?? 'object'),
            $intent_id
        );

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
        return in_array($object['status'] ?? '', ['succeeded', 'chargeable', 'consumed'], true);
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
            : self::STRIPE_API_BASE . '/payment_intents/' . rawurlencode($id);

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
        if (in_array($order->get_status(), ['cancelled', 'refunded', 'failed'], true)) {
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
        $ok = self::reconcile($order);
        if ($ok) {
            wp_send_json_success(['message' => 'Payment confirmed. Order marked Processing.']);
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
