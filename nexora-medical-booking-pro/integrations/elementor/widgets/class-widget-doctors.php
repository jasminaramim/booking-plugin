<?php
/**
 * Elementor Doctor Grid Widget
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Elementor_Widget_Doctors extends \Elementor\Widget_Base {

    public function get_name(): string {
        return 'nmbp_doctor_grid';
    }

    public function get_title(): string {
        return __('Doctor Grid', 'nexora-medical-booking-pro');
    }

    public function get_icon(): string {
        return 'eicon-person';
    }

    public function get_categories(): array {
        return ['nexora-medical'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'nexora-medical-booking-pro'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'preset',
            [
                'label'   => __('Doctor Card Preset', 'nexora-medical-booking-pro'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'classic_medical',
                'options' => [
                    'classic_medical' => __('01. Classic Medical', 'nexora-medical-booking-pro'),
                    'modern_glass'    => __('02. Modern Glass', 'nexora-medical-booking-pro'),
                    'hospital_pro'    => __('03. Hospital Pro', 'nexora-medical-booking-pro'),
                    'minimal_clean'   => __('04. Minimal Clean', 'nexora-medical-booking-pro'),
                ],
            ]
        );

        $this->add_control(
            'limit',
            [
                'label'   => __('Limit', 'nexora-medical-booking-pro'),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => 6,
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'nexora-medical-booking-pro'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'accent_color',
            [
                'label'     => __('Accent Color', 'nexora-medical-booking-pro'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#0d9488',
                'selectors' => [
                    '{{WRAPPER}} .nmbp-doctor-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        echo NMBP_Shortcodes::instance()->render_doctors([
            'preset' => $settings['preset'],
            'limit'  => $settings['limit'],
        ]);
    }
}

