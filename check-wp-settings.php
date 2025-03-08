<?php
// Load WordPress
require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

// Include plugin.php for is_plugin_active
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// Check WordPress settings
echo "<h1>WordPress Settings:</h1>";

// Check if the user is logged in
echo "<p>User logged in: " . (is_user_logged_in() ? "Yes" : "No") . "</p>";

// Check if the user has admin permissions
echo "<p>User has admin permissions: " . (current_user_can('manage_options') ? "Yes" : "No") . "</p>";

// Check REST API settings
echo "<h2>REST API Settings:</h2>";
echo "<p>REST URL: " . rest_url() . "</p>";
echo "<p>REST Nonce: " . wp_create_nonce('wp_rest') . "</p>";

// Check wpScheduleData
echo "<h2>wpScheduleData:</h2>";
echo "<pre>";
print_r(get_option('wpschema_vue_settings'));
echo "</pre>";

// Check if the plugin is active
echo "<h2>Plugin Status:</h2>";
echo "<p>Plugin active: " . (is_plugin_active('WPschemaVUE/wpschema-vue.php') ? "Yes" : "No") . "</p>";

// Check if the database tables exist
global $wpdb;

$tables = array(
    $wpdb->prefix . 'schedule_organizations',
    $wpdb->prefix . 'schedule_user_organizations',
    $wpdb->prefix . 'schedule_resources',
    $wpdb->prefix . 'schedule_entries',
    $wpdb->prefix . 'wpschema_organization_permissions'
);

echo "<h2>Database Tables:</h2>";

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    echo "<p>- $table: " . ($exists ? "EXISTS" : "DOES NOT EXIST") . "</p>";
}

// Check if the REST API endpoints are registered
echo "<h2>REST API Endpoints:</h2>";
$rest_server = rest_get_server();
if ($rest_server) {
    $endpoints = $rest_server->get_routes();
    echo "<p>Endpoints for 'schedule/v1':</p>";
    echo "<ul>";
    foreach ($endpoints as $route => $handlers) {
        if (strpos($route, 'schedule/v1') === 0) {
            echo "<li>$route</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>REST server not available</p>";
}
