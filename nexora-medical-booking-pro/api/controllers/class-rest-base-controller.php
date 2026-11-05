<?php
/**
 * REST Base Controller
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

abstract class NMBP_REST_Base_Controller {

    protected $namespace = NMBP_REST_API::NAMESPACE;

    /**
     * Check if user is administrator or has system management capabilities
     */
    public function check_admin_permission($request): bool {
        return current_user_can('manage_options') || current_user_can(NMBP_Permissions::CAP_MANAGE_SYSTEM);
    }

    /**
     * Check if user has appointment management permission
     */
    public function check_appointment_manage_permission($request): bool {
        return current_user_can(NMBP_Permissions::CAP_MANAGE_APPOINTMENTS)
            || current_user_can('manage_options');
    }

    /**
     * Check if user can view appointments
     */
    public function check_appointment_view_permission($request): bool {
        return current_user_can(NMBP_Permissions::CAP_VIEW_APPOINTMENTS)
            || current_user_can('manage_options');
    }

    /**
     * Check if user can manage clinics
     */
    public function check_clinic_permission($request): bool {
        return current_user_can(NMBP_Permissions::CAP_MANAGE_CLINICS)
            || current_user_can('manage_options');
    }

    /**
     * Check if user can manage doctors
     */
    public function check_doctor_permission($request): bool {
        return current_user_can(NMBP_Permissions::CAP_MANAGE_DOCTORS)
            || current_user_can('manage_options');
    }

    /**
     * Check if user can manage schedules
     */
    public function check_schedule_permission($request): bool {
        return current_user_can(NMBP_Permissions::CAP_MANAGE_SCHEDULES)
            || current_user_can('manage_options');
    }

    /**
     * Success JSON response
     */
    protected function success($data = [], string $message = '', int $status = 200): WP_REST_Response {
        return new WP_REST_Response([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $status);
    }

    /**
     * Error JSON response
     */
    protected function error(string $message, int $status = 400, $data = null): WP_REST_Response {
        return new WP_REST_Response([
            'success' => false,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }
}

