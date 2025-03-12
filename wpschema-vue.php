<?php
/**
 * Plugin Name: WPschemaVUE
 * Description: Ett komplett schemahanteringssystem med hierarkiska organisationer och Vue 3
 * Version: 1.0.0
 * Author: Johan
 * Text Domain: wpschema-vue
 * Domain Path: /languages
 */

// Säkerhetskontroll - förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

// Definiera konstanter
define('WPSCHEMA_VUE_VERSION', '1.0.0');
define('WPSCHEMA_VUE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPSCHEMA_VUE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPSCHEMA_VUE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Klass för att hantera plugin-initiering
 */
class WPschemaVUE {
    /**
     * Konstruktor
     */
    public function __construct() {
        // Registrera aktiverings- och avaktiveringskrokar
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Lägg till actions för att ladda klasser och funktionalitet
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Aktivera pluginet
     */
    public function activate() {
        // Inkludera aktivator-klassen
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'includes/class-activator.php';
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'includes/fix-roles.php';
        
        // Kör aktiveringsrutinen
        WPschemaVUE_Activator::activate();
        
        // Kör fix för roller
        fix_user_roles();
        
        // Spara aktiveringsversion för framtida uppdateringar
        update_option('wpschema_vue_version', WPSCHEMA_VUE_VERSION);
        
        // Rensa permalänkar
        flush_rewrite_rules();
    }
    
    /**
     * Avaktivera pluginet
     */
    public function deactivate() {
        // Inkludera deaktivator-klassen
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'includes/class-deactivator.php';
        
        // Kör avaktiveringsrutinen
        WPschemaVUE_Deactivator::deactivate();
        
        // Rensa permalänkar
        flush_rewrite_rules();
    }
    
    /**
     * Initiera pluginet
     */
    public function init() {
        // Ladda textdomän för översättningar
        load_plugin_textdomain('wpschema-vue', false, dirname(WPSCHEMA_VUE_PLUGIN_BASENAME) . '/languages');
        
        // Inkludera nödvändiga filer
        $this->include_files();
        
        // Initiera admin-funktionalitet om vi är i admin
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Initiera publik funktionalitet
        $this->init_public();
        
        // Initiera REST API
        $this->init_api();
    }
    
    /**
     * Inkludera nödvändiga filer
     */
    private function include_files() {
        // Kärnklasser
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'includes/class-organization.php';
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'includes/class-user-organization.php';
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'includes/class-resource.php';
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'includes/class-schedule.php';
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'includes/class-permissions.php';
    }
    
    /**
     * Initiera admin-funktionalitet
     */
    private function init_admin() {
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'admin/class-admin.php';
        $admin = new WPschemaVUE_Admin();
        $admin->init();
    }
    
    /**
     * Initiera publik funktionalitet
     */
    private function init_public() {
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'public/class-public.php';
        $public = new WPschemaVUE_Public();
        $public->init();
    }
    
    /**
     * Initiera REST API
     */
    private function init_api() {
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'includes/class-api.php';
        $api = new WPschemaVUE_API();
        $api->init();
    }
}

add_action( 'init', 'wpschema_vue_register_custom_roles' );
function wpschema_vue_register_custom_roles() {
    $permissions = new WPschemaVUE_Permissions();
    $permissions->register_roles();
}

// Ta bort de föråldrade filtren och använd den nya rekommenderade metoden
add_filter('rest_authentication_errors', function($errors) {
    // Tillåt REST API
    return $errors;
});

// Lägg till CORS-headers för REST API
add_action('rest_api_init', function() {
    error_log('REST API init körs');
    
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-WP-Nonce');
        return $value;
    });
}, 15);

// Registrera REST API endpoints direkt i huvudfilen
add_action('rest_api_init', function() {
    error_log('Registrerar REST routes i huvudfilen');
    
    require_once WPSCHEMA_VUE_PLUGIN_DIR . 'admin/class-admin.php';
    $admin = new WPschemaVUE_Admin();
    
    register_rest_route('schedule/v1', '/users/create', array(
        'methods' => 'POST',
        'callback' => array($admin, 'create_user'),
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => array(
            'email' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email'
            ),
            'first_name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'last_name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            )
        )
    ));
});

// Starta pluginet
$wpschema_vue = new WPschemaVUE();
