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
 *   1. Finds orders in status "pending" paid via stripe_paynow, aged 5 min–48 hr
 *   2. Reads the stored Stripe PaymentIntent or Source ID from order meta
 *   3. Queries Stripe's API
 *   4. If Stripe says it's paid, marks the order as Processing
 *
 * Also registers an admin notice on the order screen if reconciled,
 * and an AJAX "Reconcile now" button for single-order manual retry.
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

    public static function init() {
        add_action('init', [__CLASS__, 'schedule_cron']);
        add_action(self::CRON_HOOK, [__CLASS__, 'run']);
        add_filter('cron_schedules', [__CLASS__, 'register_interval']);

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

    public static function run() {
        $orders = wc_get_orders([
            'status'         => ['pending', 'on-hold'],
            'payment_method' => 'stripe_paynow',
            'date_created'   => '>' . (time() - self::MAX_AGE_SECONDS),
            'limit'          => 50,
            'return'         => 'objects',
        ]);

        $cutoff = time() - self::MIN_AGE_SECONDS;
        foreach ($orders as $order) {
            $created = $order->get_date_created();
            if (!$created || $created->getTimestamp() > $cutoff) {
                continue;
            }
            self::reconcile($order);
        }
    }

    public static function reconcile(WC_Order $order) {
        $order_id  = $order->get_id();
        $intent_id = self::extract_intent_id($order);

        if (!$intent_id) {
            self::log("Order {$order_id}: no Stripe PaymentIntent/Source ID found in meta; skipping.");
            return false;
        }

        $secret_key = self::get_secret_key();
        if (!$secret_key) {
            self::log("Order {$order_id}: no Stripe secret key available; aborting.");
            return false;
        }

        $object = self::fetch_stripe_object($intent_id, $secret_key);
        if (is_wp_error($object)) {
            self::log("Order {$order_id}: Stripe API error: " . $object->get_error_message());
            return false;
        }

        $status = $object['status'] ?? '';
        $paid_statuses = ['succeeded', 'chargeable', 'consumed'];
        if (!in_array($status, $paid_statuses, true)) {
            self::log("Order {$order_id}: {$intent_id} status={$status}; not yet paid.");
            return false;
        }

        if (!self::amounts_match($order, $object)) {
            self::log("Order {$order_id}: amount mismatch vs Stripe object {$intent_id}; skipping to avoid incorrect capture.");
            return false;
        }

        $charge_id = self::extract_charge_id($object);
        $order->payment_complete($charge_id ?: $intent_id);
        $order->add_order_note(sprintf(
            'PayNow payment reconciled via safety-net cron. Stripe %s: %s. Webhook was likely missed.',
            ($object['object'] ?? 'object'),
            $intent_id
        ));
        $order->update_meta_data('_ah_ho_paynow_reconciled_at', current_time('mysql'));
        $order->save();

        self::log("Order {$order_id}: RECONCILED via {$intent_id} (status={$status}).");
        return true;
    }

    private static function extract_intent_id(WC_Order $order) {
        $candidates = ['_stripe_intent_id', '_pi_id', '_stripe_payment_intent', '_stripe_source_id', '_source_id'];
        foreach ($candidates as $key) {
            $value = $order->get_meta($key, true);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    private static function extract_charge_id(array $object) {
        if (($object['object'] ?? '') === 'payment_intent') {
            $charges = $object['charges']['data'] ?? $object['latest_charge'] ?? null;
            if (is_string($charges)) {
                return $charges;
            }
            if (is_array($charges) && isset($charges[0]['id'])) {
                return $charges[0]['id'];
            }
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

    private static function get_secret_key() {
        $option_keys = ['wc_stripe_api_settings', 'woocommerce_stripe_api_settings', 'wc_stripe_settings'];
        foreach ($option_keys as $key) {
            $opts = get_option($key);
            if (!is_array($opts)) {
                continue;
            }
            $mode = $opts['mode'] ?? ($opts['testmode'] === 'yes' ? 'test' : 'live');
            $candidates = $mode === 'test'
                ? ['test_secret_key', 'secret_key_test', 'api_secret_test']
                : ['secret_key', 'live_secret_key', 'secret_key_live', 'api_secret_live', 'api_secret'];
            foreach ($candidates as $field) {
                if (!empty($opts[$field]) && is_string($opts[$field])) {
                    return $opts[$field];
                }
            }
        }

        if (defined('AH_HO_STRIPE_SECRET_KEY') && AH_HO_STRIPE_SECRET_KEY) {
            return AH_HO_STRIPE_SECRET_KEY;
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
        if (!in_array($order->get_status(), ['pending', 'on-hold'], true)) {
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
