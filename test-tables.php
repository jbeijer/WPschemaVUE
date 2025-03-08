<?php
// Load WordPress
require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );

// Check if the tables exist
global $wpdb;

$tables = array(
    $wpdb->prefix . 'schedule_organizations',
    $wpdb->prefix . 'schedule_user_organizations',
    $wpdb->prefix . 'schedule_resources',
    $wpdb->prefix . 'schedule_entries',
    $wpdb->prefix . 'wpschema_organization_permissions'
);

echo "Checking if tables exist:\n";

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    echo "- $table: " . ($exists ? "EXISTS" : "DOES NOT EXIST") . "\n";
    
    if ($exists) {
        // Show table structure
        echo "  Table structure:\n";
        $columns = $wpdb->get_results("DESCRIBE $table");
        foreach ($columns as $column) {
            echo "  - {$column->Field}: {$column->Type}" . ($column->Null === 'NO' ? ' NOT NULL' : '') . "\n";
        }
    }
}
