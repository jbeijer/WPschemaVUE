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

    /**
     * Sparar eller uppdaterar en användares roll i en organisation.
     * 
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @param string $role Användarens roll i organisationen (base, scheduler, admin)
     * @return int|bool ID för den nya posten eller true vid uppdatering, false vid fel
     */
    public static function save_user_organization($user_id, $organization_id, $role) {
        try {
            global $wpdb;
            
            // Kontrollera att wpdb är tillgängligt
            if (!isset($wpdb) || !is_object($wpdb)) {
                error_log('KRITISKT FEL: $wpdb är inte tillgängligt i save_user_organization');
                return false;
            }
            
            // Kontrollera att tabellen finns
            $table = $wpdb->prefix . 'schedule_user_organizations';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            
            if (!$table_exists) {
                error_log("KRITISKT FEL: Tabellen $table existerar inte");
                return false;
            }
            
            // Validera indata
            $user_id = (int) $user_id;
            $organization_id = (int) $organization_id;
            $role = sanitize_text_field($role);
            
            // Kontrollera att användar-ID och organisations-ID är giltiga
            if ($user_id <= 0) {
                error_log("Ogiltigt användar-ID: $user_id");
                return false;
            }
            
            if ($organization_id <= 0) {
                error_log("Ogiltigt organisations-ID: $organization_id");
                return false;
            }
            
            // Normalisera rollvärdet till små bokstäver för att matcha enum-värdena i databasen
            $role = strtolower($role);
            
            // Kontrollera att rollen är ett giltigt enum-värde
            $valid_roles = array('base', 'schemalaggare', 'schemaanmain');
            if (!in_array($role, $valid_roles)) {
                error_log("Ogiltig roll: '$role'. Giltiga roller är: " . implode(', ', $valid_roles) . ". Använder 'base' som standard.");
                $role = 'base'; // Använd standardrollen om ett ogiltigt värde anges
            }
            
            // Logga för felsökning
            error_log("Sparar användarorganisation: user_id=$user_id, organization_id=$organization_id, role=$role");
            
            // Kontrollera om relationen redan finns
            $exists_query = $wpdb->prepare("SELECT id FROM $table WHERE user_id = %d AND organization_id = %d", $user_id, $organization_id);
            error_log("SQL-fråga för att kontrollera om relationen finns: $exists_query");
            $exists = $wpdb->get_var($exists_query);
            
            if ($exists) {
                // Uppdatera befintlig relation
                error_log("Uppdaterar befintlig användarorganisation med ID: $exists");
                
                // Kontrollera att rollen är giltig för databasen
                // Hämta kolumninformation för att kontrollera enum-värden
                $column_info_query = "SHOW COLUMNS FROM $table LIKE 'role'";
                $column_info = $wpdb->get_row($column_info_query);
                
                if ($column_info && isset($column_info->Type)) {
                    // Extrahera enum-värden från kolumntypen
                    preg_match("/^enum\((.*)\)$/", $column_info->Type, $matches);
                    if (isset($matches[1])) {
                        $enum_values = str_getcsv($matches[1], ',', "'");
                        error_log("Enum-värden från databasen: " . implode(', ', $enum_values));
                        
                        if (!in_array($role, $enum_values)) {
                            error_log("Rollen '$role' matchar inte något av de tillåtna värdena i databasen. Använder 'base' som standard.");
                            $role = 'base';
                        }
                    }
                }
                
                $update_data = array('role' => $role);
                $where_data = array('user_id' => $user_id, 'organization_id' => $organization_id);
                
                error_log("Försöker uppdatera med data: " . print_r($update_data, true) . ", where: " . print_r($where_data, true));
                
                $result = $wpdb->update(
                    $table, 
                    $update_data, 
                    $where_data, 
                    array('%s'), 
                    array('%d', '%d')
                );
                
                if ($result === false) {
                    error_log("Databasfel vid uppdatering av användarorganisation: " . $wpdb->last_error);
                    return false;
                }
                
                error_log("Uppdatering lyckades. Resultat: $result");
                return $exists; // Returnera ID för den uppdaterade posten
            } else {
                // Skapa ny relation
                error_log("Skapar ny användarorganisation");
                
                // Kontrollera att rollen är giltig för databasen
                // Hämta kolumninformation för att kontrollera enum-värden
                $column_info_query = "SHOW COLUMNS FROM $table LIKE 'role'";
                $column_info = $wpdb->get_row($column_info_query);
                
                if ($column_info && isset($column_info->Type)) {
                    // Extrahera enum-värden från kolumntypen
                    preg_match("/^enum\((.*)\)$/", $column_info->Type, $matches);
                    if (isset($matches[1])) {
                        $enum_values = str_getcsv($matches[1], ',', "'");
                        error_log("Enum-värden från databasen: " . implode(', ', $enum_values));
                        
                        if (!in_array($role, $enum_values)) {
                            error_log("Rollen '$role' matchar inte något av de tillåtna värdena i databasen. Använder 'base' som standard.");
                            $role = 'base';
                        }
                    }
                }
                
                $data = array(
                    'user_id'         => $user_id,
                    'organization_id' => $organization_id,
                    'role'            => $role
                );
                $format = array('%d', '%d', '%s');
                
                error_log("Försöker skapa ny post med data: " . print_r($data, true));
                
                $result = $wpdb->insert($table, $data, $format);
                
                if ($result === false) {
                    error_log("Databasfel vid skapande av användarorganisation: " . $wpdb->last_error);
                    return false;
                }
                
                $new_id = $wpdb->insert_id;
                error_log("Ny användarorganisation skapad med ID: $new_id");
                return $new_id;
            }
        } catch (Exception $e) {
            error_log('Exception i save_user_organization: ' . $e->getMessage());
            error_log('Exception stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    /**
     * Kontrollera om en association mellan användare och organisation existerar
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @return bool True om associationen existerar, false annars
     */
    public static function association_exists($user_id, $organization_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'schedule_user_organizations';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND organization_id = %d",
            $user_id,
            $organization_id
        ));
        
        return (int)$result > 0;
    }
    
    /**
     * Skapa en ny association mellan användare och organisation
     *
     * @param array $data Associationsdata (user_id, organization_id, role)
     * @return int|bool ID för den nya associationen eller false vid fel
     */
    public static function create_association($data) {
        // Använd save_user_organization för att skapa associationen
        return self::save_user_organization($data['user_id'], $data['organization_id'], $data['role']);
    }
    
    /**
     * Hämta alla användare för en organisation
     *
     * @param int $organization_id Organisations-ID
     * @return array Lista med användare och deras roller
     */
    public static function get_organization_users($organization_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'schedule_user_organizations';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT uo.*, u.display_name, u.user_email 
             FROM $table uo
             JOIN {$wpdb->users} u ON uo.user_id = u.ID
             WHERE uo.organization_id = %d",
            $organization_id
        ));
        
        if (!$results) {
            return array();
        }
        
        $users = array();
        foreach ($results as $row) {
            $users[] = array(
                'user_id' => (int)$row->user_id,
                'organization_id' => (int)$row->organization_id,
                'role' => $row->role,
                'user_data' => array(
                    'display_name' => $row->display_name,
                    'user_email' => $row->user_email
                )
            );
        }
        
        return $users;
    }
    
    /**
     * Hämta användarens roll i en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @return string|null Användarens roll eller null om användaren inte finns i organisationen
     */
    public static function get_user_role($user_id, $organization_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'schedule_user_organizations';
        
        $role = $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM $table WHERE user_id = %d AND organization_id = %d",
            $user_id,
            $organization_id
        ));
        
        return $role;
    }
    
    /**
     * Hämta alla organisationer för en användare
     *
     * @param int $user_id Användar-ID
     * @return array Lista med organisationer och användarens roller
     */
    public static function get_user_organizations($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'schedule_user_organizations';
        $org_table = $wpdb->prefix . 'schedule_organizations';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT uo.*, o.name as organization_name
             FROM $table uo
             JOIN $org_table o ON uo.organization_id = o.id
             WHERE uo.user_id = %d",
            $user_id
        ));
        
        if (!$results) {
            return array();
        }
        
        $organizations = array();
        foreach ($results as $row) {
            $organizations[] = array(
                'id' => (int)$row->organization_id,
                'name' => $row->organization_name,
                'role' => $row->role
            );
        }
        
        return $organizations;
    }
    
    /**
     * Kontrollera om en användare har en specifik roll i en organisation.
     *
     * @param int $user_id Användar-ID.
     * @param int $organization_id Organisations-ID.
     * @param string $role Roll att kontrollera.
     * @return bool True om användaren har rollen, false annars.
     */
    public static function user_has_role($user_id, $organization_id, $role) {
        $user_role = self::get_user_role($user_id, $organization_id);
        return $user_role === $role;
    }
    
    /**
     * Kontrollera om en användare har en minsta roll i en organisation.
     *
     * @param int $user_id Användar-ID.
     * @param int $organization_id Organisations-ID.
     * @param string $min_role Minsta roll att kontrollera (base, scheduler, admin).
     * @return bool True om användaren har minst den angivna rollen, false annars.
     */
    public static function user_has_min_role($user_id, $organization_id, $min_role) {
        $user_role = self::get_user_role($user_id, $organization_id);
        
        if (!$user_role) {
            return false;
        }
        
        // Definiera rollhierarkin
        $role_hierarchy = array(
            'base' => 1,
            'schemalaggare' => 2,
            'schemaanmain' => 3
        );
        
        // Kontrollera om användarens roll är minst lika hög som den efterfrågade
        return isset($role_hierarchy[$user_role]) && 
               isset($role_hierarchy[$min_role]) && 
               $role_hierarchy[$user_role] >= $role_hierarchy[$min_role];
    }
    
    /**
     * Ta bort en användares koppling till en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @return bool True om borttagningen lyckades, false annars
     */
    public static function delete_user_organization($user_id, $organization_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'schedule_user_organizations';
        
        // Validera indata
        $user_id = (int) $user_id;
        $organization_id = (int) $organization_id;
        
        // Logga för felsökning
        error_log("Tar bort användarorganisation: user_id=$user_id, organization_id=$organization_id");
        
        // Kontrollera först om relationen finns
        if (!self::association_exists($user_id, $organization_id)) {
            error_log("Användarorganisation finns inte: user_id=$user_id, organization_id=$organization_id");
            return false;
        }
        
        // Ta bort relationen
        $result = $wpdb->delete(
            $table,
            array(
                'user_id' => $user_id,
                'organization_id' => $organization_id
            ),
            array('%d', '%d')
        );
        
        if ($result === false) {
            error_log("Databasfel vid borttagning av användarorganisation: " . $wpdb->last_error);
            return false;
        }
        
        error_log("Användarorganisation borttagen: user_id=$user_id, organization_id=$organization_id");
        return true;
    }
    
    /**
     * Kontrollera om en användare har behörighet att utföra en specifik åtgärd i en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @param string $action Åtgärd att kontrollera (manage_users, manage_resources, etc.)
     * @return bool True om användaren har behörighet, false annars
     */
    public static function user_can($user_id, $organization_id, $action) {
        $user_role = self::get_user_role($user_id, $organization_id);
        
        if (!$user_role) {
            return false;
        }
        
        // Definiera behörigheter för varje roll
        $role_permissions = array(
            'base' => array(
                'view_schedule' => true,
                'view_resources' => true
            ),
            'schemalaggare' => array(
                'view_schedule' => true,
                'view_resources' => true,
                'manage_schedule' => true,
                'assign_shifts' => true
            ),
            'schemaanmain' => array(
                'view_schedule' => true,
                'view_resources' => true,
                'manage_schedule' => true,
                'assign_shifts' => true,
                'manage_users' => true,
                'manage_resources' => true,
                'manage_organizations' => true,
                'lock_shifts' => true,
                'force_delete_shifts' => true
            )
        );
        
        // Kontrollera om rollen har behörigheten
        if (isset($role_permissions[$user_role]) && isset($role_permissions[$user_role][$action])) {
            return $role_permissions[$user_role][$action];
        }
        
        return false;
    }
    
    /**
     * Hämta alla behörigheter för en användare i en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @return array Lista med behörigheter
     */
    public static function get_user_permissions($user_id, $organization_id) {
        $user_role = self::get_user_role($user_id, $organization_id);
        
        if (!$user_role) {
            return array();
        }
        
        // Definiera behörigheter för varje roll
        $role_permissions = array(
            'base' => array(
                'view_schedule' => true,
                'view_resources' => true
            ),
            'schemalaggare' => array(
                'view_schedule' => true,
                'view_resources' => true,
                'manage_schedule' => true,
                'assign_shifts' => true
            ),
            'schemaanmain' => array(
                'view_schedule' => true,
                'view_resources' => true,
                'manage_schedule' => true,
                'assign_shifts' => true,
                'manage_users' => true,
                'manage_resources' => true,
                'manage_organizations' => true,
                'lock_shifts' => true,
                'force_delete_shifts' => true
            )
        );
        
        return isset($role_permissions[$user_role]) ? $role_permissions[$user_role] : array();
    }
}
