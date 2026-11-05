<?php
/**
 * Fired during plugin activation
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Activator {

    public static function activate(): void {
        // 1. Run migrations and create/update tables
        NMBP_Migrations::run_migrations();

        // 2. Register roles and capabilities
        NMBP_Permissions::register_roles_and_caps();

        // 3. Schedule cron for periodic lock and log cleanup
        if (!wp_next_scheduled('nmbp_hourly_cleanup_event')) {
            wp_schedule_event(time(), 'hourly', 'nmbp_hourly_cleanup_event');
        }

        // Set activation flag
        update_option('nmbp_just_activated', true);
    }
}

