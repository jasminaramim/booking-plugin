<?php
/**
 * Notification Orchestrator
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Notification_Manager {

    /**
     * Dispatch notification event
     */
    public static function trigger_event(string $event_name, int $appointment_id): void {
        $appointment = NMBP_Appointment_Manager::get_appointment($appointment_id);
        if (!$appointment) {
            return;
        }

        // Email dispatch
        NMBP_Email_Manager::send_event_email($event_name, $appointment);

        // SMS / WhatsApp adapter hook
        do_action('nmbp_notification_event', $event_name, $appointment);
    }

    /**
     * Log a sent notification
     */
    public static function log_notification(array $data): int {
        global $wpdb;
        $table = NMBP_Database::table('notifications');

        $wpdb->insert($table, [
            'appointment_id'    => !empty($data['appointment_id']) ? absint($data['appointment_id']) : null,
            'recipient_email'   => sanitize_email($data['recipient_email'] ?? ''),
            'recipient_phone'   => sanitize_text_field($data['recipient_phone'] ?? ''),
            'notification_type' => sanitize_text_field($data['notification_type'] ?? 'general'),
            'channel'           => sanitize_text_field($data['channel'] ?? 'email'),
            'subject'           => sanitize_text_field($data['subject'] ?? ''),
            'message_body'      => wp_kses_post($data['message_body'] ?? ''),
            'status'            => sanitize_text_field($data['status'] ?? 'sent'),
            'error_message'     => !empty($data['error_message']) ? sanitize_text_field($data['error_message']) : null,
            'created_at'        => current_time('mysql'),
        ]);

        return (int) $wpdb->insert_id;
    }
}

