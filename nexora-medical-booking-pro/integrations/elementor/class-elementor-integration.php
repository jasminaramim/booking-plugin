<?php
/**
 * Elementor Integration & Widget Registrar
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Elementor_Integration {

    private static ?NMBP_Elementor_Integration $instance = null;

    public static function instance(): NMBP_Elementor_Integration {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if (did_action('elementor/loaded') || class_exists('\Elementor\Plugin')) {
            $this->init_elementor();
        } else {
            add_action('elementor/loaded', [$this, 'init_elementor']);
        }
    }

    public function init_elementor(): void {
        add_action('elementor/elements/categories_registered', [$this, 'register_category']);
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets']);
    }

    public function register_category($elements_manager) {
        if (!is_object($elements_manager) || !method_exists($elements_manager, 'add_category')) {
            return;
        }
        $elements_manager->add_category(
            'nexora-medical',
            [
                'title' => __('Nexora Medical Booking', 'nexora-medical-booking-pro'),
                'icon'  => 'fa fa-hospital',
            ]
        );
    }

    public function register_widgets($widgets_manager) {
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }

        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        require_once NMBP_PLUGIN_DIR . 'integrations/elementor/widgets/class-widget-clinics.php';
        require_once NMBP_PLUGIN_DIR . 'integrations/elementor/widgets/class-widget-doctors.php';
        require_once NMBP_PLUGIN_DIR . 'integrations/elementor/widgets/class-widget-departments.php';
        require_once NMBP_PLUGIN_DIR . 'integrations/elementor/widgets/class-widget-booking.php';
        require_once NMBP_PLUGIN_DIR . 'integrations/elementor/widgets/class-widget-profile.php';
        require_once NMBP_PLUGIN_DIR . 'integrations/elementor/widgets/class-widget-search.php';
        require_once NMBP_PLUGIN_DIR . 'integrations/elementor/widgets/class-widget-patient-dashboard.php';
        require_once NMBP_PLUGIN_DIR . 'integrations/elementor/widgets/class-widget-cta.php';

        $widgets_manager->register(new NMBP_Elementor_Widget_Clinics());
        $widgets_manager->register(new NMBP_Elementor_Widget_Doctors());
        $widgets_manager->register(new NMBP_Elementor_Widget_Departments());
        $widgets_manager->register(new NMBP_Elementor_Widget_Booking());
        $widgets_manager->register(new NMBP_Elementor_Widget_Profile());
        $widgets_manager->register(new NMBP_Elementor_Widget_Search());
        $widgets_manager->register(new NMBP_Elementor_Widget_Patient_Dashboard());
        $widgets_manager->register(new NMBP_Elementor_Widget_CTA());
    }
}

