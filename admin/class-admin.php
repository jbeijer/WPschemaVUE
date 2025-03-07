<?php
/**
 * Admin-klass för WPschemaVUE
 *
 * Hanterar admin-funktionalitet för pluginet
 *
 * @package WPschemaVUE
 */

// Säkerhetskontroll - förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-klass
 */
class WPschemaVUE_Admin {
    
    /**
     * Initiera admin-funktionalitet
     */
    public function init() {
        // Lägg till admin-menyn
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Registrera admin-skript och stilar
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Lägg till inställningslänk på plugins-sidan
        add_filter('plugin_action_links_' . WPSCHEMA_VUE_PLUGIN_BASENAME, array($this, 'add_settings_link'));
    }
    
    /**
     * Lägg till admin-meny
     */
    public function add_admin_menu() {
        // Huvudmeny
        add_menu_page(
            __('Schema Manager', 'wpschema-vue'),
            __('Schema Manager', 'wpschema-vue'),
            'manage_options',
            'wpschema-vue',
            array($this, 'render_admin_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Undermeny för organisationer
        add_submenu_page(
            'wpschema-vue',
            __('Organisationer', 'wpschema-vue'),
            __('Organisationer', 'wpschema-vue'),
            'manage_options',
            'wpschema-vue-organizations',
            array($this, 'render_organizations_page')
        );
        
        // Undermeny för resurser
        add_submenu_page(
            'wpschema-vue',
            __('Resurser', 'wpschema-vue'),
            __('Resurser', 'wpschema-vue'),
            'manage_options',
            'wpschema-vue-resources',
            array($this, 'render_resources_page')
        );
        
        // Undermeny för scheman
        add_submenu_page(
            'wpschema-vue',
            __('Scheman', 'wpschema-vue'),
            __('Scheman', 'wpschema-vue'),
            'manage_options',
            'wpschema-vue-schedules',
            array($this, 'render_schedules_page')
        );
        
        // Undermeny för inställningar
        add_submenu_page(
            'wpschema-vue',
            __('Inställningar', 'wpschema-vue'),
            __('Inställningar', 'wpschema-vue'),
            'manage_options',
            'wpschema-vue-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Registrera skript och stilar
     */
    public function enqueue_scripts($hook) {
        // Ladda bara på plugin-sidor
        if (strpos($hook, 'wpschema-vue') === false) {
            return;
        }
        
        // Registrera och ladda Vue-appen
        wp_register_script(
            'wpschema-vue-admin-app',
            WPSCHEMA_VUE_PLUGIN_URL . 'admin/vue-app/dist/js/app.js',
            array(),
            WPSCHEMA_VUE_VERSION,
            true
        );
        
        // Registrera och ladda stilar
        wp_register_style(
            'wpschema-vue-admin-styles',
            WPSCHEMA_VUE_PLUGIN_URL . 'admin/vue-app/dist/css/app.css',
            array(),
            WPSCHEMA_VUE_VERSION
        );
        
        // Skicka data till Vue-appen
        wp_localize_script('wpschema-vue-admin-app', 'wpScheduleData', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'rest_url' => esc_url_raw(rest_url('schedule/v1')),
            'admin_url' => admin_url('admin.php'),
            'plugin_url' => WPSCHEMA_VUE_PLUGIN_URL,
            'current_user' => $this->get_current_user_data(),
            'pages' => array(
                'dashboard' => 'wpschema-vue',
                'organizations' => 'wpschema-vue-organizations',
                'resources' => 'wpschema-vue-resources',
                'schedules' => 'wpschema-vue-schedules',
                'settings' => 'wpschema-vue-settings'
            )
        ));
        
        // Ladda skript och stilar
        wp_enqueue_script('wpschema-vue-admin-app');
        wp_enqueue_style('wpschema-vue-admin-styles');
    }
    
    /**
     * Lägg till inställningslänk på plugins-sidan
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wpschema-vue-settings') . '">' . __('Inställningar', 'wpschema-vue') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Rendera huvudadmin-sidan
     */
    public function render_admin_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Schema Manager', 'wpschema-vue') . '</h1>';
        echo '<div id="wpschema-vue-admin-app"></div>';
        echo '</div>';
    }
    
    /**
     * Rendera organisationssidan
     */
    public function render_organizations_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Organisationer', 'wpschema-vue') . '</h1>';
        echo '<div id="wpschema-vue-admin-app" data-page="organizations"></div>';
        echo '</div>';
    }
    
    /**
     * Rendera resurssidan
     */
    public function render_resources_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Resurser', 'wpschema-vue') . '</h1>';
        echo '<div id="wpschema-vue-admin-app" data-page="resources"></div>';
        echo '</div>';
    }
    
    /**
     * Rendera schemasidan
     */
    public function render_schedules_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Scheman', 'wpschema-vue') . '</h1>';
        echo '<div id="wpschema-vue-admin-app" data-page="schedules"></div>';
        echo '</div>';
    }
    
    /**
     * Rendera inställningssidan
     */
    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Inställningar', 'wpschema-vue') . '</h1>';
        echo '<div id="wpschema-vue-admin-app" data-page="settings"></div>';
        echo '</div>';
    }
    
    /**
     * Hämta data för inloggad användare
     */
    private function get_current_user_data() {
        $current_user = wp_get_current_user();
        
        if (!$current_user->exists()) {
            return null;
        }
        
        return array(
            'id' => $current_user->ID,
            'username' => $current_user->user_login,
            'display_name' => $current_user->display_name,
            'email' => $current_user->user_email,
            'roles' => $current_user->roles
        );
    }
}
