<?php
/**
 * Aktivator-klass för WPschemaVUE
 *
 * Hanterar aktivering av pluginet och skapar nödvändiga databastabeller
 *
 * @package WPschemaVUE
 */

// Säkerhetskontroll - förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Aktivator-klass
 */
class WPschemaVUE_Activator {
    
    /**
     * Aktivera pluginet
     */
    public static function activate() {
        self::create_tables();
    }
    
    /**
     * Skapa databastabeller
     */
    private static function create_tables() {
        global $wpdb;
        
        // Hämta collation för databasen
        $charset_collate = $wpdb->get_charset_collate();
        
        // Definiera tabellnamn med prefix
        $organizations_table = $wpdb->prefix . 'schedule_organizations';
        $user_organizations_table = $wpdb->prefix . 'schedule_user_organizations';
        $resources_table = $wpdb->prefix . 'schedule_resources';
        $schedule_entries_table = $wpdb->prefix . 'schedule_entries';
        
        // SQL för att skapa organizations-tabellen
        $organizations_sql = "CREATE TABLE $organizations_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            parent_id bigint(20) unsigned DEFAULT NULL,
            path varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY parent_id (parent_id),
            KEY path (path)
        ) $charset_collate;";
        
        // SQL för att skapa user_organizations-tabellen
        $user_organizations_sql = "CREATE TABLE $user_organizations_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            organization_id bigint(20) unsigned NOT NULL,
            role enum('base','scheduler','admin') NOT NULL DEFAULT 'base',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_organization (user_id,organization_id),
            KEY organization_id (organization_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // SQL för att skapa resources-tabellen
        $resources_sql = "CREATE TABLE $resources_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            organization_id bigint(20) unsigned NOT NULL,
            color varchar(7) DEFAULT '#3788d8',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY organization_id (organization_id)
        ) $charset_collate;";
        
        // SQL för att skapa schedule_entries-tabellen
        $schedule_entries_sql = "CREATE TABLE $schedule_entries_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            resource_id bigint(20) unsigned NOT NULL,
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            notes text,
            status enum('scheduled','confirmed','completed') NOT NULL DEFAULT 'scheduled',
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY resource_id (resource_id),
            KEY time_range (start_time,end_time)
        ) $charset_collate;";
        
        // Kräv dbDelta för att köra SQL-frågor
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Skapa tabellerna
        dbDelta($organizations_sql);
        dbDelta($user_organizations_sql);
        dbDelta($resources_sql);
        dbDelta($schedule_entries_sql);
        
        // Spara databasversion för framtida uppdateringar
        update_option('wpschema_vue_db_version', WPSCHEMA_VUE_VERSION);
    }
}
