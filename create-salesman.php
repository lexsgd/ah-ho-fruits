<?php
/**
 * Standalone script to create salesman accounts.
 * DELETE THIS FILE after use.
 *
 * Access via: https://ahhofruit.com/create-salesman.php?key=ahho2026setup
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Simple secret key protection
if (!isset($_GET['key']) || $_GET['key'] !== 'ahho2026setup') {
    die('Unauthorized');
}

header('Content-Type: text/plain; charset=utf-8');

// Check if role exists
$role = get_role('ah_ho_salesperson');
echo "Role ah_ho_salesperson exists: " . ($role ? 'YES' : 'NO') . "\n\n";

for ($i = 1; $i <= 5; $i++) {
    $username = 'salesman' . $i;
    $email = 'enquiry+' . $username . '@ahhofruit.com';

    if (username_exists($username)) {
        echo $username . ": already exists\n";
        continue;
    }

    if (email_exists($email)) {
        echo $username . ": email " . $email . " already in use\n";
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
        echo $username . ": ERROR - " . $user_id->get_error_message() . "\n";
    } else {
        echo $username . ": CREATED (ID " . $user_id . ")\n";
    }
}

echo "\nDone. DELETE this file now!\n";
