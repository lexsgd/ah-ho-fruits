<?php
/**
 * Free-Shipping Links — signed, expiring free-delivery links for regular customers
 *
 * Issue #4796: give selected regular customers a private link that grants free
 * delivery below the $60 threshold.
 *
 * The grant is carried by an HMAC-signed token, not a guessable flag:
 *
 *   https://ahhofruit.com/?fs=<expiry>.<label>.<signature>
 *
 * The signature is keyed on wp_salt('auth'), so a link cannot be forged or
 * hand-edited, and every link carries its own expiry. The browser cookie stores
 * the token itself and is re-verified on every request, so editing the cookie
 * in devtools does nothing.
 *
 * Generate links at: WooCommerce -> Free-Shipping Links
 *
 * @package AhHoCustom
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

const AH_HO_FS_COOKIE    = 'ah_ho_fs_token';
const AH_HO_FS_USER_META = '_ah_ho_free_shipping_until';  // unix ts; absent/past = no grant
const AH_HO_FS_MAX_DAYS  = 365;

/* -------------------------------------------------------------------------
 * Token
 * ---------------------------------------------------------------------- */

/**
 * Sign a token payload. Keyed on the site's auth salt — secret, already
 * required to exist, and rotating it invalidates every outstanding link.
 */
function ah_ho_fs_sign($expiry, $label) {
    return hash_hmac('sha256', $expiry . '|' . $label, wp_salt('auth'));
}

/**
 * Build a token. $days is clamped so a typo cannot mint a decade-long link.
 */
function ah_ho_fs_make_token($days, $label = 'regular') {
    $days   = max(1, min(AH_HO_FS_MAX_DAYS, (int) $days));
    $label  = preg_replace('/[^a-z0-9_-]/i', '', (string) $label) ?: 'regular';
    $expiry = time() + ($days * DAY_IN_SECONDS);

    return $expiry . '.' . $label . '.' . ah_ho_fs_sign($expiry, $label);
}

/**
 * Validate a token. Returns the expiry timestamp, or false.
 *
 * Rejects: wrong shape, bad signature, expired. hash_equals() is used so the
 * comparison cannot be timing-probed.
 */
function ah_ho_fs_verify_token($token) {
    if (!is_string($token) || substr_count($token, '.') !== 2) {
        return false;
    }

    list($expiry, $label, $sig) = explode('.', $token);

    if (!ctype_digit($expiry) || $label === '' || $sig === '') {
        return false;
    }
    if (!hash_equals(ah_ho_fs_sign($expiry, $label), $sig)) {
        return false;
    }
    if ((int) $expiry <= time()) {
        return false;
    }

    return (int) $expiry;
}

/* -------------------------------------------------------------------------
 * Capture — must run before output, or setcookie() cannot send a header
 * ---------------------------------------------------------------------- */

add_action('init', 'ah_ho_fs_capture_link');

function ah_ho_fs_capture_link() {
    // is_string() guard: ?fs[]=x makes this an array, and passing an array to a
    // string function is a fatal TypeError on PHP 8.
    if (!isset($_GET['fs']) || !is_string($_GET['fs'])) {
        return;
    }

    $token  = wp_unslash($_GET['fs']);
    $expiry = ah_ho_fs_verify_token($token);

    if (!$expiry) {
        return;   // forged, malformed or expired — silently ignored
    }

    // Cookie holds the signed token, never a bare "1". Re-verified on every
    // request, so a tampered or hand-written cookie fails the signature check.
    setcookie(AH_HO_FS_COOKIE, $token, array(
        'expires'  => $expiry,
        'path'     => COOKIEPATH,
        'domain'   => COOKIE_DOMAIN,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    $_COOKIE[AH_HO_FS_COOKIE] = $token;   // apply on this request too

    if (is_user_logged_in()) {
        ah_ho_fs_grant_user(get_current_user_id(), $expiry);
    }
}

/* -------------------------------------------------------------------------
 * Eligibility
 * ---------------------------------------------------------------------- */

/**
 * Store the grant against the account as an EXPIRY, not a permanent flag, so it
 * lapses on its own. Never shortens an existing longer grant.
 */
function ah_ho_fs_grant_user($user_id, $expiry) {
    if ($user_id > 0 && $expiry > (int) get_user_meta($user_id, AH_HO_FS_USER_META, true)) {
        update_user_meta($user_id, AH_HO_FS_USER_META, $expiry);
    }
}

/**
 * Is the current visitor entitled to free delivery via a link?
 *
 * Filterable so a grant can be revoked without a database edit:
 *   add_filter('ah_ho_free_shipping_link_eligible', '__return_false');
 */
function ah_ho_fs_is_eligible() {
    $eligible = false;

    if (isset($_COOKIE[AH_HO_FS_COOKIE]) && ah_ho_fs_verify_token(wp_unslash($_COOKIE[AH_HO_FS_COOKIE]))) {
        $eligible = true;
    }

    if (!$eligible && is_user_logged_in()) {
        $until    = (int) get_user_meta(get_current_user_id(), AH_HO_FS_USER_META, true);
        $eligible = $until > time();
    }

    return (bool) apply_filters('ah_ho_free_shipping_link_eligible', $eligible);
}

/* -------------------------------------------------------------------------
 * Apply
 * ---------------------------------------------------------------------- */

/**
 * Make the Free Shipping method AVAILABLE to link holders.
 *
 * This has to come first. WooCommerce's Free Shipping method enforces its own
 * "minimum order amount" ($60 here) inside is_available(), so below that the
 * rate is never generated at all — and woocommerce_package_rates cannot promote
 * a rate that does not exist. Filtering rates alone silently did nothing on a
 * sub-$60 cart, which is exactly the bug this feature was meant to fix.
 */
add_filter('woocommerce_shipping_free_shipping_is_available', 'ah_ho_fs_force_free_shipping_available', 10, 3);

function ah_ho_fs_force_free_shipping_available($is_available, $package, $method = null) {
    return $is_available || ah_ho_fs_is_eligible();
}

add_filter('woocommerce_package_rates', 'ah_ho_fs_apply_free_shipping', 5, 2);

function ah_ho_fs_apply_free_shipping($rates, $package) {
    if (!WC()->cart || !ah_ho_fs_is_eligible()) {
        return $rates;
    }

    return ah_ho_prefer_free_rates($rates);
}

/**
 * Carry the grant from cookie to account at checkout, so the customer keeps it
 * on a new device until the link expires.
 */
add_action('woocommerce_checkout_order_created', 'ah_ho_fs_record_on_order', 10, 1);

function ah_ho_fs_record_on_order($order) {
    if (!isset($_COOKIE[AH_HO_FS_COOKIE])) {
        return;
    }

    $expiry = ah_ho_fs_verify_token(wp_unslash($_COOKIE[AH_HO_FS_COOKIE]));
    if (!$expiry) {
        return;
    }

    ah_ho_fs_grant_user($order->get_customer_id(), $expiry);

    $order->add_order_note(
        sprintf(
            /* translators: %s: expiry date */
            __('Free-delivery link applied (valid until %s)', 'ah-ho-fruits'),
            date_i18n(get_option('date_format'), $expiry)
        ),
        0
    );
}

/**
 * Flag the case the old version only logged: an eligible customer still charged
 * for shipping, usually a zone with no free_shipping method configured.
 */
add_action('woocommerce_order_status_processing', 'ah_ho_fs_flag_unexpected_charge', 10, 1);

function ah_ho_fs_flag_unexpected_charge($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $customer_id = $order->get_customer_id();
    if ($customer_id <= 0 || (int) get_user_meta($customer_id, AH_HO_FS_USER_META, true) <= time()) {
        return;
    }

    $shipping = (float) $order->get_shipping_total();
    if ($shipping > 0) {
        $order->add_order_note(
            sprintf(
                /* translators: %s: shipping amount */
                __('⚠️ Free-delivery customer was still charged %s shipping — check that this zone has a Free Shipping method.', 'ah-ho-fruits'),
                wc_price($shipping)
            )
        );
    }
}

/* -------------------------------------------------------------------------
 * Admin — generate links
 * ---------------------------------------------------------------------- */

add_action('admin_menu', 'ah_ho_fs_admin_menu');

function ah_ho_fs_admin_menu() {
    add_submenu_page(
        'woocommerce',
        __('Free-Shipping Links', 'ah-ho-fruits'),
        __('Free-Shipping Links', 'ah-ho-fruits'),
        'manage_woocommerce',
        'ah-ho-free-shipping-links',
        'ah_ho_fs_admin_page'
    );
}

function ah_ho_fs_admin_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have permission to view this page.', 'ah-ho-fruits'));
    }

    $link = '';
    if (isset($_POST['ah_ho_fs_generate']) && check_admin_referer('ah_ho_fs_generate')) {
        $days  = isset($_POST['days']) ? (int) $_POST['days'] : 90;
        $label = isset($_POST['label']) ? sanitize_text_field(wp_unslash($_POST['label'])) : 'regular';
        $link  = add_query_arg('fs', ah_ho_fs_make_token($days, $label), home_url('/'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Free-Shipping Links', 'ah-ho-fruits'); ?></h1>
        <p><?php esc_html_e('Generate a private link that gives free delivery below the $60 threshold. The link expires on its own — anyone without it pays normal delivery.', 'ah-ho-fruits'); ?></p>

        <form method="post">
            <?php wp_nonce_field('ah_ho_fs_generate'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="ah-ho-fs-days"><?php esc_html_e('Valid for (days)', 'ah-ho-fruits'); ?></label></th>
                    <td><input name="days" id="ah-ho-fs-days" type="number" min="1" max="<?php echo esc_attr(AH_HO_FS_MAX_DAYS); ?>" value="90" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="ah-ho-fs-label"><?php esc_html_e('Label', 'ah-ho-fruits'); ?></label></th>
                    <td>
                        <input name="label" id="ah-ho-fs-label" type="text" value="regular" class="regular-text">
                        <p class="description"><?php esc_html_e('For your own reference, e.g. "wholesale" or "cny2026". Letters, numbers, - and _ only.', 'ah-ho-fruits'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Generate link', 'ah-ho-fruits'), 'primary', 'ah_ho_fs_generate'); ?>
        </form>

        <?php if ($link) : ?>
            <h2><?php esc_html_e('Your link', 'ah-ho-fruits'); ?></h2>
            <p><input type="text" readonly class="large-text code" value="<?php echo esc_attr($link); ?>" onclick="this.select()"></p>
            <p class="description"><?php esc_html_e('Share this only with the customers it is meant for. Anyone who opens it gets free delivery until it expires.', 'ah-ho-fruits'); ?></p>
        <?php endif; ?>

        <h2><?php esc_html_e('Revoking', 'ah-ho-fruits'); ?></h2>
        <p><?php esc_html_e('Links expire by themselves. To cut every outstanding link off immediately, change the WordPress auth salt in wp-config.php — that invalidates all signatures at once.', 'ah-ho-fruits'); ?></p>
    </div>
    <?php
}
