<?php
/**
 * Plugin Name: Nexora Medical Booking Pro
 * Plugin URI: https://nexoramedical.com/pro
 * Description: Premium Multi-Clinic, Multi-Doctor Medical Appointment Booking & Hospital Management System for WordPress.
 * Version: 1.0.0
 * Author: Jasmin
 * Author URI: https://nexoramedical.com
 * Text Domain: nexora-medical-booking-pro
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

// Plugin constants
define('NMBP_VERSION', '1.0.0');
define('NMBP_DB_VERSION', '1.0.0');
define('NMBP_PLUGIN_FILE', __FILE__);
define('NMBP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NMBP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NMBP_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('NMBP_PREFIX', 'nmbp_');

/**
 * Autoload core classes
 */
spl_autoload_register(function ($class) {
    if (strpos($class, 'NMBP_') !== 0) {
        return;
    }

    $class_name = str_replace('NMBP_', '', $class);
    $class_file = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';

    $paths = [
        NMBP_PLUGIN_DIR . 'includes/' . $class_file,
        NMBP_PLUGIN_DIR . 'admin/' . $class_file,
        NMBP_PLUGIN_DIR . 'api/' . $class_file,
        NMBP_PLUGIN_DIR . 'api/controllers/' . $class_file,
        NMBP_PLUGIN_DIR . 'frontend/' . $class_file,
        NMBP_PLUGIN_DIR . 'integrations/woocommerce/' . $class_file,
        NMBP_PLUGIN_DIR . 'integrations/elementor/' . $class_file,
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Require manual files that may not match standard class naming
require_once NMBP_PLUGIN_DIR . 'includes/class-database.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-migrations.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-permissions.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-activator.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-audit-logger.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-clinic-manager.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-doctor-manager.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-patient-manager.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-schedule-manager.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-appointment-manager.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-payment-manager.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-notification-manager.php';
require_once NMBP_PLUGIN_DIR . 'includes/class-email-manager.php';

require_once NMBP_PLUGIN_DIR . 'admin/class-admin.php';
require_once NMBP_PLUGIN_DIR . 'admin/class-admin-menu.php';
require_once NMBP_PLUGIN_DIR . 'api/class-rest-api.php';
require_once NMBP_PLUGIN_DIR . 'frontend/class-frontend.php';
require_once NMBP_PLUGIN_DIR . 'frontend/class-shortcodes.php';
require_once NMBP_PLUGIN_DIR . 'integrations/woocommerce/class-woocommerce-gateway.php';
require_once NMBP_PLUGIN_DIR . 'integrations/elementor/class-elementor-integration.php';

/**
 * Activation Hook
 */
register_activation_hook(__FILE__, ['NMBP_Activator', 'activate']);

/**
 * Deactivation Hook
 */
register_deactivation_hook(__FILE__, ['NMBP_Deactivator', 'deactivate']);

/**
 * Main Plugin Orchestrator
 */
final class Nexora_Medical_Booking_Pro {

    private static ?Nexora_Medical_Booking_Pro $instance = null;

    public static function instance(): Nexora_Medical_Booking_Pro {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks(): void {
        add_action('plugins_loaded', [$this, 'on_plugins_loaded']);
        add_action('init', [$this, 'on_init']);
    }

    public function on_plugins_loaded(): void {
        // Load text domain
        load_plugin_textdomain(
            'nexora-medical-booking-pro',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );

        // Check if DB migration is needed
        NMBP_Migrations::check_and_run_migrations();

        // Initialize admin
        if (is_admin()) {
            NMBP_Admin::instance();
            NMBP_Admin_Menu::instance();
        }

        // Initialize REST API
        NMBP_REST_API::instance();

        // Initialize Frontend & Shortcodes
        NMBP_Frontend::instance();
        NMBP_Shortcodes::instance();

        // Initialize Integrations
        NMBP_WooCommerce_Gateway::instance();
        NMBP_Elementor_Integration::instance();
    }

    public function on_init(): void {
        // Register custom post types or rewrite rules if needed
    }
}

// Bootstrap plugin instance
function nmbp(): Nexora_Medical_Booking_Pro {
    return Nexora_Medical_Booking_Pro::instance();
}

nmbp();

