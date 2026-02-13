<?php
/**
 * One-time setup: Create 5 salesman accounts via AJAX endpoint.
 *
 * Trigger via: /wp-admin/admin-ajax.php?action=ah_ho_create_salesman&key=ahho2026setup
 *
 * This file should be REMOVED after the accounts are created.
 *
 * @package AhHoCustom
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create salesman accounts via AJAX (no login required).
 * Protected by a secret key.
 */
function ah_ho_ajax_create_salesman() {
    // Simple secret key protection
    if (!isset($_GET['key']) || $_GET['key'] !== 'ahho2026setup') {
        wp_die('Unauthorized');
    }

    header('Content-Type: text/plain');

    $results = array();

    for ($i = 1; $i <= 5; $i++) {
        $username = 'salesman' . $i;
        $email = 'enquiry+' . $username . '@ahhofruit.com';

        if (username_exists($username)) {
            $results[] = $username . ': already exists';
            continue;
        }

        if (email_exists($email)) {
            $results[] = $username . ': email ' . $email . ' already in use';
            continue;
        }

        $user_id = wp_insert_user(array(
            'user_login'   => $username,
            'user_pass'    => 'ahho1234',
            'user_email'   => $email,
            'display_name' => 'Salesman ' . $i,
            'first_name'   => 'Salesman ' . $i,
            'role'         => 'ah_ho_salesperson',
        ));

        if (is_wp_error($user_id)) {
            $results[] = $username . ': ERROR - ' . $user_id->get_error_message();
        } else {
            $results[] = $username . ': CREATED (ID ' . $user_id . ')';
        }
    }

    // Check if role exists
    $role = get_role('ah_ho_salesperson');
    $results[] = '';
    $results[] = 'Role ah_ho_salesperson exists: ' . ($role ? 'YES' : 'NO');

    echo implode("\n", $results);
    wp_die();
}
add_action('wp_ajax_nopriv_ah_ho_create_salesman', 'ah_ho_ajax_create_salesman');
add_action('wp_ajax_ah_ho_create_salesman', 'ah_ho_ajax_create_salesman');
