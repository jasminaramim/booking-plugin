<?php
/**
 * Elementor Appointment Search Widget
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Elementor_Widget_Search extends \Elementor\Widget_Base {

    public function get_name(): string {
        return 'nmbp_appointment_search';
    }

    public function get_title(): string {
        return __('Appointment Search & Tracker', 'nexora-medical-booking-pro');
    }

    public function get_icon(): string {
        return 'eicon-search';
    }

    public function get_categories(): array {
        return ['nexora-medical'];
    }

    protected function render() {
        echo NMBP_Shortcodes::instance()->render_appointment_search();
    }
}

