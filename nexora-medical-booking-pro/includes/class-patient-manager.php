<?php
/**
 * Patient Management Handler
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Patient_Manager {

    /**
     * Get patients list with search and pagination
     */
    public static function get_patients(array $args = []): array {
        global $wpdb;
        $table = NMBP_Database::table('patients');

        $defaults = [
            'search'   => '',
            'status'   => '',
            'orderby'  => 'created_at',
            'order'    => 'DESC',
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
            $where[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s)';
            $like = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = implode(' AND ', $where);

        $allowed_orderby = ['first_name', 'last_name', 'email', 'created_at', 'id'];
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $limit = absint($args['per_page']);
        $offset = (max(1, absint($args['page'])) - 1) * $limit;

        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $params)) : (int) $wpdb->get_var($count_sql);

        $query = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $p = array_merge($params, [$limit, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($query, $p), ARRAY_A);

        return [
            'items'       => $rows,
            'total'       => $total,
            'total_pages' => ceil($total / max(1, $limit)),
            'page'        => absint($args['page']),
        ];
    }

    /**
     * Get single patient by ID
     */
    public static function get_patient(int $id): ?array {
        global $wpdb;
        $table = NMBP_Database::table('patients');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
    }

    /**
     * Find or create patient during booking submission
     */
    public static function find_or_create_patient(array $data): int {
        global $wpdb;
        $table = NMBP_Database::table('patients');

        $email = sanitize_email($data['email'] ?? '');
        $phone = sanitize_text_field($data['phone'] ?? '');

        // Search by email or phone
        $existing_id = null;
        if (!empty($email)) {
            $existing_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE email = %s LIMIT 1", $email));
        }

        if (!$existing_id && !empty($phone)) {
            $existing_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE phone = %s LIMIT 1", $phone));
        }

        $wp_user_id = get_current_user_id() ?: (!empty($data['wp_user_id']) ? absint($data['wp_user_id']) : null);

        // Auto create WP user if configured and patient is not logged in
        $general_settings = get_option('nmbp_general_settings', []);
        $auto_create_wp = !empty($general_settings['auto_create_patient_account']);

        if (!$wp_user_id && $auto_create_wp && !empty($email) && !email_exists($email)) {
            $username = sanitize_user(current(explode('@', $email)));
            $base_user = $username;
            $counter = 1;
            while (username_exists($username)) {
                $username = $base_user . $counter++;
            }
            $random_password = wp_generate_password(12, true);
            $new_user_id = wp_create_user($username, $random_password, $email);
            if (!is_wp_error($new_user_id)) {
                wp_update_user([
                    'ID'         => $new_user_id,
                    'first_name' => sanitize_text_field($data['first_name'] ?? ''),
                    'last_name'  => sanitize_text_field($data['last_name'] ?? ''),
                    'role'       => NMBP_Permissions::ROLE_PATIENT,
                ]);
                $wp_user_id = $new_user_id;
            }
        }

        if ($existing_id) {
            // Update latest contact info if missing
            $update = [
                'updated_at' => current_time('mysql'),
            ];
            if ($wp_user_id) {
                $update['wp_user_id'] = $wp_user_id;
            }
            if (!empty($data['first_name'])) {
                $update['first_name'] = sanitize_text_field($data['first_name']);
            }
            if (!empty($data['last_name'])) {
                $update['last_name'] = sanitize_text_field($data['last_name']);
            }
            if (!empty($data['phone'])) {
                $update['phone'] = sanitize_text_field($data['phone']);
            }
            if (!empty($data['blood_group'])) {
                $update['blood_group'] = sanitize_text_field($data['blood_group']);
            }
            if (!empty($data['date_of_birth'])) {
                $update['date_of_birth'] = sanitize_text_field($data['date_of_birth']);
            }
            if (!empty($data['gender'])) {
                $update['gender'] = sanitize_text_field($data['gender']);
            }
            if (!empty($data['address'])) {
                $update['address'] = sanitize_textarea_field($data['address']);
            }
            $wpdb->update($table, $update, ['id' => $existing_id]);
            return (int) $existing_id;
        }

        // Insert new patient
        $insert = [
            'first_name'             => sanitize_text_field($data['first_name'] ?? ''),
            'last_name'              => sanitize_text_field($data['last_name'] ?? ''),
            'email'                  => $email,
            'phone'                  => $phone,
            'status'                 => 'active',
            'created_at'             => current_time('mysql'),
            'updated_at'             => current_time('mysql'),
        ];

        if (!empty($wp_user_id)) {
            $insert['wp_user_id'] = $wp_user_id;
        }
        if (!empty($data['gender'])) {
            $insert['gender'] = sanitize_text_field($data['gender']);
        }
        if (!empty($data['date_of_birth'])) {
            $insert['date_of_birth'] = sanitize_text_field($data['date_of_birth']);
        }
        if (!empty($data['blood_group'])) {
            $insert['blood_group'] = sanitize_text_field($data['blood_group']);
        }
        if (!empty($data['address'])) {
            $insert['address'] = sanitize_textarea_field($data['address']);
        }
        if (!empty($data['emergency_contact'])) {
            $insert['emergency_contact'] = sanitize_text_field($data['emergency_contact']);
        }
        if (!empty($data['medical_history_notes'])) {
            $insert['medical_history_notes'] = sanitize_textarea_field($data['medical_history_notes']);
        }

        $wpdb->insert($table, $insert);
        $patient_id = (int) $wpdb->insert_id;

        if ($patient_id) {
            NMBP_Audit_Logger::log('patient', $patient_id, 'created', null, $insert);
        }

        return $patient_id;
    }

    /**
     * Update patient
     */
    public static function update_patient(int $id, array $data): bool {
        global $wpdb;
        $table = NMBP_Database::table('patients');

        $update = ['updated_at' => current_time('mysql')];
        $fields = ['first_name', 'last_name', 'email', 'phone', 'gender', 'blood_group', 'emergency_contact', 'status'];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $update[$field] = sanitize_text_field($data[$field]);
            }
        }

        if (isset($data['date_of_birth'])) {
            $dob = sanitize_text_field($data['date_of_birth']);
            $update['date_of_birth'] = !empty($dob) ? $dob : null;
        }

        if (isset($data['address'])) {
            $update['address'] = sanitize_textarea_field($data['address']);
        }
        if (isset($data['medical_history_notes'])) {
            $update['medical_history_notes'] = sanitize_textarea_field($data['medical_history_notes']);
        }

        $res = $wpdb->update($table, $update, ['id' => $id]);
        return $res !== false;
    }
}

