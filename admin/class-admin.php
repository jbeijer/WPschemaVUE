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
require_once ABSPATH . 'wp-includes/rest-api.php';
require_once ABSPATH . 'wp-includes/pluggable.php';
require_once ABSPATH . 'wp-includes/user.php';
require_once ABSPATH . 'wp-includes/class-wp-error.php';

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args) {}
}
if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response($data) {
        return $data;
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($data) {
        return $data;
    }
}
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) { return 'nonce'; }
}
if (!function_exists('rest_url')) {
    function rest_url() { return '/wp-json/'; }
}
if (!function_exists('rest_url_raw')) {
    function rest_url_raw() { return '/wp-json/'; }
}
if (!function_exists('admin_url')) {
    function admin_url($path = '') { return '/wp-admin/' . $path; }
}
if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '') { return '/wp-content/plugins/' . $path; }
}
if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) { return $url; }
}
if (!function_exists('esc_url')) {
    function esc_url($url) { return $url; }
}

// Säkerställ att nödvändiga WordPress-funktioner finns
if (!class_exists('WP_Error')) {
    class WP_Error {
        public function get_error_message() { return ''; }
    }
}

if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
        return substr(md5(uniqid()), 0, $length);
    }
}

if (!function_exists('wp_insert_user')) {
    function wp_insert_user($userdata) {
        return new WP_Error('not_implemented', 'Funktionen är inte implementerad');
    }
}

if (!function_exists('wp_new_user_notification')) {
    function wp_new_user_notification($user_id, $deprecated = null, $notify = '') {}
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
        
        // Registrera REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
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
        
        // Undermeny för användare
        add_submenu_page(
            'wpschema-vue',
            __('Användare', 'wpschema-vue'),
            __('Användare', 'wpschema-vue'),
            'manage_options',
            'wpschema-vue-users',
            array($this, 'render_admin_page')
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
            'rest_url' => esc_url_raw(rest_url()),
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
        echo '<div id="wpschema-vue-admin-app" data-page="users"></div>';
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

    /**
     * Registrera REST API endpoints
     */
    public function register_rest_routes() {
        error_log('Registrerar REST routes för WPschemaVUE');
        
        register_rest_route('schedule/v1', '/users', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_users'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        register_rest_route('schedule/v1', '/users/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_user'),
            'permission_callback' => array($this, 'check_admin_permissions'),
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
        
        error_log('REST routes registrerade');
    }

    /**
     * Kontrollera admin-behörigheter
     */
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }

    /**
     * Hämta användare
     */
    public function get_users($request) {
        if (class_exists('WPschemaVUE_Permissions')) {
            $users = WPschemaVUE_Permissions::get_all_users();
            return rest_ensure_response($users);
        }
        return rest_ensure_response(array());
    }

    /**
     * Skapa användare
     */
    public function create_user($request) {
        error_log('create_user metod anropad');
        error_log('Request params: ' . print_r($request->get_json_params(), true));
        
        $params = $request->get_json_params();
        
        // Validera nödvändiga fält
        if (empty($params['email']) || empty($params['first_name']) || empty($params['last_name'])) {
            return new WP_Error(
                'missing_fields', 
                'Alla obligatoriska fält måste fyllas i.', 
                array('status' => 400)
            );
        }

        // Skapa användaren
        $userdata = array(
            'user_login' => $params['email'],
            'user_email' => $params['email'],
            'first_name' => sanitize_text_field($params['first_name']),
            'last_name' => sanitize_text_field($params['last_name']),
            'display_name' => sanitize_text_field($params['first_name'] . ' ' . $params['last_name']),
            'user_pass' => wp_generate_password(),
            'role' => 'schema_user'
        );

        $user_id = wp_insert_user($userdata);

        if (is_wp_error($user_id)) {
            return new WP_Error(
                'user_creation_failed', 
                $user_id->get_error_message(), 
                array('status' => 400)
            );
        }

        // Skicka välkomstmail med lösenord
        wp_new_user_notification($user_id, null, 'both');

        return rest_ensure_response(array(
            'id' => $user_id,
            'message' => 'Användaren har skapats och ett välkomstmail har skickats.'
        ));
    }
}
