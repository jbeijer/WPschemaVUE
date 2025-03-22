<?php
/**
 * Resource-klass för WPschemaVUE
 *
 * Hanterar resurser inom organisationer
 *
 * @package WPschemaVUE
 */

// Säkerhetskontroll - förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

// Dummy stubs for WordPress functions to satisfy intelephense
if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = array()) {
        return array_merge($defaults, $args);
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return sanitize_text_field($str);
    }
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

/**
 * Resource-klass
 */
class WPschemaVUE_Resource {
    
    /**
     * Tabellnamn
     */
    private $table;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'schedule_resources';
        $this->create_table();
        
        // Kontrollera och uppdatera tabellstrukturen om det behövs
        $this->maybe_update_table_structure();
    }
    
    /**
     * Kontrollerar och uppdaterar tabellstrukturen om det behövs
     */
    private function maybe_update_table_structure() {
        global $wpdb;
        
        // Kontrollera om kolumnen is_24_7 finns
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->table} LIKE 'is_24_7'");
        
        // Om kolumnen inte finns, lägg till den
        if (empty($column_exists)) {
            error_log("Kolumnen is_24_7 saknas i tabellen {$this->table}, lägger till den");
            $wpdb->query("ALTER TABLE {$this->table} 
                ADD COLUMN is_24_7 tinyint(1) DEFAULT 1 AFTER color,
                ADD COLUMN start_time time DEFAULT NULL AFTER is_24_7,
                ADD COLUMN end_time time DEFAULT NULL AFTER start_time");
        }
    }
    
    private function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            organization_id bigint(20) unsigned NOT NULL,
            color varchar(7) DEFAULT '#3788d8',
            is_24_7 tinyint(1) DEFAULT 1,
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY organization_id (organization_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Hämta alla resurser för en organisation
     *
     * @param int $organization_id Organisations-ID
     * @param array $args Argument för att filtrera resultatet
     * @return array Array med resurser
     */
    public function get_resources($organization_id, $args = array()) {
        global $wpdb;
        
        // Standardvärden för argument
        $defaults = array(
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0,
        );
        
        // Slå samman argument med standardvärden
        $args = wp_parse_args($args, $defaults);
        
        // Bygg SQL-frågan
        $sql = "SELECT * FROM {$this->table} WHERE organization_id = %d";
        
        // Lägg till ORDER BY
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        // Lägg till LIMIT om limit är större än 0
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d, %d", $args['offset'], $args['limit']);
        }
        
        // Förbered och kör frågan
        $query = $wpdb->prepare($sql, $organization_id);
        $resources = $wpdb->get_results($query, ARRAY_A);
        
        return $resources ?: array();
    }
    
    /**
     * Hämta en resurs
     *
     * @param int $id Resurs-ID
     * @return object|null Resursdata som objekt, eller null om resursen inte finns
     */
    public function get_resource($id) {
        global $wpdb;
        
        $sql = $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id);
        $resource = $wpdb->get_row($sql);
        
        return $resource;
    }
    
    /**
     * Skapa en resurs
     *
     * @param array $data Resursdata
     * @return int|false ID för den nya resursen eller false vid fel
     */
    public function create_resource($data) {
        global $wpdb;
        
        // Validera data
        if (empty($data['name']) || empty($data['organization_id'])) {
            return false;
        }
        
        // Kontrollera att organisationen finns
        require_once 'class-organization.php';
        $organization = new WPschemaVUE_Organization();
        $org_data = $organization->get_organization($data['organization_id']);
        if (!$org_data) {
            return false;
        }
        
        // Förbered data för insättning
        $insert_data = array(
            'name' => sanitize_text_field($data['name']),
            'organization_id' => $data['organization_id'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );
        
        // Lägg till beskrivning om den finns
        if (!empty($data['description'])) {
            $insert_data['description'] = sanitize_textarea_field($data['description']);
        }
        
        // Hantera tidsinställningar
        if (isset($data['is_24_7']) && $data['is_24_7']) {
            // Om resursen är tillgänglig 24/7
            $insert_data['is_24_7'] = 1;
            $insert_data['start_time'] = null;
            $insert_data['end_time'] = null;
        } else {
            // Om resursen har specifika tider
            $insert_data['is_24_7'] = 0;
            
            // Kontrollera att start_time och end_time finns
            if (empty($data['start_time']) || empty($data['end_time'])) {
                return new WP_Error(
                    'missing_time',
                    __('Start- och sluttid måste anges om resursen inte är tillgänglig 24/7.', 'wpschema-vue'),
                    array('status' => 400)
                );
            }
            
            // Validera tidsformat
            if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $data['start_time']) ||
                !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $data['end_time'])) {
                return new WP_Error(
                    'invalid_time_format',
                    __('Tiderna måste anges i formatet HH:MM eller HH:MM:SS.', 'wpschema-vue'),
                    array('status' => 400)
                );
            }
            
            $insert_data['start_time'] = $data['start_time'];
            $insert_data['end_time'] = $data['end_time'];
        }
        
        // Sätt in i databasen
        $result = $wpdb->insert($this->table, $insert_data);
        
        if (!$result) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Uppdatera en resurs
     *
     * @param int $id Resurs-ID
     * @param array $data Resursdata
     * @return bool|WP_Error True vid framgång, WP_Error vid fel
     */
    public function update_resource($id, $data) {
        global $wpdb;
        
        // Kontrollera att resursen finns
        $resource = $this->get_resource($id);
        if (!$resource) {
            return new WP_Error(
                'resource_not_found',
                __('Resursen hittades inte.', 'wpschema-vue')
            );
        }
        
        // Förbered uppdateringsdata
        $update_data = array();
        
        // Namn
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        
        // Beskrivning
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }
        
        // Färg
        if (isset($data['color'])) {
            $update_data['color'] = sanitize_text_field($data['color']);
        }
        
        // 24/7-tillgänglighet
        if (isset($data['is_24_7'])) {
            if ($data['is_24_7']) {
                $update_data['is_24_7'] = 1;
                $update_data['start_time'] = null;
                $update_data['end_time'] = null;
            } else {
                $update_data['is_24_7'] = 0;
                
                // Om is_24_7 är false måste start_time och end_time anges
                if (isset($data['start_time'])) {
                    // Validera tidsformatet (HH:MM eller HH:MM:SS)
                    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $data['start_time'])) {
                        return new WP_Error(
                            'invalid_start_time',
                            __('Starttiden måste anges i formatet HH:MM eller HH:MM:SS.', 'wpschema-vue')
                        );
                    }
                    $update_data['start_time'] = $data['start_time'];
                }
                
                if (isset($data['end_time'])) {
                    // Validera tidsformatet (HH:MM eller HH:MM:SS)
                    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $data['end_time'])) {
                        return new WP_Error(
                            'invalid_end_time',
                            __('Sluttiden måste anges i formatet HH:MM eller HH:MM:SS.', 'wpschema-vue')
                        );
                    }
                    $update_data['end_time'] = $data['end_time'];
                }
            }
        } else {
            // Om is_24_7 inte anges men start_time eller end_time anges
            if (isset($data['start_time']) || isset($data['end_time'])) {
                $update_data['is_24_7'] = 0;
                
                if (isset($data['start_time'])) {
                    // Validera tidsformatet (HH:MM eller HH:MM:SS)
                    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $data['start_time'])) {
                        return new WP_Error(
                            'invalid_start_time',
                            __('Starttiden måste anges i formatet HH:MM eller HH:MM:SS.', 'wpschema-vue')
                        );
                    }
                    $update_data['start_time'] = $data['start_time'];
                }
                
                if (isset($data['end_time'])) {
                    // Validera tidsformatet (HH:MM eller HH:MM:SS)
                    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $data['end_time'])) {
                        return new WP_Error(
                            'invalid_end_time',
                            __('Sluttiden måste anges i formatet HH:MM eller HH:MM:SS.', 'wpschema-vue')
                        );
                    }
                    $update_data['end_time'] = $data['end_time'];
                }
            }
        }
        
        // Uppdatera modified_at
        $update_data['updated_at'] = current_time('mysql');
        
        // Uppdatera resursen
        $result = $wpdb->update(
            $this->table,
            $update_data,
            array('id' => $id)
        );
        
        // Returnera resultatet
        if ($result === false) {
            return new WP_Error(
                'update_failed',
                __('Kunde inte uppdatera resursen.', 'wpschema-vue')
            );
        }
        
        return true;
    }
    
    /**
     * Ta bort en resurs
     *
     * @param int $id Resurs-ID
     * @return bool True vid framgång, false vid fel
     */
    public function delete_resource($id) {
        global $wpdb;
        
        // Kontrollera att resursen finns
        $resource = $this->get_resource($id);
        if (!$resource) {
            return false;
        }
        
        // Ta bort resursen
        $result = $wpdb->delete(
            $this->table,
            array('id' => $id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Hämta antal scheman för en resurs
     *
     * @param int $id Resurs-ID
     * @return int Antal scheman
     */
    public function get_schedule_count($id) {
        global $wpdb;
        
        $schedule_table = $wpdb->prefix . 'schedule_entries';
        
        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$schedule_table} WHERE resource_id = %d", $id)
        );
    }
    
    /**
     * Kontrollera om en resurs tillhör en organisation
     *
     * @param int $id Resurs-ID
     * @param int $organization_id Organisations-ID
     * @return bool True om resursen tillhör organisationen, false annars
     */
    public function resource_belongs_to_organization($id, $organization_id) {
        global $wpdb;
        
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE id = %d AND organization_id = %d",
                $id,
                $organization_id
            )
        );
        
        return $count > 0;
    }
    
    /**
     * Hämta organisationen för en resurs
     *
     * @param int $id Resurs-ID
     * @return int|null Organisations-ID eller null om resursen inte finns
     */
    public function get_resource_organization($id) {
        global $wpdb;
        
        $organization_id = $wpdb->get_var(
            $wpdb->prepare("SELECT organization_id FROM {$this->table} WHERE id = %d", $id)
        );
        
        return $organization_id ? (int) $organization_id : null;
    }
}
