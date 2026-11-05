<?php
/**
 * Database migrations and seeding
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Migrations {

    public static function check_and_run_migrations(): void {
        $installed_version = get_option('nmbp_db_version', '0.0.0');

        if (version_compare($installed_version, NMBP_DB_VERSION, '<')) {
            self::run_migrations();
            update_option('nmbp_db_version', NMBP_DB_VERSION);
        }
    }

    public static function run_migrations(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $schemas = NMBP_Database::get_schema_sql();

        foreach ($schemas as $sql) {
            dbDelta($sql);
            $wpdb->query($sql);
        }

        self::seed_default_departments();
        self::seed_default_settings();
        self::seed_initial_clinic_and_doctors();
    }

    /**
     * Seed initial demo clinic and doctors if none exist
     */
    private static function seed_initial_clinic_and_doctors(): void {
        global $wpdb;
        $clinics_table = NMBP_Database::table('clinics');
        $doctors_table = NMBP_Database::table('doctors');
        $dc_table = NMBP_Database::table('doctor_clinics');
        $sched_table = NMBP_Database::table('schedules');

        $clinic_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$clinics_table}");
        $doctor_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$doctors_table}");

        if ($clinic_count === 0) {
            $wpdb->insert($clinics_table, [
                'name'                  => 'Nexora City Hospital - Central Branch',
                'slug'                  => 'nexora-central-branch',
                'logo_url'              => '',
                'cover_image_url'       => 'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=1200&q=80',
                'email'                 => 'central@nexoramedical.com',
                'phone'                 => '+1 (555) 019-2834',
                'address'               => '742 Evergreen Medical Plaza, Suite 400',
                'city'                  => 'New York',
                'state'                 => 'NY',
                'postal_code'           => '10001',
                'country'               => 'United States',
                'opening_time'          => '08:00:00',
                'closing_time'          => '20:00:00',
                'weekly_holidays'       => '[]',
                'default_fee'           => 100.00,
                'receptionist_user_ids' => '[]',
                'status'                => 'active',
                'created_at'            => current_time('mysql'),
                'updated_at'            => current_time('mysql'),
            ]);
            $clinic_id = (int) $wpdb->insert_id;
        } else {
            $clinic_id = (int) $wpdb->get_var("SELECT id FROM {$clinics_table} LIMIT 1");
        }

        if ($doctor_count === 0 && $clinic_id > 0) {
            // Doctor 1: Sarah Jenkins
            $wpdb->insert($doctors_table, [
                'department_id'           => 1, // Cardiology
                'first_name'              => 'Sarah',
                'last_name'               => 'Jenkins',
                'email'                   => 'sarah.jenkins@nexoramedical.com',
                'phone'                   => '+1 (555) 234-5678',
                'photo_url'               => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=400&q=80',
                'specialization'          => 'Senior Interventional Cardiologist',
                'qualifications'          => 'MD, FACC, Board Certified Cardiologist',
                'experience_years'        => 12,
                'bmdc_reg_number'         => 'BMDC-A-92841',
                'biography'               => 'Dr. Sarah Jenkins is an esteemed interventional cardiologist with over a decade of clinical experience in comprehensive heart disease management and preventative cardiology.',
                'consultation_fee'        => 120.00,
                'appointment_duration'    => 20,
                'break_time_start'        => '13:00:00',
                'break_time_end'          => '14:00:00',
                'max_patients_per_session'=> 20,
                'status'                  => 'active',
                'created_at'              => current_time('mysql'),
                'updated_at'              => current_time('mysql'),
            ]);
            $doc1_id = (int) $wpdb->insert_id;

            // Associate with clinic
            $wpdb->insert($dc_table, [
                'doctor_id'        => $doc1_id,
                'clinic_id'        => $clinic_id,
                'consultation_fee' => 120.00,
                'room_number'      => 'Suite 402',
                'status'           => 'active',
                'created_at'       => current_time('mysql'),
            ]);

            // Schedules for Doc 1
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            foreach ($days as $day) {
                $wpdb->insert($sched_table, [
                    'doctor_id'     => $doc1_id,
                    'clinic_id'     => $clinic_id,
                    'day_of_week'   => $day,
                    'start_time'    => '09:00:00',
                    'end_time'      => '17:00:00',
                    'slot_duration' => 20,
                    'buffer_time'   => 5,
                    'break_start'   => '13:00:00',
                    'break_end'     => '14:00:00',
                    'max_capacity'  => 20,
                    'status'        => 'active',
                    'created_at'    => current_time('mysql'),
                    'updated_at'    => current_time('mysql'),
                ]);
            }

            // Doctor 2: Michael Chen
            $wpdb->insert($doctors_table, [
                'department_id'           => 2, // Neurology
                'first_name'              => 'Michael',
                'last_name'               => 'Chen',
                'email'                   => 'michael.chen@nexoramedical.com',
                'phone'                   => '+1 (555) 876-5432',
                'photo_url'               => 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&w=400&q=80',
                'specialization'          => 'Senior Neurologist & Brain Specialist',
                'qualifications'          => 'MBBS, PhD (Neuroscience), Fellowship in Clinical Neurology',
                'experience_years'        => 15,
                'bmdc_reg_number'         => 'BMDC-B-31045',
                'biography'               => 'Specializing in neurodegenerative conditions, migraines, and complex spinal nervous disorders with compassionate patient-focused therapy.',
                'consultation_fee'        => 140.00,
                'appointment_duration'    => 30,
                'break_time_start'        => '13:00:00',
                'break_time_end'          => '14:00:00',
                'max_patients_per_session'=> 16,
                'status'                  => 'active',
                'created_at'              => current_time('mysql'),
                'updated_at'              => current_time('mysql'),
            ]);
            $doc2_id = (int) $wpdb->insert_id;

            // Associate with clinic
            $wpdb->insert($dc_table, [
                'doctor_id'        => $doc2_id,
                'clinic_id'        => $clinic_id,
                'consultation_fee' => 140.00,
                'room_number'      => 'Suite 505',
                'status'           => 'active',
                'created_at'       => current_time('mysql'),
            ]);

            foreach ($days as $day) {
                $wpdb->insert($sched_table, [
                    'doctor_id'     => $doc2_id,
                    'clinic_id'     => $clinic_id,
                    'day_of_week'   => $day,
                    'start_time'    => '10:00:00',
                    'end_time'      => '18:00:00',
                    'slot_duration' => 30,
                    'buffer_time'   => 5,
                    'break_start'   => '13:00:00',
                    'break_end'     => '14:00:00',
                    'max_capacity'  => 16,
                    'status'        => 'active',
                    'created_at'    => current_time('mysql'),
                    'updated_at'    => current_time('mysql'),
                ]);
            }
        }
    }

    /**
     * Seed core medical departments if none exist
     */
    private static function seed_default_departments(): void {
        global $wpdb;
        $table = NMBP_Database::table('departments');

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $departments = [
            ['name' => 'Cardiology', 'slug' => 'cardiology', 'description' => 'Comprehensive heart and cardiovascular care.', 'icon' => 'heart'],
            ['name' => 'Neurology', 'slug' => 'neurology', 'description' => 'Disorders of the nervous system and brain.', 'icon' => 'brain'],
            ['name' => 'Dermatology', 'slug' => 'dermatology', 'description' => 'Advanced skin, hair, and nail treatments.', 'icon' => 'shield'],
            ['name' => 'Gynecology & Obstetrics', 'slug' => 'gynecology', 'description' => 'Women health, prenatal, and maternity care.', 'icon' => 'users'],
            ['name' => 'Pediatrics', 'slug' => 'pediatrics', 'description' => 'Dedicated medical care for infants, children, and teens.', 'icon' => 'smile'],
            ['name' => 'Orthopedics', 'slug' => 'orthopedics', 'description' => 'Bone, joint, ligament, and spine management.', 'icon' => 'activity'],
            ['name' => 'Dentistry', 'slug' => 'dentistry', 'description' => 'Oral health, cosmetic dentistry, and dental surgery.', 'icon' => 'smile'],
            ['name' => 'General Medicine', 'slug' => 'general-medicine', 'description' => 'Primary care, diagnostic health, and routine wellness.', 'icon' => 'cross'],
            ['name' => 'General Surgery', 'slug' => 'surgery', 'description' => 'Pre-operative consultations and surgical procedures.', 'icon' => 'scissors'],
            ['name' => 'ENT (Otolaryngology)', 'slug' => 'ent', 'description' => 'Ear, nose, and throat medical treatments.', 'icon' => 'mic'],
        ];

        foreach ($departments as $dept) {
            $wpdb->insert($table, [
                'name' => sanitize_text_field($dept['name']),
                'slug' => sanitize_title($dept['slug']),
                'description' => sanitize_textarea_field($dept['description']),
                'icon' => sanitize_text_field($dept['icon']),
                'status' => 'active',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]);
        }
    }

    /**
     * Seed initial configuration options
     */
    private static function seed_default_settings(): void {
        if (!get_option('nmbp_general_settings')) {
            update_option('nmbp_general_settings', [
                'company_name' => 'Nexora Medical Group',
                'support_email' => get_option('admin_email'),
                'support_phone' => '+1 (555) 019-2834',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'date_format' => 'Y-m-d',
                'time_format' => 'h:i A',
                'timezone' => wp_timezone_string(),
                'require_approval' => true,
                'allow_guest_booking' => true,
                'auto_create_patient_account' => true,
            ]);
        }

        if (!get_option('nmbp_appointment_settings')) {
            update_option('nmbp_appointment_settings', [
                'default_status' => 'pending',
                'slot_duration' => 20,
                'buffer_time' => 5,
                'min_advance_hours' => 2,
                'max_advance_days' => 30,
                'cancellation_notice_hours' => 24,
                'allow_reschedule' => true,
                'allow_cancellation' => true,
                'temp_lock_duration_minutes' => 10,
            ]);
        }

        if (!get_option('nmbp_payment_settings')) {
            update_option('nmbp_payment_settings', [
                'enable_offline' => true,
                'enable_cash_at_clinic' => true,
                'enable_online' => true,
                'online_gateway' => 'woocommerce', // 'woocommerce' or 'stripe'
                'payment_timing' => 'before_approval', // 'before_approval' or 'after_approval'
                'offline_instruction' => 'Please present your appointment reference number at the reception desk upon arrival.',
            ]);
        }

        if (!get_option('nmbp_design_settings')) {
            update_option('nmbp_design_settings', [
                'primary_color' => '#0d9488', // Teal
                'secondary_color' => '#0f172a', // Navy slate
                'accent_color' => '#0284c7', // Sky blue
                'background_color' => '#f8fafc',
                'card_preset' => 'classic_medical', // classic_medical, modern_glass, hospital_pro, minimal_clean
                'form_preset' => 'multi_step', // multi_step, classic_form, calendar_view, compact_form
                'font_family' => 'Inter, -apple-system, BlinkMacSystemFont, sans-serif',
                'border_radius' => '8px',
                'custom_css' => '',
            ]);
        }
    }
}

