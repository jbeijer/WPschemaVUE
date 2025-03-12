<?php
// Säkerhetskontroll
if (!defined('ABSPATH')) {
    exit;
}

function fix_user_roles() {
    global $wpdb;
    
    // 1. Backup existing data
    $table = $wpdb->prefix . 'schedule_user_organizations';
    $existing_data = $wpdb->get_results("SELECT * FROM $table");
    
    // 2. Drop the table
    $wpdb->query("DROP TABLE IF EXISTS $table");
    
    // 3. Recreate the table with correct roles
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        organization_id bigint(20) unsigned NOT NULL,
        role enum('base','schemalaggare','schemaanmain') NOT NULL DEFAULT 'base',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_organization (user_id,organization_id),
        KEY organization_id (organization_id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // 4. Restore data with corrected roles
    if ($existing_data) {
        foreach ($existing_data as $row) {
            // Map old roles to new roles
            $role = strtolower($row->role);
            switch ($role) {
                case 'scheduler':
                    $role = 'schemalaggare';
                    break;
                case 'admin':
                case 'wpschema_anvandare':
                    $role = 'schemaanmain';
                    break;
                default:
                    $role = 'base';
            }
            
            $wpdb->insert(
                $table,
                array(
                    'user_id' => $row->user_id,
                    'organization_id' => $row->organization_id,
                    'role' => $role,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at
                ),
                array('%d', '%d', '%s', '%s', '%s')
            );
        }
    }
    
    return true;
} 