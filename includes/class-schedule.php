<?php
/**
 * Schedule-klass för WPschemaVUE
 *
 * Hanterar schemaläggning (arbetspass)
 *
 * @package WPschemaVUE
 */

// Säkerhetskontroll - förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schedule-klass
 */
class WPschemaVUE_Schedule {
    
    /**
     * Tabellnamn
     */
    private $table;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'schedule_entries';
    }
    
    /**
     * Hämta scheman för en resurs
     *
     * @param int $resource_id Resurs-ID
     * @param array $args Argument för att filtrera resultatet
     * @return array Array med scheman
     */
    public function get_resource_schedules($resource_id, $args = array()) {
        global $wpdb;
        
        // Standardvärden för argument
        $defaults = array(
            'start_date' => null,
            'end_date' => null,
            'orderby' => 'start_time',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0,
        );
        
        // Slå samman argument med standardvärden
        $args = wp_parse_args($args, $defaults);
        
        // Bygg SQL-frågan
        $sql = "SELECT * FROM {$this->table} WHERE resource_id = %d";
        
        // Lägg till datumfiltrering om det finns
        $params = array($resource_id);
        
        if ($args['start_date']) {
            $sql .= " AND start_time >= %s";
            $params[] = $args['start_date'];
        }
        
        if ($args['end_date']) {
            $sql .= " AND end_time <= %s";
            $params[] = $args['end_date'];
        }
        
        // Lägg till ORDER BY
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        // Lägg till LIMIT om limit är större än 0
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d, %d", $args['offset'], $args['limit']);
        }
        
        // Förbered och kör frågan
        $query = $wpdb->prepare($sql, $params);
        $schedules = $wpdb->get_results($query, ARRAY_A);
        
        // Om inga resultat, returnera tom array
        if (!$schedules) {
            return array();
        }
        
        // Lägg till användardata för varje schema
        foreach ($schedules as &$schedule) {
            $schedule['user_data'] = $this->get_user_data($schedule['user_id']);
        }
        
        return $schedules;
    }
    
    /**
     * Hämta scheman för en användare
     *
     * @param int $user_id Användar-ID
     * @param array $args Argument för att filtrera resultatet
     * @return array Array med scheman
     */
    public function get_user_schedules($user_id, $args = array()) {
        global $wpdb;
        
        // Standardvärden för argument
        $defaults = array(
            'start_date' => null,
            'end_date' => null,
            'orderby' => 'start_time',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0,
        );
        
        // Slå samman argument med standardvärden
        $args = wp_parse_args($args, $defaults);
        
        // Bygg SQL-frågan
        $sql = "SELECT * FROM {$this->table} WHERE user_id = %d";
        
        // Lägg till datumfiltrering om det finns
        $params = array($user_id);
        
        if ($args['start_date']) {
            $sql .= " AND start_time >= %s";
            $params[] = $args['start_date'];
        }
        
        if ($args['end_date']) {
            $sql .= " AND end_time <= %s";
            $params[] = $args['end_date'];
        }
        
        // Lägg till ORDER BY
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        // Lägg till LIMIT om limit är större än 0
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d, %d", $args['offset'], $args['limit']);
        }
        
        // Förbered och kör frågan
        $query = $wpdb->prepare($sql, $params);
        $schedules = $wpdb->get_results($query, ARRAY_A);
        
        // Om inga resultat, returnera tom array
        if (!$schedules) {
            return array();
        }
        
        // Lägg till resursdata för varje schema
        foreach ($schedules as &$schedule) {
            $schedule['resource_data'] = $this->get_resource_data($schedule['resource_id']);
        }
        
        return $schedules;
    }
    
    /**
     * Hämta ett schema
     *
     * @param int $id Schema-ID
     * @return array|null Schemat eller null om det inte finns
     */
    public function get_schedule($id) {
        global $wpdb;
        
        // Hämta schemat
        $schedule = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        
        if (!$schedule) {
            return null;
        }
        
        // Lägg till användardata
        $schedule['user_data'] = $this->get_user_data($schedule['user_id']);
        
        // Lägg till resursdata
        $schedule['resource_data'] = $this->get_resource_data($schedule['resource_id']);
        
        return $schedule;
    }
    
    /**
     * Skapa ett schema
     *
     * @param array $data Schemadata
     * @return int|false ID för det nya schemat eller false vid fel
     */
    public function create_schedule($data) {
        global $wpdb;
        
        // Validera data
        if (empty($data['user_id']) || empty($data['resource_id']) || 
            empty($data['start_time']) || empty($data['end_time'])) {
            return false;
        }
        
        // Kontrollera att användaren finns
        $user = get_user_by('id', $data['user_id']);
        if (!$user) {
            return false;
        }
        
        // Kontrollera att resursen finns
        $resource = new WPschemaVUE_Resource();
        $resource_data = $resource->get_resource($data['resource_id']);
        if (!$resource_data) {
            return false;
        }
        
        // Validera start- och sluttid
        $start_time = strtotime($data['start_time']);
        $end_time = strtotime($data['end_time']);
        
        if (!$start_time || !$end_time || $start_time >= $end_time) {
            return false;
        }
        
        // Kontrollera överlappande scheman
        if ($this->has_overlapping_schedules($data['user_id'], $data['start_time'], $data['end_time'])) {
            return false;
        }
        
        // Förbered data för insättning
        $insert_data = array(
            'user_id' => $data['user_id'],
            'resource_id' => $data['resource_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );
        
        // Lägg till anteckningar om de finns
        if (!empty($data['notes'])) {
            $insert_data['notes'] = sanitize_textarea_field($data['notes']);
        }
        
        // Lägg till status om den finns
        if (!empty($data['status'])) {
            $valid_statuses = array('scheduled', 'confirmed', 'completed');
            if (in_array($data['status'], $valid_statuses)) {
                $insert_data['status'] = $data['status'];
            }
        } else {
            $insert_data['status'] = 'scheduled';
        }
        
        // Sätt in i databasen
        $result = $wpdb->insert($this->table, $insert_data);
        
        if (!$result) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Uppdatera ett schema
     *
     * @param int $id Schema-ID
     * @param array $data Schemadata
     * @return bool True vid framgång, false vid fel
     */
    public function update_schedule($id, $data) {
        global $wpdb;
        
        // Hämta befintligt schema
        $schedule = $this->get_schedule($id);
        if (!$schedule) {
            return false;
        }
        
        // Förbered data för uppdatering
        $update_data = array(
            'updated_at' => current_time('mysql'),
        );
        
        // Uppdatera start- och sluttid om de finns
        $check_overlap = false;
        $start_time = $schedule['start_time'];
        $end_time = $schedule['end_time'];
        
        if (!empty($data['start_time'])) {
            $new_start_time = strtotime($data['start_time']);
            if ($new_start_time) {
                $update_data['start_time'] = $data['start_time'];
                $start_time = $data['start_time'];
                $check_overlap = true;
            }
        }
        
        if (!empty($data['end_time'])) {
            $new_end_time = strtotime($data['end_time']);
            if ($new_end_time) {
                $update_data['end_time'] = $data['end_time'];
                $end_time = $data['end_time'];
                $check_overlap = true;
            }
        }
        
        // Validera start- och sluttid
        $start_time_ts = strtotime($start_time);
        $end_time_ts = strtotime($end_time);
        
        if ($start_time_ts >= $end_time_ts) {
            return false;
        }
        
        // Kontrollera överlappande scheman om tiderna har ändrats
        if ($check_overlap && $this->has_overlapping_schedules($schedule['user_id'], $start_time, $end_time, $id)) {
            return false;
        }
        
        // Uppdatera anteckningar om de finns
        if (isset($data['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($data['notes']);
        }
        
        // Uppdatera status om den finns
        if (!empty($data['status'])) {
            $valid_statuses = array('scheduled', 'confirmed', 'completed');
            if (in_array($data['status'], $valid_statuses)) {
                $update_data['status'] = $data['status'];
            }
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
     * Ta bort ett schema
     *
     * @param int $id Schema-ID
     * @return bool True vid framgång, false vid fel
     */
    public function delete_schedule($id) {
        global $wpdb;
        
        // Hämta befintligt schema
        $schedule = $this->get_schedule($id);
        if (!$schedule) {
            return false;
        }
        
        // Ta bort från databasen
        $result = $wpdb->delete(
            $this->table,
            array('id' => $id)
        );
        
        return $result !== false;
    }
    
    /**
     * Kontrollera om det finns överlappande scheman för en användare
     *
     * @param int $user_id Användar-ID
     * @param string $start_time Starttid
     * @param string $end_time Sluttid
     * @param int $exclude_id Schema-ID att exkludera (för uppdateringar)
     * @return bool True om det finns överlappande scheman, false annars
     */
    public function has_overlapping_schedules($user_id, $start_time, $end_time, $exclude_id = 0) {
        global $wpdb;
        
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE user_id = %d 
                AND (
                    (start_time <= %s AND end_time > %s) OR
                    (start_time < %s AND end_time >= %s) OR
                    (start_time >= %s AND end_time <= %s)
                )";
        
        $params = array(
            $user_id,
            $end_time,
            $start_time,
            $end_time,
            $start_time,
            $start_time,
            $end_time
        );
        
        // Exkludera det angivna schemat om det finns
        if ($exclude_id > 0) {
            $sql .= " AND id != %d";
            $params[] = $exclude_id;
        }
        
        $count = (int) $wpdb->get_var($wpdb->prepare($sql, $params));
        
        return $count > 0;
    }
    
    /**
     * Hämta användardata
     *
     * @param int $user_id Användar-ID
     * @return array Användardata
     */
    private function get_user_data($user_id) {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return array();
        }
        
        return array(
            'display_name' => $user->display_name,
        );
    }
    
    /**
     * Hämta resursdata
     *
     * @param int $resource_id Resurs-ID
     * @return array Resursdata
     */
    private function get_resource_data($resource_id) {
        $resource = new WPschemaVUE_Resource();
        $resource_data = $resource->get_resource($resource_id);
        
        if (!$resource_data) {
            return array();
        }
        
        return array(
            'name' => $resource_data['name'],
            'color' => $resource_data['color'],
            'organization_id' => $resource_data['organization_id'],
        );
    }
}
