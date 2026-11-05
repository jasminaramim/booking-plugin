<?php
/**
 * REST Departments Controller
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_REST_Departments_Controller extends NMBP_REST_Base_Controller {

    public function register_routes(): void {
        register_rest_route($this->namespace, '/departments', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create_item'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/departments/(?P<id>\d+)', [
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_item'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'delete_item'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);
    }

    public function get_items($request) {
        global $wpdb;
        $table = NMBP_Database::table('departments');
        $rows = $wpdb->get_results("SELECT * FROM {$table} WHERE status = 'active' ORDER BY name ASC", ARRAY_A);
        return $this->success($rows);
    }

    public function create_item($request) {
        global $wpdb;
        $table = NMBP_Database::table('departments');
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;

        if (empty($body['name'])) {
            return $this->error(__('Department name is required.', 'nexora-medical-booking-pro'), 400);
        }

        $slug = !empty($body['slug']) ? sanitize_title($body['slug']) : sanitize_title($body['name']);
        $wpdb->insert($table, [
            'name'        => sanitize_text_field($body['name']),
            'slug'        => $slug,
            'description' => sanitize_textarea_field($body['description'] ?? ''),
            'icon'        => sanitize_text_field($body['icon'] ?? 'medical'),
            'image_url'   => esc_url_raw($body['image_url'] ?? ''),
            'status'      => sanitize_text_field($body['status'] ?? 'active'),
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
        ]);

        $id = $wpdb->insert_id;
        $dept = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        return $this->success($dept, __('Department created successfully.', 'nexora-medical-booking-pro'), 201);
    }

    public function update_item($request) {
        global $wpdb;
        $table = NMBP_Database::table('departments');
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;

        $update = ['updated_at' => current_time('mysql')];
        if (isset($body['name'])) $update['name'] = sanitize_text_field($body['name']);
        if (isset($body['description'])) $update['description'] = sanitize_textarea_field($body['description']);
        if (isset($body['icon'])) $update['icon'] = sanitize_text_field($body['icon']);
        if (isset($body['image_url'])) $update['image_url'] = esc_url_raw($body['image_url']);
        if (isset($body['status'])) $update['status'] = sanitize_text_field($body['status']);

        $wpdb->update($table, $update, ['id' => $id]);
        $dept = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        return $this->success($dept, __('Department updated successfully.', 'nexora-medical-booking-pro'));
    }

    public function delete_item($request) {
        global $wpdb;
        $table = NMBP_Database::table('departments');
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $wpdb->delete($table, ['id' => $id]);
        return $this->success(null, __('Department deleted successfully.', 'nexora-medical-booking-pro'));
    }
}

