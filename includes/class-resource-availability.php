<?php
/**
 * Hanterar resurstillgänglighet
 */
class Resource_Availability {
    private $wpdb;
    private $table_name;
    private $special_dates_table;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'resource_availability';
        $this->special_dates_table = $wpdb->prefix . 'resource_special_dates';
        
        $this->create_tables();
    }

    /**
     * Skapar nödvändiga tabeller
     */
    private function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();

        // Tabell för veckoschema
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            resource_id bigint(20) unsigned NOT NULL,
            weekday tinyint(1) NOT NULL,
            start_time time,
            end_time time,
            is_24_7 tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY resource_id (resource_id),
            KEY weekday (weekday)
        ) $charset_collate;";

        // Tabell för specialdagar
        $sql2 = "CREATE TABLE IF NOT EXISTS {$this->special_dates_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            resource_id bigint(20) unsigned NOT NULL,
            date date NOT NULL,
            start_time time,
            end_time time,
            is_closed tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY resource_id (resource_id),
            KEY date (date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
    }

    /**
     * Registrerar REST API endpoints
     */
    public function register_routes() {
        register_rest_route('schedule/v1', '/resources/(?P<resource_id>\d+)/availability', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_availability'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'resource_id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    )
                )
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'save_availability'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'resource_id' => array(
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

        $resource_id = $request['resource_id'];
        
        // Hämta organisationen som resursen tillhör
        $organization_id = $this->get_resource_organization($resource_id);
        if (!$organization_id) {
            return new WP_Error('not_found', 'Resursen hittades inte', array('status' => 404));
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
            "SELECT organization_id FROM {$this->wpdb->prefix}resources WHERE id = %d",
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
     * Hämtar tillgänglighet för en resurs
     */
    public function get_availability($request) {
        $resource_id = $request['resource_id'];

        // Hämta veckoschema
        $weekly_schedule = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE resource_id = %d",
            $resource_id
        ));

        // Hämta specialdagar
        $special_dates = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->special_dates_table} WHERE resource_id = %d",
            $resource_id
        ));

        // Formatera veckoschema
        $schedule = array_fill(0, 7, array(
            'enabled' => false,
            'startTime' => '09:00',
            'endTime' => '17:00'
        ));

        foreach ($weekly_schedule as $day) {
            $schedule[$day->weekday] = array(
                'enabled' => true,
                'startTime' => $day->start_time,
                'endTime' => $day->end_time
            );
        }

        // Formatera specialdagar
        $formatted_special_dates = array_map(function($date) {
            return array(
                'date' => $date->date,
                'startTime' => $date->start_time,
                'endTime' => $date->end_time,
                'isClosed' => (bool)$date->is_closed
            );
        }, $special_dates);

        return array(
            'success' => true,
            'data' => array(
                'is24_7' => $this->is_24_7($resource_id),
                'weeklySchedule' => $schedule,
                'specialDates' => $formatted_special_dates
            )
        );
    }

    /**
     * Sparar tillgänglighet för en resurs
     */
    public function save_availability($request) {
        $resource_id = $request['resource_id'];
        $data = $request->get_json_params();

        // Validera data
        if (!$this->validate_availability_data($data)) {
            return array(
                'success' => false,
                'error' => array(
                    'code' => 'validation_error',
                    'message' => 'Ogiltig data',
                    'details' => array()
                )
            );
        }

        // Börja transaktion
        $this->wpdb->query('START TRANSACTION');

        try {
            // Ta bort existerande veckoschema
            $this->wpdb->delete($this->table_name, array('resource_id' => $resource_id));

            // Ta bort existerande specialdagar
            $this->wpdb->delete($this->special_dates_table, array('resource_id' => $resource_id));

            // Spara 24/7-status
            if ($data['is24_7']) {
                $this->save_24_7_availability($resource_id);
            } else {
                // Spara veckoschema
                foreach ($data['weeklySchedule'] as $weekday => $schedule) {
                    if ($schedule['enabled']) {
                        $this->wpdb->insert(
                            $this->table_name,
                            array(
                                'resource_id' => $resource_id,
                                'weekday' => $weekday,
                                'start_time' => $schedule['startTime'],
                                'end_time' => $schedule['endTime']
                            ),
                            array('%d', '%d', '%s', '%s')
                        );
                    }
                }
            }

            // Spara specialdagar
            foreach ($data['specialDates'] as $date) {
                $this->wpdb->insert(
                    $this->special_dates_table,
                    array(
                        'resource_id' => $resource_id,
                        'date' => $date['date'],
                        'start_time' => $date['isClosed'] ? null : $date['startTime'],
                        'end_time' => $date['isClosed'] ? null : $date['endTime'],
                        'is_closed' => $date['isClosed'] ? 1 : 0
                    ),
                    array('%d', '%s', '%s', '%s', '%d')
                );
            }

            $this->wpdb->query('COMMIT');
            return $this->get_availability($request);

        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return array(
                'success' => false,
                'error' => array(
                    'code' => 'server_error',
                    'message' => 'Ett fel uppstod vid sparande av tillgänglighet',
                    'details' => array()
                )
            );
        }
    }

    /**
     * Sparar 24/7-tillgänglighet
     */
    private function save_24_7_availability($resource_id) {
        for ($i = 0; $i < 7; $i++) {
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'resource_id' => $resource_id,
                    'weekday' => $i,
                    'is_24_7' => 1
                ),
                array('%d', '%d', '%d')
            );
        }
    }

    /**
     * Kontrollerar om en resurs är 24/7-tillgänglig
     */
    private function is_24_7($resource_id) {
        return (bool)$this->wpdb->get_var($this->wpdb->prepare(
            "SELECT is_24_7 FROM {$this->table_name} WHERE resource_id = %d LIMIT 1",
            $resource_id
        ));
    }

    /**
     * Validerar tillgänglighetsdata
     */
    private function validate_availability_data($data) {
        if (!isset($data['is24_7']) || !isset($data['weeklySchedule']) || !isset($data['specialDates'])) {
            return false;
        }

        if (!is_array($data['weeklySchedule']) || count($data['weeklySchedule']) !== 7) {
            return false;
        }

        foreach ($data['weeklySchedule'] as $schedule) {
            if (!isset($schedule['enabled']) || !isset($schedule['startTime']) || !isset($schedule['endTime'])) {
                return false;
            }
        }

        foreach ($data['specialDates'] as $date) {
            if (!isset($date['date']) || !isset($date['isClosed'])) {
                return false;
            }
            if (!$date['isClosed'] && (!isset($date['startTime']) || !isset($date['endTime']))) {
                return false;
            }
        }

        return true;
    }
} 