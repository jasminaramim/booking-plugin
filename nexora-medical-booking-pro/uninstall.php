<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

// Only remove database tables and options if the explicit setting is enabled by an administrator
$delete_data = get_option('nmbp_delete_data_on_uninstall', 'no');

if ($delete_data === 'yes' || $delete_data === '1' || $delete_data === true) {
    global $wpdb;

    $tables = [
        $wpdb->prefix . 'nmbp_audit_logs',
        $wpdb->prefix . 'nmbp_notifications',
        $wpdb->prefix . 'nmbp_payments',
        $wpdb->prefix . 'nmbp_appointments',
        $wpdb->prefix . 'nmbp_patients',
        $wpdb->prefix . 'nmbp_leaves',
        $wpdb->prefix . 'nmbp_schedules',
        $wpdb->prefix . 'nmbp_doctor_clinics',
        $wpdb->prefix . 'nmbp_doctors',
        $wpdb->prefix . 'nmbp_departments',
        $wpdb->prefix . 'nmbp_clinics',
    ];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    // Delete plugin options
    $options = [
        'nmbp_db_version',
        'nmbp_general_settings',
        'nmbp_clinic_settings',
        'nmbp_doctor_settings',
        'nmbp_appointment_settings',
        'nmbp_schedule_settings',
        'nmbp_payment_settings',
        'nmbp_email_settings',
        'nmbp_notification_settings',
        'nmbp_design_settings',
        'nmbp_privacy_settings',
        'nmbp_delete_data_on_uninstall',
    ];

    foreach ($options as $option) {
        delete_option($option);
    }
}

