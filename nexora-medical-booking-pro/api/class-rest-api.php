<?php
/**
 * REST API Initialization & Dispatcher
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_REST_API {

    public const NAMESPACE = 'nmbp/v1';

    private static ?NMBP_REST_API $instance = null;

    public static function instance(): NMBP_REST_API {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        require_once NMBP_PLUGIN_DIR . 'api/controllers/class-rest-base-controller.php';
        require_once NMBP_PLUGIN_DIR . 'api/controllers/class-rest-dashboard-controller.php';
        require_once NMBP_PLUGIN_DIR . 'api/controllers/class-rest-clinics-controller.php';
        require_once NMBP_PLUGIN_DIR . 'api/controllers/class-rest-doctors-controller.php';
        require_once NMBP_PLUGIN_DIR . 'api/controllers/class-rest-departments-controller.php';
        require_once NMBP_PLUGIN_DIR . 'api/controllers/class-rest-schedules-controller.php';
        require_once NMBP_PLUGIN_DIR . 'api/controllers/class-rest-appointments-controller.php';
        require_once NMBP_PLUGIN_DIR . 'api/controllers/class-rest-patients-controller.php';
        require_once NMBP_PLUGIN_DIR . 'api/controllers/class-rest-payments-controller.php';
        require_once NMBP_PLUGIN_DIR . 'api/controllers/class-rest-settings-controller.php';

        (new NMBP_REST_Dashboard_Controller())->register_routes();
        (new NMBP_REST_Clinics_Controller())->register_routes();
        (new NMBP_REST_Doctors_Controller())->register_routes();
        (new NMBP_REST_Departments_Controller())->register_routes();
        (new NMBP_REST_Schedules_Controller())->register_routes();
        (new NMBP_REST_Appointments_Controller())->register_routes();
        (new NMBP_REST_Patients_Controller())->register_routes();
        (new NMBP_REST_Payments_Controller())->register_routes();
        (new NMBP_REST_Settings_Controller())->register_routes();
    }
}

