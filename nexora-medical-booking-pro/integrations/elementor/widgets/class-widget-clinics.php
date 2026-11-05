<?php
/**
 * Elementor Clinic Grid Widget
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Elementor_Widget_Clinics extends \Elementor\Widget_Base {

    public function get_name(): string {
        return 'nmbp_clinic_grid';
    }

    public function get_title(): string {
        return __('Clinic Grid', 'nexora-medical-booking-pro');
    }

    public function get_icon(): string {
        return 'eicon-gallery-grid';
    }

    public function get_categories(): array {
        return ['nexora-medical'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content Settings', 'nexora-medical-booking-pro'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'limit',
            [
                'label'   => __('Number of Clinics', 'nexora-medical-booking-pro'),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'min'     => 1,
                'max'     => 50,
                'default' => 6,
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Card Style', 'nexora-medical-booking-pro'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'primary_color',
            [
                'label'     => __('Theme Color', 'nexora-medical-booking-pro'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#0d9488',
                'selectors' => [
                    '{{WRAPPER}} .nmbp-btn-primary' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        echo NMBP_Shortcodes::instance()->render_clinics([
            'limit' => $settings['limit'] ?? 6,
        ]);
    }
}

