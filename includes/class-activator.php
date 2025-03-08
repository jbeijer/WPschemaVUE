<?php
/**
 * Aktivator-klass för WPschemaVUE
 * Hanterar aktivering av pluginet och skapar nödvändiga databastabeller
 * @package WPschemaVUE
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPschemaVUE_Activator klassen.
 */
class WPschemaVUE_Activator {

	/**
	 * Aktivera pluginet.
	 */
	public static function activate() {
		self::create_tables();
		self::update_schema();
	}
	
	/**
	 * Uppdatera databasschemat om det behövs.
	 */
	private static function update_schema() {
		global $wpdb;
		
		$current_version = get_option('wpschema_vue_db_version', '0.0.0');
		
		// Om versionen är samma som den aktuella, behöver vi inte uppdatera
		if ($current_version === WPSCHEMA_VUE_VERSION) {
			return;
		}
		
		// Uppdatera user_organizations_table för att lägga till 'schemaanmain' rollen
		$user_organizations_table = $wpdb->prefix . 'schedule_user_organizations';
		
		// Kontrollera om tabellen finns
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$user_organizations_table'") === $user_organizations_table;
		
		if ($table_exists) {
			// Kontrollera om 'schemaanmain' redan finns i enum-listan
			$column_info = $wpdb->get_row("SHOW COLUMNS FROM $user_organizations_table LIKE 'role'");
			
			if ($column_info && strpos($column_info->Type, 'schemaanmain') === false) {
				// Lägg till 'schemaanmain' i enum-listan
				$wpdb->query("ALTER TABLE $user_organizations_table MODIFY COLUMN role ENUM('base','scheduler','admin','wpschema_anvandare','schemaanmain') NOT NULL DEFAULT 'base'");
			}
		}
		
		// Uppdatera versionen
		update_option('wpschema_vue_db_version', WPSCHEMA_VUE_VERSION);
	}

	/**
	 * Skapa databastabeller.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$organizations_table = $wpdb->prefix . 'schedule_organizations';
		$user_organizations_table = $wpdb->prefix . 'schedule_user_organizations';
		$resources_table = $wpdb->prefix . 'schedule_resources';
		$schedule_entries_table = $wpdb->prefix . 'schedule_entries';
		$organization_permissions_table = $wpdb->prefix . 'wpschema_organization_permissions';

		$organizations_sql = "CREATE TABLE $organizations_table (\n            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n            name varchar(255) NOT NULL,\n            parent_id bigint(20) unsigned DEFAULT NULL,\n            path varchar(255) DEFAULT NULL,\n            created_at datetime DEFAULT CURRENT_TIMESTAMP,\n            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            PRIMARY KEY  (id),\n            KEY parent_id (parent_id),\n            KEY path (path)\n        ) $charset_collate;";

		$user_organizations_sql = "CREATE TABLE $user_organizations_table (\n            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n            user_id bigint(20) unsigned NOT NULL,\n            organization_id bigint(20) unsigned NOT NULL,\n            role enum('base','scheduler','admin','wpschema_anvandare','schemaanmain') NOT NULL DEFAULT 'base',\n            created_at datetime DEFAULT CURRENT_TIMESTAMP,\n            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            PRIMARY KEY  (id),\n            UNIQUE KEY user_organization (user_id,organization_id),\n            KEY organization_id (organization_id),\n            KEY user_id (user_id)\n        ) $charset_collate;";

		$organization_permissions_sql = "CREATE TABLE $organization_permissions_table (\n            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n            user_id bigint(20) unsigned NOT NULL,\n            organization_id bigint(20) unsigned NOT NULL,\n            can_edit boolean DEFAULT FALSE,\n            can_delete boolean DEFAULT FALSE,\n            created_at datetime DEFAULT CURRENT_TIMESTAMP,\n            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            PRIMARY KEY  (id),\n            UNIQUE KEY user_organization (user_id,organization_id),\n            KEY organization_id (organization_id),\n            KEY user_id (user_id)\n        ) $charset_collate;";

		$resources_sql = "CREATE TABLE $resources_table (\n            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n            name varchar(255) NOT NULL,\n            description text,\n            organization_id bigint(20) unsigned NOT NULL,\n            color varchar(7) DEFAULT '#3788d8',\n            created_at datetime DEFAULT CURRENT_TIMESTAMP,\n            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            PRIMARY KEY  (id),\n            KEY organization_id (organization_id)\n        ) $charset_collate;";

		$schedule_entries_sql = "CREATE TABLE $schedule_entries_table (\n            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n            user_id bigint(20) unsigned NOT NULL,\n            resource_id bigint(20) unsigned NOT NULL,\n            start_time datetime NOT NULL,\n            end_time datetime NOT NULL,\n            notes text,\n            status enum('scheduled','confirmed','completed') NOT NULL DEFAULT 'scheduled',\n            created_by bigint(20) unsigned NOT NULL,\n            created_at datetime DEFAULT CURRENT_TIMESTAMP,\n            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            PRIMARY KEY  (id),\n            KEY user_id (user_id),\n            KEY resource_id (resource_id),\n            KEY time_range (start_time,end_time)\n        ) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $organizations_sql );
		dbDelta( $user_organizations_sql );
		dbDelta( $organization_permissions_sql );
		dbDelta( $resources_sql );
		dbDelta( $schedule_entries_sql );

		update_option( 'wpschema_vue_db_version', WPSCHEMA_VUE_VERSION );
	}
}
