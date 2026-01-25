<?php
header('Content-Type: text/plain');

// Database connection
$db = new mysqli('localhost', 'contactl_wp153', 'sf[dMzM,y7@I', 'contactl_wp153');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "Checking WordPress URLs...\n\n";

// Check siteurl and home
$result = $db->query("SELECT option_name, option_value FROM wpgr_options WHERE option_name IN ('siteurl', 'home')");

while ($row = $result->fetch_assoc()) {
    echo $row['option_name'] . ": " . $row['option_value'] . "\n";
}

$db->close();
