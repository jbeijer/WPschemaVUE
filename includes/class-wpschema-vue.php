<?php
/**
 * Huvudklass för WPschemaVUE plugin
 */
class WPschemaVUE {
    /**
     * Plugin-namn
     */
    private $plugin_name;
    
    /**
     * Plugin-version
     */
    private $version;
    
    /**
     * Admin-instans
     */
    private $admin;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        error_log('WPschemaVUE konstruktor körs');
        
        $this->plugin_name = 'wpschema-vue';
        $this->version = WPSCHEMA_VUE_VERSION;
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    /**
     * Ladda beroenden
     */
    private function load_dependencies() {
        error_log('Laddar beroenden');
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-permissions.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-organization.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-user-organization.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-admin.php';
        
        $this->admin = new WPschemaVUE_Admin();
        error_log('Admin-klass skapad');
    }
    
    /**
     * Definiera admin hooks
     */
    private function define_admin_hooks() {
        error_log('Definierar admin hooks');
        
        // Initiera admin-klassen
        $this->admin->init();
        
        error_log('Admin hooks definierade');
    }
    
    /**
     * Definiera publika hooks
     */
    private function define_public_hooks() {
        // Här kan vi lägga till publika hooks senare
        error_log('Definierar publika hooks');
    }
} 