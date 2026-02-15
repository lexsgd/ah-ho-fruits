<?php
// Temporary debug - DELETE AFTER USE
add_action('rest_api_init', function() {
    register_rest_route('ah-ho-debug/v1', '/check', array(
        'methods' => 'GET',
        'callback' => function() { return array('ok' => true, 'time' => time()); },
        'permission_callback' => '__return_true',
    ));
});
