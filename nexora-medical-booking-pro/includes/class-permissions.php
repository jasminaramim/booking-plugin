<?php
/**
 * Roles and Capabilities Manager
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Permissions {

    public const ROLE_CLINIC_MANAGER = 'nmbp_clinic_manager';
    public const ROLE_DOCTOR         = 'nmbp_doctor';
    public const ROLE_RECEPTIONIST   = 'nmbp_receptionist';
    public const ROLE_PATIENT        = 'nmbp_patient';

    public const CAP_MANAGE_SYSTEM       = 'nmbp_manage_system';
    public const CAP_MANAGE_CLINICS      = 'nmbp_manage_clinics';
    public const CAP_MANAGE_DOCTORS      = 'nmbp_manage_doctors';
    public const CAP_MANAGE_DEPARTMENTS  = 'nmbp_manage_departments';
    public const CAP_MANAGE_SCHEDULES    = 'nmbp_manage_schedules';
    public const CAP_MANAGE_APPOINTMENTS = 'nmbp_manage_appointments';
    public const CAP_APPROVE_APPOINTMENTS= 'nmbp_approve_appointments';
    public const CAP_VIEW_APPOINTMENTS   = 'nmbp_view_appointments';
    public const CAP_MANAGE_PAYMENTS     = 'nmbp_manage_payments';
    public const CAP_VERIFY_PAYMENTS     = 'nmbp_verify_payments';
    public const CAP_MANAGE_PATIENTS     = 'nmbp_manage_patients';
    public const CAP_MANAGE_SETTINGS     = 'nmbp_manage_settings';
    public const CAP_VIEW_REPORTS        = 'nmbp_view_reports';
    public const CAP_DOCTOR_PORTAL       = 'nmbp_doctor_portal';
    public const CAP_PATIENT_PORTAL      = 'nmbp_patient_portal';
    public const CAP_RECEPTIONIST_PORTAL = 'nmbp_receptionist_portal';

    /**
     * Register roles and capabilities
     */
    public static function register_roles_and_caps(): void {
        // 1. Give administrator full access
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $all_caps = self::get_all_capabilities();
            foreach ($all_caps as $cap) {
                $admin_role->add_cap($cap);
            }
        }

        // 2. Clinic Manager Role
        add_role(self::ROLE_CLINIC_MANAGER, __('Clinic Manager', 'nexora-medical-booking-pro'), [
            'read'                       => true,
            self::CAP_MANAGE_CLINICS      => true,
            self::CAP_MANAGE_DOCTORS      => true,
            self::CAP_MANAGE_SCHEDULES    => true,
            self::CAP_MANAGE_APPOINTMENTS => true,
            self::CAP_APPROVE_APPOINTMENTS=> true,
            self::CAP_VIEW_APPOINTMENTS   => true,
            self::CAP_MANAGE_PAYMENTS     => true,
            self::CAP_VERIFY_PAYMENTS     => true,
            self::CAP_MANAGE_PATIENTS     => true,
            self::CAP_VIEW_REPORTS        => true,
        ]);

        // 3. Doctor Role
        add_role(self::ROLE_DOCTOR, __('Doctor', 'nexora-medical-booking-pro'), [
            'read'                   => true,
            self::CAP_DOCTOR_PORTAL   => true,
            self::CAP_VIEW_APPOINTMENTS => true,
            self::CAP_MANAGE_SCHEDULES => true, // Own schedule
        ]);

        // 4. Receptionist Role
        add_role(self::ROLE_RECEPTIONIST, __('Receptionist', 'nexora-medical-booking-pro'), [
            'read'                       => true,
            self::CAP_RECEPTIONIST_PORTAL => true,
            self::CAP_VIEW_APPOINTMENTS   => true,
            self::CAP_MANAGE_APPOINTMENTS => true,
            self::CAP_MANAGE_PATIENTS     => true,
            self::CAP_VERIFY_PAYMENTS     => true,
        ]);

        // 5. Patient Role
        add_role(self::ROLE_PATIENT, __('Patient', 'nexora-medical-booking-pro'), [
            'read'                 => true,
            self::CAP_PATIENT_PORTAL => true,
        ]);
    }

    /**
     * Remove custom roles on full uninstall
     */
    public static function remove_roles_and_caps(): void {
        remove_role(self::ROLE_CLINIC_MANAGER);
        remove_role(self::ROLE_DOCTOR);
        remove_role(self::ROLE_RECEPTIONIST);
        remove_role(self::ROLE_PATIENT);

        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach (self::get_all_capabilities() as $cap) {
                $admin_role->remove_cap($cap);
            }
        }
    }

    public static function get_all_capabilities(): array {
        return [
            self::CAP_MANAGE_SYSTEM,
            self::CAP_MANAGE_CLINICS,
            self::CAP_MANAGE_DOCTORS,
            self::CAP_MANAGE_DEPARTMENTS,
            self::CAP_MANAGE_SCHEDULES,
            self::CAP_MANAGE_APPOINTMENTS,
            self::CAP_APPROVE_APPOINTMENTS,
            self::CAP_VIEW_APPOINTMENTS,
            self::CAP_MANAGE_PAYMENTS,
            self::CAP_VERIFY_PAYMENTS,
            self::CAP_MANAGE_PATIENTS,
            self::CAP_MANAGE_SETTINGS,
            self::CAP_VIEW_REPORTS,
            self::CAP_DOCTOR_PORTAL,
            self::CAP_PATIENT_PORTAL,
            self::CAP_RECEPTIONIST_PORTAL,
        ];
    }

    /**
     * Check if user is Super Admin
     */
    public static function is_super_admin(int $user_id = 0): bool {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }
        return user_can($user_id, 'manage_options') || user_can($user_id, self::CAP_MANAGE_SYSTEM);
    }

    /**
     * Get Doctor record ID linked to WP user ID
     */
    public static function get_doctor_id_by_wp_user(int $wp_user_id): ?int {
        global $wpdb;
        $table = NMBP_Database::table('doctors');
        $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE wp_user_id = %d LIMIT 1", $wp_user_id));
        return $id ? (int) $id : null;
    }

    /**
     * Get Patient record ID linked to WP user ID
     */
    public static function get_patient_id_by_wp_user(int $wp_user_id): ?int {
        global $wpdb;
        $table = NMBP_Database::table('patients');
        $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE wp_user_id = %d LIMIT 1", $wp_user_id));
        return $id ? (int) $id : null;
    }

    /**
     * Check if a user has access to a specific clinic
     */
    public static function can_access_clinic(int $user_id, int $clinic_id): bool {
        if (self::is_super_admin($user_id)) {
            return true;
        }

        global $wpdb;
        $table = NMBP_Database::table('clinics');
        $receptionists_json = $wpdb->get_var($wpdb->prepare("SELECT receptionist_user_ids FROM {$table} WHERE id = %d", $clinic_id));

        if (!empty($receptionists_json)) {
            $ids = json_decode($receptionists_json, true);
            if (is_array($ids) && in_array($user_id, $ids, true)) {
                return true;
            }
        }

        // If user is a doctor assigned to this clinic
        $doctor_id = self::get_doctor_id_by_wp_user($user_id);
        if ($doctor_id) {
            $dc_table = NMBP_Database::table('doctor_clinics');
            $assigned = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$dc_table} WHERE doctor_id = %d AND clinic_id = %d AND status = 'active'",
                $doctor_id,
                $clinic_id
            ));
            if ($assigned > 0) {
                return true;
            }
        }

        return false;
    }
}

