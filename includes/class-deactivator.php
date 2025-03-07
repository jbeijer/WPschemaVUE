<?php
/**
 * Deaktivator-klass för WPschemaVUE
 *
 * Hanterar avaktivering av pluginet
 *
 * @package WPschemaVUE
 */

// Säkerhetskontroll - förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Deaktivator-klass
 */
class WPschemaVUE_Deactivator {
    
    /**
     * Avaktivera pluginet
     */
    public static function deactivate() {
        // Rensa eventuella schemalagda händelser
        self::clear_scheduled_events();
        
        // Rensa transients
        self::clear_transients();
    }
    
    /**
     * Rensa schemalagda händelser
     */
    private static function clear_scheduled_events() {
        // Exempel på att rensa en schemalagd händelse
        $timestamp = wp_next_scheduled('wpschema_vue_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wpschema_vue_daily_cleanup');
        }
    }
    
    /**
     * Rensa transients
     */
    private static function clear_transients() {
        global $wpdb;
        
        // Rensa alla transients relaterade till pluginet
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_wpschema_vue_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_wpschema_vue_%'");
    }
    
    /**
     * Avinstallera pluginet (statisk metod som kan anropas från uninstall.php)
     * 
     * OBS: Denna metod anropas inte automatiskt vid avaktivering, utan måste anropas
     * explicit från en uninstall.php-fil om användaren väljer att ta bort pluginet helt.
     */
    public static function uninstall() {
        // Fråga användaren om de vill ta bort all data
        // Detta skulle normalt hanteras via en inställning i admin
        $remove_data = get_option('wpschema_vue_remove_data_on_uninstall', false);
        
        if ($remove_data) {
            global $wpdb;
            
            // Ta bort databastabeller
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}schedule_entries");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}schedule_resources");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}schedule_user_organizations");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}schedule_organizations");
            
            // Ta bort alternativ
            delete_option('wpschema_vue_version');
            delete_option('wpschema_vue_db_version');
            delete_option('wpschema_vue_remove_data_on_uninstall');
            
            // Ta bort alla alternativ relaterade till pluginet
            $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'wpschema_vue_%'");
        }
    }
}
