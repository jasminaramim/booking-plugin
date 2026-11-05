<?php
/**
 * REST Patients Controller
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_REST_Patients_Controller extends NMBP_REST_Base_Controller {

    public function register_routes(): void {
        register_rest_route($this->namespace, '/patients', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_items'],
            'permission_callback' => [$this, 'check_appointment_view_permission'],
        ]);

        register_rest_route($this->namespace, '/patients/(?P<id>\d+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_item'],
                'permission_callback' => [$this, 'check_appointment_view_permission'],
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_item'],
                'permission_callback' => [$this, 'check_appointment_manage_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/patient/me', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_my_profile'],
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'update_my_profile'],
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            ],
        ]);
    }

    public function get_items($request) {
        $params = is_object($request) && method_exists($request, 'get_params') ? $request->get_params() : (array)$request;
        $res = NMBP_Patient_Manager::get_patients($params);
        return $this->success($res);
    }

    public function get_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $patient = NMBP_Patient_Manager::get_patient($id);
        if (!$patient) {
            return $this->error(__('Patient not found.', 'nexora-medical-booking-pro'), 404);
        }
        return $this->success($patient);
    }

    public function update_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;
        $res = NMBP_Patient_Manager::update_patient($id, $body);
        return $res ? $this->success(null, __('Patient updated successfully.', 'nexora-medical-booking-pro')) : $this->error(__('Failed to update patient.', 'nexora-medical-booking-pro'));
    }

    public function get_my_profile($request) {
        $user_id = get_current_user_id();
        $patient_id = NMBP_Permissions::get_patient_id_by_wp_user($user_id);
        if (!$patient_id) {
            return $this->error(__('No patient profile linked with this user.', 'nexora-medical-booking-pro'), 404);
        }
        $patient = NMBP_Patient_Manager::get_patient($patient_id);
        return $this->success($patient);
    }

    public function update_my_profile($request) {
        $user_id = get_current_user_id();
        $patient_id = NMBP_Permissions::get_patient_id_by_wp_user($user_id);
        if (!$patient_id) {
            return $this->error(__('No patient profile linked with this user.', 'nexora-medical-booking-pro'), 404);
        }
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;
        $res = NMBP_Patient_Manager::update_patient($patient_id, $body);
        return $res ? $this->success(null, __('Profile updated successfully.', 'nexora-medical-booking-pro')) : $this->error(__('Failed to update profile.', 'nexora-medical-booking-pro'));
    }
}

