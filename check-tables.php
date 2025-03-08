<?php
// Load WordPress
require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

// Check if the tables exist
global $wpdb;

$tables = array(
    $wpdb->prefix . 'schedule_organizations',
    $wpdb->prefix . 'schedule_user_organizations',
    $wpdb->prefix . 'schedule_resources',
    $wpdb->prefix . 'schedule_entries',
    $wpdb->prefix . 'wpschema_organization_permissions'
);

echo "<h1>Checking if tables exist:</h1>";

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    echo "<p>- $table: " . ($exists ? "EXISTS" : "DOES NOT EXIST") . "</p>";
    
    if ($exists) {
        // Show table structure
        echo "<ul>Table structure:";
        $columns = $wpdb->get_results("DESCRIBE $table");
        foreach ($columns as $column) {
            echo "<li>{$column->Field}: {$column->Type}" . ($column->Null === 'NO' ? ' NOT NULL' : '') . "</li>";
        }
        echo "</ul>";
    }
}
