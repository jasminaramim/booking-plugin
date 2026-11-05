<?php
/**
 * Payment Manager & Gateway Orchestrator
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Payment_Manager {

    /**
     * Create initial payment record
     */
    public static function create_initial_payment(array $data): int {
        global $wpdb;
        $table = NMBP_Database::table('payments');

        $payment_number = 'PAY-' . date('Ym') . '-' . strtoupper(wp_generate_password(6, false, false));

        $general_settings = get_option('nmbp_general_settings', []);
        $currency = sanitize_text_field($general_settings['currency'] ?? 'USD');

        $insert_data = [
            'payment_number' => $payment_number,
            'appointment_id' => absint($data['appointment_id']),
            'patient_id'     => absint($data['patient_id']),
            'amount'         => floatval($data['amount'] ?? 0.00),
            'currency'       => $currency,
            'gateway'        => sanitize_text_field($data['gateway'] ?? 'offline_clinic'),
            'transaction_id' => !empty($data['transaction_id']) ? sanitize_text_field($data['transaction_id']) : null,
            'receipt_url'    => !empty($data['receipt_url']) ? esc_url_raw($data['receipt_url']) : null,
            'payment_status' => sanitize_text_field($data['payment_status'] ?? 'pending'),
            'created_at'     => current_time('mysql'),
            'updated_at'     => current_time('mysql'),
        ];

        $wpdb->insert($table, $insert_data);
        $payment_id = (int) $wpdb->insert_id;

        if ($payment_id) {
            NMBP_Audit_Logger::log('payment', $payment_id, 'created', null, $insert_data);
        }

        return $payment_id;
    }

    /**
     * Verify payment manually (Admin / Receptionist action for offline or cash payments)
     */
    public static function verify_payment(int $payment_id, int $verified_by_user_id, string $notes = ''): bool {
        global $wpdb;
        $table = NMBP_Database::table('payments');
        $app_table = NMBP_Database::table('appointments');

        $payment = self::get_payment($payment_id);
        if (!$payment) {
            return false;
        }

        $now = current_time('mysql');
        $update = [
            'payment_status'      => 'paid',
            'verified_by_user_id' => $verified_by_user_id,
            'verified_at'         => $now,
            'updated_at'          => $now,
        ];

        $res = $wpdb->update($table, $update, ['id' => $payment_id]);
        if ($res !== false) {
            // Also update appointment payment_status
            $wpdb->update($app_table, [
                'payment_status' => 'paid',
                'updated_at'     => $now,
            ], ['id' => $payment['appointment_id']]);

            NMBP_Audit_Logger::log('payment', $payment_id, 'verified_paid', $payment, $update);
            NMBP_Notification_Manager::trigger_event('payment_received', (int) $payment['appointment_id']);
            return true;
        }

        return false;
    }

    /**
     * Mark payment as refunded
     */
    public static function refund_payment(int $payment_id, string $reason = ''): bool {
        global $wpdb;
        $table = NMBP_Database::table('payments');
        $app_table = NMBP_Database::table('appointments');

        $payment = self::get_payment($payment_id);
        if (!$payment) {
            return false;
        }

        $now = current_time('mysql');
        $update = [
            'payment_status' => 'refunded',
            'updated_at'     => $now,
        ];

        $res = $wpdb->update($table, $update, ['id' => $payment_id]);
        if ($res !== false) {
            $wpdb->update($app_table, [
                'payment_status'     => 'refunded',
                'appointment_status' => 'refunded',
                'updated_at'         => $now,
            ], ['id' => $payment['appointment_id']]);

            NMBP_Audit_Logger::log('payment', $payment_id, 'refunded', $payment, ['reason' => $reason]);
            return true;
        }

        return false;
    }

    /**
     * Get single payment record
     */
    public static function get_payment(int $id): ?array {
        global $wpdb;
        $table = NMBP_Database::table('payments');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        if ($row) {
            $row['amount'] = (float) $row['amount'];
        }
        return $row;
    }

    /**
     * Get payments for an appointment
     */
    public static function get_payments_for_appointment(int $appointment_id): array {
        global $wpdb;
        $table = NMBP_Database::table('payments');
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE appointment_id = %d ORDER BY id DESC", $appointment_id), ARRAY_A);
        foreach ($rows as &$r) {
            $r['amount'] = (float) $r['amount'];
        }
        return $rows;
    }

    /**
     * Get list of payments with filtering
     */
    public static function get_payments(array $args = []): array {
        global $wpdb;
        $p_table = NMBP_Database::table('payments');
        $app_table = NMBP_Database::table('appointments');
        $patient_table = NMBP_Database::table('patients');

        $defaults = [
            'status'   => '',
            'gateway'  => '',
            'search'   => '',
            'per_page' => 20,
            'page'     => 1,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $params = [];

        if (!empty($args['status'])) {
            $where[] = 'p.payment_status = %s';
            $params[] = sanitize_text_field($args['status']);
        }
        if (!empty($args['gateway'])) {
            $where[] = 'p.gateway = %s';
            $params[] = sanitize_text_field($args['gateway']);
        }
        if (!empty($args['search'])) {
            $where[] = '(p.payment_number LIKE %s OR p.transaction_id LIKE %s OR pt.first_name LIKE %s OR pt.last_name LIKE %s OR a.appointment_number LIKE %s)';
            $like = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            for ($i = 0; $i < 5; $i++) {
                $params[] = $like;
            }
        }

        $where_sql = implode(' AND ', $where);

        $join = "LEFT JOIN {$app_table} a ON p.appointment_id = a.id
                 LEFT JOIN {$patient_table} pt ON p.patient_id = pt.id";

        $count_sql = "SELECT COUNT(*) FROM {$p_table} p {$join} WHERE {$where_sql}";
        $total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $params)) : (int) $wpdb->get_var($count_sql);

        $limit = absint($args['per_page']);
        $offset = (max(1, absint($args['page'])) - 1) * $limit;

        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $query = "SELECT p.*, a.appointment_number, pt.first_name as patient_first_name, pt.last_name as patient_last_name, pt.email as patient_email
                  FROM {$p_table} p {$join}
                  WHERE {$where_sql}
                  ORDER BY p.id {$order}
                  LIMIT %d OFFSET %d";

        $p = array_merge($params, [$limit, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($query, $p), ARRAY_A);

        foreach ($rows as &$r) {
            $r['amount'] = (float) $r['amount'];
        }

        return [
            'items'       => $rows,
            'total'       => $total,
            'total_pages' => ceil($total / max(1, $limit)),
            'page'        => absint($args['page']),
        ];
    }
}

