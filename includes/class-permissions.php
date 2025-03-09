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
if (!defined('ABSPATH')) {
    exit;
}

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
require_once ABSPATH . 'wp-includes/pluggable.php';

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
            'manage_shifts' => true, // Ability to manage shifts
            'assign_shifts' => true, // Ability to assign shifts to others
        ),
        'wpschema_anvandare' => array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'view_organizations' => true, // Ability to view organizations
        ),
        'schemaanmain' => array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'manage_resources' => true, // Ability to manage resources
            'manage_users' => true, // Ability to manage users
            'manage_organizations' => true, // Ability to manage organizations
            'lock_shifts' => true, // Ability to lock shifts
            'force_delete_shifts' => true, // Ability to force delete shifts
        ),
        'admin' => array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'manage_resources' => true, // Ability to manage resources
            'lock_shifts' => true, // Ability to lock shifts
            'force_delete_shifts' => true, // Ability to force delete shifts
        ),
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
     * Registrerar roller och behörigheter.
     */
    public function register_roles() {
        foreach ($this->roles as $role => $capabilities) {
            add_role($role, ucfirst(str_replace('_', ' ', $role)), $capabilities);
        }
    }
}
