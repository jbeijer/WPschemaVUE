<?php
/**
 * UserOrganization-klass för WPschemaVUE
 *
 * Hanterar kopplingar mellan användare och organisationer
 *
 * @package WPschemaVUE
 */

// Säkerhetskontroll - förhindra direkt åtkomst
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists('wp_parse_args') ) {
    function wp_parse_args($args, $defaults) {
        return is_array($args) ? array_merge($defaults, $args) : $defaults;
    }
}

if ( ! function_exists('current_time') ) {
    function current_time($type) {
        return date('Y-m-d H:i:s');
    }
}

if ( ! function_exists('get_user_by') ) {
    function get_user_by($field, $value) {
        return null;
    }
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', true);
}

/**
 * UserOrganization-klass
 */
class WPschemaVUE_UserOrganization {

    /**
     * Tabellnamn
     */
    private $table;

    /**
     * Konstruktor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'schedule_user_organizations';
    }

    /**
     * Hämta alla användare i en organisation
     *
     * @param int $organization_id Organisations-ID
     * @param array $args Argument för att filtrera resultatet
     * @return array Array med användarorganisationer
     */
    public function get_organization_users($organization_id, $args = array()) {
        global $wpdb;

        // Standardvärden för argument
        $defaults = array(
            'orderby' => 'id',
            'order'   => 'ASC',
            'limit'   => -1,
            'offset'  => 0,
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
        $user_organizations = $wpdb->get_results($query, ARRAY_A);

        // Om inga resultat, returnera tom array
        if (!$user_organizations) {
            return array();
        }

        // Lägg till användardata för varje användarorganisation
        foreach ($user_organizations as &$user_organization) {
            $user_organization['user_data'] = $this->get_user_data($user_organization['user_id']);
        }

        return $user_organizations;
    }

    /**
     * Hämta alla organisationer för en användare
     *
     * @param int $user_id Användar-ID
     * @param array $args Argument för att filtrera resultatet
     * @return array Array med användarorganisationer
     */
    public function get_user_organizations($user_id, $args = array()) {
        global $wpdb;

        // Standardvärden för argument
        $defaults = array(
            'orderby' => 'id',
            'order'   => 'ASC',
            'limit'   => -1,
            'offset'  => 0,
        );

        // Slå samman argument med standardvärden
        $args = wp_parse_args($args, $defaults);

        // Bygg SQL-frågan
        $sql = "SELECT * FROM {$this->table} WHERE user_id = %d";

        // Lägg till ORDER BY
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";

        // Lägg till LIMIT om limit är större än 0
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d, %d", $args['offset'], $args['limit']);
        }

        // Förbered och kör frågan
        $query = $wpdb->prepare($sql, $user_id);
        $user_organizations = $wpdb->get_results($query, ARRAY_A);

        // Om inga resultat, returnera tom array
        if (!$user_organizations) {
            return array();
        }

        // Lägg till organisationsdata för varje användarorganisation
        foreach ($user_organizations as &$user_organization) {
            $user_organization['organization_data'] = $this->get_organization_data($user_organization['organization_id']);
        }

        return $user_organizations;
    }

    /**
     * Hämta en specifik användarorganisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @return array|null Användarorganisationen eller null om den inte finns
     */
    public function get_user_organization($user_id, $organization_id) {
        global $wpdb;

        // Hämta användarorganisationen
        $user_organization = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE user_id = %d AND organization_id = %d",
                $user_id,
                $organization_id
            ),
            ARRAY_A
        );

        if (!$user_organization) {
            return null;
        }

        // Lägg till användardata
        $user_organization['user_data'] = $this->get_user_data($user_id);

        // Lägg till organisationsdata
        $user_organization['organization_data'] = $this->get_organization_data($organization_id);

        return $user_organization;
    }

    /**
     * Lägg till en användare i en organisation
     *
     * @param array $data Användarorganisationsdata
     * @return int|false ID för den nya användarorganisationen eller false vid fel
     */
    public function add_user_to_organization($data) {
        global $wpdb;

        // Validera data
        if (empty($data['user_id']) || empty($data['organization_id']) || empty($data['role'])) {
            return false;
        }

        // Kontrollera att användaren finns
        $user = get_user_by('id', $data['user_id']);
        if (!$user) {
            return false;
        }

        // Kontrollera att organisationen finns
        $organization = new WPschemaVUE_Organization();
        $org_data = $organization->get_organization($data['organization_id']);
        if (!$org_data) {
            return false;
        }

        // Kontrollera att rollen är giltig
        $valid_roles = array('base', 'scheduler', 'admin');
        if (!in_array($data['role'], $valid_roles)) {
            return false;
        }

        // Kontrollera om användaren redan finns i organisationen
        $existing = $this->get_user_organization($data['user_id'], $data['organization_id']);
        if ($existing) {
            return false; // Användaren finns redan i organisationen
        }

        // Förbered data för insättning
        $insert_data = array(
            'user_id'          => $data['user_id'],
            'organization_id'  => $data['organization_id'],
            'role'             => $data['role'],
            'created_at'       => current_time('mysql'),
            'updated_at'       => current_time('mysql'),
        );

        // Sätt in i databasen
        $result = $wpdb->insert($this->table, $insert_data);

        if (!$result) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Uppdatera en användarorganisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @param array $data Användarorganisationsdata
     * @return bool True vid framgång, false vid fel
     */
    public function update_user_organization($user_id, $organization_id, $data) {
        global $wpdb;

        // Hämta befintlig användarorganisation
        $user_organization = $this->get_user_organization($user_id, $organization_id);
        if (!$user_organization) {
            return false;
        }

        // Validera data
        if (empty($data['role'])) {
            return false;
        }

        // Kontrollera att rollen är giltig
        $valid_roles = array('base', 'scheduler', 'admin');
        if (!in_array($data['role'], $valid_roles)) {
            return false;
        }

        // Förbered data för uppdatering
        $update_data = array(
            'role'       => $data['role'],
            'updated_at' => current_time('mysql'),
        );

        // Uppdatera i databasen
        $result = $wpdb->update(
            $this->table,
            $update_data,
            array(
                'user_id'         => $user_id,
                'organization_id' => $organization_id,
            )
        );

        return $result !== false;
    }

    /**
     * Ta bort en användare från en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @return bool True vid framgång, false vid fel
     */
    public function remove_user_from_organization($user_id, $organization_id) {
        global $wpdb;

        // Hämta befintlig användarorganisation
        $user_organization = $this->get_user_organization($user_id, $organization_id);
        if (!$user_organization) {
            return false;
        }

        // Ta bort från databasen
        $result = $wpdb->delete(
            $this->table,
            array(
                'user_id'         => $user_id,
                'organization_id' => $organization_id,
            )
        );

        return $result !== false;
    }

    /**
     * Kontrollera om en användare har en specifik roll i en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @param string $role Roll att kontrollera
     * @return bool True om användaren har rollen, false annars
     */
    public function user_has_role($user_id, $organization_id, $role) {
        // Hämta användarorganisationen
        $user_organization = $this->get_user_organization($user_id, $organization_id);
        if (!$user_organization) {
            return false;
        }

        return $user_organization['role'] === $role;
    }

    /**
     * Kontrollera om en användare har minst en specifik roll i en organisation
     *
     * @param int $user_id Användar-ID
     * @param int $organization_id Organisations-ID
     * @param string $min_role Minsta roll att kontrollera (base, scheduler, admin)
     * @return bool True om användaren har minst den angivna rollen, false annars
     */
    public function user_has_min_role($user_id, $organization_id, $min_role) {
        // Hämta användarorganisationen
        $user_organization = $this->get_user_organization($user_id, $organization_id);
        if (!$user_organization) {
            return false;
        }

        // Definiera rollhierarkin
        $roles = array(
            'base'      => 1,
            'scheduler' => 2,
            'admin'     => 3,
        );

        // Kontrollera om användarens roll är minst den angivna rollen
        return $roles[$user_organization['role']] >= $roles[$min_role];
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
            'user_email'   => $user->user_email,
        );
    }

    /**
     * Hämta organisationsdata
     *
     * @param int $organization_id Organisations-ID
     * @return array Organisationsdata
     */
    private function get_organization_data($organization_id) {
        $organization = new WPschemaVUE_Organization();
        $org_data = $organization->get_organization($organization_id);

        if (!$org_data) {
            return array();
        }

        return array(
            'name' => $org_data['name'],
            'path' => $org_data['path'],
        );
    }

}
