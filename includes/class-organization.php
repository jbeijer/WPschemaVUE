<?php
/**
 * Organization-klass för WPschemaVUE
 *
 * Hanterar organisationer i hierarkisk struktur
 *
 * @package WPschemaVUE
 */

// Säkerhetskontroll - förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Organization-klass
 */
class WPschemaVUE_Organization {
    
    /**
     * Tabellnamn
     */
    private $table;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'schedule_organizations';
    }
    
    /**
     * Hämta alla organisationer
     *
     * @param array $args Argument för att filtrera resultatet
     * @return array Array med organisationer
     */
    public function get_organizations($args = array()) {
        global $wpdb;
        
        // Standardvärden för argument
        $defaults = array(
            'parent_id' => null,
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0,
        );
        
        // Slå samman argument med standardvärden
        $args = wp_parse_args($args, $defaults);
        
        // Bygg SQL-frågan
        $sql = "SELECT * FROM {$this->table}";
        
        // Lägg till WHERE-villkor om parent_id är angivet
        if ($args['parent_id'] !== null) {
            if ($args['parent_id'] === 0) {
                $sql .= " WHERE parent_id IS NULL";
            } else {
                $sql .= $wpdb->prepare(" WHERE parent_id = %d", $args['parent_id']);
            }
        }
        
        // Lägg till ORDER BY
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        // Lägg till LIMIT om limit är större än 0
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d, %d", $args['offset'], $args['limit']);
        }
        
        // Hämta resultat
        $organizations = $wpdb->get_results($sql, ARRAY_A);
        
        // Lägg till antal barn för varje organisation
        foreach ($organizations as &$organization) {
            $organization['children_count'] = $this->get_children_count($organization['id']);
        }
        
        return $organizations;
    }
    
    /**
     * Hämta en organisation
     *
     * @param int $id Organisations-ID
     * @return array|null Organisationen eller null om den inte finns
     */
    public function get_organization($id) {
        global $wpdb;
        
        // Hämta organisationen
        $organization = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        
        if (!$organization) {
            return null;
        }
        
        // Lägg till antal barn
        $organization['children_count'] = $this->get_children_count($id);
        
        return $organization;
    }
    
    /**
     * Skapa en organisation
     *
     * @param array $data Organisationsdata
     * @return int|false ID för den nya organisationen eller false vid fel
     */
    public function create_organization($data) {
        global $wpdb;
        
        // Validera data
        if (empty($data['name'])) {
            return false;
        }
        
        // Förbered data för insättning
        $insert_data = array(
            'name' => sanitize_text_field($data['name']),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );
        
        // Hantera parent_id
        if (!empty($data['parent_id'])) {
            $parent = $this->get_organization($data['parent_id']);
            if ($parent) {
                $insert_data['parent_id'] = $data['parent_id'];
            }
        }
        
        // Sätt in i databasen
        $result = $wpdb->insert($this->table, $insert_data);
        
        if (!$result) {
            return false;
        }
        
        $organization_id = $wpdb->insert_id;
        
        // Uppdatera path
        $this->update_path($organization_id);
        
        return $organization_id;
    }
    
    /**
     * Uppdatera en organisation
     *
     * @param int $id Organisations-ID
     * @param array $data Organisationsdata
     * @return bool True vid framgång, false vid fel
     */
    public function update_organization($id, $data) {
        global $wpdb;
        
        // Hämta befintlig organisation
        $organization = $this->get_organization($id);
        if (!$organization) {
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
        
        // Hantera parent_id om det finns
        $update_parent = false;
        if (isset($data['parent_id'])) {
            // Kontrollera att parent_id inte är samma som id (kan inte vara sin egen förälder)
            if ($data['parent_id'] == $id) {
                return false;
            }
            
            // Kontrollera att parent_id inte är ett barn till denna organisation (skulle skapa cirkulär referens)
            if ($data['parent_id'] > 0 && $this->is_descendant($data['parent_id'], $id)) {
                return false;
            }
            
            if ($data['parent_id'] == 0) {
                $update_data['parent_id'] = null;
            } else {
                $parent = $this->get_organization($data['parent_id']);
                if ($parent) {
                    $update_data['parent_id'] = $data['parent_id'];
                }
            }
            
            $update_parent = true;
        }
        
        // Uppdatera i databasen
        $result = $wpdb->update(
            $this->table,
            $update_data,
            array('id' => $id)
        );
        
        if ($result === false) {
            return false;
        }
        
        // Om parent_id har ändrats, uppdatera path för denna organisation och alla dess barn
        if ($update_parent) {
            $this->update_path($id);
            $this->update_children_paths($id);
        }
        
        return true;
    }
    
    /**
     * Ta bort en organisation
     *
     * @param int $id Organisations-ID
     * @return bool True vid framgång, false vid fel
     */
    public function delete_organization($id) {
        global $wpdb;
        
        // Hämta befintlig organisation
        $organization = $this->get_organization($id);
        if (!$organization) {
            return false;
        }
        
        // Kontrollera om organisationen har barn
        $children_count = $this->get_children_count($id);
        if ($children_count > 0) {
            return false; // Kan inte ta bort en organisation med barn
        }
        
        // Ta bort från databasen
        $result = $wpdb->delete(
            $this->table,
            array('id' => $id)
        );
        
        return $result !== false;
    }
    
    /**
     * Hämta antal barn för en organisation
     *
     * @param int $id Organisations-ID
     * @return int Antal barn
     */
    public function get_children_count($id) {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$this->table} WHERE parent_id = %d", $id)
        );
    }
    
    /**
     * Kontrollera om en organisation är en ättling till en annan
     *
     * @param int $id Organisations-ID
     * @param int $ancestor_id Förfader-ID
     * @return bool True om organisationen är en ättling, false annars
     */
    public function is_descendant($id, $ancestor_id) {
        global $wpdb;
        
        // Hämta organisationen
        $organization = $this->get_organization($id);
        if (!$organization || !$organization['parent_id']) {
            return false;
        }
        
        // Kontrollera om förälder är förfadern
        if ($organization['parent_id'] == $ancestor_id) {
            return true;
        }
        
        // Kontrollera rekursivt
        return $this->is_descendant($organization['parent_id'], $ancestor_id);
    }
    
    /**
     * Uppdatera path för en organisation
     *
     * @param int $id Organisations-ID
     * @return bool True vid framgång, false vid fel
     */
    private function update_path($id) {
        global $wpdb;
        
        // Hämta organisationen
        $organization = $wpdb->get_row(
            $wpdb->prepare("SELECT id, parent_id FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        
        if (!$organization) {
            return false;
        }
        
        // Om ingen förälder, path är bara ID
        if (!$organization['parent_id']) {
            $path = $id;
        } else {
            // Hämta förälderns path
            $parent_path = $wpdb->get_var(
                $wpdb->prepare("SELECT path FROM {$this->table} WHERE id = %d", $organization['parent_id'])
            );
            
            if (!$parent_path) {
                return false;
            }
            
            $path = $parent_path . '/' . $id;
        }
        
        // Uppdatera path
        $result = $wpdb->update(
            $this->table,
            array('path' => $path),
            array('id' => $id)
        );
        
        return $result !== false;
    }
    
    /**
     * Uppdatera path för alla barn till en organisation
     *
     * @param int $id Organisations-ID
     * @return bool True vid framgång, false vid fel
     */
    private function update_children_paths($id) {
        global $wpdb;
        
        // Hämta alla direkta barn
        $children = $wpdb->get_results(
            $wpdb->prepare("SELECT id FROM {$this->table} WHERE parent_id = %d", $id),
            ARRAY_A
        );
        
        if (!$children) {
            return true; // Inga barn att uppdatera
        }
        
        // Uppdatera path för varje barn
        foreach ($children as $child) {
            $this->update_path($child['id']);
            $this->update_children_paths($child['id']); // Rekursivt uppdatera barnens barn
        }
        
        return true;
    }
    
    /**
     * Hämta alla ättlingar till en organisation
     *
     * @param int $id Organisations-ID
     * @return array Array med ättlingar
     */
    public function get_descendants($id) {
        global $wpdb;
        
        // Hämta organisationen för att få path
        $organization = $this->get_organization($id);
        if (!$organization) {
            return array();
        }
        
        // Hämta alla organisationer med path som börjar med denna organisations path
        $descendants = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE path LIKE %s AND id != %d ORDER BY path",
                $organization['path'] . '/%', $id
            ),
            ARRAY_A
        );
        
        return $descendants ?: array();
    }
    
    /**
     * Hämta alla förfäder till en organisation
     *
     * @param int $id Organisations-ID
     * @return array Array med förfäder
     */
    public function get_ancestors($id) {
        global $wpdb;
        
        // Hämta organisationen för att få path
        $organization = $this->get_organization($id);
        if (!$organization || !$organization['parent_id']) {
            return array();
        }
        
        // Dela upp path för att få alla förfäder-ID
        $path_parts = explode('/', $organization['path']);
        array_pop($path_parts); // Ta bort sista delen (organisationens eget ID)
        
        if (empty($path_parts)) {
            return array();
        }
        
        // Konvertera till kommaseparerad lista för SQL IN-sats
        $ancestor_ids = implode(',', $path_parts);
        
        // Hämta alla förfäder
        $ancestors = $wpdb->get_results(
            "SELECT * FROM {$this->table} WHERE id IN ({$ancestor_ids}) ORDER BY path",
            ARRAY_A
        );
        
        return $ancestors ?: array();
    }
}
