<?php
/**
 * REST Payments Controller
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_REST_Payments_Controller extends NMBP_REST_Base_Controller {

    public function register_routes(): void {
        register_rest_route($this->namespace, '/payments', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_items'],
            'permission_callback' => [$this, 'check_appointment_view_permission'],
        ]);

        register_rest_route($this->namespace, '/payments/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_item'],
            'permission_callback' => [$this, 'check_appointment_view_permission'],
        ]);

        register_rest_route($this->namespace, '/payments/(?P<id>\d+)/verify', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'verify_item'],
            'permission_callback' => function () {
                return current_user_can('manage_options')
                    || current_user_can(NMBP_Permissions::CAP_VERIFY_PAYMENTS)
                    || current_user_can(NMBP_Permissions::CAP_MANAGE_PAYMENTS);
            },
        ]);

        register_rest_route($this->namespace, '/payments/(?P<id>\d+)/refund', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'refund_item'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    public function get_items($request) {
        $params = is_object($request) && method_exists($request, 'get_params') ? $request->get_params() : (array)$request;
        $res = NMBP_Payment_Manager::get_payments($params);
        return $this->success($res);
    }

    public function get_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $payment = NMBP_Payment_Manager::get_payment($id);
        if (!$payment) {
            return $this->error(__('Payment record not found.', 'nexora-medical-booking-pro'), 404);
        }
        return $this->success($payment);
    }

    public function verify_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;
        $notes = sanitize_textarea_field($body['notes'] ?? '');

        $verified = NMBP_Payment_Manager::verify_payment($id, get_current_user_id(), $notes);
        if (!$verified) {
            return $this->error(__('Failed to verify payment.', 'nexora-medical-booking-pro'), 400);
        }

        return $this->success(null, __('Payment verified successfully as PAID.', 'nexora-medical-booking-pro'));
    }

    public function refund_item($request) {
        $id = absint(is_object($request) && method_exists($request, 'get_param') ? $request->get_param('id') : ($request['id'] ?? 0));
        $body = is_object($request) && method_exists($request, 'get_json_params') ? ($request->get_json_params() ?: $request->get_params()) : (array)$request;
        $reason = sanitize_textarea_field($body['reason'] ?? '');

        $refunded = NMBP_Payment_Manager::refund_payment($id, $reason);
        if (!$refunded) {
            return $this->error(__('Failed to process refund.', 'nexora-medical-booking-pro'), 400);
        }

        return $this->success(null, __('Payment refunded successfully.', 'nexora-medical-booking-pro'));
    }
}

