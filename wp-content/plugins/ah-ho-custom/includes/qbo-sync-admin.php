<?php
/**
 * QuickBooks Sync — admin page + manual "Sync now" button.
 *
 * The sync itself is a Python script living OUTSIDE the web root at
 * ~/ahho-qbo/ (so its .env, which holds the QuickBooks tokens, is never
 * web-reachable). Two cron jobs drive it:
 *
 *   1st of month 09:00  ->  run-sync.py monthly   (closes the previous month)
 *   every 5 minutes     ->  watch-trigger.sh      (picks up the button)
 *
 * This page does NOT run the sync directly — shell_exec is unreliable on
 * shared hosting. Instead the button drops a `trigger` file that the watcher
 * cron consumes, and we render state/last-run.json written by the runner.
 *
 * @package Ah_Ho_Custom
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base directory of the sync install.
 *
 * Deliberately absolute, NOT derived from ABSPATH: this site lives at
 * public_html/ah-ho-fruit/, so walking up from ABSPATH lands in public_html,
 * not the account home where the sync actually lives. Override with the
 * filter if the account is ever moved.
 */
function ah_ho_qbo_dir() {
    return apply_filters('ah_ho_qbo_dir', '/home2/contactl/ahho-qbo');
}

function ah_ho_qbo_state_dir() {
    return ah_ho_qbo_dir() . '/state';
}

/**
 * Last run as an array, or null if the sync has never run / isn't readable.
 */
function ah_ho_qbo_last_run() {
    $f = ah_ho_qbo_state_dir() . '/last-run.json';
    if (!is_readable($f)) {
        return null;
    }
    $data = json_decode(file_get_contents($f), true);
    return is_array($data) ? $data : null;
}

/**
 * True when a click is still waiting for the watcher cron to pick it up.
 */
function ah_ho_qbo_is_queued() {
    return file_exists(ah_ho_qbo_state_dir() . '/trigger');
}

add_action('admin_menu', 'ah_ho_qbo_menu');
function ah_ho_qbo_menu() {
    add_submenu_page(
        'woocommerce',
        __('QuickBooks Sync', 'ah-ho-custom'),
        __('QuickBooks Sync', 'ah-ho-custom'),
        'manage_woocommerce',
        'ah-ho-qbo-sync',
        'ah_ho_qbo_render_page'
    );
}

/**
 * Handle the button press: drop the trigger file for the watcher cron.
 */
add_action('admin_post_ah_ho_qbo_sync_now', 'ah_ho_qbo_handle_sync_now');
function ah_ho_qbo_handle_sync_now() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(__('You do not have permission to do that.', 'ah-ho-custom'));
    }
    check_admin_referer('ah_ho_qbo_sync_now');

    $state = ah_ho_qbo_state_dir();
    $status = 'queued';
    if (!is_dir($state) || !is_writable($state)) {
        $status = 'nowrite';
    } elseif (file_put_contents($state . '/trigger', 'wp-admin ' . gmdate('c')) === false) {
        $status = 'nowrite';
    }

    wp_safe_redirect(add_query_arg(
        array('page' => 'ah-ho-qbo-sync', 'ah_ho_qbo' => $status),
        admin_url('admin.php')
    ));
    exit;
}

function ah_ho_qbo_render_page() {
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    $last   = ah_ho_qbo_last_run();
    $queued = ah_ho_qbo_is_queued();
    $notice = isset($_GET['ah_ho_qbo']) ? sanitize_key(wp_unslash($_GET['ah_ho_qbo'])) : '';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('QuickBooks Sync', 'ah-ho-custom'); ?></h1>

        <p style="font-size:14px;max-width:46em">
            <?php esc_html_e('Website orders are sent to QuickBooks as unpaid invoices. This happens automatically on the 1st of each month for the month just ended. Use the button below if you want to send them across sooner.', 'ah-ho-custom'); ?>
        </p>

        <?php if ($notice === 'queued') : ?>
            <div class="notice notice-success"><p>
                <?php esc_html_e('Started. Orders usually appear in QuickBooks within 5 minutes — refresh this page to see the result.', 'ah-ho-custom'); ?>
            </p></div>
        <?php elseif ($notice === 'nowrite') : ?>
            <div class="notice notice-error"><p>
                <?php esc_html_e('Could not start the sync — the website cannot write to the sync folder. Please tell Lex.', 'ah-ho-custom'); ?>
            </p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="ah_ho_qbo_sync_now">
            <?php wp_nonce_field('ah_ho_qbo_sync_now'); ?>
            <p>
                <button type="submit" class="button button-primary button-hero"<?php disabled($queued); ?>>
                    <?php esc_html_e('Send orders to QuickBooks now', 'ah-ho-custom'); ?>
                </button>
                <?php if ($queued) : ?>
                    <span style="margin-left:10px"><?php esc_html_e('Already running — please wait a few minutes.', 'ah-ho-custom'); ?></span>
                <?php endif; ?>
            </p>
        </form>

        <hr>
        <h2><?php esc_html_e('Last sync', 'ah-ho-custom'); ?></h2>

        <?php if (!$last) : ?>
            <p><?php esc_html_e('No sync has run yet (or the result file cannot be read).', 'ah-ho-custom'); ?></p>
        <?php else :
            $when = strtotime($last['finished']);
            $blocked = isset($last['blocked']) ? (array) $last['blocked'] : array();
            $errors  = isset($last['errors'])  ? (array) $last['errors']  : array();
            ?>
            <table class="widefat striped" style="max-width:46em">
                <tbody>
                    <tr>
                        <th style="width:14em"><?php esc_html_e('When', 'ah-ho-custom'); ?></th>
                        <td><?php echo esc_html(wp_date('j M Y, g:ia', $when)); ?>
                            <?php echo $last['trigger'] === 'monthly'
                                ? esc_html__('(automatic monthly run)', 'ah-ho-custom')
                                : esc_html__('(you pressed the button)', 'ah-ho-custom'); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Added to QuickBooks', 'ah-ho-custom'); ?></th>
                        <td><strong><?php echo (int) $last['posted']; ?></strong> <?php esc_html_e('new invoices', 'ah-ho-custom'); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Already there', 'ah-ho-custom'); ?></th>
                        <td><?php echo (int) $last['already_there']; ?> <?php esc_html_e('orders (skipped, no duplicates)', 'ah-ho-custom'); ?></td>
                    </tr>
                </tbody>
            </table>

            <?php if ($blocked) : ?>
                <div class="notice notice-warning" style="max-width:46em;margin-top:16px">
                    <p><strong><?php esc_html_e('Some orders did NOT record.', 'ah-ho-custom'); ?></strong><br>
                    <?php esc_html_e('These products are not matched to a QuickBooks item yet, so their whole order was held back (rather than recording a wrong amount):', 'ah-ho-custom'); ?></p>
                    <ul style="list-style:disc;margin-left:22px">
                        <?php foreach ($blocked as $b) : ?>
                            <li><?php echo esc_html($b); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p><?php esc_html_e('Give this list to Lex to match up, then press the button again — the held orders will go in.', 'ah-ho-custom'); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($errors) : ?>
                <div class="notice notice-error" style="max-width:46em;margin-top:16px">
                    <p><strong><?php esc_html_e('Some orders failed to send.', 'ah-ho-custom'); ?></strong>
                    <?php esc_html_e('Please send this to Lex:', 'ah-ho-custom'); ?></p>
                    <ul style="list-style:disc;margin-left:22px">
                        <?php foreach ($errors as $e) : ?>
                            <li><code><?php echo esc_html($e); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!$blocked && !$errors) : ?>
                <p style="color:#1a7f37"><strong><?php esc_html_e('All good — every order in the period is now in QuickBooks.', 'ah-ho-custom'); ?></strong></p>
            <?php endif; ?>

            <?php if (current_user_can('manage_options') && !empty($last['log'])) : ?>
                <p><a href="#" onclick="document.getElementById('ahho-qbo-log').style.display='block';this.style.display='none';return false;"><?php esc_html_e('Show technical log', 'ah-ho-custom'); ?></a></p>
                <pre id="ahho-qbo-log" style="display:none;max-height:420px;overflow:auto;background:#fff;border:1px solid #ccd0d4;padding:12px;font-size:12px"><?php echo esc_html($last['log']); ?></pre>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
