<?php
/**
 * Doctor Management Handler
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Doctor_Manager {

    /**
     * Get list of doctors with filtering and pagination
     */
    public static function get_doctors(array $args = []): array {
        global $wpdb;
        $d_table = NMBP_Database::table('doctors');
        $dept_table = NMBP_Database::table('departments');
        $dc_table = NMBP_Database::table('doctor_clinics');

        $defaults = [
            'clinic_id'     => 0,
            'department_id' => 0,
            'status'        => '',
            'search'        => '',
            'orderby'       => 'first_name',
            'order'         => 'ASC',
            'per_page'      => 50,
            'page'          => 1,
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $params = [];
        $join = "LEFT JOIN {$dept_table} dept ON d.department_id = dept.id";

        if (!empty($args['clinic_id'])) {
            $join .= " INNER JOIN {$dc_table} dc ON (d.id = dc.doctor_id AND dc.clinic_id = " . absint($args['clinic_id']) . " AND dc.status = 'active')";
        }

        if (!empty($args['department_id'])) {
            $where[] = 'd.department_id = %d';
            $params[] = absint($args['department_id']);
        }

        if (!empty($args['status'])) {
            $where[] = 'd.status = %s';
            $params[] = sanitize_text_field($args['status']);
        }

        if (!empty($args['search'])) {
            $where[] = '(d.first_name LIKE %s OR d.last_name LIKE %s OR d.specialization LIKE %s OR dept.name LIKE %s)';
            $like = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = implode(' AND ', $where);

        $allowed_orderby = ['first_name', 'last_name', 'consultation_fee', 'experience_years', 'created_at', 'id'];
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? 'd.' . $args['orderby'] : 'd.first_name';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $limit = absint($args['per_page']);
        $offset = (max(1, absint($args['page'])) - 1) * $limit;

        $count_sql = "SELECT COUNT(DISTINCT d.id) FROM {$d_table} d {$join} WHERE {$where_sql}";
        $total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $params)) : (int) $wpdb->get_var($count_sql);

        $query = "SELECT d.*, dept.name as department_name, dept.icon as department_icon
                  FROM {$d_table} d {$join}
                  WHERE {$where_sql}
                  GROUP BY d.id
                  ORDER BY {$orderby} {$order}
                  LIMIT %d OFFSET %d";
        $p = array_merge($params, [$limit, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($query, $p), ARRAY_A);

        foreach ($rows as &$row) {
            $row['consultation_fee'] = (float) $row['consultation_fee'];
            $row['clinics'] = self::get_doctor_clinics((int) $row['id']);
        }

        return [
            'items'       => $rows,
            'total'       => $total,
            'total_pages' => ceil($total / max(1, $limit)),
            'page'        => absint($args['page']),
        ];
    }

    /**
     * Get single doctor by ID
     */
    public static function get_doctor(int $id): ?array {
        global $wpdb;
        $d_table = NMBP_Database::table('doctors');
        $dept_table = NMBP_Database::table('departments');

        $sql = "SELECT d.*, dept.name as department_name, dept.icon as department_icon
                FROM {$d_table} d
                LEFT JOIN {$dept_table} dept ON d.department_id = dept.id
                WHERE d.id = %d";

        $row = $wpdb->get_row($wpdb->prepare($sql, $id), ARRAY_A);
        if (!$row) {
            return null;
        }

        $row['consultation_fee'] = (float) $row['consultation_fee'];
        $row['clinics'] = self::get_doctor_clinics($id);
        $row['leaves'] = self::get_doctor_leaves($id);
        return $row;
    }

    /**
     * Get clinics assigned to a doctor
     */
    public static function get_doctor_clinics(int $doctor_id): array {
        global $wpdb;
        $dc_table = NMBP_Database::table('doctor_clinics');
        $c_table = NMBP_Database::table('clinics');

        $sql = "SELECT dc.id as relation_id, dc.consultation_fee as clinic_fee, dc.room_number, dc.status as assignment_status,
                       c.id as clinic_id, c.name as clinic_name, c.city, c.address, c.phone, c.default_fee
                FROM {$dc_table} dc
                INNER JOIN {$c_table} c ON dc.clinic_id = c.id
                WHERE dc.doctor_id = %d AND dc.status = 'active'";

        $rows = $wpdb->get_results($wpdb->prepare($sql, $doctor_id), ARRAY_A);
        foreach ($rows as &$r) {
            $r['clinic_fee'] = isset($r['clinic_fee']) && !is_null($r['clinic_fee']) ? (float) $r['clinic_fee'] : null;
            $r['default_fee'] = isset($r['default_fee']) ? (float) $r['default_fee'] : 0.00;
        }
        return $rows;
    }

    /**
     * Create doctor profile
     */
    public static function create_doctor(array $data): int {
        global $wpdb;
        $table = NMBP_Database::table('doctors');

        $insert_data = [
            'department_id'           => absint($data['department_id'] ?? 1),
            'first_name'              => sanitize_text_field($data['first_name'] ?? ''),
            'last_name'               => sanitize_text_field($data['last_name'] ?? ''),
            'email'                   => sanitize_email($data['email'] ?? ''),
            'phone'                   => sanitize_text_field($data['phone'] ?? ''),
            'photo_url'               => esc_url_raw($data['photo_url'] ?? ''),
            'specialization'          => sanitize_text_field($data['specialization'] ?? ''),
            'qualifications'          => sanitize_textarea_field($data['qualifications'] ?? ''),
            'experience_years'        => absint($data['experience_years'] ?? 0),
            'bmdc_reg_number'         => sanitize_text_field($data['bmdc_reg_number'] ?? ''),
            'biography'               => sanitize_textarea_field($data['biography'] ?? ''),
            'consultation_fee'        => floatval($data['consultation_fee'] ?? 0.00),
            'appointment_duration'    => absint($data['appointment_duration'] ?? 20),
            'max_patients_per_session'=> absint($data['max_patients_per_session'] ?? 20),
            'status'                  => sanitize_text_field($data['status'] ?? 'active'),
            'created_at'              => current_time('mysql'),
            'updated_at'              => current_time('mysql'),
        ];

        if (!empty($data['wp_user_id'])) {
            $insert_data['wp_user_id'] = absint($data['wp_user_id']);
        }
        if (!empty($data['break_time_start'])) {
            $insert_data['break_time_start'] = sanitize_text_field($data['break_time_start']);
        }
        if (!empty($data['break_time_end'])) {
            $insert_data['break_time_end'] = sanitize_text_field($data['break_time_end']);
        }

        $wpdb->insert($table, $insert_data);
        $doctor_id = $wpdb->insert_id;

        if ($doctor_id) {
            // Assign clinics if passed
            if (!empty($data['clinics']) && is_array($data['clinics'])) {
                self::sync_doctor_clinics($doctor_id, $data['clinics']);
            }

            NMBP_Audit_Logger::log('doctor', $doctor_id, 'created', null, $insert_data);
        }

        return $doctor_id;
    }

    /**
     * Update doctor profile
     */
    public static function update_doctor(int $id, array $data): bool {
        global $wpdb;
        $table = NMBP_Database::table('doctors');

        $existing = self::get_doctor($id);
        if (!$existing) {
            return false;
        }

        $update_data = [
            'updated_at' => current_time('mysql'),
        ];

        $fields = [
            'first_name' => 'sanitize_text_field',
            'last_name' => 'sanitize_text_field',
            'email' => 'sanitize_email',
            'phone' => 'sanitize_text_field',
            'photo_url' => 'esc_url_raw',
            'specialization' => 'sanitize_text_field',
            'qualifications' => 'sanitize_textarea_field',
            'bmdc_reg_number' => 'sanitize_text_field',
            'biography' => 'sanitize_textarea_field',
            'status' => 'sanitize_text_field',
        ];

        foreach ($fields as $key => $sanitizer) {
            if (isset($data[$key])) {
                $update_data[$key] = $sanitizer($data[$key]);
            }
        }

        if (isset($data['break_time_start'])) {
            $bts = sanitize_text_field($data['break_time_start']);
            $update_data['break_time_start'] = !empty($bts) ? $bts : null;
        }
        if (isset($data['break_time_end'])) {
            $bte = sanitize_text_field($data['break_time_end']);
            $update_data['break_time_end'] = !empty($bte) ? $bte : null;
        }

        if (isset($data['department_id'])) {
            $update_data['department_id'] = absint($data['department_id']);
        }
        if (isset($data['wp_user_id'])) {
            $update_data['wp_user_id'] = !empty($data['wp_user_id']) ? absint($data['wp_user_id']) : null;
        }
        if (isset($data['experience_years'])) {
            $update_data['experience_years'] = absint($data['experience_years']);
        }
        if (isset($data['consultation_fee'])) {
            $update_data['consultation_fee'] = floatval($data['consultation_fee']);
        }
        if (isset($data['appointment_duration'])) {
            $update_data['appointment_duration'] = max(5, absint($data['appointment_duration']));
        }
        if (isset($data['max_patients_per_session'])) {
            $update_data['max_patients_per_session'] = absint($data['max_patients_per_session']);
        }

        $res = $wpdb->update($table, $update_data, ['id' => $id]);

        if (isset($data['clinics']) && is_array($data['clinics'])) {
            self::sync_doctor_clinics($id, $data['clinics']);
        }

        if ($res !== false) {
            NMBP_Audit_Logger::log('doctor', $id, 'updated', $existing, $update_data);
            return true;
        }

        return false;
    }

    /**
     * Synchronize clinics for doctor
     */
    public static function sync_doctor_clinics(int $doctor_id, array $clinics): void {
        global $wpdb;
        $dc_table = NMBP_Database::table('doctor_clinics');

        // Delete existing relations
        $wpdb->delete($dc_table, ['doctor_id' => $doctor_id]);

        foreach ($clinics as $c) {
            $clinic_id = is_array($c) ? absint($c['clinic_id'] ?? 0) : absint($c);
            if ($clinic_id <= 0) continue;

            $fee = isset($c['clinic_fee']) && $c['clinic_fee'] !== '' ? floatval($c['clinic_fee']) : null;
            $room = isset($c['room_number']) ? sanitize_text_field($c['room_number']) : null;

            $wpdb->insert($dc_table, [
                'doctor_id'        => $doctor_id,
                'clinic_id'        => $clinic_id,
                'consultation_fee' => $fee,
                'room_number'      => $room,
                'status'           => 'active',
                'created_at'       => current_time('mysql'),
            ]);
        }
    }

    /**
     * Delete doctor
     */
    public static function delete_doctor(int $id): bool {
        global $wpdb;
        $table = NMBP_Database::table('doctors');
        $existing = self::get_doctor($id);
        if (!$existing) {
            return false;
        }

        // Delete associations & schedules
        $wpdb->delete(NMBP_Database::table('doctor_clinics'), ['doctor_id' => $id]);
        $wpdb->delete(NMBP_Database::table('schedules'), ['doctor_id' => $id]);
        $wpdb->delete(NMBP_Database::table('leaves'), ['doctor_id' => $id]);

        $res = $wpdb->delete($table, ['id' => $id]);
        if ($res) {
            NMBP_Audit_Logger::log('doctor', $id, 'deleted', $existing, null);
            return true;
        }
        return false;
    }

    /**
     * Get doctor leave entries
     */
    public static function get_doctor_leaves(int $doctor_id): array {
        global $wpdb;
        $table = NMBP_Database::table('leaves');
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE doctor_id = %d ORDER BY start_date DESC",
            $doctor_id
        ), ARRAY_A);
    }

    /**
     * Add leave request
     */
    public static function add_doctor_leave(array $data): int {
        global $wpdb;
        $table = NMBP_Database::table('leaves');

        $insert = [
            'doctor_id'  => absint($data['doctor_id']),
            'clinic_id'  => !empty($data['clinic_id']) ? absint($data['clinic_id']) : null,
            'start_date' => sanitize_text_field($data['start_date']),
            'end_date'   => sanitize_text_field($data['end_date']),
            'reason'     => sanitize_textarea_field($data['reason'] ?? ''),
            'status'     => sanitize_text_field($data['status'] ?? 'approved'),
            'created_at' => current_time('mysql'),
        ];

        $wpdb->insert($table, $insert);
        return (int) $wpdb->insert_id;
    }

    /**
     * Delete leave request
     */
    public static function delete_doctor_leave(int $leave_id): bool {
        global $wpdb;
        $table = NMBP_Database::table('leaves');
        return (bool) $wpdb->delete($table, ['id' => $leave_id]);
    }
}

