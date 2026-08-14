<?php
/**
 * Guest Account Backfill - one-off cleanup of historic guest orders
 *
 * guest-account-linking.php fixes this going forward. This page cleans up the
 * shoppers who checked out as guests *before* that shipped: at the time of
 * writing, 32 of 46 guest emails had no wp_users row, including repeat buyers
 * with 4-6 orders each. They are the ones who get told their email is unknown
 * when they try to reset a password.
 *
 * Two things happen here, and they carry very different weight:
 *
 *   LINK   - the email already belongs to a user, so the old order is simply
 *            attached to it. No email, nothing the customer sees. Always safe.
 *   CREATE - no account exists, so one is made. This is the irreversible half,
 *            because in "notify" mode WooCommerce emails a set-password link to
 *            a customer who never asked for an account.
 *
 * Hence the deliberate design: preview is the default, running requires an
 * explicit button press by someone with manage_woocommerce, and the notify
 * choice is a separate radio rather than a silent default.
 *
 * WooCommerce > Backfill Guest Accounts
 *
 * @package AhHoCustom
 * @since 1.7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Records the backfill on the order, so a second run skips it and so anyone
 * reading the order later can see why it suddenly has a customer attached.
 */
const AH_HO_BACKFILL_META = '_ah_ho_guest_account_backfilled';

add_action('admin_menu', 'ah_ho_backfill_menu', 60);

function ah_ho_backfill_menu() {
    add_submenu_page(
        'woocommerce',
        __('Backfill Guest Accounts', 'ah-ho-custom'),
        __('Backfill Guest Accounts', 'ah-ho-custom'),
        'manage_woocommerce',
        'ah-ho-guest-backfill',
        'ah_ho_backfill_page'
    );
}

/**
 * Find every guest order that could be linked or turned into an account.
 *
 * Grouped by billing email, because a repeat guest has several orders that all
 * belong to the same person and must end up on one account.
 *
 * @return array List of ['email', 'orders', 'order_count', 'last_order', 'user_id', 'action'].
 */
function ah_ho_backfill_scan() {
    global $wpdb;

    $rows = $wpdb->get_results(
        "SELECT id, LOWER(billing_email) AS em, date_created_gmt
         FROM {$wpdb->prefix}wc_orders
         WHERE customer_id = 0
           AND billing_email <> ''
           AND type = 'shop_order'
           AND status NOT IN ('trash', 'wc-checkout-draft')
         ORDER BY date_created_gmt ASC"
    );

    $grouped = array();
    foreach ($rows as $row) {
        if (!is_email($row->em)) {
            continue;
        }
        if (!isset($grouped[$row->em])) {
            $grouped[$row->em] = array(
                'email'      => $row->em,
                'orders'     => array(),
                'last_order' => '',
            );
        }
        $grouped[$row->em]['orders'][]  = (int) $row->id;
        $grouped[$row->em]['last_order'] = substr((string) $row->date_created_gmt, 0, 10);
    }

    foreach ($grouped as $email => &$entry) {
        $user                  = get_user_by('email', $email);
        $entry['user_id']      = $user ? (int) $user->ID : 0;
        $entry['action']       = $user ? 'link' : 'create';
        $entry['order_count']  = count($entry['orders']);
    }
    unset($entry);

    // Most recent customers first - if a run is ever cut short, the people most
    // likely to try logging in this week are the ones already done.
    uasort($grouped, function ($a, $b) {
        return strcmp($b['last_order'], $a['last_order']);
    });

    return array_values($grouped);
}

/**
 * Perform the backfill.
 *
 * @param bool $send_email Whether new accounts get WooCommerce's set-password email.
 * @return array Counts and per-row results.
 */
function ah_ho_backfill_run($send_email) {
    $scan    = ah_ho_backfill_scan();
    $results = array('linked' => 0, 'created' => 0, 'orders' => 0, 'failed' => 0, 'log' => array());

    // Suppress the "New account" email unless explicitly asked for. Filtering the
    // email class is the supported way; it leaves wc_create_new_customer()
    // otherwise untouched, so the account is identical either way.
    if (!$send_email) {
        add_filter('woocommerce_email_enabled_customer_new_account', '__return_false', 99);
    }

    foreach ($scan as $entry) {
        $email       = $entry['email'];
        $customer_id = $entry['user_id'];
        $created     = false;

        if (!$customer_id) {
            $first_order = wc_get_order($entry['orders'][0]);
            $customer_id = wc_create_new_customer(
                $email,
                '',
                '',
                array(
                    'first_name' => $first_order ? $first_order->get_billing_first_name() : '',
                    'last_name'  => $first_order ? $first_order->get_billing_last_name() : '',
                    'source'     => 'ah-ho-guest-backfill',
                )
            );

            if (is_wp_error($customer_id)) {
                $results['failed']++;
                $results['log'][] = sprintf('FAILED %s - %s', $email, $customer_id->get_error_message());
                continue;
            }

            $customer_id = (int) $customer_id;
            $created     = true;
            $results['created']++;
        } else {
            $results['linked']++;
        }

        $attached = 0;
        foreach ($entry['orders'] as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order || $order->get_customer_id() > 0) {
                continue;
            }
            $order->set_customer_id($customer_id);
            $order->update_meta_data(AH_HO_BACKFILL_META, $created ? 'created' : 'matched');
            $order->update_meta_data(AH_HO_GUEST_LINK_META, $created ? 'created' : 'matched');
            $order->add_order_note(
                $created
                    ? sprintf(
                        /* translators: %s: customer email */
                        __('Backfill: customer account created for %s and linked to this order.', 'ah-ho-custom'),
                        $email
                    )
                    : sprintf(
                        /* translators: %s: customer email */
                        __('Backfill: order linked to the existing customer account for %s.', 'ah-ho-custom'),
                        $email
                    )
            );
            $order->save();
            $attached++;
        }

        // Give the newest order's address to the profile, so My Account reflects
        // where they actually live now rather than a year-old address.
        $latest = wc_get_order(end($entry['orders']));
        if ($latest && function_exists('ah_ho_sync_order_address_to_user')) {
            ah_ho_sync_order_address_to_user($latest, $customer_id);
        }

        $results['orders'] += $attached;
        $results['log'][]   = sprintf(
            '%s %s -> user #%d (%d order%s)',
            $created ? 'CREATED' : 'LINKED ',
            $email,
            $customer_id,
            $attached,
            1 === $attached ? '' : 's'
        );
    }

    if (!$send_email) {
        remove_filter('woocommerce_email_enabled_customer_new_account', '__return_false', 99);
    }

    return $results;
}

/**
 * Admin page.
 */
function ah_ho_backfill_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have permission to run this tool.', 'ah-ho-custom'));
    }

    $results = null;

    if (isset($_POST['ah_ho_backfill_run'])) {
        check_admin_referer('ah_ho_backfill');
        $send_email = isset($_POST['ah_ho_backfill_notify']) && '1' === $_POST['ah_ho_backfill_notify'];
        $results    = ah_ho_backfill_run($send_email);
    }

    $scan     = ah_ho_backfill_scan();
    $to_link  = 0;
    $to_create = 0;
    foreach ($scan as $entry) {
        if ('link' === $entry['action']) {
            $to_link++;
        } else {
            $to_create++;
        }
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Backfill Guest Accounts', 'ah-ho-custom'); ?></h1>

        <p style="max-width:46em;">
            <?php esc_html_e('Shoppers who checked out as guests never got a login, so password reset tells them their email is unknown. This attaches their old orders to an account, creating one where none exists. New orders are already handled automatically — this is only for the backlog.', 'ah-ho-custom'); ?>
        </p>

        <?php if ($results) : ?>
            <div class="notice notice-success">
                <p><strong><?php esc_html_e('Backfill complete.', 'ah-ho-custom'); ?></strong>
                    <?php
                    printf(
                        /* translators: 1: created count, 2: linked count, 3: order count, 4: failure count */
                        esc_html__('%1$d account(s) created, %2$d linked to existing accounts, %3$d order(s) attached, %4$d failure(s).', 'ah-ho-custom'),
                        (int) $results['created'],
                        (int) $results['linked'],
                        (int) $results['orders'],
                        (int) $results['failed']
                    );
                    ?>
                </p>
                <?php if (!empty($results['log'])) : ?>
                    <pre style="max-height:20em;overflow:auto;background:#fff;padding:10px;border:1px solid #ccd0d4;"><?php echo esc_html(implode("\n", $results['log'])); ?></pre>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($scan)) : ?>
            <div class="notice notice-info"><p><?php esc_html_e('Nothing to do — every order already belongs to a customer account.', 'ah-ho-custom'); ?></p></div>
            <?php return; ?>
        <?php endif; ?>

        <h2><?php esc_html_e('Preview', 'ah-ho-custom'); ?></h2>
        <p>
            <?php
            printf(
                /* translators: 1: total, 2: create count, 3: link count */
                esc_html__('%1$d guest email(s): %2$d need a new account, %3$d can be linked to an account that already exists.', 'ah-ho-custom'),
                count($scan),
                (int) $to_create,
                (int) $to_link
            );
            ?>
        </p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Email', 'ah-ho-custom'); ?></th>
                    <th style="width:80px;"><?php esc_html_e('Orders', 'ah-ho-custom'); ?></th>
                    <th style="width:110px;"><?php esc_html_e('Last order', 'ah-ho-custom'); ?></th>
                    <th style="width:160px;"><?php esc_html_e('Action', 'ah-ho-custom'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($scan as $entry) : ?>
                <tr>
                    <td><?php echo esc_html($entry['email']); ?></td>
                    <td><?php echo (int) $entry['order_count']; ?></td>
                    <td><?php echo esc_html($entry['last_order']); ?></td>
                    <td>
                        <?php if ('link' === $entry['action']) : ?>
                            <span style="color:#2271b1;"><?php esc_html_e('Link to existing', 'ah-ho-custom'); ?></span>
                        <?php else : ?>
                            <strong><?php esc_html_e('Create account', 'ah-ho-custom'); ?></strong>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <form method="post" style="margin-top:24px;">
            <?php wp_nonce_field('ah_ho_backfill'); ?>
            <h2><?php esc_html_e('Do the customers get an email?', 'ah-ho-custom'); ?></h2>
            <p>
                <label>
                    <input type="radio" name="ah_ho_backfill_notify" value="0" checked>
                    <strong><?php esc_html_e('No — create the accounts quietly (recommended).', 'ah-ho-custom'); ?></strong><br>
                    <span class="description" style="margin-left:24px;">
                        <?php esc_html_e('Nothing lands in anyone\'s inbox. The account simply exists, so "Lost your password?" starts working for them. Fixes the complaint without contacting people out of the blue about an account they never asked for.', 'ah-ho-custom'); ?>
                    </span>
                </label>
            </p>
            <p>
                <label>
                    <input type="radio" name="ah_ho_backfill_notify" value="1">
                    <strong><?php esc_html_e('Yes — send each new account a set-password email.', 'ah-ho-custom'); ?></strong><br>
                    <span class="description" style="margin-left:24px;">
                        <?php esc_html_e('They can log in straight away, but every one of them gets an unexpected email — including customers who last ordered months ago. This cannot be undone once sent.', 'ah-ho-custom'); ?>
                    </span>
                </label>
            </p>
            <p>
                <button type="submit" name="ah_ho_backfill_run" value="1" class="button button-primary"
                    onclick="return confirm('<?php echo esc_js(__('Run the backfill now? Linking is reversible; created accounts and any emails sent are not.', 'ah-ho-custom')); ?>');">
                    <?php esc_html_e('Run backfill', 'ah-ho-custom'); ?>
                </button>
            </p>
        </form>
    </div>
    <?php
}
