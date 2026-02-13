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
 * Create 5 salesman accounts (one-time).
 * Uses an option flag to ensure it only runs once.
 */
function ah_ho_setup_salesman_accounts() {
    // Only run once
    if (get_option('ah_ho_salesman_accounts_created')) {
        return;
    }

    $accounts = array(
        array(
            'user_login'   => 'salesman1',
            'user_email'   => 'enquiry+salesman1@ahhofruit.com',
            'display_name' => 'Salesman 1',
        ),
        array(
            'user_login'   => 'salesman2',
            'user_email'   => 'enquiry+salesman2@ahhofruit.com',
            'display_name' => 'Salesman 2',
        ),
        array(
            'user_login'   => 'salesman3',
            'user_email'   => 'enquiry+salesman3@ahhofruit.com',
            'display_name' => 'Salesman 3',
        ),
        array(
            'user_login'   => 'salesman4',
            'user_email'   => 'enquiry+salesman4@ahhofruit.com',
            'display_name' => 'Salesman 4',
        ),
        array(
            'user_login'   => 'salesman5',
            'user_email'   => 'enquiry+salesman5@ahhofruit.com',
            'display_name' => 'Salesman 5',
        ),
    );

    $created = 0;

    foreach ($accounts as $account) {
        // Skip if user already exists
        if (username_exists($account['user_login'])) {
            continue;
        }

        $user_id = wp_insert_user(array(
            'user_login'   => $account['user_login'],
            'user_pass'    => 'ahho1234',
            'user_email'   => $account['user_email'],
            'display_name' => $account['display_name'],
            'first_name'   => $account['display_name'],
            'role'         => 'ah_ho_salesperson',
        ));

        if (!is_wp_error($user_id)) {
            $created++;
        }
    }

    // Mark as done so it doesn't run again
    update_option('ah_ho_salesman_accounts_created', true);
}
add_action('init', 'ah_ho_setup_salesman_accounts');
