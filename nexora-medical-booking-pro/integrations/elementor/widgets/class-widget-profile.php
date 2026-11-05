<?php
/**
 * Elementor Doctor Profile Widget
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Elementor_Widget_Profile extends \Elementor\Widget_Base {

    public function get_name(): string {
        return 'nmbp_doctor_profile';
    }

    public function get_title(): string {
        return __('Doctor Profile', 'nexora-medical-booking-pro');
    }

    public function get_icon(): string {
        return 'eicon-user-circle-o';
    }

    public function get_categories(): array {
        return ['nexora-medical'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Doctor Selection', 'nexora-medical-booking-pro'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'doctor_id',
            [
                'label'   => __('Doctor ID', 'nexora-medical-booking-pro'),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => 1,
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        echo NMBP_Shortcodes::instance()->render_doctor_profile([
            'id' => $settings['doctor_id'],
        ]);
    }
}

