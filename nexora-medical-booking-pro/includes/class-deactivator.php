<?php
/**
 * Fired during plugin deactivation
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Deactivator {

    public static function deactivate(): void {
        // Clear scheduled events
        $timestamp = wp_next_scheduled('nmbp_hourly_cleanup_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'nmbp_hourly_cleanup_event');
        }

        // Release all temporary locks in appointments table
        global $wpdb;
        $table = NMBP_Database::table('appointments');
        $wpdb->query("UPDATE {$table} SET temp_lock_expires_at = NULL WHERE appointment_status = 'pending'");
    }
}

