<?php
/**
 * WPschemaVUE_User_Organization class
 *
 * Hanterar kopplingen mellan användare och organisationer.
 *
 * @package YourPluginName
 */

/* global wp_update_user, is_wp_error, update_user_meta, current_user_can */

// Dummy stubs for static analysis (intelephense) - dessa block kommer aldrig att exekveras.
if ( false ) {
    /** @noinspection PhpUndefinedFunctionInspection */
    function wp_update_user( $user ) {}
    /** @noinspection PhpUndefinedFunctionInspection */
    function is_wp_error( $thing ) {}
    /** @noinspection PhpUndefinedFunctionInspection */
    function update_user_meta( $user_id, $meta_key, $meta_value ) {}
    /** @noinspection PhpUndefinedFunctionInspection */
    function current_user_can( $capability ) {}
}

// Ladda nödvändiga WordPress-funktioner vid behov
if ( ! function_exists( 'wp_update_user' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/user.php' );
}
if ( ! function_exists( 'update_user_meta' ) ) {
    require_once( ABSPATH . 'wp-includes/user.php' );
}
if ( ! function_exists( 'is_wp_error' ) ) {
    require_once( ABSPATH . 'wp-includes/pluggable.php' );
}
if ( ! function_exists( 'current_user_can' ) ) {
    require_once( ABSPATH . 'wp-includes/pluggable.php' );
}

class WPschemaVUE_User_Organization {

    public static function save_user_organization($user_id, $organization_id, $role) {
        global $wpdb;
        $table = $wpdb->prefix . 'schedule_user_organizations';
        $data = array(
            'user_id'         => $user_id,
            'organization_id' => $organization_id,
            'role'            => $role
        );
        $format = array('%d', '%d', '%s');
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id = %d AND organization_id = %d", $user_id, $organization_id));
        if ($exists) {
            $wpdb->update($table, array('role' => $role), array('user_id' => $user_id, 'organization_id' => $organization_id), array('%s'), array('%d', '%d'));
        } else {
            $wpdb->insert($table, $data, $format);
        }
        return $wpdb->insert_id;
    }
    // Existerande metoder och egenskaper...
    
    /**
     * Uppdaterar en användares roll och organisation.
     *
     * Endpoint: PUT /wp-json/schedule/v1/organizations/{orgId}/users/{userId}
     *
     * Förväntade parametrar:
     * - orgId (från route)
     * - userId (från route)
     * - role (i request body, JSON)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function update_user_organization_role( $request ) {
        $orgId = (int) $request['orgId'];
        $userId = (int) $request['userId'];
        $params = $request->get_json_params();
        $role = isset($params['role']) ? sanitize_text_field($params['role']) : '';
        if ( empty( $role ) ) {
            return new WP_Error( 'no_role', 'Role is required', array( 'status' => 400 ) );
        }
        
        wp_update_user( array( 'ID' => $userId, 'role' => $role ) );
        update_user_meta( $userId, 'organization_id', $orgId );
        self::save_user_organization($userId, $orgId, $role);
        
        return rest_ensure_response( array(
            'success' => true,
            'userId'  => $userId,
            'orgId'   => $orgId,
            'role'    => $role,
            'organization' => $orgId,
        ) );
    }
    
    /**
     * Kontrollera om en användare har en specifik roll i en organisation.
     *
     * @param int $user_id Användar-ID.
     * @param int $organization_id Organisations-ID.
     * @param string $role Roll att kontrollera.
     * @return bool True om användaren har rollen, false annars.
     */
    public function user_has_role($user_id, $organization_id, $role) {
        // Dummy-implementation; anpassa efter behov.
        return false;
    }
    
    /**
     * Kontrollera om en användare har en minsta roll i en organisation.
     *
     * @param int $user_id Användar-ID.
     * @param int $organization_id Organisations-ID.
     * @param string $min_role Minsta roll att kontrollera.
     * @return bool True om användaren har minst den angivna rollen, false annars.
     */
    public function user_has_min_role($user_id, $organization_id, $min_role) {
        // Dummy-implementation; anpassa efter behov.
        return false;
    }
}
    
// Registrera REST-endpointen för att uppdatera användarens roll och organisation
add_action( 'rest_api_init', function(){
    register_rest_route( 'schedule/v1', '/organizations/(?P<orgId>\d+)/users/(?P<userId>\d+)', array(
        'methods'  => 'PUT',
        'callback' => array( 'WPschemaVUE_User_Organization', 'update_user_organization_role' ),
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ));
});
