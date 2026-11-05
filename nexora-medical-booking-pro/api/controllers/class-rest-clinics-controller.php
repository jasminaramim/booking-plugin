<?php
/**
 * REST Clinics Controller
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_REST_Clinics_Controller extends NMBP_REST_Base_Controller {

    public function register_routes(): void {
        register_rest_route($this->namespace, '/clinics', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => '__return_true', // Public for booking frontend
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create_item'],
                'permission_callback' => [$this, 'check_clinic_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/clinics/(?P<id>\d+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_item'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_item'],
                'permission_callback' => [$this, 'check_clinic_permission'],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'delete_item'],
                'permission_callback' => [$this, 'check_clinic_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/clinics/(?P<id>\d+)/doctors', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_clinic_doctors'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function get_items($request) {
        $params = is_object($request) && method_exists($request, 'get_params') ? $request->get_params() : (array)$request;
        $result = NMBP_Clinic_Manager::get_clinics($params);
        return $this->success($result);
    }

    public function get_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $clinic = NMBP_Clinic_Manager::get_clinic($id);
        if (!$clinic) {
            return $this->error(__('Clinic not found.', 'nexora-medical-booking-pro'), 404);
        }
        return $this->success($clinic);
    }

    public function create_item($request) {
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;
        if (empty($body['name'])) {
            return $this->error(__('Clinic name is required.', 'nexora-medical-booking-pro'), 400);
        }

        $id = NMBP_Clinic_Manager::create_clinic($body);
        if (!$id) {
            return $this->error(__('Failed to create clinic.', 'nexora-medical-booking-pro'), 500);
        }

        $clinic = NMBP_Clinic_Manager::get_clinic($id);
        return $this->success($clinic, __('Clinic created successfully.', 'nexora-medical-booking-pro'), 201);
    }

    public function update_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;

        $updated = NMBP_Clinic_Manager::update_clinic($id, $body);
        if (!$updated) {
            return $this->error(__('Failed to update clinic or no changes made.', 'nexora-medical-booking-pro'), 400);
        }

        $clinic = NMBP_Clinic_Manager::get_clinic($id);
        return $this->success($clinic, __('Clinic updated successfully.', 'nexora-medical-booking-pro'));
    }

    public function delete_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $deleted = NMBP_Clinic_Manager::delete_clinic($id);
        if (!$deleted) {
            return $this->error(__('Failed to delete clinic.', 'nexora-medical-booking-pro'), 400);
        }
        return $this->success(null, __('Clinic deleted successfully.', 'nexora-medical-booking-pro'));
    }

    public function get_clinic_doctors($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $doctors = NMBP_Clinic_Manager::get_clinic_doctors($id);
        return $this->success($doctors);
    }
}

