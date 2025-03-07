<?php
/**
 * Publik-klass för WPschemaVUE
 *
 * Hanterar publik funktionalitet för pluginet
 *
 * @package WPschemaVUE
 */

// Säkerhetskontroll - förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Publik-klass
 */
class WPschemaVUE_Public {
    
    /**
     * Initiera publik funktionalitet
     */
    public function init() {
        // Registrera shortcodes
        add_shortcode('wpschema_vue', array($this, 'render_schedule_shortcode'));
        add_shortcode('wpschema_vue_my_schedule', array($this, 'render_my_schedule_shortcode'));
        add_shortcode('wpschema_vue_organization_schedule', array($this, 'render_organization_schedule_shortcode'));
        
        // Registrera publika skript och stilar
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Registrera skript och stilar
     */
    public function enqueue_scripts() {
        // Registrera Vue-appen
        wp_register_script(
            'wpschema-vue-public-app',
            WPSCHEMA_VUE_PLUGIN_URL . 'public/vue-app/dist/js/app.js',
            array(),
            WPSCHEMA_VUE_VERSION,
            true
        );
        
        // Registrera stilar
        wp_register_style(
            'wpschema-vue-public-styles',
            WPSCHEMA_VUE_PLUGIN_URL . 'public/vue-app/dist/css/app.css',
            array(),
            WPSCHEMA_VUE_VERSION
        );
        
        // Skicka data till Vue-appen
        wp_localize_script('wpschema-vue-public-app', 'wpScheduleData', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'rest_url' => esc_url_raw(rest_url('schedule/v1')),
            'plugin_url' => WPSCHEMA_VUE_PLUGIN_URL,
            'current_user' => $this->get_current_user_data()
        ));
    }
    
    /**
     * Rendera huvudshortcode
     */
    public function render_schedule_shortcode($atts) {
        // Tolka attribut
        $atts = shortcode_atts(array(
            'view' => 'default', // default, my-schedule, organization
            'organization_id' => 0,
            'resource_id' => 0,
            'days' => 7,
            'start_date' => '',
        ), $atts, 'wpschema_vue');
        
        // Ladda skript och stilar
        wp_enqueue_script('wpschema-vue-public-app');
        wp_enqueue_style('wpschema-vue-public-styles');
        
        // Skapa attribut-strängen för div-elementet
        $data_attrs = '';
        foreach ($atts as $key => $value) {
            if (!empty($value)) {
                $data_attrs .= ' data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        }
        
        // Returnera HTML för Vue-appen
        return '<div id="wpschema-vue-public-app"' . $data_attrs . '></div>';
    }
    
    /**
     * Rendera mitt schema shortcode
     */
    public function render_my_schedule_shortcode($atts) {
        // Lägg till view=my-schedule till attributen
        $atts['view'] = 'my-schedule';
        
        // Anropa huvudshortcode
        return $this->render_schedule_shortcode($atts);
    }
    
    /**
     * Rendera organisationsschema shortcode
     */
    public function render_organization_schedule_shortcode($atts) {
        // Lägg till view=organization till attributen
        $atts['view'] = 'organization';
        
        // Anropa huvudshortcode
        return $this->render_schedule_shortcode($atts);
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
