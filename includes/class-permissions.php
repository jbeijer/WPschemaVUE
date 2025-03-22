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

if (!function_exists('get_users')) {
    function get_users($args = array()) {
        return array();
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

    /**
     * Kontrollera om användaren tillhör en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @return bool True om användaren tillhör organisationen, annars false
     */
    public function user_belongs_to_organization($user_id, $organization_id) {
        // Kontrollera om vi har cachat resultatet
        $cache_key = "user_{$user_id}_org_{$organization_id}_belongs";
        if (isset($this->permission_cache[$cache_key])) {
            return $this->permission_cache[$cache_key];
        }
        
        // Hämta användarens organisationer
        $user_orgs = $this->user_organization->get_user_organizations($user_id);
        
        // Kontrollera om användaren tillhör organisationen
        $belongs = false;
        if (!empty($user_orgs)) {
            foreach ($user_orgs as $org) {
                if ($org->organization_id == $organization_id) {
                    $belongs = true;
                    break;
                }
            }
        }
        
        // Cacha resultatet
        $this->permission_cache[$cache_key] = $belongs;
        
        return $belongs;
    }
    
    /**
     * Kontrollera om användaren har en specifik roll i en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @param string $role Roll att kontrollera (bas, schemalaggare, schemaanmain)
     * @return bool True om användaren har rollen, annars false
     */
    public function user_has_role_in_organization($user_id, $organization_id, $role) {
        // Kontrollera om vi har cachat resultatet
        $cache_key = "user_{$user_id}_org_{$organization_id}_role_{$role}";
        if (isset($this->permission_cache[$cache_key])) {
            return $this->permission_cache[$cache_key];
        }
        
        // Hämta användarens organisationer och roller
        $user_orgs = $this->user_organization->get_user_organizations($user_id);
        
        // Kontrollera om användaren har rollen i organisationen
        $has_role = false;
        if (!empty($user_orgs)) {
            foreach ($user_orgs as $org) {
                if ($org->organization_id == $organization_id) {
                    // Om vi söker efter en specifik roll
                    if ($role) {
                        if ($org->role == $role) {
                            $has_role = true;
                            break;
                        }
                        
                        // Schemaanmain har alla rättigheter som schemalaggare har
                        if ($role == 'schemalaggare' && $org->role == 'schemaanmain') {
                            $has_role = true;
                            break;
                        }
                        
                        // Schemalaggare och schemaanmain har alla rättigheter som bas har
                        if ($role == 'bas' && ($org->role == 'schemalaggare' || $org->role == 'schemaanmain')) {
                            $has_role = true;
                            break;
                        }
                    } else {
                        // Om ingen specifik roll angavs, räcker det att användaren tillhör organisationen
                        $has_role = true;
                        break;
                    }
                }
            }
        }
        
        // Cacha resultatet
        $this->permission_cache[$cache_key] = $has_role;
        
        return $has_role;
    }
    
    /**
     * Kontrollera om användaren har behörighet att hantera resurser
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @return bool True om användaren har behörighet, annars false
     */
    public function user_can_manage_resources($user_id, $organization_id) {
        return $this->user_has_role_in_organization($user_id, $organization_id, 'schemaanmain');
    }
    
    /**
     * Kontrollera om användaren har behörighet att hantera scheman
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @return bool True om användaren har behörighet, annars false
     */
    public function user_can_manage_schedules($user_id, $organization_id) {
        return $this->user_has_role_in_organization($user_id, $organization_id, 'schemalaggare') || 
               $this->user_has_role_in_organization($user_id, $organization_id, 'schemaanmain');
    }
}
