<?php
require_once __DIR__ . '/class-user-organization.php';
/**
 * Permissions-klass för WPschemaVUE
 *
 * Hanterar behörighetskontroller för användare i organisationer
 *
 * @package WPschemaVUE
 */

// Säkerhetskontroll - förhindra direkt åtkomst
defined( 'ABSPATH' ) || exit;

// Inkludera WordPress core-funktioner
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-includes/user.php');

// Dummy stubs for WordPress functions to satisfy intelephense
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('add_role')) {
    function add_role($role, $display_name, $capabilities = array()) {
        return null;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 0;
    }
}

if (!function_exists('user_can')) {
    function user_can($user_id, $capability) {
        return false;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct($code, $message, $data = array()) {}
    }
}

// Ladda WordPress core-filer först
require_once ABSPATH . 'wp-includes/plugin.php';

// Ladda våra egna klasser
require_once plugin_dir_path(__FILE__) . 'class-organization.php';
require_once plugin_dir_path(__FILE__) . 'class-resource.php';
require_once plugin_dir_path(__FILE__) . 'class-schedule.php';

/**
 * Permissions-klass
 */
class WPschemaVUE_Permissions {
    
    /**
     * UserOrganization-instans
     */
    private $user_organization;
    
    /**
     * Organization-instans
     */
    private $organization;
    
    /**
     * Resource-instans
     */
    private $resource;
    
    /**
     * Cache för behörigheter
     */
    private $permission_cache = array();
    
    /**
     * Rolldefinitioner
     */
    private $roles = array(
        'base' => array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        ),
        'schemalaggare' => array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'manage_shifts' => true,
            'assign_shifts' => true,
        ),
        'schemaanmain' => array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'manage_resources' => true,
            'manage_users' => true,
            'manage_organizations' => true,
            'lock_shifts' => true,
            'force_delete_shifts' => true,
        )
    );
    
    /**
     * Konstruktor
     */
    public function __construct() {
        if (!class_exists('WPschemaVUE_User_Organization')) {
            require_once __DIR__ . '/class-user-organization.php';
        }
        $this->user_organization = new WPschemaVUE_User_Organization();
        $this->organization = new WPschemaVUE_Organization();
        $this->resource = new WPschemaVUE_Resource();
    }
    
    /**
     * Registrera WordPress-roller
     */
    public function register_roles() {
        add_role(
            'schema_user',
            'Schema Användare',
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'schema_access' => true
            )
        );
    }

    /**
     * Hämta alla användare med deras organisationsdata
     */
    public static function get_all_users() {
        global $wpdb;
        
        // Hämta alla WordPress-användare
        $users = get_users(array(
            'role__in' => array('administrator', 'schema_user', 'editor'),
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));

        // Hämta organisationsdata för varje användare
        $user_org = new WPschemaVUE_User_Organization();
        
        $processed_users = array();
        foreach ($users as $user) {
            $user_data = array(
                'user_id' => $user->ID,
                'user_data' => array(
                    'ID' => $user->ID,
                    'display_name' => $user->display_name,
                    'user_email' => $user->user_email,
                    'roles' => $user->roles
                ),
                'organizations' => array(),
                'organization_roles' => array()
            );

            // Hämta användarens organisationer och roller
            $org_data = $user_org->get_user_organizations($user->ID);
            if (!empty($org_data)) {
                foreach ($org_data as $org) {
                    $user_data['organizations'][] = $org->organization_id;
                    $user_data['organization_roles'][$org->organization_id] = $org->role;
                    
                    // Sätt primär organisation om den inte är satt
                    if (!isset($user_data['organization_id'])) {
                        $user_data['organization_id'] = $org->organization_id;
                        $user_data['role'] = $org->role;
                    }
                }
            }

            $processed_users[] = $user_data;
        }

        return $processed_users;
    }

    /**
     * Kontrollera om användaren har en specifik behörighet
     */
    public static function current_user_can($capability) {
        return current_user_can($capability);
    }
}
