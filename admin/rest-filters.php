<?php
/**
 * Rest Filters for WPschemaVUE Plugin.
 *
 * Denna fil lägger till ett filter som inkluderar organisationens ID i responsen från WP REST API:s användar-endpoint.
 */

if ( ! function_exists( 'get_user_meta' ) ) {
    function get_user_meta( $user_id, $meta_key, $single = false ) {
        // Stub-funktion för att förhindra undefined function-fel vid statisk analys.
        return '';
    }
}

// Stub-funktion för register_meta för att förhindra undefined function-fel vid statisk analys.
if ( ! function_exists( 'register_meta' ) ) {
    function register_meta( $object_type, $meta_key, $args ) {
        // Returnera true som en dummy-implementation.
        return true;
    }
}

/**
 * Inkludera organisationens meta-värde i REST-svaret för användare.
 */
add_filter( 'rest_prepare_user', function( $response, $user, $request ) {
    if ( !function_exists( 'get_user_meta' ) ) {
        require_once( ABSPATH . 'wp-includes/user.php' );
    }
    $organization = get_user_meta( $user->ID, 'organization_id', true );
    // Konvertera till heltal om det finns ett värde, annars sätt till 0.
    $response->data['organization'] = $organization ? intval( $organization ) : 0;
    return $response;
}, 10, 3 );

if ( ! function_exists( 'register_meta' ) ) {
    require_once( ABSPATH . 'wp-includes/meta.php' );
}
if ( function_exists( 'register_meta' ) ) {
    // Registrera 'organization_id' som användarmeta så att den sparas korrekt i databasen och syns i REST API:t.
    register_meta( 'user', 'organization_id', array(
        'type'         => 'integer',
        'description'  => 'Organization ID',
        'single'       => true,
        'show_in_rest' => true,
    ) );
}
