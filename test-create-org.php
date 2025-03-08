<?php
// Load WordPress
require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

// Include necessary files
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once WPSCHEMA_VUE_PLUGIN_DIR . 'includes/class-organization.php';

// Create a test organization
$organization = new WPschemaVUE_Organization();

// Test with parent_id = null
$org_data = array(
    'name' => 'Test Organization ' . time(),
    'parent_id' => null
);

echo "<h1>Testing Organization Creation</h1>";
echo "<h2>Test 1: parent_id = null</h2>";
echo "<pre>";
echo "Organization data: ";
print_r($org_data);
echo "</pre>";

$organization_id = $organization->create_organization($org_data);

echo "<p>Result: ";
if ($organization_id) {
    echo "Success! Organization created with ID: $organization_id";
    
    // Get the organization to verify
    $new_org = $organization->get_organization($organization_id);
    echo "<pre>";
    print_r($new_org);
    echo "</pre>";
} else {
    echo "Failed to create organization";
}
echo "</p>";

// Test with parent_id = 0
$org_data = array(
    'name' => 'Test Organization ' . time(),
    'parent_id' => 0
);

echo "<h2>Test 2: parent_id = 0</h2>";
echo "<pre>";
echo "Organization data: ";
print_r($org_data);
echo "</pre>";

$organization_id = $organization->create_organization($org_data);

echo "<p>Result: ";
if ($organization_id) {
    echo "Success! Organization created with ID: $organization_id";
    
    // Get the organization to verify
    $new_org = $organization->get_organization($organization_id);
    echo "<pre>";
    print_r($new_org);
    echo "</pre>";
} else {
    echo "Failed to create organization";
}
echo "</p>";

// Test with parent_id = '' (empty string)
$org_data = array(
    'name' => 'Test Organization ' . time(),
    'parent_id' => ''
);

echo "<h2>Test 3: parent_id = '' (empty string)</h2>";
echo "<pre>";
echo "Organization data: ";
print_r($org_data);
echo "</pre>";

$organization_id = $organization->create_organization($org_data);

echo "<p>Result: ";
if ($organization_id) {
    echo "Success! Organization created with ID: $organization_id";
    
    // Get the organization to verify
    $new_org = $organization->get_organization($organization_id);
    echo "<pre>";
    print_r($new_org);
    echo "</pre>";
} else {
    echo "Failed to create organization";
}
echo "</p>";

// Test with parent_id = 1 (assuming organization with ID 1 exists)
$org_data = array(
    'name' => 'Test Organization ' . time(),
    'parent_id' => 1
);

echo "<h2>Test 4: parent_id = 1</h2>";
echo "<pre>";
echo "Organization data: ";
print_r($org_data);
echo "</pre>";

$organization_id = $organization->create_organization($org_data);

echo "<p>Result: ";
if ($organization_id) {
    echo "Success! Organization created with ID: $organization_id";
    
    // Get the organization to verify
    $new_org = $organization->get_organization($organization_id);
    echo "<pre>";
    print_r($new_org);
    echo "</pre>";
} else {
    echo "Failed to create organization";
}
echo "</p>";
