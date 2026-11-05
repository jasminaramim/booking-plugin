<?php
/**
 * Clinic Management Handler
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Clinic_Manager {

    /**
     * Get list of clinics with filtering and pagination
     */
    public static function get_clinics(array $args = []): array {
        global $wpdb;
        $table = NMBP_Database::table('clinics');

        $defaults = [
            'status'   => '',
            'search'   => '',
            'orderby'  => 'name',
            'order'    => 'ASC',
            'per_page' => 50,
            'page'     => 1,
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $params = [];

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $params[] = sanitize_text_field($args['status']);
        }

        if (!empty($args['search'])) {
            $where[] = '(name LIKE %s OR city LIKE %s OR address LIKE %s)';
            $like = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = implode(' AND ', $where);

        $allowed_orderby = ['name', 'city', 'created_at', 'id'];
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'name';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $limit = absint($args['per_page']);
        $offset = (max(1, absint($args['page'])) - 1) * $limit;

        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $params)) : (int) $wpdb->get_var($count_sql);

        $query = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $p = array_merge($params, [$limit, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($query, $p), ARRAY_A);

        // Decode JSON fields
        foreach ($rows as &$row) {
            $row['weekly_holidays'] = !empty($row['weekly_holidays']) ? json_decode($row['weekly_holidays'], true) : [];
            $row['receptionist_user_ids'] = !empty($row['receptionist_user_ids']) ? json_decode($row['receptionist_user_ids'], true) : [];
            $row['default_fee'] = (float) $row['default_fee'];
        }

        return [
            'items'       => $rows,
            'total'       => $total,
            'total_pages' => ceil($total / max(1, $limit)),
            'page'        => absint($args['page']),
        ];
    }

    /**
     * Get single clinic by ID
     */
    public static function get_clinic(int $id): ?array {
        global $wpdb;
        $table = NMBP_Database::table('clinics');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);

        if (!$row) {
            return null;
        }

        $row['weekly_holidays'] = !empty($row['weekly_holidays']) ? json_decode($row['weekly_holidays'], true) : [];
        $row['receptionist_user_ids'] = !empty($row['receptionist_user_ids']) ? json_decode($row['receptionist_user_ids'], true) : [];
        $row['default_fee'] = (float) $row['default_fee'];
        return $row;
    }

    /**
     * Create new clinic
     */
    public static function create_clinic(array $data): int {
        global $wpdb;
        $table = NMBP_Database::table('clinics');

        $slug = !empty($data['slug']) ? sanitize_title($data['slug']) : sanitize_title($data['name'] ?? 'clinic');
        // Ensure unique slug
        $base_slug = $slug;
        $counter = 1;
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s", $slug))) {
            $slug = $base_slug . '-' . $counter++;
        }

        $insert_data = [
            'name'                  => sanitize_text_field($data['name'] ?? ''),
            'slug'                  => $slug,
            'logo_url'              => esc_url_raw($data['logo_url'] ?? ''),
            'cover_image_url'       => esc_url_raw($data['cover_image_url'] ?? ''),
            'email'                 => sanitize_email($data['email'] ?? ''),
            'phone'                 => sanitize_text_field($data['phone'] ?? ''),
            'address'               => sanitize_textarea_field($data['address'] ?? ''),
            'city'                  => sanitize_text_field($data['city'] ?? ''),
            'state'                 => sanitize_text_field($data['state'] ?? ''),
            'postal_code'           => sanitize_text_field($data['postal_code'] ?? ''),
            'country'               => sanitize_text_field($data['country'] ?? ''),
            'opening_time'          => sanitize_text_field($data['opening_time'] ?? '08:00:00'),
            'closing_time'          => sanitize_text_field($data['closing_time'] ?? '20:00:00'),
            'weekly_holidays'       => isset($data['weekly_holidays']) ? wp_json_encode((array) $data['weekly_holidays']) : '[]',
            'default_fee'           => floatval($data['default_fee'] ?? 0.00),
            'receptionist_user_ids' => isset($data['receptionist_user_ids']) ? wp_json_encode((array) $data['receptionist_user_ids']) : '[]',
            'status'                => sanitize_text_field($data['status'] ?? 'active'),
            'created_at'            => current_time('mysql'),
            'updated_at'            => current_time('mysql'),
        ];

        if (!empty($data['map_latitude'])) {
            $insert_data['map_latitude'] = floatval($data['map_latitude']);
        }
        if (!empty($data['map_longitude'])) {
            $insert_data['map_longitude'] = floatval($data['map_longitude']);
        }

        $wpdb->insert($table, $insert_data);
        $clinic_id = $wpdb->insert_id;

        if ($clinic_id) {
            NMBP_Audit_Logger::log('clinic', $clinic_id, 'created', null, $insert_data);
        }

        return $clinic_id;
    }

    /**
     * Update clinic
     */
    public static function update_clinic(int $id, array $data): bool {
        global $wpdb;
        $table = NMBP_Database::table('clinics');

        $existing = self::get_clinic($id);
        if (!$existing) {
            return false;
        }

        $update_data = [
            'updated_at' => current_time('mysql'),
        ];

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['logo_url'])) {
            $update_data['logo_url'] = esc_url_raw($data['logo_url']);
        }
        if (isset($data['cover_image_url'])) {
            $update_data['cover_image_url'] = esc_url_raw($data['cover_image_url']);
        }
        if (isset($data['email'])) {
            $update_data['email'] = sanitize_email($data['email']);
        }
        if (isset($data['phone'])) {
            $update_data['phone'] = sanitize_text_field($data['phone']);
        }
        if (isset($data['address'])) {
            $update_data['address'] = sanitize_textarea_field($data['address']);
        }
        if (isset($data['city'])) {
            $update_data['city'] = sanitize_text_field($data['city']);
        }
        if (isset($data['state'])) {
            $update_data['state'] = sanitize_text_field($data['state']);
        }
        if (isset($data['postal_code'])) {
            $update_data['postal_code'] = sanitize_text_field($data['postal_code']);
        }
        if (isset($data['country'])) {
            $update_data['country'] = sanitize_text_field($data['country']);
        }
        if (isset($data['map_latitude'])) {
            $update_data['map_latitude'] = ($data['map_latitude'] !== '' && $data['map_latitude'] !== null) ? floatval($data['map_latitude']) : null;
        }
        if (isset($data['map_longitude'])) {
            $update_data['map_longitude'] = ($data['map_longitude'] !== '' && $data['map_longitude'] !== null) ? floatval($data['map_longitude']) : null;
        }
        if (isset($data['opening_time'])) {
            $update_data['opening_time'] = sanitize_text_field($data['opening_time']);
        }
        if (isset($data['closing_time'])) {
            $update_data['closing_time'] = sanitize_text_field($data['closing_time']);
        }
        if (isset($data['weekly_holidays'])) {
            $update_data['weekly_holidays'] = wp_json_encode((array) $data['weekly_holidays']);
        }
        if (isset($data['default_fee'])) {
            $update_data['default_fee'] = floatval($data['default_fee']);
        }
        if (isset($data['receptionist_user_ids'])) {
            $update_data['receptionist_user_ids'] = wp_json_encode((array) $data['receptionist_user_ids']);
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }

        $res = $wpdb->update($table, $update_data, ['id' => $id]);
        if ($res !== false) {
            NMBP_Audit_Logger::log('clinic', $id, 'updated', $existing, $update_data);
            return true;
        }

        return false;
    }

    /**
     * Delete clinic
     */
    public static function delete_clinic(int $id): bool {
        global $wpdb;
        $table = NMBP_Database::table('clinics');
        $existing = self::get_clinic($id);
        if (!$existing) {
            return false;
        }

        // Also remove doctor-clinic associations
        $dc_table = NMBP_Database::table('doctor_clinics');
        $wpdb->delete($dc_table, ['clinic_id' => $id]);

        $res = $wpdb->delete($table, ['id' => $id]);
        if ($res) {
            NMBP_Audit_Logger::log('clinic', $id, 'deleted', $existing, null);
            return true;
        }
        return false;
    }

    /**
     * Get doctors assigned to clinic
     */
    public static function get_clinic_doctors(int $clinic_id): array {
        global $wpdb;
        $dc_table = NMBP_Database::table('doctor_clinics');
        $d_table = NMBP_Database::table('doctors');
        $dept_table = NMBP_Database::table('departments');

        $sql = "SELECT d.*, dc.consultation_fee as clinic_fee, dc.room_number, dept.name as department_name
                FROM {$d_table} d
                INNER JOIN {$dc_table} dc ON d.id = dc.doctor_id
                LEFT JOIN {$dept_table} dept ON d.department_id = dept.id
                WHERE dc.clinic_id = %d AND dc.status = 'active' AND d.status = 'active'
                ORDER BY d.first_name ASC";

        return $wpdb->get_results($wpdb->prepare($sql, $clinic_id), ARRAY_A);
    }
}

