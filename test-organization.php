<?php
// Load WordPress
require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );

// Include the Organization class
require_once( dirname( __FILE__ ) . '/includes/class-organization.php' );

// Create an organization
$organization = new WPschemaVUE_Organization();

// Test data
$org_data = array(
    'name' => 'Test Organization ' . time(),
    'parent_id' => null
);

// Create the organization
$organization_id = $organization->create_organization($org_data);

if ($organization_id) {
    echo "Organization created with ID: $organization_id\n";
    
    // Get the organization
    $org = $organization->get_organization($organization_id);
    echo "Organization data: " . print_r($org, true) . "\n";
} else {
    echo "Failed to create organization\n";
}
