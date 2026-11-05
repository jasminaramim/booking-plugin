<?php
/**
 * Elementor Department Grid Widget
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Elementor_Widget_Departments extends \Elementor\Widget_Base {

    public function get_name(): string {
        return 'nmbp_department_grid';
    }

    public function get_title(): string {
        return __('Department Grid', 'nexora-medical-booking-pro');
    }

    public function get_icon(): string {
        return 'eicon-apps';
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
        $this->end_controls_section();
    }

    protected function render() {
        echo NMBP_Shortcodes::instance()->render_departments();
    }
}

