<?php
/**
 * Hanterar resurser
 */
class Resources {
    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'resources';
        
        $this->create_table();
    }

    /**
     * Skapar nödvändig tabell
     */
    private function create_table() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            organization_id bigint(20) unsigned NOT NULL,
            color varchar(7) DEFAULT '#000000',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY organization_id (organization_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Registrerar REST API endpoints
     */
    public function register_routes() {
        // Hämta alla resurser för en organisation
        register_rest_route('schedule/v1', '/organizations/(?P<organization_id>\d+)/resources', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_organization_resources'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'organization_id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    )
                )
            )
        ));

        // Hantera enskild resurs
        register_rest_route('schedule/v1', '/resources/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_resource'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    )
                )
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_resource'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    )
                )
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_resource'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    )
                )
            )
        ));
    }

    /**
     * Kontrollerar användarbehörighet
     */
    public function check_permission($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('not_authenticated', 'Du måste vara inloggad', array('status' => 401));
        }

        $user_id = get_current_user_id();
        
        // Om vi har organization_id i request, kontrollera behörighet för den organisationen
        if (isset($request['organization_id'])) {
            $organization_id = $request['organization_id'];
        } else {
            // Om vi har resource_id, hämta organisationen för den resursen
            $resource_id = $request['id'];
            $organization_id = $this->get_resource_organization($resource_id);
        }

        if (!$organization_id) {
            return new WP_Error('not_found', 'Organisationen eller resursen hittades inte', array('status' => 404));
        }

        // Kontrollera behörighet i organisationen
        if (!$this->has_organization_permission($organization_id)) {
            return new WP_Error('not_authorized', 'Du har inte behörighet att hantera denna resurs', array('status' => 403));
        }

        return true;
    }

    /**
     * Hämtar organisationen som en resurs tillhör
     */
    private function get_resource_organization($resource_id) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT organization_id FROM {$this->table_name} WHERE id = %d",
            $resource_id
        ));
    }

    /**
     * Kontrollerar om användaren har behörighet i en organisation
     */
    private function has_organization_permission($organization_id) {
        $user_id = get_current_user_id();
        
        // Kontrollera om användaren är admin eller schemaadmin i organisationen
        $role = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT role FROM {$this->wpdb->prefix}user_organizations 
            WHERE user_id = %d AND organization_id = %d",
            $user_id,
            $organization_id
        ));

        return in_array($role, array('admin', 'scheduler'));
    }

    /**
     * Hämtar alla resurser för en organisation
     */
    public function get_organization_resources($request) {
        $organization_id = $request['organization_id'];

        $resources = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE organization_id = %d ORDER BY name ASC",
            $organization_id
        ));

        return array(
            'success' => true,
            'data' => $resources
        );
    }

    /**
     * Hämtar en specifik resurs
     */
    public function get_resource($request) {
        $resource_id = $request['id'];

        $resource = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $resource_id
        ));

        if (!$resource) {
            return array(
                'success' => false,
                'error' => array(
                    'code' => 'not_found',
                    'message' => 'Resursen hittades inte',
                    'details' => array()
                )
            );
        }

        return array(
            'success' => true,
            'data' => $resource
        );
    }

    /**
     * Uppdaterar en resurs
     */
    public function update_resource($request) {
        $resource_id = $request['id'];
        $data = $request->get_json_params();

        // Validera data
        if (!$this->validate_resource_data($data)) {
            return array(
                'success' => false,
                'error' => array(
                    'code' => 'validation_error',
                    'message' => 'Ogiltig data',
                    'details' => array()
                )
            );
        }

        // Hämta organisationen för resursen
        $organization_id = $this->get_resource_organization($resource_id);

        // Kontrollera att resursnamnet är unikt inom organisationen (exklusive den aktuella resursen)
        if ($this->resource_name_exists($data['name'], $organization_id, $resource_id)) {
            return array(
                'success' => false,
                'error' => array(
                    'code' => 'validation_error',
                    'message' => 'En resurs med detta namn finns redan i organisationen',
                    'details' => array()
                )
            );
        }

        $result = $this->wpdb->update(
            $this->table_name,
            array(
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'color' => '#3788d8' // Använd alltid standardfärg
            ),
            array('id' => $resource_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            return array(
                'success' => false,
                'error' => array(
                    'code' => 'server_error',
                    'message' => 'Ett fel uppstod vid uppdatering av resursen',
                    'details' => array()
                )
            );
        }

        return $this->get_resource($request);
    }

    /**
     * Tar bort en resurs
     */
    public function delete_resource($request) {
        $resource_id = $request['id'];

        // Kontrollera om resursen har några scheman
        $has_schedules = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}schedules WHERE resource_id = %d",
            $resource_id
        ));

        if ($has_schedules > 0) {
            return array(
                'success' => false,
                'error' => array(
                    'code' => 'resource_in_use',
                    'message' => 'Resursen kan inte tas bort eftersom den har scheman',
                    'details' => array()
                )
            );
        }

        $result = $this->wpdb->delete(
            $this->table_name,
            array('id' => $resource_id),
            array('%d')
        );

        if ($result === false) {
            return array(
                'success' => false,
                'error' => array(
                    'code' => 'server_error',
                    'message' => 'Ett fel uppstod vid borttagning av resursen',
                    'details' => array()
                )
            );
        }

        return array(
            'success' => true,
            'data' => array(
                'message' => 'Resursen har tagits bort'
            )
        );
    }

    /**
     * Validerar resursdata
     */
    private function validate_resource_data($data) {
        if (!isset($data['name']) || empty($data['name'])) {
            return false;
        }

        return true;
    }

    /**
     * Kontrollerar om ett resursnamn redan finns i en organisation
     */
    private function resource_name_exists($name, $organization_id, $exclude_id = null) {
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE name = %s AND organization_id = %d",
            $name,
            $organization_id
        );

        if ($exclude_id) {
            $query .= $this->wpdb->prepare(" AND id != %d", $exclude_id);
        }

        return (bool)$this->wpdb->get_var($query);
    }
} 