<?php
/**
 * REST Doctors Controller
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_REST_Doctors_Controller extends NMBP_REST_Base_Controller {

    public function register_routes(): void {
        register_rest_route($this->namespace, '/doctors', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => '__return_true', // Public for booking
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create_item'],
                'permission_callback' => [$this, 'check_doctor_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/doctors/(?P<id>\d+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_item'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_item'],
                'permission_callback' => [$this, 'check_doctor_permission'],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'delete_item'],
                'permission_callback' => [$this, 'check_doctor_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/doctors/(?P<id>\d+)/leaves', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_doctor_leaves'],
                'permission_callback' => [$this, 'check_doctor_permission'],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'add_doctor_leave'],
                'permission_callback' => [$this, 'check_doctor_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/doctors/leaves/(?P<leave_id>\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [$this, 'delete_doctor_leave'],
            'permission_callback' => [$this, 'check_doctor_permission'],
        ]);
    }

    public function get_items($request) {
        $params = is_object($request) && method_exists($request, 'get_params') ? $request->get_params() : (array)$request;
        $result = NMBP_Doctor_Manager::get_doctors($params);
        return $this->success($result);
    }

    public function get_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $doctor = NMBP_Doctor_Manager::get_doctor($id);
        if (!$doctor) {
            return $this->error(__('Doctor not found.', 'nexora-medical-booking-pro'), 404);
        }
        return $this->success($doctor);
    }

    public function create_item($request) {
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;
        if (empty($body['first_name']) || empty($body['last_name'])) {
            return $this->error(__('First name and last name are required.', 'nexora-medical-booking-pro'), 400);
        }

        $id = NMBP_Doctor_Manager::create_doctor($body);
        if (!$id) {
            return $this->error(__('Failed to create doctor.', 'nexora-medical-booking-pro'), 500);
        }

        $doctor = NMBP_Doctor_Manager::get_doctor($id);
        return $this->success($doctor, __('Doctor profile created successfully.', 'nexora-medical-booking-pro'), 201);
    }

    public function update_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;

        $updated = NMBP_Doctor_Manager::update_doctor($id, $body);
        if (!$updated) {
            return $this->error(__('Failed to update doctor or no changes made.', 'nexora-medical-booking-pro'), 400);
        }

        $doctor = NMBP_Doctor_Manager::get_doctor($id);
        return $this->success($doctor, __('Doctor updated successfully.', 'nexora-medical-booking-pro'));
    }

    public function delete_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $deleted = NMBP_Doctor_Manager::delete_doctor($id);
        if (!$deleted) {
            return $this->error(__('Failed to delete doctor.', 'nexora-medical-booking-pro'), 400);
        }
        return $this->success(null, __('Doctor deleted successfully.', 'nexora-medical-booking-pro'));
    }

    public function get_doctor_leaves($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $leaves = NMBP_Doctor_Manager::get_doctor_leaves($id);
        return $this->success($leaves);
    }

    public function add_doctor_leave($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;
        $body['doctor_id'] = $id;

        if (empty($body['start_date']) || empty($end_date = $body['end_date'] ?? '')) {
            return $this->error(__('Start date and end date are required.', 'nexora-medical-booking-pro'), 400);
        }

        $leave_id = NMBP_Doctor_Manager::add_doctor_leave($body);
        return $this->success(['id' => $leave_id], __('Leave recorded successfully.', 'nexora-medical-booking-pro'));
    }

    public function delete_doctor_leave($request) {
        $leave_id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('leave_id') : ($request['leave_id'] ?? 0));
        $deleted = NMBP_Doctor_Manager::delete_doctor_leave($leave_id);
        return $deleted ? $this->success(null, __('Leave deleted.', 'nexora-medical-booking-pro')) : $this->error(__('Failed to delete leave.', 'nexora-medical-booking-pro'));
    }
}

