<?php
/**
 * MU Plugin: Create salesman accounts.
 * DELETE this file after accounts are created.
 * Trigger: https://ahhofruit.com/?ah_ho_create=ahho2026setup
 */
add_action('init', function() {
    if (!isset($_GET['ah_ho_create']) || $_GET['ah_ho_create'] !== 'ahho2026setup') {
        return;
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== Ah Ho Salesman Setup ===\n\n";
    $role = get_role('ah_ho_salesperson');
    echo "Role ah_ho_salesperson: " . ($role ? 'YES' : 'NO') . "\n\n";
    for ($i = 1; $i <= 5; $i++) {
        $u = 'salesman' . $i;
        $e = 'enquiry+' . $u . '@ahhofruit.com';
        if (username_exists($u)) {
            echo $u . ": already exists\n";
            continue;
        }
        $role_to_use = $role ? 'ah_ho_salesperson' : 'subscriber';
        $id = wp_insert_user(array(
            'user_login'   => $u,
            'user_pass'    => 'ahho1234',
            'user_email'   => $e,
            'display_name' => 'Salesman ' . $i,
            'first_name'   => 'Salesman ' . $i,
            'role'         => $role_to_use,
        ));
        if (is_wp_error($id)) {
            echo $u . ": ERROR - " . $id->get_error_message() . "\n";
        } else {
            echo $u . ": CREATED (ID " . $id . ", role: " . $role_to_use . ")\n";
        }
    }
    echo "\nDone! DELETE wp-content/mu-plugins/create-salesman.php now.\n";
    exit;
}, 1);
