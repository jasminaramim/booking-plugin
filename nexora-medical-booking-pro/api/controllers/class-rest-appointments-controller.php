<?php
/**
 * REST Appointments Controller
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_REST_Appointments_Controller extends NMBP_REST_Base_Controller {

    public function register_routes(): void {
        register_rest_route($this->namespace, '/appointments', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => [$this, 'check_appointment_view_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_item'],
                'permission_callback' => [$this, 'check_appointment_view_permission'],
            ],
        ]);

        // Public booking submission
        register_rest_route($this->namespace, '/booking/submit', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'submit_booking'],
            'permission_callback' => '__return_true', // Public
        ]);

        // Approval workflow
        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)/approve', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'approve_item'],
            'permission_callback' => [$this, 'check_appointment_manage_permission'],
        ]);

        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)/reject', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'reject_item'],
            'permission_callback' => [$this, 'check_appointment_manage_permission'],
        ]);

        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)/cancel', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'cancel_item'],
            'permission_callback' => '__return_true', // Protected inside for patient check
        ]);

        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)/reschedule', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'reschedule_item'],
            'permission_callback' => [$this, 'check_appointment_manage_permission'],
        ]);

        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)/status', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'update_status'],
            'permission_callback' => [$this, 'check_appointment_manage_permission'],
        ]);

        // Patient Portal: My Appointments
        register_rest_route($this->namespace, '/patient/my-appointments', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_patient_appointments'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ]);

        // Doctor Portal: My Appointments
        register_rest_route($this->namespace, '/doctor/my-appointments', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_doctor_appointments'],
            'permission_callback' => function () {
                return is_user_logged_in() && NMBP_Permissions::get_doctor_id_by_wp_user(get_current_user_id());
            },
        ]);
    }

    public function get_items($request) {
        $params = is_object($request) && method_exists($request, 'get_params') ? $request->get_params() : (array)$request;
        $res = NMBP_Appointment_Manager::get_appointments($params);
        return $this->success($res);
    }

    public function get_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $app = NMBP_Appointment_Manager::get_appointment($id);
        if (!$app) {
            return $this->error(__('Appointment not found.', 'nexora-medical-booking-pro'), 404);
        }
        return $this->success($app);
    }

    public function submit_booking($request) {
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;

        // Anti-spam honeypot check
        if (!empty($body['fax_number']) || !empty($body['website_trap'])) {
            return $this->error(__('Spam detected.', 'nexora-medical-booking-pro'), 403);
        }

        $result = NMBP_Appointment_Manager::create_appointment($body);
        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success($result, $result['message'], 201);
    }

    public function approve_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;
        $notes = sanitize_textarea_field($body['admin_notes'] ?? '');

        $res = NMBP_Appointment_Manager::approve_appointment($id, $notes);
        if (!$res['success']) {
            return $this->error($res['message'], 400);
        }

        return $this->success(null, $res['message']);
    }

    public function reject_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;
        $reason = sanitize_textarea_field($body['reason'] ?? '');

        $res = NMBP_Appointment_Manager::reject_appointment($id, $reason);
        if (!$res['success']) {
            return $this->error($res['message'], 400);
        }

        return $this->success(null, $res['message']);
    }

    public function cancel_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;
        $reason = sanitize_textarea_field($body['reason'] ?? '');

        // If patient is cancelling, verify ownership
        $user_id = get_current_user_id();
        if (!current_user_can('manage_options') && !current_user_can(NMBP_Permissions::CAP_MANAGE_APPOINTMENTS)) {
            $patient_id = NMBP_Permissions::get_patient_id_by_wp_user($user_id);
            $app = NMBP_Appointment_Manager::get_appointment($id);
            if (!$app || (int) $app['patient_id'] !== $patient_id) {
                return $this->error(__('You are not authorized to cancel this appointment.', 'nexora-medical-booking-pro'), 403);
            }
        }

        $res = NMBP_Appointment_Manager::cancel_appointment($id, $reason);
        if (!$res['success']) {
            return $this->error($res['message'], 400);
        }

        return $this->success(null, $res['message']);
    }

    public function reschedule_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;

        $date = sanitize_text_field($body['appointment_date'] ?? '');
        $start = sanitize_text_field($body['start_time'] ?? '');
        $end = sanitize_text_field($body['end_time'] ?? '');

        if (empty($date) || empty($start) || empty($end)) {
            return $this->error(__('Date, start time, and end time are required.', 'nexora-medical-booking-pro'), 400);
        }

        $res = NMBP_Appointment_Manager::reschedule_appointment($id, $date, $start, $end);
        if (!$res['success']) {
            return $this->error($res['message'], 400);
        }

        return $this->success(null, $res['message']);
    }

    public function update_status($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;
        $status = sanitize_text_field($body['status'] ?? '');
        $notes = sanitize_textarea_field($body['notes'] ?? '');

        $updated = NMBP_Appointment_Manager::update_status($id, $status, $notes);
        if (!$updated) {
            return $this->error(__('Failed to update status.', 'nexora-medical-booking-pro'), 400);
        }

        return $this->success(null, __('Status updated successfully.', 'nexora-medical-booking-pro'));
    }

    public function get_patient_appointments($request) {
        $user_id = get_current_user_id();
        $patient_id = NMBP_Permissions::get_patient_id_by_wp_user($user_id);

        if (!$patient_id) {
            return $this->success(['items' => [], 'total' => 0]);
        }

        $res = NMBP_Appointment_Manager::get_appointments([
            'patient_id' => $patient_id,
            'per_page'   => 50,
        ]);

        return $this->success($res);
    }

    public function get_doctor_appointments($request) {
        $user_id = get_current_user_id();
        $doctor_id = NMBP_Permissions::get_doctor_id_by_wp_user($user_id);

        $params = is_object($request) && method_exists($request, 'get_params') ? $request->get_params() : (array)$request;
        $params['doctor_id'] = $doctor_id;

        $res = NMBP_Appointment_Manager::get_appointments($params);
        return $this->success($res);
    }
}

