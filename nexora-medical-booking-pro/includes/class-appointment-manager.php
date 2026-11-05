<?php
/**
 * Appointment Management & Core Booking Engine
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Appointment_Manager {

    /**
     * Generate unique reference number (e.g. NMBP-202611-A8F9)
     */
    public static function generate_reference_number(): string {
        $prefix = 'NMBP-' . date('Ym') . '-';
        $random = strtoupper(wp_generate_password(5, false, false));
        return $prefix . $random;
    }

    /**
     * Create an appointment (Booking submission)
     * Wraps in DB transaction, acquires atomic lock, and prevents double booking.
     */
    public static function create_appointment(array $data): array {
        global $wpdb;
        $app_table = NMBP_Database::table('appointments');

        $doctor_id = absint($data['doctor_id'] ?? 0);
        $clinic_id = absint($data['clinic_id'] ?? 0);
        $department_id = absint($data['department_id'] ?? 0);
        $date = sanitize_text_field($data['appointment_date'] ?? '');
        $start_time = sanitize_text_field($data['start_time'] ?? '');
        $end_time = sanitize_text_field($data['end_time'] ?? '');
        $appointment_type = sanitize_text_field($data['appointment_type'] ?? 'in_person');
        $payment_method = sanitize_text_field($data['payment_method'] ?? 'cash');
        $patient_notes = sanitize_textarea_field($data['patient_notes'] ?? '');

        // Auto-resolve clinic_id if not provided
        if (!$clinic_id && $doctor_id) {
            $doc = NMBP_Doctor_Manager::get_doctor($doctor_id);
            if (!empty($doc['clinics']) && is_array($doc['clinics'])) {
                $clinic_id = absint($doc['clinics'][0]['clinic_id'] ?? 0);
            }
            if (!$clinic_id) {
                $clinic_table = NMBP_Database::table('clinics');
                $clinic_id = absint($wpdb->get_var("SELECT id FROM {$clinic_table} WHERE status = 'active' ORDER BY id ASC LIMIT 1"));
            }
        }

        if (!$doctor_id || !$clinic_id || empty($date) || empty($start_time) || empty($end_time)) {
            return [
                'success' => false,
                'message' => __('Missing required booking information.', 'nexora-medical-booking-pro'),
            ];
        }

        // If department is missing, retrieve from doctor
        if (!$department_id) {
            $doc = NMBP_Doctor_Manager::get_doctor($doctor_id);
            if ($doc) {
                $department_id = absint($doc['department_id']);
            }
        }

        // Step 1: Start atomic transaction
        NMBP_Database::start_transaction();

        // Step 2: Validate atomic availability
        $is_available = NMBP_Database::is_slot_available($doctor_id, $date, $start_time, $end_time);
        if (!$is_available) {
            NMBP_Database::rollback();
            return [
                'success' => false,
                'message' => __('This time slot has just been reserved by another patient. Please choose another slot.', 'nexora-medical-booking-pro'),
            ];
        }

        // Step 3: Find or register patient
        $patient_id = NMBP_Patient_Manager::find_or_create_patient($data['patient'] ?? []);
        if (!$patient_id) {
            NMBP_Database::rollback();
            return [
                'success' => false,
                'message' => __('Failed to register patient details.', 'nexora-medical-booking-pro'),
            ];
        }

        // Step 4: Determine consultation fee
        $fee = self::calculate_consultation_fee($doctor_id, $clinic_id);

        // Step 5: Temporary lock expiration (10 minutes)
        $app_settings = get_option('nmbp_appointment_settings', []);
        $lock_minutes = absint($app_settings['temp_lock_duration_minutes'] ?? 10);
        $lock_expires = date('Y-m-d H:i:s', strtotime("+{$lock_minutes} minutes", current_time('timestamp')));

        $reference = self::generate_reference_number();
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$app_table} WHERE appointment_number = %s", $reference))) {
            $reference = self::generate_reference_number();
        }

        $insert_data = [
            'appointment_number'   => $reference,
            'clinic_id'            => $clinic_id,
            'doctor_id'            => $doctor_id,
            'patient_id'           => $patient_id,
            'department_id'        => $department_id,
            'appointment_date'     => $date,
            'start_time'           => $start_time,
            'end_time'             => $end_time,
            'appointment_type'     => $appointment_type,
            'consultation_fee'     => $fee,
            'payment_method'       => $payment_method,
            'payment_status'       => 'unpaid',
            'appointment_status'   => 'pending', // Requires admin approval
            'patient_notes'        => $patient_notes,
            'temp_lock_expires_at' => $lock_expires,
            'created_at'           => current_time('mysql'),
            'updated_at'           => current_time('mysql'),
        ];

        $res = $wpdb->insert($app_table, $insert_data);
        if (!$res) {
            NMBP_Database::rollback();
            return [
                'success' => false,
                'message' => __('Failed to create appointment record.', 'nexora-medical-booking-pro'),
            ];
        }

        $appointment_id = (int) $wpdb->insert_id;

        // Step 6: Create initial payment record
        $gateway = 'offline_cash';
        if ($payment_method === 'stripe') {
            $gateway = 'stripe';
        } elseif ($payment_method === 'paypal') {
            $gateway = 'paypal';
        } elseif ($payment_method === 'woocommerce' || $payment_method === 'online') {
            $gateway = 'woocommerce';
        } elseif ($payment_method === 'cash' || $payment_method === 'offline') {
            $gateway = 'offline_cash';
        }

        $payment_id = NMBP_Payment_Manager::create_initial_payment([
            'appointment_id' => $appointment_id,
            'patient_id'     => $patient_id,
            'amount'         => $fee,
            'gateway'        => $gateway,
            'transaction_id' => !empty($data['transaction_id']) ? sanitize_text_field($data['transaction_id']) : null,
            'payment_status' => !empty($data['transaction_id']) ? 'paid' : 'pending',
        ]);

        // Commit transaction
        NMBP_Database::commit();

        // Audit log
        NMBP_Audit_Logger::log('appointment', $appointment_id, 'created', null, $insert_data);

        // Send notifications
        NMBP_Notification_Manager::trigger_event('booking_submitted', $appointment_id);

        return [
            'success'              => true,
            'appointment_id'       => $appointment_id,
            'appointment_number'   => $reference,
            'payment_id'           => $payment_id,
            'fee'                  => $fee,
            'appointment_status'   => 'pending',
            'message'              => __('Your appointment request has been submitted successfully and is awaiting admin approval.', 'nexora-medical-booking-pro'),
        ];
    }

    /**
     * Calculate consultation fee: clinic-specific override > doctor default > clinic default
     */
    public static function calculate_consultation_fee(int $doctor_id, int $clinic_id): float {
        global $wpdb;

        // 1. Check doctor-clinic association
        $dc_table = NMBP_Database::table('doctor_clinics');
        $override_fee = $wpdb->get_var($wpdb->prepare(
            "SELECT consultation_fee FROM {$dc_table} WHERE doctor_id = %d AND clinic_id = %d AND status = 'active'",
            $doctor_id,
            $clinic_id
        ));

        if (!is_null($override_fee) && $override_fee !== '') {
            return (float) $override_fee;
        }

        // 2. Doctor default fee
        $d_table = NMBP_Database::table('doctors');
        $doc_fee = $wpdb->get_var($wpdb->prepare("SELECT consultation_fee FROM {$d_table} WHERE id = %d", $doctor_id));
        if ($doc_fee > 0) {
            return (float) $doc_fee;
        }

        // 3. Clinic default fee
        $c_table = NMBP_Database::table('clinics');
        $clinic_fee = $wpdb->get_var($wpdb->prepare("SELECT default_fee FROM {$c_table} WHERE id = %d", $clinic_id));
        return (float) ($clinic_fee ?? 0.00);
    }

    /**
     * Approve appointment (Admin action)
     * Revalidates availability to strictly enforce zero double-bookings!
     */
    public static function approve_appointment(int $appointment_id, string $admin_notes = ''): array {
        global $wpdb;
        $table = NMBP_Database::table('appointments');

        $app = self::get_appointment($appointment_id);
        if (!$app) {
            return ['success' => false, 'message' => __('Appointment not found.', 'nexora-medical-booking-pro')];
        }

        if ($app['appointment_status'] === 'confirmed') {
            return ['success' => true, 'message' => __('Appointment is already confirmed.', 'nexora-medical-booking-pro')];
        }

        // Start transaction
        NMBP_Database::start_transaction();

        // Critical requirement: Revalidate availability during admin approval!
        $is_available = NMBP_Database::is_slot_available(
            (int) $app['doctor_id'],
            $app['appointment_date'],
            $app['start_time'],
            $app['end_time'],
            $appointment_id
        );

        if (!$is_available) {
            NMBP_Database::rollback();
            return [
                'success' => false,
                'message' => __('Cannot approve: This doctor already has another confirmed appointment conflicting with this time slot.', 'nexora-medical-booking-pro'),
            ];
        }

        $update = [
            'appointment_status'   => 'confirmed',
            'temp_lock_expires_at' => null, // Lock is now permanent confirmed
            'updated_at'           => current_time('mysql'),
        ];
        if (!empty($admin_notes)) {
            $update['admin_notes'] = sanitize_textarea_field($admin_notes);
        }

        $wpdb->update($table, $update, ['id' => $appointment_id]);
        NMBP_Database::commit();

        NMBP_Audit_Logger::log('appointment', $appointment_id, 'approved', $app, $update);
        NMBP_Notification_Manager::trigger_event('appointment_approved', $appointment_id);

        return ['success' => true, 'message' => __('Appointment has been confirmed successfully.', 'nexora-medical-booking-pro')];
    }

    /**
     * Reject appointment
     */
    public static function reject_appointment(int $appointment_id, string $reason = ''): array {
        global $wpdb;
        $table = NMBP_Database::table('appointments');

        $app = self::get_appointment($appointment_id);
        if (!$app) {
            return ['success' => false, 'message' => __('Appointment not found.', 'nexora-medical-booking-pro')];
        }

        $update = [
            'appointment_status'   => 'rejected',
            'cancellation_reason'  => sanitize_textarea_field($reason),
            'temp_lock_expires_at' => null,
            'updated_at'           => current_time('mysql'),
        ];

        $wpdb->update($table, $update, ['id' => $appointment_id]);

        NMBP_Audit_Logger::log('appointment', $appointment_id, 'rejected', $app, $update);
        NMBP_Notification_Manager::trigger_event('appointment_rejected', $appointment_id);

        return ['success' => true, 'message' => __('Appointment has been rejected.', 'nexora-medical-booking-pro')];
    }

    /**
     * Cancel appointment
     */
    public static function cancel_appointment(int $appointment_id, string $reason = ''): array {
        global $wpdb;
        $table = NMBP_Database::table('appointments');

        $app = self::get_appointment($appointment_id);
        if (!$app) {
            return ['success' => false, 'message' => __('Appointment not found.', 'nexora-medical-booking-pro')];
        }

        $update = [
            'appointment_status'   => 'cancelled',
            'cancellation_reason'  => sanitize_textarea_field($reason),
            'temp_lock_expires_at' => null,
            'updated_at'           => current_time('mysql'),
        ];

        $wpdb->update($table, $update, ['id' => $appointment_id]);

        NMBP_Audit_Logger::log('appointment', $appointment_id, 'cancelled', $app, $update);
        NMBP_Notification_Manager::trigger_event('appointment_cancelled', $appointment_id);

        return ['success' => true, 'message' => __('Appointment has been cancelled.', 'nexora-medical-booking-pro')];
    }

    /**
     * Reschedule appointment
     */
    public static function reschedule_appointment(int $appointment_id, string $new_date, string $new_start_time, string $new_end_time): array {
        global $wpdb;
        $table = NMBP_Database::table('appointments');

        $app = self::get_appointment($appointment_id);
        if (!$app) {
            return ['success' => false, 'message' => __('Appointment not found.', 'nexora-medical-booking-pro')];
        }

        NMBP_Database::start_transaction();

        $is_available = NMBP_Database::is_slot_available(
            (int) $app['doctor_id'],
            $new_date,
            $new_start_time,
            $new_end_time,
            $appointment_id
        );

        if (!$is_available) {
            NMBP_Database::rollback();
            return ['success' => false, 'message' => __('The selected new time slot is unavailable.', 'nexora-medical-booking-pro')];
        }

        $update = [
            'appointment_date'     => sanitize_text_field($new_date),
            'start_time'           => sanitize_text_field($new_start_time),
            'end_time'             => sanitize_text_field($new_end_time),
            'updated_at'           => current_time('mysql'),
        ];

        $wpdb->update($table, $update, ['id' => $appointment_id]);
        NMBP_Database::commit();

        NMBP_Audit_Logger::log('appointment', $appointment_id, 'rescheduled', $app, $update);
        NMBP_Notification_Manager::trigger_event('appointment_rescheduled', $appointment_id);

        return ['success' => true, 'message' => __('Appointment rescheduled successfully.', 'nexora-medical-booking-pro')];
    }

    /**
     * Mark appointment completed or no-show
     */
    public static function update_status(int $appointment_id, string $new_status, string $notes = ''): bool {
        global $wpdb;
        $table = NMBP_Database::table('appointments');

        $allowed = ['pending', 'confirmed', 'rejected', 'cancelled', 'completed', 'no_show', 'refunded'];
        if (!in_array($new_status, $allowed, true)) {
            return false;
        }

        $app = self::get_appointment($appointment_id);
        if (!$app) {
            return false;
        }

        $update = [
            'appointment_status' => $new_status,
            'updated_at'         => current_time('mysql'),
        ];

        if (!empty($notes)) {
            $update['admin_notes'] = sanitize_textarea_field($notes);
        }

        $res = $wpdb->update($table, $update, ['id' => $appointment_id]);
        if ($res !== false) {
            NMBP_Audit_Logger::log('appointment', $appointment_id, 'status_changed_' . $new_status, $app, $update);
            return true;
        }
        return false;
    }

    /**
     * Get single appointment with joined doctor, clinic, patient, and department
     */
    public static function get_appointment(int $id): ?array {
        global $wpdb;
        $app_table = NMBP_Database::table('appointments');
        $c_table = NMBP_Database::table('clinics');
        $d_table = NMBP_Database::table('doctors');
        $p_table = NMBP_Database::table('patients');
        $dept_table = NMBP_Database::table('departments');

        $sql = "SELECT a.*,
                       c.name as clinic_name, c.address as clinic_address, c.phone as clinic_phone, c.city as clinic_city,
                       d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.photo_url as doctor_photo, d.specialization as doctor_specialization,
                       p.first_name as patient_first_name, p.last_name as patient_last_name, p.email as patient_email, p.phone as patient_phone, p.blood_group as patient_blood_group,
                       dept.name as department_name
                FROM {$app_table} a
                LEFT JOIN {$c_table} c ON a.clinic_id = c.id
                LEFT JOIN {$d_table} d ON a.doctor_id = d.id
                LEFT JOIN {$p_table} p ON a.patient_id = p.id
                LEFT JOIN {$dept_table} dept ON a.department_id = dept.id
                WHERE a.id = %d";

        $row = $wpdb->get_row($wpdb->prepare($sql, $id), ARRAY_A);
        if (!$row) {
            return null;
        }

        $row['consultation_fee'] = (float) $row['consultation_fee'];
        return $row;
    }

    /**
     * Get appointment by reference number
     */
    public static function get_by_reference(string $reference): ?array {
        global $wpdb;
        $app_table = NMBP_Database::table('appointments');
        $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$app_table} WHERE appointment_number = %s", sanitize_text_field($reference)));
        return $id ? self::get_appointment((int) $id) : null;
    }

    /**
     * List appointments with full filtering, sorting and pagination
     */
    public static function get_appointments(array $args = []): array {
        global $wpdb;
        $app_table = NMBP_Database::table('appointments');
        $c_table = NMBP_Database::table('clinics');
        $d_table = NMBP_Database::table('doctors');
        $p_table = NMBP_Database::table('patients');
        $dept_table = NMBP_Database::table('departments');

        $defaults = [
            'clinic_id'      => 0,
            'doctor_id'      => 0,
            'patient_id'     => 0,
            'status'         => '',
            'payment_status' => '',
            'date_from'      => '',
            'date_to'        => '',
            'search'         => '',
            'orderby'        => 'appointment_date',
            'order'          => 'DESC',
            'per_page'       => 20,
            'page'           => 1,
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $params = [];

        if (!empty($args['clinic_id'])) {
            $where[] = 'a.clinic_id = %d';
            $params[] = absint($args['clinic_id']);
        }
        if (!empty($args['doctor_id'])) {
            $where[] = 'a.doctor_id = %d';
            $params[] = absint($args['doctor_id']);
        }
        if (!empty($args['patient_id'])) {
            $where[] = 'a.patient_id = %d';
            $params[] = absint($args['patient_id']);
        }
        if (!empty($args['status'])) {
            $where[] = 'a.appointment_status = %s';
            $params[] = sanitize_text_field($args['status']);
        }
        if (!empty($args['payment_status'])) {
            $where[] = 'a.payment_status = %s';
            $params[] = sanitize_text_field($args['payment_status']);
        }
        if (!empty($args['date_from'])) {
            $where[] = 'a.appointment_date >= %s';
            $params[] = sanitize_text_field($args['date_from']);
        }
        if (!empty($args['date_to'])) {
            $where[] = 'a.appointment_date <= %s';
            $params[] = sanitize_text_field($args['date_to']);
        }
        if (!empty($args['search'])) {
            $where[] = '(a.appointment_number LIKE %s OR p.first_name LIKE %s OR p.last_name LIKE %s OR p.phone LIKE %s OR d.first_name LIKE %s OR d.last_name LIKE %s)';
            $like = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            for ($i = 0; $i < 6; $i++) {
                $params[] = $like;
            }
        }

        $where_sql = implode(' AND ', $where);

        $join = "LEFT JOIN {$c_table} c ON a.clinic_id = c.id
                 LEFT JOIN {$d_table} d ON a.doctor_id = d.id
                 LEFT JOIN {$p_table} p ON a.patient_id = p.id
                 LEFT JOIN {$dept_table} dept ON a.department_id = dept.id";

        $count_sql = "SELECT COUNT(*) FROM {$app_table} a {$join} WHERE {$where_sql}";
        $total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $params)) : (int) $wpdb->get_var($count_sql);

        $allowed_orderby = ['appointment_date', 'start_time', 'created_at', 'consultation_fee', 'id'];
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? 'a.' . $args['orderby'] : 'a.appointment_date';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $limit = absint($args['per_page']);
        $offset = (max(1, absint($args['page'])) - 1) * $limit;

        $query = "SELECT a.*,
                         c.name as clinic_name, c.city as clinic_city,
                         d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.photo_url as doctor_photo,
                         p.first_name as patient_first_name, p.last_name as patient_last_name, p.phone as patient_phone, p.email as patient_email,
                         dept.name as department_name
                  FROM {$app_table} a {$join}
                  WHERE {$where_sql}
                  ORDER BY {$orderby} {$order}, a.start_time ASC
                  LIMIT %d OFFSET %d";

        $p = array_merge($params, [$limit, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($query, $p), ARRAY_A);

        foreach ($rows as &$r) {
            $r['consultation_fee'] = (float) $r['consultation_fee'];
        }

        return [
            'items'       => $rows,
            'total'       => $total,
            'total_pages' => ceil($total / max(1, $limit)),
            'page'        => absint($args['page']),
        ];
    }
}

