<?php
/**
 * REST Dashboard & Analytics Controller
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_REST_Dashboard_Controller extends NMBP_REST_Base_Controller {

    public function register_routes(): void {
        register_rest_route($this->namespace, '/dashboard/stats', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_dashboard_stats'],
            'permission_callback' => [$this, 'check_appointment_view_permission'],
        ]);

        register_rest_route($this->namespace, '/dashboard/calendar', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_calendar_events'],
            'permission_callback' => [$this, 'check_appointment_view_permission'],
        ]);
    }

    /**
     * Compute comprehensive medical dashboard stats
     */
    public function get_dashboard_stats($request) {
        global $wpdb;

        $clinics_table = NMBP_Database::table('clinics');
        $doctors_table = NMBP_Database::table('doctors');
        $patients_table = NMBP_Database::table('patients');
        $app_table = NMBP_Database::table('appointments');
        $payments_table = NMBP_Database::table('payments');

        $today = current_time('Y-m-d');
        $month_start = current_time('Y-m-01');

        $total_clinics = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$clinics_table} WHERE status = 'active'");
        $total_doctors = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$doctors_table} WHERE status = 'active'");
        $total_patients = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$patients_table} WHERE status = 'active'");

        $today_bookings = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$app_table} WHERE appointment_date = %s",
            $today
        ));

        $pending_approvals = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$app_table} WHERE appointment_status = 'pending'");
        $confirmed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$app_table} WHERE appointment_status = 'confirmed'");
        $completed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$app_table} WHERE appointment_status = 'completed'");
        $cancelled = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$app_table} WHERE appointment_status = 'cancelled'");

        // Revenue calculations (paid only)
        $today_revenue = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$payments_table} WHERE payment_status = 'paid' AND DATE(created_at) = %s",
            $today
        )) ?: 0.00;

        $monthly_revenue = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$payments_table} WHERE payment_status = 'paid' AND created_at >= %s",
            $month_start
        )) ?: 0.00;

        $total_revenue = (float) $wpdb->get_var(
            "SELECT SUM(amount) FROM {$payments_table} WHERE payment_status = 'paid'"
        ) ?: 0.00;

        // Branch-wise stats
        $branch_stats = $wpdb->get_results(
            "SELECT c.id, c.name, COUNT(a.id) as total_appointments,
                    COALESCE(SUM(CASE WHEN a.payment_status = 'paid' THEN a.consultation_fee ELSE 0 END), 0) as total_revenue
             FROM {$clinics_table} c
             LEFT JOIN {$app_table} a ON c.id = a.clinic_id
             GROUP BY c.id, c.name
             ORDER BY total_appointments DESC",
            ARRAY_A
        );

        // Recent bookings (latest 8)
        $recent_bookings = NMBP_Appointment_Manager::get_appointments([
            'per_page' => 8,
            'page'     => 1,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        ])['items'];

        return $this->success([
            'counts' => [
                'clinics'           => $total_clinics,
                'doctors'           => $total_doctors,
                'patients'          => $total_patients,
                'today_bookings'    => $today_bookings,
                'pending_approvals' => $pending_approvals,
                'confirmed'         => $confirmed,
                'completed'         => $completed,
                'cancelled'         => $cancelled,
            ],
            'revenue' => [
                'today'   => $today_revenue,
                'monthly' => $monthly_revenue,
                'total'   => $total_revenue,
            ],
            'branch_stats'    => $branch_stats,
            'recent_bookings' => $recent_bookings,
        ]);
    }

    /**
     * Get calendar events for appointment calendar
     */
    public function get_calendar_events($request) {
        $start = sanitize_text_field(is_object($request) && method_exists($request, 'get_param') ? ($request->get_param('start') ?? current_time('Y-m-01')) : ($request['start'] ?? current_time('Y-m-01')));
        $end = sanitize_text_field(is_object($request) && method_exists($request, 'get_param') ? ($request->get_param('end') ?? date('Y-m-t', current_time('timestamp'))) : ($request['end'] ?? date('Y-m-t', current_time('timestamp'))));

        $res = NMBP_Appointment_Manager::get_appointments([
            'date_from' => $start,
            'date_to'   => $end,
            'per_page'  => 500,
        ]);

        $events = [];
        foreach ($res['items'] as $item) {
            $events[] = [
                'id'                 => $item['id'],
                'title'              => $item['patient_first_name'] . ' ' . $item['patient_last_name'] . ' (Dr. ' . $item['doctor_first_name'] . ')',
                'start'              => $item['appointment_date'] . 'T' . $item['start_time'],
                'end'                => $item['appointment_date'] . 'T' . $item['end_time'],
                'status'             => $item['appointment_status'],
                'appointment_number' => $item['appointment_number'],
                'doctor'             => 'Dr. ' . $item['doctor_first_name'] . ' ' . $item['doctor_last_name'],
                'clinic'             => $item['clinic_name'],
            ];
        }

        return $this->success($events);
    }
}

