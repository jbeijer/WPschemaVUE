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

// Stub-funktioner för att förhindra undefined function-fel vid statisk analys
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return '';
    }
}
if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return '';
    }
}
if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
    }
}
if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
    }
}
if (!function_exists('update_option')) {
    function update_option($option, $value) {
    }
}
if (!function_exists('flush_rewrite_rules')) {
    function flush_rewrite_rules() {
    }
}
if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain($domain, $deprecated, $plugin_rel_path) {
    }
}
if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
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
        
        // Kör aktiveringsrutinen
        WPschemaVUE_Activator::activate();
        
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
        require_once WPSCHEMA_VUE_PLUGIN_DIR . 'admin/rest-filters.php';
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

// Starta pluginet
$wpschema_vue = new WPschemaVUE();
