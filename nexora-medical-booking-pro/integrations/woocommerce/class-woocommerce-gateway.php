<?php
/**
 * WooCommerce Payment Gateway Integration
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_WooCommerce_Gateway {

    private static ?NMBP_WooCommerce_Gateway $instance = null;

    public static function instance(): NMBP_WooCommerce_Gateway {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        add_action('woocommerce_payment_complete', [$this, 'handle_wc_payment_completed']);
        add_action('woocommerce_order_status_completed', [$this, 'handle_wc_payment_completed']);
        add_action('woocommerce_order_status_processing', [$this, 'handle_wc_payment_completed']);
        add_action('woocommerce_order_status_refunded', [$this, 'handle_wc_order_refunded']);
    }

    /**
     * Get or create virtual WooCommerce booking service product
     */
    public static function get_or_create_booking_product(): int {
        if (!class_exists('WC_Product_Simple')) {
            return 0;
        }

        $product_id = (int) get_option('nmbp_wc_product_id', 0);
        if ($product_id && get_post($product_id)) {
            return $product_id;
        }

        $product = new WC_Product_Simple();
        $product->set_name(__('Medical Consultation Booking', 'nexora-medical-booking-pro'));
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true);
        $product->set_price(0);
        $product->set_regular_price(0);
        $product_id = $product->save();

        update_option('nmbp_wc_product_id', $product_id);
        return $product_id;
    }

    /**
     * Create WooCommerce checkout order for an appointment
     */
    public static function create_checkout_order_for_appointment(int $appointment_id): ?string {
        if (!class_exists('WC_Order')) {
            return null;
        }

        $app = NMBP_Appointment_Manager::get_appointment($appointment_id);
        if (!$app) {
            return null;
        }

        $product_id = self::get_or_create_booking_product();
        if (!$product_id) {
            return null;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return null;
        }

        $fee = (float) $app['consultation_fee'];

        // Create WooCommerce order
        $order = wc_create_order();
        $item_id = $order->add_product($product, 1, [
            'subtotal' => $fee,
            'total'    => $fee,
        ]);

        $order->set_address([
            'first_name' => $app['patient_first_name'],
            'last_name'  => $app['patient_last_name'],
            'email'      => $app['patient_email'],
            'phone'      => $app['patient_phone'],
        ], 'billing');

        $order->update_meta_data('_nmbp_appointment_id', $appointment_id);
        $order->update_meta_data('_nmbp_appointment_number', $app['appointment_number']);
        $order->calculate_totals();
        $order->save();

        return $order->get_checkout_payment_url();
    }

    /**
     * Handle payment completion from WooCommerce
     */
    public function handle_wc_payment_completed(int $order_id): void {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $appointment_id = (int) $order->get_meta('_nmbp_appointment_id');
        if (!$appointment_id) {
            return;
        }

        global $wpdb;
        $payments_table = NMBP_Database::table('payments');
        $app_table = NMBP_Database::table('appointments');

        $tx_id = $order->get_transaction_id() ?: ('WC-' . $order_id);

        $now = current_time('mysql');

        // Update payment record
        $wpdb->update($payments_table, [
            'payment_status' => 'paid',
            'transaction_id' => sanitize_text_field($tx_id),
            'verified_at'    => $now,
            'updated_at'     => $now,
        ], ['appointment_id' => $appointment_id]);

        // Update appointment payment status
        $wpdb->update($app_table, [
            'payment_status' => 'paid',
            'updated_at'     => $now,
        ], ['id' => $appointment_id]);

        NMBP_Audit_Logger::log('payment', $appointment_id, 'wc_payment_completed', null, ['order_id' => $order_id, 'tx_id' => $tx_id]);
        NMBP_Notification_Manager::trigger_event('payment_received', $appointment_id);
    }

    /**
     * Handle order refund
     */
    public function handle_wc_order_refunded(int $order_id): void {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $appointment_id = (int) $order->get_meta('_nmbp_appointment_id');
        if (!$appointment_id) return;

        global $wpdb;
        $payment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . NMBP_Database::table('payments') . " WHERE appointment_id = %d LIMIT 1",
            $appointment_id
        ));

        if ($payment_id) {
            NMBP_Payment_Manager::refund_payment((int) $payment_id, 'WooCommerce order refunded');
        }
    }
}

