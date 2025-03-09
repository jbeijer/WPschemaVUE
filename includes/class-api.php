<?php
if (!class_exists('WP_REST_Server')) { class WP_REST_Server {} }
if (!class_exists('WP_Error')) { class WP_Error { public $errors = array(); public $error_data = array(); public function __construct($code = '', $message = '', $data = '') { $this->errors[$code][] = $message; if ( ! empty( $data ) ) { $this->error_data[$code] = $data; } } } }
if (!function_exists('is_wp_error')) { function is_wp_error($thing) { return $thing instanceof WP_Error; } }
if (!function_exists('rest_ensure_response')) { function rest_ensure_response($response) { return $response; } }
if (!function_exists('is_user_logged_in')) { function is_user_logged_in() { return true; } }
if (!function_exists('get_userdata')) { function get_userdata($user_id) { return (object)[ 'ID' => $user_id, 'user_login' => 'default', 'display_name' => 'Default User', 'user_email' => 'default@example.com', 'roles' => [] ]; } }
if (!class_exists('WPschemaVUE_User_Organization')) { class WPschemaVUE_User_Organization {} }
if (!class_exists('WPschemaVUE_Permissions')) { class WPschemaVUE_Permissions { public function update_user_role($user_id, $role) {} } }
/**
 * API-klass för WPschemaVUE
 *
 * Hanterar REST API-endpoints för pluginet
 *
 * @package WPschemaVUE
 */

// Säkerhetskontroll - förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API-klass
 */
class WPschemaVUE_API {
    
    /**
     * API-namespace
     */
    private $namespace = 'schedule/v1';
    
    /**
     * Initiera API-funktionalitet
     */
    public function init() {
        // Registrera REST API-endpoints
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Registrera API-routes
     */
    public function register_routes() {
        // Organisationer
        register_rest_route($this->namespace, '/organizations', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_organizations'),
                'permission_callback' => array($this, 'check_user_logged_in'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_organization'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_organization_args(),
            ),
        ));
        
        register_rest_route($this->namespace, '/organizations/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_organization'),
                'permission_callback' => array($this, 'check_organization_permission'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
            array(
                'methods' => 'PUT, PATCH',
                'callback' => array($this, 'update_organization'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_organization_args(),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_organization'),
                'permission_callback' => array($this, 'check_admin_permission'),
            ),
        ));
        
        // Användarorganisationer
        register_rest_route($this->namespace, '/organizations/(?P<id>\d+)/users', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_organization_users'),
                'permission_callback' => array($this, 'check_organization_permission'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'add_organization_user'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_user_organization_args(),
            ),
        ));
        
        register_rest_route($this->namespace, '/organizations/(?P<id>\d+)/users/(?P<user_id>\d+)', array(
            array(
                'methods' => 'PUT, PATCH',
                'callback' => array($this, 'update_organization_user'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_user_organization_args(),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_organization_user'),
                'permission_callback' => array($this, 'check_admin_permission'),
            ),
        ));
        
        // Resurser
        register_rest_route($this->namespace, '/organizations/(?P<id>\d+)/resources', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_resources'),
                'permission_callback' => array($this, 'check_organization_permission'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_resource'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_resource_args(),
            ),
        ));
        
        register_rest_route($this->namespace, '/resources/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_resource'),
                'permission_callback' => array($this, 'check_resource_permission'),
            ),
            array(
                'methods' => 'PUT, PATCH',
                'callback' => array($this, 'update_resource'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_resource_args(),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_resource'),
                'permission_callback' => array($this, 'check_admin_permission'),
            ),
        ));
        
        // Scheman
        register_rest_route($this->namespace, '/schedules/resource/(?P<resource_id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_resource_schedules'),
                'permission_callback' => array($this, 'check_resource_permission'),
            ),
        ));
        
        register_rest_route($this->namespace, '/schedules/my-schedule', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_my_schedule'),
                'permission_callback' => array($this, 'check_user_logged_in'),
            ),
        ));
        
        register_rest_route($this->namespace, '/schedules', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_schedule'),
                'permission_callback' => array($this, 'check_schedule_create_permission'),
                'args' => $this->get_schedule_args(),
            ),
        ));
        
        register_rest_route($this->namespace, '/schedules/(?P<id>\d+)', array(
            array(
                'methods' => 'PUT, PATCH',
                'callback' => array($this, 'update_schedule'),
                'permission_callback' => array($this, 'check_schedule_update_permission'),
                'args' => $this->get_schedule_args(),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_schedule'),
                'permission_callback' => array($this, 'check_schedule_delete_permission'),
            ),
        ));
        
        // Användarinformation
        register_rest_route($this->namespace, '/me', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_current_user_info'),
                'permission_callback' => array($this, 'check_user_logged_in'),
            ),
        ));
    }
    
    /**
     * Kontrollera om användaren är inloggad
     */
    public function check_user_logged_in() {
        return is_user_logged_in();
    }
    
    /**
     * Kontrollera om användaren har behörighet till en organisation
     */
    public function check_organization_permission($request) {
        if (!$this->check_user_logged_in()) {
            return false;
        }
        
        $organization_id = $request['id'];
        
        // Här skulle vi anropa Permissions-klassen för att kontrollera behörighet
        // För nu, returnera true för alla inloggade användare
        return true;
    }
    
    /**
     * Kontrollera om användaren har admin-behörighet
     */
    public function check_admin_permission($request) {
        error_log('Checking admin permission');
        
        if (!$this->check_user_logged_in()) {
            error_log('User not logged in');
            return false;
        }
        
        // För felsökning, returnera alltid true
        error_log('Returning true for admin permission');
        return true;
        
        // Här skulle vi anropa Permissions-klassen för att kontrollera admin-behörighet
        // För nu, kontrollera om användaren är admin
        // return current_user_can('manage_options');
    }
    
    /**
     * Kontrollera om användaren har behörighet till en resurs
     */
    public function check_resource_permission($request) {
        if (!$this->check_user_logged_in()) {
            return false;
        }
        
        $resource_id = $request['id'] ?? $request['resource_id'];
        
        // Här skulle vi anropa Permissions-klassen för att kontrollera behörighet
        // För nu, returnera true för alla inloggade användare
        return true;
    }
    
    /**
     * Kontrollera om användaren har behörighet att skapa scheman
     */
    public function check_schedule_create_permission($request) {
        if (!$this->check_user_logged_in()) {
            return false;
        }
        
        // Här skulle vi anropa Permissions-klassen för att kontrollera behörighet
        // För nu, returnera true för alla inloggade användare
        return true;
    }
    
    /**
     * Kontrollera om användaren har behörighet att uppdatera ett schema
     */
    public function check_schedule_update_permission($request) {
        if (!$this->check_user_logged_in()) {
            return false;
        }
        
        $schedule_id = $request['id'];
        
        // Här skulle vi anropa Permissions-klassen för att kontrollera behörighet
        // För nu, returnera true för alla inloggade användare
        return true;
    }
    
    /**
     * Kontrollera om användaren har behörighet att ta bort ett schema
     */
    public function check_schedule_delete_permission($request) {
        if (!$this->check_user_logged_in()) {
            return false;
        }
        
        $schedule_id = $request['id'];
        
        // Här skulle vi anropa Permissions-klassen för att kontrollera behörighet
        // För nu, returnera true för alla inloggade användare
        return true;
    }
    
    /**
     * Hämta argument för organisation
     */
    private function get_organization_args() {
        error_log('Getting organization args');
        return array(
            'name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'parent_id' => array(
                'type' => ['integer', 'null'],
                'default' => null,
            ),
        );
    }
    
    /**
     * Hämta argument för användarorganisation
     */
    private function get_user_organization_args() {
        return array(
            'user_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'role' => array(
                'required' => true,
                'type' => 'string',
                'enum' => array('base', 'scheduler', 'admin'),
            ),
        );
    }
    
    /**
     * Hämta argument för resurs
     */
    private function get_resource_args() {
        return array(
            'name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'description' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            'color' => array(
                'type' => 'string',
                'default' => '#3788d8',
                'pattern' => '/^#[0-9a-f]{6}$/i',
            ),
        );
    }
    
    /**
     * Hämta argument för schema
     */
    private function get_schedule_args() {
        return array(
            'user_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'resource_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'start_time' => array(
                'required' => true,
                'type' => 'string',
                'format' => 'date-time',
            ),
            'end_time' => array(
                'required' => true,
                'type' => 'string',
                'format' => 'date-time',
            ),
            'notes' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            'status' => array(
                'type' => 'string',
                'enum' => array('scheduled', 'confirmed', 'completed'),
                'default' => 'scheduled',
            ),
        );
    }
    
    /**
     * Hämta alla organisationer
     */
    public function get_organizations($request) {
        // Skapa organization-objekt
        $organization = new WPschemaVUE_Organization();
        
        // Hämta alla organisationer
        $organizations = $organization->get_organizations();
        
        return rest_ensure_response($organizations);
    }
    
    /**
     * Skapa en organisation
     */
    public function create_organization($request) {
        // Logga inkommande data för felsökning
        error_log('Create organization request data: ' . print_r($request->get_params(), true));
        
        $data = $request->get_params();
        
        // Validera data
        if (empty($data['name'])) {
            error_log('Missing name in create_organization');
            return new WP_Error(
                'missing_name',
                __('Organisationsnamn måste anges.', 'wpschema-vue'),
                array('status' => 400)
            );
        }
        
        try {
            // Skapa organization-objekt
            $organization = new WPschemaVUE_Organization();
            
            // Förbered data
            $org_data = array(
                'name' => sanitize_text_field($data['name'])
            );
            
            // Hantera parent_id
            if (isset($data['parent_id'])) {
                if ($data['parent_id'] !== null && $data['parent_id'] !== '' && $data['parent_id'] !== 0) {
                    $org_data['parent_id'] = (int) $data['parent_id'];
                    error_log('Setting parent_id to: ' . $org_data['parent_id']);
                } else {
                    error_log('parent_id is null, empty, or 0 - setting to null');
                    $org_data['parent_id'] = null;
                }
            } else {
                error_log('parent_id not set - defaulting to null');
                $org_data['parent_id'] = null;
            }
            
            error_log('Organization data to create: ' . print_r($org_data, true));
            
            // Skapa organisationen
            $organization_id = $organization->create_organization($org_data);
            
            if (!$organization_id) {
                error_log('Failed to create organization');
                return new WP_Error(
                    'create_failed',
                    __('Det gick inte att skapa organisationen.', 'wpschema-vue'),
                    array('status' => 500)
                );
            }
            
            error_log('Organization created with ID: ' . $organization_id);
            
            // Hämta den nya organisationen
            $new_organization = $organization->get_organization($organization_id);
            error_log('New organization data: ' . print_r($new_organization, true));
            
            return rest_ensure_response($new_organization);
        } catch (Exception $e) {
            error_log('Exception in create_organization: ' . $e->getMessage());
            return new WP_Error(
                'create_exception',
                __('Ett fel uppstod: ' . $e->getMessage(), 'wpschema-vue'),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Hämta en organisation
     */
    public function get_organization($request) {
        $id = (int) $request['id'];
        
        // Skapa organization-objekt
        $organization = new WPschemaVUE_Organization();
        
        // Hämta organisationen
        $org_data = $organization->get_organization($id);
        
        if (!$org_data) {
            return new WP_Error(
                'not_found',
                __('Organisationen hittades inte.', 'wpschema-vue'),
                array('status' => 404)
            );
        }
        
        return rest_ensure_response($org_data);
    }
    
    /**
     * Uppdatera en organisation
     */
    public function update_organization($request) {
        $id = (int) $request['id'];
        $data = $request->get_params();
        
        // Validera data
        if (empty($data['name'])) {
            return new WP_Error(
                'missing_name',
                __('Organisationsnamn måste anges.', 'wpschema-vue'),
                array('status' => 400)
            );
        }
        
        // Skapa organization-objekt
        $organization = new WPschemaVUE_Organization();
        
        // Kontrollera att organisationen finns
        $org_data = $organization->get_organization($id);
        if (!$org_data) {
            return new WP_Error(
                'not_found',
                __('Organisationen hittades inte.', 'wpschema-vue'),
                array('status' => 404)
            );
        }
        
        // Förbered data
        $update_data = array(
            'name' => sanitize_text_field($data['name'])
        );
        
        // Hantera parent_id endast om det är satt och inte null
        if (isset($data['parent_id'])) {
            if ($data['parent_id'] !== null && $data['parent_id'] !== '') {
                $update_data['parent_id'] = (int) $data['parent_id'];
            } else {
                $update_data['parent_id'] = null;
            }
        }
        
        // Uppdatera organisationen
        $result = $organization->update_organization($id, $update_data);
        
        if (!$result) {
            return new WP_Error(
                'update_failed',
                __('Det gick inte att uppdatera organisationen.', 'wpschema-vue'),
                array('status' => 500)
            );
        }
        
        // Hämta den uppdaterade organisationen
        $updated_organization = $organization->get_organization($id);
        
        return rest_ensure_response($updated_organization);
    }
    
    /**
     * Ta bort en organisation
     */
    public function delete_organization($request) {
        $id = (int) $request['id'];
        
        // Skapa organization-objekt
        $organization = new WPschemaVUE_Organization();
        
        // Kontrollera att organisationen finns
        $org_data = $organization->get_organization($id);
        if (!$org_data) {
            return new WP_Error(
                'not_found',
                __('Organisationen hittades inte.', 'wpschema-vue'),
                array('status' => 404)
            );
        }
        
        // Kontrollera om organisationen har barn
        if ($org_data['children_count'] > 0) {
            return new WP_Error(
                'has_children',
                __('Kan inte ta bort en organisation med underorganisationer.', 'wpschema-vue'),
                array('status' => 400)
            );
        }
        
        // Ta bort organisationen
        $result = $organization->delete_organization($id);
        
        if (!$result) {
            return new WP_Error(
                'delete_failed',
                __('Det gick inte att ta bort organisationen.', 'wpschema-vue'),
                array('status' => 500)
            );
        }
        
        return rest_ensure_response(array(
            'deleted' => true,
            'id' => $id
        ));
    }
    
    /**
     * Hämta användare i en organisation
     */
    public function get_organization_users($request) {
        $organization_id = (int) $request['id'];
        
        // Logga för felsökning
        error_log("Hämtar användare för organisation: $organization_id");
        
        require_once 'class-user-organization.php';
        $users = WPschemaVUE_User_Organization::get_organization_users($organization_id);
        
        error_log("Hittade " . count($users) . " användare för organisation $organization_id");
        
        return rest_ensure_response($users);
    }
    
    /**
     * Lägg till en användare i en organisation
     */
    public function add_organization_user($request) {
        $organization_id = (int) $request['id'];
        $data = $request->get_params();
        
        // Validera inkommande data
        if (empty($data['user_id'])) {
            return new WP_Error(
                'missing_user_id',
                __('Användar-ID måste anges.', 'wpschema-vue'),
                array('status' => 400)
            );
        }
        
        $user_id = (int) $data['user_id'];
        $role = sanitize_text_field($data['role'] ?? 'base');
        
        // Logga för felsökning
        error_log("Lägger till användare i organisation: user_id=$user_id, organization_id=$organization_id, role=$role");
        
        require_once 'class-user-organization.php';
        
        try {
            // Kontrollera befintlig koppling
            if (WPschemaVUE_User_Organization::association_exists($user_id, $organization_id)) {
                error_log("Användaren $user_id är redan kopplad till organisation $organization_id");
                return new WP_Error(
                    'duplicate_association',
                    __('Användaren är redan kopplad till denna organisation.', 'wpschema-vue'),
                    array('status' => 409)
                );
            }

            // Skapa association
            $association_data = array(
                'user_id' => $user_id,
                'organization_id' => $organization_id,
                'role' => $role
            );

            $association_id = WPschemaVUE_User_Organization::create_association($association_data);
            
            if (!$association_id) {
                error_log("Fel vid skapande av användarorganisation");
                return new WP_Error(
                    'create_failed',
                    __('Kunde inte lägga till användaren i organisationen.', 'wpschema-vue'),
                    array('status' => 500)
                );
            }
            
            // Hämta användardata
            $user_data = get_userdata($user_id);
            
            $response_data = array(
                'user_id' => $user_id,
                'organization_id' => $organization_id,
                'role' => $role,
                'user_data' => array(
                    'display_name' => $user_data->display_name,
                    'user_email' => $user_data->user_email
                )
            );
            
            error_log("Användare $user_id har lagts till i organisation $organization_id med roll $role");
            return rest_ensure_response($response_data);
            
        } catch (Exception $e) {
            error_log("Exception vid läggande av användare till organisation: " . $e->getMessage());
            return new WP_Error(
                'database_error',
                __('Databasfel: ' . $e->getMessage(), 'wpschema-vue'),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Uppdatera en användare i en organisation
     */
    public function update_organization_user($request) {
        try {
            global $wpdb;
            $organization_id = (int) $request['id'];
            $user_id = (int) $request['user_id'];
            $data = $request->get_json_params();
            $role = isset($data['role']) ? sanitize_text_field($data['role']) : '';
            
            // Logga inkommande data för felsökning
            error_log('Uppdaterar användarroll - Rå data: ' . print_r($data, true));
            error_log('Uppdaterar användarroll: user_id=' . $user_id . ', organization_id=' . $organization_id . ', role=' . $role);
            
            if (empty($role)) {
                error_log('Fel: Roll saknas i förfrågan');
                return new WP_Error(
                    'missing_role',
                    __('Roll krävs.', 'wpschema-vue'),
                    array('status' => 400)
                );
            }
            
            // Normalisera rollvärdet till små bokstäver för att matcha enum-värdena i databasen
            $role = strtolower($role);
            
            // Hämta de exakta enum-värdena från databasen
            $user_organizations_table = $wpdb->prefix . 'schedule_user_organizations';
            $column_info = $wpdb->get_row("SHOW COLUMNS FROM $user_organizations_table LIKE 'role'");
            $valid_roles = array('base', 'scheduler', 'admin', 'wpschema_anvandare', 'schemaanmain'); // Standardvärden
            
            if ($column_info && isset($column_info->Type)) {
                // Extrahera enum-värden från kolumntypen
                preg_match("/^enum\((.*)\)$/", $column_info->Type, $matches);
                if (isset($matches[1])) {
                    $enum_values = str_getcsv($matches[1], ',', "'");
                    if (!empty($enum_values)) {
                        $valid_roles = $enum_values;
                        error_log("Enum-värden från databasen: " . implode(', ', $valid_roles));
                    }
                }
            }
            
            // Kontrollera att rollen är ett giltigt enum-värde
            if (!in_array($role, $valid_roles)) {
                error_log("Ogiltig roll: '$role'. Giltiga roller är: " . implode(', ', $valid_roles));
                
                // Försök att mappa rollen till ett giltigt värde
                $role_mapping = array(
                    'bas' => 'base',
                    'schemaläggare' => 'scheduler',
                    'schemalagare' => 'scheduler',
                    'anställd' => 'base',
                    'anstalld' => 'base',
                    'wpschema-användare' => 'wpschema_anvandare',
                    'wpschema-anvandare' => 'wpschema_anvandare',
                    'schema-admin' => 'schemaanmain',
                    'schemaadmin' => 'schemaanmain'
                );
                
                if (isset($role_mapping[$role])) {
                    $role = $role_mapping[$role];
                    error_log("Mappade rollen till: $role");
                } else {
                    // Om vi inte kan mappa rollen, använd standardrollen 'base'
                    error_log("Kunde inte mappa rollen, använder 'base' som standard");
                    $role = 'base';
                }
                
                // Kontrollera igen efter mappning
                if (!in_array($role, $valid_roles)) {
                    return new WP_Error(
                        'invalid_role',
                        __('Ogiltig roll. Giltiga roller är: ' . implode(', ', $valid_roles), 'wpschema-vue'),
                        array('status' => 400)
                    );
                }
            }
            
            require_once 'class-user-organization.php';
            $result = WPschemaVUE_User_Organization::save_user_organization($user_id, $organization_id, $role);
            
            if ($result === false) {
                error_log('Fel vid sparande av användarorganisation');
                return new WP_Error(
                    'update_failed',
                    __('Kunde inte uppdatera användarrollen i organisationen.', 'wpschema-vue'),
                    array('status' => 500)
                );
            }
            
            // Vi uppdaterar inte WordPress-rollen längre eftersom vi använder vår egen rollhantering
            // $update = wp_update_user(array('ID' => $user_id, 'role' => $role));
            
            $user_data = get_userdata($user_id);
            if (!$user_data) {
                error_log('Fel: Kunde inte hämta användardata för användare med ID: ' . $user_id);
                return new WP_Error(
                    'user_not_found',
                    __('Kunde inte hämta användardata.', 'wpschema-vue'),
                    array('status' => 404)
                );
            }
            
            $response_data = array(
                'user_id'         => $user_id,
                'organization_id' => $organization_id,
                'role'            => $role,
                'user_data'       => array(
                    'display_name' => $user_data->display_name,
                    'user_email'   => $user_data->user_email,
                )
            );
            
            error_log('Användarroll uppdaterad, returnerar: ' . print_r($response_data, true));
            return rest_ensure_response($response_data);
            
        } catch (Exception $e) {
            error_log('Exception i update_organization_user: ' . $e->getMessage());
            error_log('Exception stack trace: ' . $e->getTraceAsString());
            return new WP_Error(
                'server_error',
                __('Ett serverfel inträffade: ' . $e->getMessage(), 'wpschema-vue'),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Ta bort en användare från en organisation
     */
    public function delete_organization_user($request) {
        $organization_id = (int) $request['id'];
        $user_id = (int) $request['user_id'];
        
        // Logga för felsökning
        error_log("Ta bort användare från organisation: user_id=$user_id, organization_id=$organization_id");
        
        require_once 'class-user-organization.php';
        
        // Kontrollera om associationen existerar
        if (!WPschemaVUE_User_Organization::association_exists($user_id, $organization_id)) {
            error_log("Användaren $user_id är inte kopplad till organisation $organization_id");
            return new WP_Error(
                'association_not_found',
                __('Användaren är inte kopplad till denna organisation.', 'wpschema-vue'),
                array('status' => 404)
            );
        }
        
        // Ta bort associationen
        $result = WPschemaVUE_User_Organization::delete_user_organization($user_id, $organization_id);
        
        if (!$result) {
            error_log("Fel vid borttagning av användarorganisation");
            return new WP_Error(
                'delete_failed',
                __('Kunde inte ta bort användaren från organisationen.', 'wpschema-vue'),
                array('status' => 500)
            );
        }
        
        error_log("Användare $user_id har tagits bort från organisation $organization_id");
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Användaren har tagits bort från organisationen.', 'wpschema-vue')
        ));
    }
    
    /**
     * Hämta resurser för en organisation
     */
    public function get_resources($request) {
        // Här skulle vi anropa Resource-klassen för att hämta resurser
        // För nu, returnera en tom array
        return rest_ensure_response(array());
    }
    
    /**
     * Skapa en resurs
     */
    public function create_resource($request) {
        // Här skulle vi anropa Resource-klassen för att skapa en resurs
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Hämta en resurs
     */
    public function get_resource($request) {
        // Här skulle vi anropa Resource-klassen för att hämta en resurs
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Uppdatera en resurs
     */
    public function update_resource($request) {
        // Här skulle vi anropa Resource-klassen för att uppdatera en resurs
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Ta bort en resurs
     */
    public function delete_resource($request) {
        // Här skulle vi anropa Resource-klassen för att ta bort en resurs
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Hämta scheman för en resurs
     */
    public function get_resource_schedules($request) {
        // Här skulle vi anropa Schedule-klassen för att hämta scheman
        // För nu, returnera en tom array
        return rest_ensure_response(array());
    }
    
    /**
     * Hämta mitt schema
     */
    public function get_my_schedule($request) {
        // Här skulle vi anropa Schedule-klassen för att hämta användarens schema
        // För nu, returnera en tom array
        return rest_ensure_response(array());
    }
    
    /**
     * Skapa ett schema
     */
    public function create_schedule($request) {
        // Här skulle vi anropa Schedule-klassen för att skapa ett schema
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Uppdatera ett schema
     */
    public function update_schedule($request) {
        // Här skulle vi anropa Schedule-klassen för att uppdatera ett schema
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Ta bort ett schema
     */
    public function delete_schedule($request) {
        // Här skulle vi anropa Schedule-klassen för att ta bort ett schema
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Hämta information om inloggad användare
     */
    public function get_current_user_info($request) {
        $current_user = wp_get_current_user();
        
        if (!$current_user->exists()) {
            return new WP_Error(
                'not_logged_in',
                __('Du måste vara inloggad för att använda denna funktion.', 'wpschema-vue'),
                array('status' => 401)
            );
        }
        
        // Logga för felsökning
        error_log("Hämtar information om inloggad användare: " . $current_user->ID);
        
        // Hämta användarens organisationer
        require_once 'class-user-organization.php';
        $organizations = WPschemaVUE_User_Organization::get_user_organizations($current_user->ID);
        
        error_log("Hittade " . count($organizations) . " organisationer för användare " . $current_user->ID);
        
        $user_data = array(
            'id' => $current_user->ID,
            'username' => $current_user->user_login,
            'display_name' => $current_user->display_name,
            'email' => $current_user->user_email,
            'roles' => $current_user->roles,
            'organizations' => $organizations
        );
        
        return rest_ensure_response($user_data);
    }
}
