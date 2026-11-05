<?php
/**
 * Admin Menu Setup
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Admin_Menu {

    private static ?NMBP_Admin_Menu $instance = null;

    public static function instance(): NMBP_Admin_Menu {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menus']);
    }

    public function register_admin_menus(): void {
        $capability = NMBP_Permissions::CAP_VIEW_APPOINTMENTS;
        if (!current_user_can($capability) && !current_user_can('manage_options')) {
            return;
        }

        // SVG icon for hospital / health
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#0d9488"><path d="M19 3H5c-1.1 0-1.99.9-1.99 2L3 19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/></svg>'
        );

        // Main menu
        add_menu_page(
            __('Nexora Medical', 'nexora-medical-booking-pro'),
            __('Nexora Medical', 'nexora-medical-booking-pro'),
            $capability,
            'nmbp-dashboard',
            [$this, 'render_admin_page'],
            $icon_svg,
            26
        );

        $submenus = [
            ['title' => __('Dashboard', 'nexora-medical-booking-pro'), 'slug' => 'nmbp-dashboard', 'cap' => $capability],
            ['title' => __('Appointments', 'nexora-medical-booking-pro'), 'slug' => 'nmbp-appointments', 'cap' => NMBP_Permissions::CAP_VIEW_APPOINTMENTS],
            ['title' => __('Clinics', 'nexora-medical-booking-pro'), 'slug' => 'nmbp-clinics', 'cap' => NMBP_Permissions::CAP_MANAGE_CLINICS],
            ['title' => __('Doctors', 'nexora-medical-booking-pro'), 'slug' => 'nmbp-doctors', 'cap' => NMBP_Permissions::CAP_MANAGE_DOCTORS],
            ['title' => __('Departments', 'nexora-medical-booking-pro'), 'slug' => 'nmbp-departments', 'cap' => NMBP_Permissions::CAP_MANAGE_DEPARTMENTS],
            ['title' => __('Schedules', 'nexora-medical-booking-pro'), 'slug' => 'nmbp-schedules', 'cap' => NMBP_Permissions::CAP_MANAGE_SCHEDULES],
            ['title' => __('Patients', 'nexora-medical-booking-pro'), 'slug' => 'nmbp-patients', 'cap' => NMBP_Permissions::CAP_MANAGE_PATIENTS],
            ['title' => __('Payments', 'nexora-medical-booking-pro'), 'slug' => 'nmbp-payments', 'cap' => NMBP_Permissions::CAP_MANAGE_PAYMENTS],
            ['title' => __('Design Studio', 'nexora-medical-booking-pro'), 'slug' => 'nmbp-design-studio', 'cap' => 'manage_options'],
            ['title' => __('Settings', 'nexora-medical-booking-pro'), 'slug' => 'nmbp-settings', 'cap' => NMBP_Permissions::CAP_MANAGE_SETTINGS],
            ['title' => __('System Status', 'nexora-medical-booking-pro'), 'slug' => 'nmbp-system-status', 'cap' => 'manage_options'],
        ];

        foreach ($submenus as $sub) {
            add_submenu_page(
                'nmbp-dashboard',
                $sub['title'] . ' &lsaquo; Nexora Medical',
                $sub['title'],
                $sub['cap'],
                $sub['slug'],
                [$this, 'render_admin_page']
            );
        }
    }

    public function render_admin_page(): void {
        $page = sanitize_key($_GET['page'] ?? 'nmbp-dashboard');
        ?>
        <div class="wrap nmbp-admin-wrap" style="margin: 0; padding: 0 20px 20px 0;">
            <div id="nmbp-admin-root" data-current-page="<?php echo esc_attr($page); ?>">
                <div style="padding: 40px; text-align: center; color: #64748b; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
                    <div style="display: inline-block; width: 40px; height: 40px; border: 3px solid #cbd5e1; border-top-color: #0d9488; border-radius: 50%; animation: nmbp-spin 0.8s linear infinite;"></div>
                    <p style="margin-top: 15px; font-weight: 500;"><?php esc_html_e('Loading Nexora Medical Booking Pro...', 'nexora-medical-booking-pro'); ?></p>
                </div>
                <style>
                    @keyframes nmbp-spin { to { transform: rotate(360deg); } }
                </style>
            </div>
        </div>
        <?php
    }
}

