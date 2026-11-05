<?php
/**
 * Smart Scheduling Engine & Double-Booking Prevention
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Schedule_Manager {

    /**
     * Get schedules for a doctor across clinics or for a specific clinic
     */
    public static function get_schedules(int $doctor_id, int $clinic_id = 0): array {
        global $wpdb;
        $table = NMBP_Database::table('schedules');

        if ($clinic_id > 0) {
            $sql = "SELECT * FROM {$table} WHERE doctor_id = %d AND clinic_id = %d AND status = 'active' ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), start_time ASC";
            return $wpdb->get_results($wpdb->prepare($sql, $doctor_id, $clinic_id), ARRAY_A);
        }

        $sql = "SELECT s.*, c.name as clinic_name FROM {$table} s
                INNER JOIN " . NMBP_Database::table('clinics') . " c ON s.clinic_id = c.id
                WHERE s.doctor_id = %d AND s.status = 'active'
                ORDER BY s.clinic_id, FIELD(s.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), s.start_time ASC";
        return $wpdb->get_results($wpdb->prepare($sql, $doctor_id), ARRAY_A);
    }

    /**
     * Save / replace weekly schedules for doctor at clinic
     */
    public static function save_doctor_schedules(int $doctor_id, int $clinic_id, array $schedules): bool {
        global $wpdb;
        $table = NMBP_Database::table('schedules');

        // Delete existing active schedules for this doctor at this clinic
        $wpdb->delete($table, [
            'doctor_id' => $doctor_id,
            'clinic_id' => $clinic_id,
        ]);

        foreach ($schedules as $sched) {
            $day = strtolower(sanitize_text_field($sched['day_of_week'] ?? ''));
            if (!in_array($day, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'], true)) {
                continue;
            }

            $start = sanitize_text_field($sched['start_time'] ?? '09:00:00');
            $end = sanitize_text_field($sched['end_time'] ?? '17:00:00');
            $duration = absint($sched['slot_duration'] ?? 20);
            $buffer = absint($sched['buffer_time'] ?? 5);
            $break_start = !empty($sched['break_start']) ? sanitize_text_field($sched['break_start']) : null;
            $break_end = !empty($sched['break_end']) ? sanitize_text_field($sched['break_end']) : null;
            $max_cap = absint($sched['max_capacity'] ?? 20);

            $wpdb->insert($table, [
                'doctor_id'     => $doctor_id,
                'clinic_id'     => $clinic_id,
                'day_of_week'   => $day,
                'start_time'    => $start,
                'end_time'      => $end,
                'slot_duration' => max(5, $duration),
                'buffer_time'   => $buffer,
                'break_start'   => $break_start,
                'break_end'     => $break_end,
                'max_capacity'  => $max_cap,
                'status'        => 'active',
                'created_at'    => current_time('mysql'),
                'updated_at'    => current_time('mysql'),
            ]);
        }

        NMBP_Audit_Logger::log('schedule', $doctor_id, 'updated', null, ['clinic_id' => $clinic_id, 'schedules_count' => count($schedules)]);
        return true;
    }

    /**
     * Compute available appointment time slots for doctor, clinic, and date
     */
    public static function get_available_slots(int $doctor_id, int $clinic_id, string $date_str): array {
        global $wpdb;

        $date_timestamp = strtotime($date_str);
        if (!$date_timestamp) {
            return ['slots' => [], 'error' => __('Invalid date format.', 'nexora-medical-booking-pro')];
        }

        $formatted_date = date('Y-m-d', $date_timestamp);
        $today = current_time('Y-m-d');
        $now_time = current_time('H:i:s');

        // Check advance booking constraints
        $app_settings = get_option('nmbp_appointment_settings', []);
        $min_advance_hours = absint($app_settings['min_advance_hours'] ?? 2);
        $max_advance_days = absint($app_settings['max_advance_days'] ?? 30);

        if ($formatted_date < $today) {
            return ['slots' => [], 'error' => __('Cannot book appointments for past dates.', 'nexora-medical-booking-pro')];
        }

        $max_allowed_date = date('Y-m-d', strtotime("+{$max_advance_days} days", strtotime($today)));
        if ($formatted_date > $max_allowed_date) {
            return ['slots' => [], 'error' => sprintf(__('Appointments can only be booked up to %d days in advance.', 'nexora-medical-booking-pro'), $max_advance_days)];
        }

        // 1. Check clinic weekly holidays
        $clinic = NMBP_Clinic_Manager::get_clinic($clinic_id);
        if (!$clinic) {
            return ['slots' => [], 'error' => __('Selected clinic was not found.', 'nexora-medical-booking-pro')];
        }

        $day_name = date('l', $date_timestamp); // e.g. Friday, Monday
        $holidays = (array) ($clinic['weekly_holidays'] ?? []);
        foreach ($holidays as $h) {
            if (strcasecmp($h, $day_name) === 0) {
                return ['slots' => [], 'error' => sprintf(__('Clinic is closed on %ss.', 'nexora-medical-booking-pro'), $day_name)];
            }
        }

        // 2. Check doctor leaves
        $leaves_table = NMBP_Database::table('leaves');
        $on_leave = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$leaves_table}
             WHERE doctor_id = %d
               AND status = 'approved'
               AND (clinic_id IS NULL OR clinic_id = %d)
               AND (%s BETWEEN start_date AND end_date)",
            $doctor_id,
            $clinic_id,
            $formatted_date
        ));

        if ($on_leave > 0) {
            return ['slots' => [], 'error' => __('The doctor is on leave on this date.', 'nexora-medical-booking-pro')];
        }

        // 3. Find doctor schedule for this day_of_week at this clinic
        $day_of_week = strtolower($day_name);
        $schedules_table = NMBP_Database::table('schedules');
        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$schedules_table}
             WHERE doctor_id = %d AND clinic_id = %d AND day_of_week = %s AND status = 'active'
             ORDER BY start_time ASC",
            $doctor_id,
            $clinic_id,
            $day_of_week
        ), ARRAY_A);

        // Fallback: If no clinic-specific schedule is found, check if doctor has general weekly schedules
        if (empty($schedules)) {
            $schedules = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$schedules_table}
                 WHERE doctor_id = %d AND day_of_week = %s AND status = 'active'
                 ORDER BY start_time ASC",
                $doctor_id,
                $day_of_week
            ), ARRAY_A);
        }

        // Smart auto-fallback: If no specific schedule was explicitly configured by admin yet,
        // use clinic opening/closing hours and doctor appointment duration so patients can immediately book!
        if (empty($schedules)) {
            $open = !empty($clinic['opening_time']) ? $clinic['opening_time'] : '09:00:00';
            $close = !empty($clinic['closing_time']) ? $clinic['closing_time'] : '17:00:00';
            $doc = NMBP_Doctor_Manager::get_doctor($doctor_id);
            $dur = !empty($doc['appointment_duration']) ? absint($doc['appointment_duration']) : 20;

            $schedules = [[
                'id'            => 0,
                'doctor_id'     => $doctor_id,
                'clinic_id'     => $clinic_id,
                'day_of_week'   => $day_of_week,
                'start_time'    => $open,
                'end_time'      => $close,
                'slot_duration' => $dur,
                'buffer_time'   => 5,
                'break_start'   => '13:00:00',
                'break_end'     => '14:00:00',
                'max_capacity'  => 1,
                'status'        => 'active',
            ]];
        }

        // 4. Fetch already booked/reserved appointments for this doctor on this date
        $app_table = NMBP_Database::table('appointments');
        $now_mysql = current_time('mysql');

        $booked_appointments = $wpdb->get_results($wpdb->prepare(
            "SELECT start_time, end_time, appointment_status, temp_lock_expires_at
             FROM {$app_table}
             WHERE doctor_id = %d
               AND appointment_date = %s
               AND appointment_status NOT IN ('cancelled', 'rejected', 'refunded')
               AND (temp_lock_expires_at IS NULL OR temp_lock_expires_at > %s)",
            $doctor_id,
            $formatted_date,
            $now_mysql
        ), ARRAY_A);

        $slots = [];

        foreach ($schedules as $sched) {
            $slot_duration = max(5, absint($sched['slot_duration']));
            $buffer_time = absint($sched['buffer_time']);
            $step = ($slot_duration + $buffer_time) * 60; // in seconds

            $session_start = strtotime($formatted_date . ' ' . $sched['start_time']);
            $session_end = strtotime($formatted_date . ' ' . $sched['end_time']);

            $break_start = !empty($sched['break_start']) ? strtotime($formatted_date . ' ' . $sched['break_start']) : 0;
            $break_end = !empty($sched['break_end']) ? strtotime($formatted_date . ' ' . $sched['break_end']) : 0;

            for ($t = $session_start; $t + ($slot_duration * 60) <= $session_end; $t += $step) {
                $slot_start_time = date('H:i:s', $t);
                $slot_end_time = date('H:i:s', $t + ($slot_duration * 60));
                $slot_display = date('h:i A', $t) . ' - ' . date('h:i A', $t + ($slot_duration * 60));

                // If same day, enforce minimum advance booking hours
                if ($formatted_date === $today) {
                    $slot_timestamp = $t;
                    $earliest_allowed_timestamp = strtotime("+{$min_advance_hours} hours", current_time('timestamp'));
                    if ($slot_timestamp < $earliest_allowed_timestamp) {
                        continue;
                    }
                }

                // Check break time collision
                if ($break_start && $break_end) {
                    $slot_ts_end = $t + ($slot_duration * 60);
                    if ($t < $break_end && $slot_ts_end > $break_start) {
                        continue; // Falls within doctor break
                    }
                }

                // Check conflict against booked appointments
                $is_booked = false;
                foreach ($booked_appointments as $b) {
                    $b_start = $b['start_time'];
                    $b_end = $b['end_time'];

                    if (
                        ($slot_start_time <= $b_start && $slot_end_time > $b_start) ||
                        ($slot_start_time < $b_end && $slot_end_time >= $b_end) ||
                        ($slot_start_time >= $b_start && $slot_end_time <= $b_end)
                    ) {
                        $is_booked = true;
                        break;
                    }
                }

                if (!$is_booked) {
                    $slots[] = [
                        'start_time' => $slot_start_time,
                        'end_time'   => $slot_end_time,
                        'display'    => $slot_display,
                    ];
                }
            }
        }

        return [
            'slots' => $slots,
            'total' => count($slots),
            'date'  => $formatted_date,
        ];
    }
}

