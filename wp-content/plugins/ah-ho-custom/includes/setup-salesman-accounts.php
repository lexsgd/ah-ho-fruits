<?php
/**
 * One-time setup: Create 5 salesman accounts
 *
 * This file should be REMOVED after the accounts are created.
 *
 * @package AhHoCustom
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create 5 salesman accounts if they don't exist.
 */
function ah_ho_setup_salesman_accounts() {
    // Skip if all 5 already exist
    if (username_exists('salesman1') && username_exists('salesman5')) {
        return;
    }

    for ($i = 1; $i <= 5; $i++) {
        $username = 'salesman' . $i;

        if (username_exists($username)) {
            continue;
        }

        $result = wp_insert_user(array(
            'user_login'   => $username,
            'user_pass'    => 'ahho1234',
            'user_email'   => 'enquiry+' . $username . '@ahhofruit.com',
            'display_name' => 'Salesman ' . $i,
            'first_name'   => 'Salesman ' . $i,
            'role'         => 'ah_ho_salesperson',
        ));

        if (is_wp_error($result)) {
            error_log('AH HO: Failed to create ' . $username . ': ' . $result->get_error_message());
        } else {
            error_log('AH HO: Created ' . $username . ' with ID ' . $result);
        }
    }
}
add_action('init', 'ah_ho_setup_salesman_accounts', 99);
