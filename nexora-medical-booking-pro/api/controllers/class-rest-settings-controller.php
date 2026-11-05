<?php
/**
 * REST Settings & System Status Controller
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_REST_Settings_Controller extends NMBP_REST_Base_Controller {

    public function register_routes(): void {
        register_rest_route($this->namespace, '/settings', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_settings'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'update_settings'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/system/status', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_system_status'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route($this->namespace, '/audit-logs', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_audit_logs'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    public function get_settings($request) {
        return $this->success([
            'general'      => get_option('nmbp_general_settings', []),
            'appointment'  => get_option('nmbp_appointment_settings', []),
            'payment'      => get_option('nmbp_payment_settings', []),
            'design'       => get_option('nmbp_design_settings', []),
            'privacy'      => get_option('nmbp_privacy_settings', [
                'delete_data_on_uninstall' => get_option('nmbp_delete_data_on_uninstall', 'no') === 'yes',
                'retention_months'         => 36,
            ]),
        ]);
    }

    public function update_settings($request) {
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;

        if (isset($body['general']) && is_array($body['general'])) {
            update_option('nmbp_general_settings', $body['general']);
        }
        if (isset($body['appointment']) && is_array($body['appointment'])) {
            update_option('nmbp_appointment_settings', $body['appointment']);
        }
        if (isset($body['payment']) && is_array($body['payment'])) {
            update_option('nmbp_payment_settings', $body['payment']);
        }
        if (isset($body['design']) && is_array($body['design'])) {
            update_option('nmbp_design_settings', $body['design']);
        }
        if (isset($body['privacy']) && is_array($body['privacy'])) {
            update_option('nmbp_privacy_settings', $body['privacy']);
            if (isset($body['privacy']['delete_data_on_uninstall'])) {
                update_option('nmbp_delete_data_on_uninstall', $body['privacy']['delete_data_on_uninstall'] ? 'yes' : 'no');
            }
        }

        return $this->success(null, __('Settings saved successfully.', 'nexora-medical-booking-pro'));
    }

    public function get_system_status($request) {
        global $wpdb;

        return $this->success([
            'wordpress_version' => get_bloginfo('version'),
            'php_version'       => PHP_VERSION,
            'mysql_version'     => $wpdb->db_version(),
            'plugin_version'    => NMBP_VERSION,
            'db_version'        => get_option('nmbp_db_version', '0.0.0'),
            'woocommerce_active'=> class_exists('WooCommerce'),
            'elementor_active'  => did_action('elementor/loaded'),
            'timezone'          => wp_timezone_string(),
            'max_execution_time'=> ini_get('max_execution_time'),
            'memory_limit'      => ini_get('memory_limit'),
            'upload_max_filesize'=> ini_get('upload_max_filesize'),
        ]);
    }

    public function get_audit_logs($request) {
        global $wpdb;
        $table = NMBP_Database::table('audit_logs');
        $limit = min(100, max(1, absint(is_object($request) && method_exists($request, 'get_param') ? ($request->get_param('limit') ?? 50) : ($request['limit'] ?? 50))));

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d",
            $limit
        ), ARRAY_A);

        return $this->success($logs);
    }
}

