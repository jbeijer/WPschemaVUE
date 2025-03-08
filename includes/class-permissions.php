<?php
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

require_once ABSPATH . 'wp-includes/pluggable.php';

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
        $this->user_organization = new WPschemaVUE_UserOrganization();
        $this->organization = new WPschemaVUE_Organization();
        $this->resource = new WPschemaVUE_Resource();
    }
    
    /**
     * Kontrollera om en användare har en specifik roll i en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @param string $role Roll att kontrollera
     * @return bool True om användaren har rollen, false annars
     */
    public function user_has_role($user_id, $organization_id, $role) {
        // Kontrollera cache
        $cache_key = "role_{$user_id}_{$organization_id}_{$role}";
        if (isset($this->permission_cache[$cache_key])) {
            return $this->permission_cache[$cache_key];
        }
        
        // Kontrollera direkt roll
        $has_role = $this->user_organization->user_has_role($user_id, $organization_id, $role);
        
        // Om användaren inte har rollen direkt, kontrollera ärvda roller
        if (!$has_role) {
            $has_role = $this->user_has_inherited_role($user_id, $organization_id, $role);
        }
        
        // Spara i cache
        $this->permission_cache[$cache_key] = $has_role;
        
        return $has_role;
    }
    
    /**
     * Kontrollera om en användare har minst en specifik roll i en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @param string $min_role Minsta roll att kontrollera (base, scheduler, admin)
     * @return bool True om användaren har minst den angivna rollen, false annars
     */
    public function user_has_min_role($user_id, $organization_id, $min_role) {
        // Kontrollera cache
        $cache_key = "min_role_{$user_id}_{$organization_id}_{$min_role}";
        if (isset($this->permission_cache[$cache_key])) {
            return $this->permission_cache[$cache_key];
        }
        
        // Kontrollera direkt roll
        $has_min_role = $this->user_organization->user_has_min_role($user_id, $organization_id, $min_role);
        
        // Om användaren inte har rollen direkt, kontrollera ärvda roller
        if (!$has_min_role) {
            $has_min_role = $this->user_has_inherited_min_role($user_id, $organization_id, $min_role);
        }
        
        // Spara i cache
        $this->permission_cache[$cache_key] = $has_min_role;
        
        return $has_min_role;
    }
    
    /**
     * Kontrollera om en användare har en ärvd roll i en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @param string $role Roll att kontrollera
     * @return bool True om användaren har en ärvd roll, false annars
     */
    private function user_has_inherited_role($user_id, $organization_id, $role) {
        // Hämta organisationen
        $organization = $this->organization->get_organization($organization_id);
        if (!$organization || !$organization['parent_id']) {
            return false;
        }
        
        // Kontrollera om användaren har rollen i föräldraorganisationen
        return $this->user_has_role($user_id, $organization['parent_id'], $role);
    }
    
    /**
     * Kontrollera om en användare har en ärvd minsta roll i en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @param string $min_role Minsta roll att kontrollera
     * @return bool True om användaren har en ärvd minsta roll, false annars
     */
    private function user_has_inherited_min_role($user_id, $organization_id, $min_role) {
        // Hämta organisationen
        $organization = $this->organization->get_organization($organization_id);
        if (!$organization || !$organization['parent_id']) {
            return false;
        }
        
        // Kontrollera om användaren har minsta rollen i föräldraorganisationen
        return $this->user_has_min_role($user_id, $organization['parent_id'], $min_role);
    }
    
    /**
     * Kontrollera om en användare kan se en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @return bool True om användaren kan se organisationen, false annars
     */
    public function can_view_organization($user_id, $organization_id) {
        // Kontrollera cache
        $cache_key = "view_org_{$user_id}_{$organization_id}";
        if (isset($this->permission_cache[$cache_key])) {
            return $this->permission_cache[$cache_key];
        }
        
        // Kontrollera om användaren har någon roll i organisationen
        $can_view = $this->user_has_min_role($user_id, $organization_id, 'base');
        
        // Spara i cache
        $this->permission_cache[$cache_key] = $can_view;
        
        return $can_view;
    }
    
    /**
     * Kontrollera om en användare kan redigera en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @return bool True om användaren kan redigera organisationen, false annars
     */
    public function can_edit_organization($user_id, $organization_id) {
        // Kontrollera cache
        $cache_key = "edit_org_{$user_id}_{$organization_id}";
        if (isset($this->permission_cache[$cache_key])) {
            return $this->permission_cache[$cache_key];
        }
        
        // Kontrollera om användaren har admin-roll i organisationen
        $can_edit = $this->user_has_min_role($user_id, $organization_id, 'admin');
        
        // Spara i cache
        $this->permission_cache[$cache_key] = $can_edit;
        
        return $can_edit;
    }
    
    /**
     * Kontrollera om en användare kan se en resurs
     *
     * @param int $user_id Användar-ID
     * @param int $resource_id Resurs-ID
     * @return bool True om användaren kan se resursen, false annars
     */
    public function can_view_resource($user_id, $resource_id) {
        // Kontrollera cache
        $cache_key = "view_resource_{$user_id}_{$resource_id}";
        if (isset($this->permission_cache[$cache_key])) {
            return $this->permission_cache[$cache_key];
        }
        
        // Hämta organisationen för resursen
        $organization_id = $this->resource->get_resource_organization($resource_id);
        if (!$organization_id) {
            return false;
        }
        
        // Kontrollera om användaren kan se organisationen
        $can_view = $this->can_view_organization($user_id, $organization_id);
        
        // Spara i cache
        $this->permission_cache[$cache_key] = $can_view;
        
        return $can_view;
    }
    
    /**
     * Kontrollera om en användare kan redigera en resurs
     *
     * @param int $user_id Användar-ID
     * @param int $resource_id Resurs-ID
     * @return bool True om användaren kan redigera resursen, false annars
     */
    public function can_edit_resource($user_id, $resource_id) {
        // Kontrollera cache
        $cache_key = "edit_resource_{$user_id}_{$resource_id}";
        if (isset($this->permission_cache[$cache_key])) {
            return $this->permission_cache[$cache_key];
        }
        
        // Hämta organisationen för resursen
        $organization_id = $this->resource->get_resource_organization($resource_id);
        if (!$organization_id) {
            return false;
        }
        
        // Kontrollera om användaren har admin-roll i organisationen
        $can_edit = $this->user_has_min_role($user_id, $organization_id, 'admin');
        
        // Spara i cache
        $this->permission_cache[$cache_key] = $can_edit;
        
        return $can_edit;
    }
    
    /**
     * Kontrollera om en användare kan se ett schema
     *
     * @param int $user_id Användar-ID
     * @param int $schedule_id Schema-ID
     * @param object $schedule Schema-objekt (valfritt, för prestanda)
     * @return bool True om användaren kan se schemat, false annars
     */
    public function can_view_schedule($user_id, $schedule_id, $schedule = null) {
        // Kontrollera cache
        $cache_key = "view_schedule_{$user_id}_{$schedule_id}";
        if (isset($this->permission_cache[$cache_key])) {
            return $this->permission_cache[$cache_key];
        }
        
        // Hämta schemat om det inte är angivet
        if (!$schedule) {
            $schedule_model = new WPschemaVUE_Schedule();
            $schedule = $schedule_model->get_schedule($schedule_id);
            if (!$schedule) {
                return false;
            }
        }
        
        // Kontrollera om användaren är schemalagd
        if ($schedule['user_id'] == $user_id) {
            $this->permission_cache[$cache_key] = true;
            return true;
        }
        
        // Hämta organisationen för resursen
        $organization_id = $this->resource->get_resource_organization($schedule['resource_id']);
        if (!$organization_id) {
            return false;
        }
        
        // Kontrollera om användaren har minst scheduler-roll i organisationen
        $can_view = $this->user_has_min_role($user_id, $organization_id, 'scheduler');
        
        // Spara i cache
        $this->permission_cache[$cache_key] = $can_view;
        
        return $can_view;
    }
    
    /**
     * Kontrollera om en användare kan redigera ett schema
     *
     * @param int $user_id Användar-ID
     * @param int $schedule_id Schema-ID
     * @param object $schedule Schema-objekt (valfritt, för prestanda)
     * @return bool True om användaren kan redigera schemat, false annars
     */
    public function can_edit_schedule($user_id, $schedule_id, $schedule = null) {
        // Kontrollera cache
        $cache_key = "edit_schedule_{$user_id}_{$schedule_id}";
        if (isset($this->permission_cache[$cache_key])) {
            return $this->permission_cache[$cache_key];
        }
        
        // Hämta schemat om det inte är angivet
        if (!$schedule) {
            $schedule_model = new WPschemaVUE_Schedule();
            $schedule = $schedule_model->get_schedule($schedule_id);
            if (!$schedule) {
                return false;
            }
        }
        
        // Hämta organisationen för resursen
        $organization_id = $this->resource->get_resource_organization($schedule['resource_id']);
        if (!$organization_id) {
            return false;
        }
        
        // Kontrollera om användaren har scheduler-roll i organisationen
        if ($this->user_has_min_role($user_id, $organization_id, 'scheduler')) {
            $this->permission_cache[$cache_key] = true;
            return true;
        }
        
        // Kontrollera om användaren är schemalagd och har base-roll
        if ($schedule['user_id'] == $user_id && $this->user_has_min_role($user_id, $organization_id, 'base')) {
            $this->permission_cache[$cache_key] = true;
            return true;
        }
        
        // Spara i cache
        $this->permission_cache[$cache_key] = false;
        
        return false;
    }
    
    /**
     * Kontrollera om en användare kan skapa ett schema för en resurs
     *
     * @param int $user_id Användar-ID
     * @param int $resource_id Resurs-ID
     * @return bool True om användaren kan skapa ett schema, false annars
     */
    public function can_create_schedule($user_id, $resource_id) {
        // Kontrollera cache
        $cache_key = "create_schedule_{$user_id}_{$resource_id}";
        if (isset($this->permission_cache[$cache_key])) {
            return $this->permission_cache[$cache_key];
        }
        
        // Hämta organisationen för resursen
        $organization_id = $this->resource->get_resource_organization($resource_id);
        if (!$organization_id) {
            return false;
        }
        
        // Kontrollera om användaren har minst scheduler-roll i organisationen
        $can_create = $this->user_has_min_role($user_id, $organization_id, 'scheduler');
        
        // Spara i cache
        $this->permission_cache[$cache_key] = $can_create;
        
        return $can_create;
    }
    
    /**
     * Kontrollera om en användare kan skapa ett schema för en annan användare
     *
     * @param int $user_id Användar-ID (den som skapar schemat)
     * @param int $target_user_id Användar-ID (den som schemat skapas för)
     * @param int $resource_id Resurs-ID
     * @return bool True om användaren kan skapa ett schema för en annan användare, false annars
     */
    public function can_create_schedule_for_user($user_id, $target_user_id, $resource_id) {
        // Om användaren skapar ett schema för sig själv, kontrollera bara resursbehörighet
        if ($user_id == $target_user_id) {
            return $this->can_view_resource($user_id, $resource_id);
        }
        
        // Annars måste användaren ha scheduler-roll
        return $this->can_create_schedule($user_id, $resource_id);
    }
    
    /**
     * Registrera roller och behörigheter
     */
    public function register_roles() {
        foreach ($this->roles as $role => $capabilities) {
            add_role($role, ucfirst(str_replace('_', ' ', $role)), $capabilities);
        }
    }
    
    /**
     * Lägg till en behörighet till en roll
     *
     * @param string $role Roll
     * @param string $capability Behörighet
     * @param bool $allow Tillåt behörighet (true) eller neka (false)
     */
    public function add_capability($role, $capability, $allow) {
        if (!isset($this->roles[$role])) {
            return;
        }
        
        $this->roles[$role][$capability] = $allow;
    }
    
    /**
     * Rensa behörighetscachen
     */
    public function clear_cache() {
        $this->permission_cache = array();
    }
    
    /**
     * Hämta alla användare med deras organisationer och roller
     *
     * @return array Användarinformation
     */
    public static function get_all_users() {
        global $wpdb;
        
        if (!self::current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'Otillåten åtkomst', array('status' => 403));
        }

        $query = $wpdb->prepare(
            "SELECT u.ID, u.user_email, u.display_name, 
                    GROUP_CONCAT(o.org_name) as organisations,
                    GROUP_CONCAT(ur.role) as roles
             FROM {$wpdb->users} u
             LEFT JOIN {$wpdb->prefix}wpschemavue_user_org_roles ur ON u.ID = ur.user_id
             LEFT JOIN {$wpdb->prefix}wpschemavue_organisations o ON ur.org_id = o.id
             GROUP BY u.ID
             ORDER BY u.display_name"
        );

        return $wpdb->get_results($query);
    }
    
    /**
     * Kontrollera om den aktuella användaren har en viss behörighet
     *
     * @param string $capability Behörighet
     * @param int $user_id Användar-ID (valfritt)
     * @return bool True om användaren har behörigheten, false annars
     */
    public static function current_user_can($capability, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        return user_can($user_id, $capability);
    }
}
