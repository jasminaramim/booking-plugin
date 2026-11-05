<?php
/**
 * Elementor Booking CTA Widget
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Elementor_Widget_CTA extends \Elementor\Widget_Base {

    public function get_name(): string {
        return 'nmbp_booking_cta';
    }

    public function get_title(): string {
        return __('Booking CTA Banner', 'nexora-medical-booking-pro');
    }

    public function get_icon(): string {
        return 'eicon-call-to-action';
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
            'title',
            [
                'label'   => __('Title', 'nexora-medical-booking-pro'),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __('Need an Expert Doctor Consultation?', 'nexora-medical-booking-pro'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'   => __('Button Label', 'nexora-medical-booking-pro'),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __('Book Appointment Now', 'nexora-medical-booking-pro'),
            ]
        );

        $this->add_control(
            'link',
            [
                'label'   => __('Link', 'nexora-medical-booking-pro'),
                'type'    => \Elementor\Controls_Manager::URL,
                'default' => ['url' => '#nmbp-booking-app'],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $url = !empty($settings['link']['url']) ? esc_url($settings['link']['url']) : '#nmbp-booking-app';
        ?>
        <div class="nmbp-cta-banner">
            <div class="nmbp-cta-inner">
                <div class="nmbp-cta-text">
                    <h3><?php echo esc_html($settings['title']); ?></h3>
                    <p><?php esc_html_e('Quick scheduling, top medical specialists, and seamless branch coordination.', 'nexora-medical-booking-pro'); ?></p>
                </div>
                <div class="nmbp-cta-btn-wrap">
                    <a href="<?php echo $url; ?>" class="nmbp-btn nmbp-btn-cta"><?php echo esc_html($settings['button_text']); ?></a>
                </div>
            </div>
        </div>
        <?php
    }
}

