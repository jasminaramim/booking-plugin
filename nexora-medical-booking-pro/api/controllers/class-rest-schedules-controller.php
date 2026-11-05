<?php
/**
 * REST Schedules & Booking Slots Controller
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_REST_Schedules_Controller extends NMBP_REST_Base_Controller {

    public function register_routes(): void {
        register_rest_route($this->namespace, '/schedules/doctor/(?P<doctor_id>\d+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_doctor_schedules'],
                'permission_callback' => [$this, 'check_schedule_permission'],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'save_doctor_schedules'],
                'permission_callback' => [$this, 'check_schedule_permission'],
            ],
        ]);

        // Public slot generator for frontend booking engine
        register_rest_route($this->namespace, '/booking/slots', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_available_slots'],
            'permission_callback' => '__return_true', // Publicly accessible with rate limit
        ]);
    }

    public function get_doctor_schedules($request) {
        $doctor_id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('doctor_id') : ($request['doctor_id'] ?? 0));
        $clinic_id = absint(is_object($request) && method_exists($request, 'get_param') ? ($request->get_param('clinic_id') ?? 0) : ($request['clinic_id'] ?? 0));
        $schedules = NMBP_Schedule_Manager::get_schedules($doctor_id, $clinic_id);
        return $this->success($schedules);
    }

    public function save_doctor_schedules($request) {
        $doctor_id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('doctor_id') : ($request['doctor_id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;

        $clinic_id = absint($body['clinic_id'] ?? 0);
        $schedules = (array) ($body['schedules'] ?? []);

        if (!$clinic_id) {
            return $this->error(__('Clinic ID is required to set schedules.', 'nexora-medical-booking-pro'), 400);
        }

        $saved = NMBP_Schedule_Manager::save_doctor_schedules($doctor_id, $clinic_id, $schedules);
        if (!$saved) {
            return $this->error(__('Failed to save schedules.', 'nexora-medical-booking-pro'), 500);
        }

        return $this->success(null, __('Schedules saved successfully.', 'nexora-medical-booking-pro'));
    }

    public function get_available_slots($request) {
        $doctor_id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('doctor_id') : ($request['doctor_id'] ?? 0));
        $clinic_id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('clinic_id') : ($request['clinic_id'] ?? 0));
        $date = sanitize_text_field(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('date') : ($request['date'] ?? ''));

        if (!$doctor_id || !$clinic_id || empty($date)) {
            return $this->error(__('Doctor ID, Clinic ID, and Date are required.', 'nexora-medical-booking-pro'), 400);
        }

        $res = NMBP_Schedule_Manager::get_available_slots($doctor_id, $clinic_id, $date);

        if (!empty($res['error'])) {
            return $this->error($res['error'], 422, ['slots' => []]);
        }

        return $this->success($res);
    }
}

