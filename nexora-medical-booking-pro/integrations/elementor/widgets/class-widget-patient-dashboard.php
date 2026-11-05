<?php
/**
 * Elementor Patient Dashboard Widget
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Elementor_Widget_Patient_Dashboard extends \Elementor\Widget_Base {

    public function get_name(): string {
        return 'nmbp_patient_dashboard';
    }

    public function get_title(): string {
        return __('Patient Dashboard', 'nexora-medical-booking-pro');
    }

    public function get_icon(): string {
        return 'eicon-dashboard';
    }

    public function get_categories(): array {
        return ['nexora-medical'];
    }

    protected function render() {
        echo NMBP_Shortcodes::instance()->render_patient_dashboard();
    }
}

