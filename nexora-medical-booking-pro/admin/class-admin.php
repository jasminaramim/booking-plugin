<?php
/**
 * Admin assets and bootstrapping
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Admin {

    private static ?NMBP_Admin $instance = null;

    public static function instance(): NMBP_Admin {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function enqueue_admin_assets(string $hook): void {
        // Reliably detect Nexora Medical screens
        $current_page = sanitize_key($_GET['page'] ?? '');
        $is_nmbp_page = (strpos($hook, 'nmbp') !== false) || (strpos($current_page, 'nmbp') === 0);

        if (!$is_nmbp_page) {
            return;
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        // Enqueue admin styles
        $css_file = 'assets/css/admin.css';
        if (file_exists(NMBP_PLUGIN_DIR . $css_file)) {
            wp_enqueue_style(
                'nmbp-admin-style',
                NMBP_PLUGIN_URL . $css_file,
                [],
                NMBP_VERSION
            );
        }

        // Enqueue React admin application script
        $js_file = 'assets/js/admin.js';
        $js_url = NMBP_PLUGIN_URL . $js_file;

        wp_enqueue_script(
            'nmbp-admin-app',
            $js_url,
            [],
            NMBP_VERSION,
            true
        );

        // Localize script with REST API parameters and context
        wp_localize_script('nmbp-admin-app', 'nmbpAdminConfig', [
            'restUrl'         => esc_url_raw(rest_url('nmbp/v1/')),
            'nonce'           => wp_create_nonce('wp_rest'),
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'siteUrl'         => site_url(),
            'pluginUrl'       => NMBP_PLUGIN_URL,
            'version'         => NMBP_VERSION,
            'currentUser'     => [
                'id'          => $user_id,
                'name'        => $user->display_name,
                'email'       => $user->user_email,
                'roles'       => $user->roles,
                'isSuperAdmin'=> NMBP_Permissions::is_super_admin($user_id),
                'doctorId'    => NMBP_Permissions::get_doctor_id_by_wp_user($user_id),
            ],
            'generalSettings' => get_option('nmbp_general_settings', []),
            'designSettings'  => get_option('nmbp_design_settings', []),
        ]);
    }
}

