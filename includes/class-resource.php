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
     * @return array|null Resursen eller null om den inte finns
     */
    public function get_resource($id) {
        global $wpdb;
        
        // Hämta resursen
        $resource = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        
        return $resource ?: null;
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
        
        // Lägg till färg om den finns
        if (!empty($data['color'])) {
            // Validera färg
            if (!preg_match('/^#[0-9a-f]{6}$/i', $data['color'])) {
                return new WP_Error(
                    'invalid_color',
                    __('Färgvärdet måste vara en giltig hex-färg (t.ex. #FF0000).', 'wpschema-vue'),
                    array('status' => 400)
                );
            }
            $insert_data['color'] = $data['color'];
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
     * @return bool True vid framgång, false vid fel
     */
    public function update_resource($id, $data) {
        global $wpdb;
        
        // Hämta befintlig resurs
        $resource = $this->get_resource($id);
        if (!$resource) {
            return false;
        }
        
        // Förbered data för uppdatering
        $update_data = array(
            'updated_at' => current_time('mysql'),
        );
        
        // Uppdatera namn om det finns
        if (!empty($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        
        // Uppdatera beskrivning om den finns
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }
        
        // Uppdatera färg om den finns
        if (!empty($data['color'])) {
            // Validera färg
            if (!preg_match('/^#[0-9a-f]{6}$/i', $data['color'])) {
                return new WP_Error(
                    'invalid_color',
                    __('Färgvärdet måste vara en giltig hex-färg (t.ex. #FF0000).', 'wpschema-vue'),
                    array('status' => 400)
                );
            }
            $update_data['color'] = $data['color'];
        }
        
        // Uppdatera i databasen
        $result = $wpdb->update(
            $this->table,
            $update_data,
            array('id' => $id)
        );
        
        return $result !== false;
    }
    
    /**
     * Ta bort en resurs
     *
     * @param int $id Resurs-ID
     * @return bool True vid framgång, false vid fel
     */
    public function delete_resource($id) {
        global $wpdb;
        
        // Hämta befintlig resurs
        $resource = $this->get_resource($id);
        if (!$resource) {
            return false;
        }
        
        // Kontrollera om resursen har scheman
        $schedule_count = $this->get_schedule_count($id);
        if ($schedule_count > 0) {
            return false; // Kan inte ta bort en resurs med scheman
        }
        
        // Ta bort från databasen
        $result = $wpdb->delete(
            $this->table,
            array('id' => $id)
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
