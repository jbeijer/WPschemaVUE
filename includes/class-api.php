<?php
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
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_organizations'),
                'permission_callback' => array($this, 'check_user_logged_in'),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_organization'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_organization_args(),
            ),
        ));
        
        register_rest_route($this->namespace, '/organizations/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
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
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_organization'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_organization_args(),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_organization'),
                'permission_callback' => array($this, 'check_admin_permission'),
            ),
        ));
        
        // Användarorganisationer
        register_rest_route($this->namespace, '/organizations/(?P<id>\d+)/users', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_organization_users'),
                'permission_callback' => array($this, 'check_organization_permission'),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'add_organization_user'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_user_organization_args(),
            ),
        ));
        
        register_rest_route($this->namespace, '/organizations/(?P<id>\d+)/users/(?P<user_id>\d+)', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_organization_user'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_user_organization_args(),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_organization_user'),
                'permission_callback' => array($this, 'check_admin_permission'),
            ),
        ));
        
        // Resurser
        register_rest_route($this->namespace, '/organizations/(?P<id>\d+)/resources', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_resources'),
                'permission_callback' => array($this, 'check_organization_permission'),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_resource'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_resource_args(),
            ),
        ));
        
        register_rest_route($this->namespace, '/resources/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_resource'),
                'permission_callback' => array($this, 'check_resource_permission'),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_resource'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => $this->get_resource_args(),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_resource'),
                'permission_callback' => array($this, 'check_admin_permission'),
            ),
        ));
        
        // Scheman
        register_rest_route($this->namespace, '/schedules/resource/(?P<resource_id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_resource_schedules'),
                'permission_callback' => array($this, 'check_resource_permission'),
            ),
        ));
        
        register_rest_route($this->namespace, '/schedules/my-schedule', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_my_schedule'),
                'permission_callback' => array($this, 'check_user_logged_in'),
            ),
        ));
        
        register_rest_route($this->namespace, '/schedules', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_schedule'),
                'permission_callback' => array($this, 'check_schedule_create_permission'),
                'args' => $this->get_schedule_args(),
            ),
        ));
        
        register_rest_route($this->namespace, '/schedules/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_schedule'),
                'permission_callback' => array($this, 'check_schedule_update_permission'),
                'args' => $this->get_schedule_args(),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_schedule'),
                'permission_callback' => array($this, 'check_schedule_delete_permission'),
            ),
        ));
        
        // Användarinformation
        register_rest_route($this->namespace, '/me', array(
            array(
                'methods' => WP_REST_Server::READABLE,
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
        if (!$this->check_user_logged_in()) {
            return false;
        }
        
        // Här skulle vi anropa Permissions-klassen för att kontrollera admin-behörighet
        // För nu, kontrollera om användaren är admin
        return current_user_can('manage_options');
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
        return array(
            'name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'parent_id' => array(
                'type' => 'integer',
                'default' => 0,
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
        // Här skulle vi anropa Organization-klassen för att hämta organisationer
        // För nu, returnera en tom array
        return rest_ensure_response(array());
    }
    
    /**
     * Skapa en organisation
     */
    public function create_organization($request) {
        // Här skulle vi anropa Organization-klassen för att skapa en organisation
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Hämta en organisation
     */
    public function get_organization($request) {
        // Här skulle vi anropa Organization-klassen för att hämta en organisation
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Uppdatera en organisation
     */
    public function update_organization($request) {
        // Här skulle vi anropa Organization-klassen för att uppdatera en organisation
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Ta bort en organisation
     */
    public function delete_organization($request) {
        // Här skulle vi anropa Organization-klassen för att ta bort en organisation
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Hämta användare i en organisation
     */
    public function get_organization_users($request) {
        // Här skulle vi anropa UserOrganization-klassen för att hämta användare
        // För nu, returnera en tom array
        return rest_ensure_response(array());
    }
    
    /**
     * Lägg till en användare i en organisation
     */
    public function add_organization_user($request) {
        // Här skulle vi anropa UserOrganization-klassen för att lägga till en användare
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Uppdatera en användare i en organisation
     */
    public function update_organization_user($request) {
        // Här skulle vi anropa UserOrganization-klassen för att uppdatera en användare
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
    }
    
    /**
     * Ta bort en användare från en organisation
     */
    public function delete_organization_user($request) {
        // Här skulle vi anropa UserOrganization-klassen för att ta bort en användare
        // För nu, returnera ett felmeddelande
        return new WP_Error(
            'not_implemented',
            __('Denna funktion är inte implementerad ännu.', 'wpschema-vue'),
            array('status' => 501)
        );
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
        
        // Här skulle vi anropa UserOrganization-klassen för att hämta användarens organisationer
        // För nu, returnera grundläggande användarinformation
        $user_data = array(
            'id' => $current_user->ID,
            'username' => $current_user->user_login,
            'display_name' => $current_user->display_name,
            'email' => $current_user->user_email,
            'roles' => $current_user->roles,
            'organizations' => array() // Här skulle vi lägga till användarens organisationer
        );
        
        return rest_ensure_response($user_data);
    }
}
