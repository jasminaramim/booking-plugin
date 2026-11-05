<?php
/**
 * Elementor Appointment Booking Widget
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Elementor_Widget_Booking extends \Elementor\Widget_Base {

    public function get_name(): string {
        return 'nmbp_appointment_booking';
    }

    public function get_title(): string {
        return __('Appointment Booking', 'nexora-medical-booking-pro');
    }

    public function get_icon(): string {
        return 'eicon-calendar';
    }

    public function get_categories(): array {
        return ['nexora-medical'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Booking Form Layout', 'nexora-medical-booking-pro'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'form_preset',
            [
                'label'   => __('Form Preset', 'nexora-medical-booking-pro'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'multi_step',
                'options' => [
                    'multi_step'    => __('01. Multi-step Stepper', 'nexora-medical-booking-pro'),
                    'classic_form'  => __('02. Classic Form', 'nexora-medical-booking-pro'),
                    'calendar_view' => __('03. Calendar View', 'nexora-medical-booking-pro'),
                    'compact_form'  => __('04. Compact Form', 'nexora-medical-booking-pro'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        echo NMBP_Shortcodes::instance()->render_booking();
    }
}

