<?php
/**
 * WordPress function stubs for IDE type checking
 */

if (!function_exists('add_action')) {
    /**
     * Adds a callback function to an action hook.
     *
     * @param string   $hook_name     The name of the action to add the callback to.
     * @param callable $callback      The callback to be run when the action is called.
     * @param int      $priority      Optional. The priority of the callback. Default 10.
     * @param int      $accepted_args Optional. The number of arguments the callback accepts. Default 1.
     * @return true
     */
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    /**
     * Adds a callback function to a filter hook.
     *
     * @param string   $hook_name     The name of the filter to add the callback to.
     * @param callable $callback      The callback to be run when the filter is applied.
     * @param int      $priority      Optional. The priority of the callback. Default 10.
     * @param int      $accepted_args Optional. The number of arguments the callback accepts. Default 1.
     * @return true
     */
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_menu_page')) {
    /**
     * Adds a top-level menu page.
     *
     * @param string   $page_title    The text to be displayed in the title tags of the page.
     * @param string   $menu_title    The text to be used for the menu.
     * @param string   $capability    The capability required for this menu to be displayed.
     * @param string   $menu_slug     The slug name to refer to this menu by.
     * @param callable $function      Optional. The function to be called to output the content for this page.
     * @param string   $icon_url      Optional. The URL to the icon to be used for this menu.
     * @param int      $position      Optional. The position in the menu order this menu should appear.
     * @return string The resulting page's hook_suffix.
     */
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null) {
        return '';
    }
}

if (!function_exists('add_submenu_page')) {
    /**
     * Adds a submenu page to a top-level menu.
     *
     * @param string   $parent_slug   The slug name for the parent menu.
     * @param string   $page_title    The text to be displayed in the title tags of the page.
     * @param string   $menu_title    The text to be used for the menu.
     * @param string   $capability    The capability required for this menu to be displayed.
     * @param string   $menu_slug     The slug name to refer to this menu by.
     * @param callable $function      Optional. The function to be called to output the content for this page.
     * @return string The resulting page's hook_suffix.
     */
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '') {
        return '';
    }
}

if (!function_exists('wp_enqueue_script')) {
    /**
     * Enqueues a script.
     *
     * @param string           $handle    Name of the script.
     * @param string           $src       Full URL of the script, or path of the script relative to the WordPress root directory.
     * @param string[]         $deps      Optional. An array of registered script handles this script depends on.
     * @param string|bool|null $ver       Optional. String specifying script version number, if it has one.
     * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
     */
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {}
}

if (!function_exists('wp_enqueue_style')) {
    /**
     * Enqueues a stylesheet.
     *
     * @param string           $handle Name of the stylesheet.
     * @param string           $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
     * @param string[]         $deps   Optional. An array of registered stylesheet handles this stylesheet depends on.
     * @param string|bool|null $ver    Optional. String specifying stylesheet version number, if it has one.
     * @param string           $media  Optional. The media for which this stylesheet has been defined.
     */
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {}
}

if (!function_exists('__')) {
    /**
     * Retrieves the translation of $text.
     *
     * @param string $text   Text to translate.
     * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
     * @return string Translated text.
     */
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('plugins_url')) {
    /**
     * Retrieves the URL to a plugin file.
     *
     * @param string $path   Optional. Path relative to the plugin file.
     * @param string $plugin Optional. Plugin file path.
     * @return string Plugin file URL.
     */
    function plugins_url($path = '', $plugin = '') {
        return $path;
    }
}

if (!function_exists('wp_localize_script')) {
    /**
     * Localizes a script.
     *
     * @param string $handle      Script handle the data will be attached to.
     * @param string $object_name Name for the JavaScript object.
     * @param array  $l10n        Array of data to be passed to the script.
     * @return bool True if the script was successfully localized.
     */
    function wp_localize_script($handle, $object_name, $l10n) {
        return true;
    }
}

if (!function_exists('wp_create_nonce')) {
    /**
     * Creates a cryptographic nonce.
     *
     * @param string|int $action Scalar value to add context to the nonce.
     * @return string The one-use token.
     */
    function wp_create_nonce($action = -1) {
        return '';
    }
}

if (!function_exists('esc_url_raw')) {
    /**
     * Performs esc_url() for database usage.
     *
     * @param string $url The URL to be cleaned.
     * @return string The cleaned URL.
     */
    function esc_url_raw($url) {
        return $url;
    }
}

if (!function_exists('rest_url')) {
    /**
     * Retrieves the URL for a REST API endpoint.
     *
     * @param string $path    Optional. REST route.
     * @param string $scheme  Optional. Sanitization scheme.
     * @return string Full URL for the REST API endpoint.
     */
    function rest_url($path = '', $scheme = 'rest') {
        return $path;
    }
}

if (!function_exists('admin_url')) {
    /**
     * Retrieves the URL to the admin area.
     *
     * @param string $path   Optional. Path relative to the admin area URL.
     * @param string $scheme Optional. Sanitization scheme.
     * @return string Admin area URL.
     */
    function admin_url($path = '', $scheme = 'admin') {
        return $path;
    }
}

if (!function_exists('wp_get_current_user')) {
    /**
     * Retrieves the current logged-in user object.
     *
     * @return WP_User Current user WP_User object
     */
    function wp_get_current_user() {
        return new WP_User();
    }
}

// Stub for WP_User class
if (!class_exists('WP_User')) {
    class WP_User {
        public $ID = 0;
        public $user_login = '';
        public $display_name = '';
        public $user_email = '';
        public $roles = [];

        public function exists() {
            return false;
        }
    }
}
