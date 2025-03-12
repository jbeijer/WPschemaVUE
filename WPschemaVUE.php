<?php
// Inkludera nödvändiga filer
require_once plugin_dir_path(__FILE__) . 'includes/class-resource-availability.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-resources.php';

// I init-hooken, lägg till:
add_action('init', function() {
    // ... existing init code ...
    
    // Initiera resurstillgänglighet
    $resource_availability = new Resource_Availability();
    add_action('rest_api_init', array($resource_availability, 'register_routes'));

    // Initiera resurser
    $resources = new Resources();
    add_action('rest_api_init', array($resources, 'register_routes'));
}); 