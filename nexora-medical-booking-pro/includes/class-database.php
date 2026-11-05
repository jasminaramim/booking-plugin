<?php
/**
 * Database manager and schema definition
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Database {

    private static ?NMBP_Database $instance = null;

    public static function instance(): NMBP_Database {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get prefixed table name
     */
    public static function table(string $name): string {
        global $wpdb;
        return $wpdb->prefix . NMBP_PREFIX . $name;
    }

    /**
     * Get SQL definitions for all dedicated tables (dbDelta & Direct MySQL compatible)
     */
    public static function get_schema_sql(): array {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $clinics_table = self::table('clinics');
        $departments_table = self::table('departments');
        $doctors_table = self::table('doctors');
        $doctor_clinics_table = self::table('doctor_clinics');
        $schedules_table = self::table('schedules');
        $leaves_table = self::table('leaves');
        $patients_table = self::table('patients');
        $appointments_table = self::table('appointments');
        $payments_table = self::table('payments');
        $notifications_table = self::table('notifications');
        $audit_logs_table = self::table('audit_logs');

        return [
            "CREATE TABLE IF NOT EXISTS {$clinics_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                name varchar(255) NOT NULL,
                slug varchar(255) NOT NULL,
                logo_url text NULL,
                cover_image_url text NULL,
                email varchar(191) NOT NULL,
                phone varchar(60) NOT NULL,
                address text NOT NULL,
                city varchar(100) NULL,
                state varchar(100) NULL,
                postal_code varchar(30) NULL,
                country varchar(100) NULL,
                map_latitude decimal(10,8) NULL,
                map_longitude decimal(11,8) NULL,
                opening_time time NOT NULL DEFAULT '08:00:00',
                closing_time time NOT NULL DEFAULT '20:00:00',
                weekly_holidays text NULL,
                default_fee decimal(10,2) NOT NULL DEFAULT '0.00',
                receptionist_user_ids text NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_slug (slug),
                KEY idx_status (status),
                KEY idx_city (city)
            ) {$charset_collate};",

            "CREATE TABLE IF NOT EXISTS {$departments_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                name varchar(255) NOT NULL,
                slug varchar(255) NOT NULL,
                description text NULL,
                icon varchar(100) DEFAULT 'medical',
                image_url text NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_slug (slug),
                KEY idx_status (status)
            ) {$charset_collate};",

            "CREATE TABLE IF NOT EXISTS {$doctors_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                wp_user_id bigint(20) unsigned NULL,
                department_id bigint(20) unsigned NOT NULL,
                first_name varchar(120) NOT NULL,
                last_name varchar(120) NOT NULL,
                email varchar(191) NOT NULL,
                phone varchar(60) NOT NULL,
                photo_url text NULL,
                specialization varchar(255) NOT NULL,
                qualifications text NULL,
                experience_years int(11) NOT NULL DEFAULT 0,
                bmdc_reg_number varchar(100) NULL,
                biography text NULL,
                consultation_fee decimal(10,2) NOT NULL DEFAULT '0.00',
                appointment_duration int(11) NOT NULL DEFAULT 20,
                break_time_start time NULL,
                break_time_end time NULL,
                max_patients_per_session int(11) NOT NULL DEFAULT 20,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_department (department_id),
                KEY idx_wp_user (wp_user_id),
                KEY idx_status (status),
                KEY idx_email (email)
            ) {$charset_collate};",

            "CREATE TABLE IF NOT EXISTS {$doctor_clinics_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                doctor_id bigint(20) unsigned NOT NULL,
                clinic_id bigint(20) unsigned NOT NULL,
                consultation_fee decimal(10,2) NULL,
                room_number varchar(60) NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_doc_clinic (doctor_id, clinic_id),
                KEY idx_clinic_id (clinic_id),
                KEY idx_doctor_id (doctor_id)
            ) {$charset_collate};",

            "CREATE TABLE IF NOT EXISTS {$schedules_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                doctor_id bigint(20) unsigned NOT NULL,
                clinic_id bigint(20) unsigned NOT NULL,
                day_of_week varchar(20) NOT NULL,
                start_time time NOT NULL,
                end_time time NOT NULL,
                slot_duration int(11) NOT NULL DEFAULT 20,
                buffer_time int(11) NOT NULL DEFAULT 5,
                break_start time NULL,
                break_end time NULL,
                max_capacity int(11) NOT NULL DEFAULT 20,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_doc_clinic_day (doctor_id, clinic_id, day_of_week),
                KEY idx_clinic (clinic_id)
            ) {$charset_collate};",

            "CREATE TABLE IF NOT EXISTS {$leaves_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                doctor_id bigint(20) unsigned NOT NULL,
                clinic_id bigint(20) unsigned NULL,
                start_date date NOT NULL,
                end_date date NOT NULL,
                reason varchar(255) NULL,
                status varchar(20) NOT NULL DEFAULT 'approved',
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_doc_dates (doctor_id, start_date, end_date)
            ) {$charset_collate};",

            "CREATE TABLE IF NOT EXISTS {$patients_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                wp_user_id bigint(20) unsigned NULL,
                first_name varchar(120) NOT NULL,
                last_name varchar(120) NOT NULL,
                email varchar(191) NOT NULL,
                phone varchar(60) NOT NULL,
                gender varchar(20) NULL,
                date_of_birth date NULL,
                blood_group varchar(10) NULL,
                address text NULL,
                emergency_contact varchar(100) NULL,
                medical_history_notes text NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_wp_user (wp_user_id),
                KEY idx_email (email),
                KEY idx_phone (phone)
            ) {$charset_collate};",

            "CREATE TABLE IF NOT EXISTS {$appointments_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                appointment_number varchar(64) NOT NULL,
                clinic_id bigint(20) unsigned NOT NULL,
                doctor_id bigint(20) unsigned NOT NULL,
                patient_id bigint(20) unsigned NOT NULL,
                department_id bigint(20) unsigned NOT NULL,
                appointment_date date NOT NULL,
                start_time time NOT NULL,
                end_time time NOT NULL,
                appointment_type varchar(30) NOT NULL DEFAULT 'in_person',
                consultation_fee decimal(10,2) NOT NULL DEFAULT '0.00',
                payment_method varchar(20) NOT NULL DEFAULT 'offline',
                payment_status varchar(30) NOT NULL DEFAULT 'unpaid',
                appointment_status varchar(30) NOT NULL DEFAULT 'pending',
                patient_notes text NULL,
                admin_notes text NULL,
                cancellation_reason text NULL,
                temp_lock_expires_at datetime NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_app_num (appointment_number),
                KEY idx_doc_date_time (doctor_id, appointment_date, start_time, end_time),
                KEY idx_clinic_date (clinic_id, appointment_date),
                KEY idx_patient (patient_id),
                KEY idx_status (appointment_status),
                KEY idx_payment_status (payment_status),
                KEY idx_date (appointment_date)
            ) {$charset_collate};",

            "CREATE TABLE IF NOT EXISTS {$payments_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                payment_number varchar(64) NOT NULL,
                appointment_id bigint(20) unsigned NOT NULL,
                patient_id bigint(20) unsigned NOT NULL,
                amount decimal(10,2) NOT NULL DEFAULT '0.00',
                currency varchar(10) NOT NULL DEFAULT 'USD',
                gateway varchar(50) NOT NULL DEFAULT 'offline_cash',
                transaction_id varchar(191) NULL,
                receipt_url text NULL,
                payment_status varchar(30) NOT NULL DEFAULT 'pending',
                verified_by_user_id bigint(20) unsigned NULL,
                verified_at datetime NULL,
                metadata text NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_pay_num (payment_number),
                KEY idx_appointment (appointment_id),
                KEY idx_patient (patient_id),
                KEY idx_tx_id (transaction_id),
                KEY idx_status (payment_status)
            ) {$charset_collate};",

            "CREATE TABLE IF NOT EXISTS {$notifications_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                appointment_id bigint(20) unsigned NULL,
                recipient_email varchar(191) NOT NULL,
                recipient_phone varchar(60) NULL,
                notification_type varchar(60) NOT NULL,
                channel varchar(20) NOT NULL DEFAULT 'email',
                subject varchar(255) NOT NULL,
                message_body text NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'sent',
                error_message text NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_recipient (recipient_email),
                KEY idx_appointment (appointment_id),
                KEY idx_type (notification_type)
            ) {$charset_collate};",

            "CREATE TABLE IF NOT EXISTS {$audit_logs_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                entity_type varchar(60) NOT NULL,
                entity_id bigint(20) unsigned NOT NULL,
                user_id bigint(20) unsigned NULL,
                user_role varchar(60) NULL,
                action varchar(60) NOT NULL,
                old_value text NULL,
                new_value text NULL,
                ip_address varchar(45) NULL,
                user_agent text NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_entity (entity_type, entity_id),
                KEY idx_action (action),
                KEY idx_user (user_id),
                KEY idx_created_at (created_at)
            ) {$charset_collate};",
        ];
    }

    /**
     * Start database transaction
     */
    public static function start_transaction(): void {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
    }

    /**
     * Commit database transaction
     */
    public static function commit(): void {
        global $wpdb;
        $wpdb->query('COMMIT');
    }

    /**
     * Rollback database transaction
     */
    public static function rollback(): void {
        global $wpdb;
        $wpdb->query('ROLLBACK');
    }

    /**
     * Check if a slot is available (Double booking prevention)
     */
    public static function is_slot_available(
        int $doctor_id,
        string $date,
        string $start_time,
        string $end_time,
        int $exclude_appointment_id = 0
    ): bool {
        global $wpdb;
        $table = self::table('appointments');

        $now = current_time('mysql');

        $sql = "SELECT COUNT(*) FROM {$table}
                WHERE doctor_id = %d
                  AND appointment_date = %s
                  AND appointment_status NOT IN ('cancelled', 'rejected', 'refunded')
                  AND (
                      (temp_lock_expires_at IS NULL)
                      OR (temp_lock_expires_at > %s)
                  )
                  AND (
                      (start_time <= %s AND end_time > %s)
                      OR (start_time < %s AND end_time >= %s)
                      OR (start_time >= %s AND end_time <= %s)
                  )";

        $params = [
            $doctor_id,
            $date,
            $now,
            $start_time,
            $start_time,
            $end_time,
            $end_time,
            $start_time,
            $end_time
        ];

        if ($exclude_appointment_id > 0) {
            $sql .= " AND id != %d";
            $params[] = $exclude_appointment_id;
        }

        $conflict_count = (int) $wpdb->get_var($wpdb->prepare($sql, $params));
        return $conflict_count === 0;
    }
}
