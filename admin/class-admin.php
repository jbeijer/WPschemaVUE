<?php
/**
 * Admin-klass för WPschemaVUE
 * 
 * @package WPschemaVUE
 * 
 * @wordpress-plugin
 */

// Säkerhetskontroll - förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

// Inkludera WordPress-funktioner om de inte finns
if (!function_exists('add_action')) {
    require_once(ABSPATH . 'wp-includes/pluggable.php');
}

/**
 * Hanterar admin-funktionalitet för pluginet
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
        
        // Lägg till filter för att lägga till type="module" på script-taggen
        add_filter('script_loader_tag', array($this, 'wpschema_vue_script_module'), 10, 3);
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
     * 
     * Använder wp_enqueue_script och wp_enqueue_style för att ladda Vue-appens filer
     * med korrekta WordPress-paths via plugins_url()
     */
    public function enqueue_scripts($hook) {
        // Ladda på alla plugin-sidor (inte bara huvudsidan)
        if (strpos($hook, 'wpschema-vue') === false) {
            return;
        }

        // Ladda Vue-appens JavaScript med korrekt path
        wp_enqueue_script(
            'wpschema-vue-app',
            plugins_url('admin/dist/js/index.js', dirname(__FILE__)),
            array(),
            WPSCHEMA_VUE_VERSION,
            true
        );

        // Ladda Vue-appens CSS med korrekt path
        wp_enqueue_style(
            'wpschema-vue-style',
            plugins_url('admin/dist/css/index.css', dirname(__FILE__))
        );

        // Skicka WordPress-data till Vue-appen
        wp_localize_script('wpschema-vue-app', 'wpScheduleData', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'rest_url' => esc_url_raw(rest_url('schedule/v1')),
            'admin_url' => admin_url(),
            'plugin_url' => plugins_url('/', dirname(__FILE__)),
            'current_user' => $this->get_current_user_data(),
            'pages' => array(
                'dashboard' => 'wpschema-vue',
                'organizations' => 'wpschema-vue-organizations',
                'resources' => 'wpschema-vue-resources',
                'schedules' => 'wpschema-vue-schedules',
                'settings' => 'wpschema-vue-settings'
            )
        ));
    }
    
    /**
     * Filter för att lägga till type="module" på script-taggen
     */
    public function wpschema_vue_script_module($tag, $handle, $src) {
        if ('wpschema-vue-app' === $handle) {
            return '<script type="module" src="' . esc_url($src) . '"></script>';
        }
        return $tag;
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
        echo '<div id="wpschema-vue-admin-app"></div>';
        echo '</div>';
    }

    /**
     * Rendera organisationssidan
     */
    public function render_organizations_page() {
        echo '<div class="wrap">';
        echo '<div id="wpschema-vue-admin-app" data-page="organizations"></div>';
        echo '</div>';
    }

    /**
     * Rendera resurssidan
     */
    public function render_resources_page() {
        echo '<div class="wrap">';
        echo '<div id="wpschema-vue-admin-app" data-page="resources"></div>';
        echo '</div>';
    }

    /**
     * Rendera schemasidan
     */
    public function render_schedules_page() {
        echo '<div class="wrap">';
        echo '<div id="wpschema-vue-admin-app" data-page="schedules"></div>';
        echo '</div>';
    }

    /**
     * Rendera inställningssidan
     */
    public function render_settings_page() {
        echo '<div class="wrap">';
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
