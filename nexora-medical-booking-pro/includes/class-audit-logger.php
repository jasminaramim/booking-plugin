<?php
/**
 * Security Audit Logger for Medical Booking System
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Audit_Logger {

    public static function log(
        string $entity_type,
        int $entity_id,
        string $action,
        $old_value = null,
        $new_value = null
    ): void {
        global $wpdb;
        $table = NMBP_Database::table('audit_logs');

        $user_id = get_current_user_id();
        $user_role = 'guest';

        if ($user_id > 0) {
            $user = get_userdata($user_id);
            if ($user && !empty($user->roles)) {
                $user_role = implode(', ', $user->roles);
            }
        }

        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');

        $wpdb->insert($table, [
            'entity_type' => sanitize_text_field($entity_type),
            'entity_id'   => absint($entity_id),
            'user_id'     => $user_id > 0 ? absint($user_id) : null,
            'user_role'   => sanitize_text_field($user_role),
            'action'      => sanitize_text_field($action),
            'old_value'   => is_null($old_value) ? null : (is_string($old_value) ? $old_value : wp_json_encode($old_value)),
            'new_value'   => is_null($new_value) ? null : (is_string($new_value) ? $new_value : wp_json_encode($new_value)),
            'ip_address'  => sanitize_text_field($ip),
            'user_agent'  => sanitize_text_field(substr($ua, 0, 255)),
            'created_at'  => current_time('mysql'),
        ]);
    }
}

