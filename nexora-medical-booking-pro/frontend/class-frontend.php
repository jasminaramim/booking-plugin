<?php
/**
 * Frontend Assets & Dynamic Styling
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Frontend {

    private static ?NMBP_Frontend $instance = null;

    public static function instance(): NMBP_Frontend {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_head', [$this, 'inject_dynamic_css'], 99);
        add_action('wp_footer', [$this, 'render_booking_modal_container']);
    }

    public function enqueue_frontend_assets(): void {
        wp_enqueue_style(
            'nmbp-frontend-style',
            NMBP_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            NMBP_VERSION
        );

        wp_enqueue_script(
            'nmbp-frontend-app',
            NMBP_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            NMBP_VERSION,
            true
        );

        $design = get_option('nmbp_design_settings', []);
        $general = get_option('nmbp_general_settings', []);
        $payments = get_option('nmbp_payment_settings', []);
        $user_id = get_current_user_id();

        wp_localize_script('nmbp-frontend-app', 'nmbpConfig', [
            'restUrl'         => esc_url_raw(rest_url('nmbp/v1/')),
            'nonce'           => wp_create_nonce('wp_rest'),
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'currencySymbol'  => $general['currency_symbol'] ?? '$',
            'isLoggedIn'      => is_user_logged_in(),
            'currentUserId'   => $user_id,
            'patientId'       => $user_id ? NMBP_Permissions::get_patient_id_by_wp_user($user_id) : null,
            'doctorId'        => $user_id ? NMBP_Permissions::get_doctor_id_by_wp_user($user_id) : null,
            'cardPreset'      => $design['card_preset'] ?? 'classic_medical',
            'formPreset'      => $design['form_preset'] ?? 'multi_step',
            'payments'        => [
                'enable_cash'            => !isset($payments['enable_cash']) || !empty($payments['enable_cash']),
                'cash_title'             => !empty($payments['cash_title']) ? $payments['cash_title'] : __('Pay at Clinic (Cash / Card)', 'nexora-medical-booking-pro'),
                'cash_desc'              => !empty($payments['cash_desc']) ? $payments['cash_desc'] : __('Pay directly at the hospital reception upon check-in.', 'nexora-medical-booking-pro'),
                'enable_stripe'          => !empty($payments['enable_stripe']),
                'stripe_title'           => !empty($payments['stripe_title']) ? $payments['stripe_title'] : __('Credit / Debit Card (Stripe)', 'nexora-medical-booking-pro'),
                'stripe_desc'            => !empty($payments['stripe_desc']) ? $payments['stripe_desc'] : __('Pay securely online via Stripe payment gateway.', 'nexora-medical-booking-pro'),
                'stripe_mode'            => $payments['stripe_mode'] ?? 'test',
                'stripe_publishable_key' => $payments['stripe_publishable_key'] ?? '',
                'enable_paypal'          => !empty($payments['enable_paypal']),
                'paypal_title'           => !empty($payments['paypal_title']) ? $payments['paypal_title'] : __('PayPal Express Checkout', 'nexora-medical-booking-pro'),
                'paypal_desc'            => !empty($payments['paypal_desc']) ? $payments['paypal_desc'] : __('Fast and secure payment with your PayPal account or card.', 'nexora-medical-booking-pro'),
                'paypal_mode'            => $payments['paypal_mode'] ?? 'sandbox',
                'paypal_client_id'       => $payments['paypal_client_id'] ?? '',
                'enable_woocommerce'     => !empty($payments['enable_woocommerce']) && class_exists('WooCommerce'),
                'payment_timing'         => $payments['payment_timing'] ?? 'before_approval',
            ],
            'i18n'            => [
                'selectClinic'     => __('Please select a clinic first.', 'nexora-medical-booking-pro'),
                'selectDoctor'     => __('Please select a doctor.', 'nexora-medical-booking-pro'),
                'selectSlot'       => __('Please select an available appointment time slot.', 'nexora-medical-booking-pro'),
                'bookingSuccess'   => __('Appointment booked successfully!', 'nexora-medical-booking-pro'),
                'loadingSlots'     => __('Checking real-time doctor availability...', 'nexora-medical-booking-pro'),
                'noSlots'          => __('No available slots found for this date. Please pick another day.', 'nexora-medical-booking-pro'),
            ],
        ]);
    }

    public function inject_dynamic_css(): void {
        $design = get_option('nmbp_design_settings', []);
        $primary = sanitize_hex_color($design['primary_color'] ?? '#0d9488');
        $secondary = sanitize_hex_color($design['secondary_color'] ?? '#0f172a');
        $accent = sanitize_hex_color($design['accent_color'] ?? '#0284c7');
        $radius = sanitize_text_field($design['border_radius'] ?? '8px');
        $custom_css = wp_strip_all_tags($design['custom_css'] ?? '');

        echo "<style id='nmbp-dynamic-theme-vars'>
            :root {
                --nmbp-primary: {$primary};
                --nmbp-primary-hover: #0f766e;
                --nmbp-secondary: {$secondary};
                --nmbp-accent: {$accent};
                --nmbp-radius: {$radius};
                --nmbp-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            {$custom_css}
        </style>";
    }

    public function render_booking_modal_container(): void {
        $payments = get_option('nmbp_payment_settings', []);
        $enable_cash = !isset($payments['enable_cash']) || !empty($payments['enable_cash']);
        $cash_title = !empty($payments['cash_title']) ? $payments['cash_title'] : __('Pay at Clinic (Cash / Card)', 'nexora-medical-booking-pro');
        $cash_desc = !empty($payments['cash_desc']) ? $payments['cash_desc'] : __('Pay directly at the hospital reception upon check-in.', 'nexora-medical-booking-pro');

        $enable_stripe = !empty($payments['enable_stripe']);
        $stripe_title = !empty($payments['stripe_title']) ? $payments['stripe_title'] : __('Credit / Debit Card (Stripe)', 'nexora-medical-booking-pro');
        $stripe_desc = !empty($payments['stripe_desc']) ? $payments['stripe_desc'] : __('Pay securely online via Stripe payment gateway.', 'nexora-medical-booking-pro');

        $enable_paypal = !empty($payments['enable_paypal']);
        $paypal_title = !empty($payments['paypal_title']) ? $payments['paypal_title'] : __('PayPal Express Checkout', 'nexora-medical-booking-pro');
        $paypal_desc = !empty($payments['paypal_desc']) ? $payments['paypal_desc'] : __('Fast and secure payment with your PayPal account or card.', 'nexora-medical-booking-pro');
        ?>
        <div id="nmbp-quick-booking-modal" class="nmbp-modal-backdrop" style="display:none;" aria-hidden="true">
            <div class="nmbp-modal-window nmbp-quick-modal-window">
                <div class="nmbp-modal-header">
                    <div>
                        <span class="nmbp-modal-badge">&#9877;&#65039; <?php esc_html_e('APPOINTMENT BOOKING', 'nexora-medical-booking-pro'); ?></span>
                        <h3 class="nmbp-quick-modal-title" style="margin:4px 0 0 0;"><?php esc_html_e('Book Consultation Now', 'nexora-medical-booking-pro'); ?></h3>
                    </div>
                    <button type="button" class="nmbp-modal-close" id="nmbp-close-quick-modal" aria-label="<?php esc_attr_e('Close', 'nexora-medical-booking-pro'); ?>">&times;</button>
                </div>

                <div class="nmbp-modal-body">
                    <!-- Doctor Summary Header in Modal -->
                    <div class="nmbp-modal-doctor-summary" id="nmbp-modal-doctor-summary">
                        <img src="" id="nmbp-modal-doc-img" class="nmbp-modal-doc-avatar" alt="Doctor">
                        <div class="nmbp-modal-doc-info">
                            <h4 id="nmbp-modal-doc-name" style="margin:0 0 2px 0; font-size:17px; color:#0f172a;">-</h4>
                            <p id="nmbp-modal-doc-dept" style="margin:0 0 4px 0; font-size:13px; color:#0d9488; font-weight:600;">-</p>
                            <span class="nmbp-modal-fee-badge" id="nmbp-modal-doc-fee">-</span>
                        </div>
                    </div>

                    <!-- Step View in Modal -->
                    <div id="nmbp-modal-form-view">
                        <!-- Clinic Selection (if doctor has multiple branches) -->
                        <div class="nmbp-form-group" id="nmbp-modal-clinic-group" style="display:none; margin-bottom:14px;">
                            <label class="nmbp-label"><?php esc_html_e('Select Hospital Branch / Clinic *', 'nexora-medical-booking-pro'); ?></label>
                            <select id="nmbp-modal-clinic-select" class="nmbp-input"></select>
                        </div>

                        <!-- Date & Slot Selection -->
                        <div class="nmbp-form-group" style="margin-bottom:14px;">
                            <label class="nmbp-label"><?php esc_html_e('Select Consultation Date *', 'nexora-medical-booking-pro'); ?></label>
                            <input type="date" id="nmbp-modal-date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" class="nmbp-input">
                        </div>

                        <div class="nmbp-form-group" style="margin-bottom:18px;">
                            <label class="nmbp-label"><?php esc_html_e('Select Available Time Slot *', 'nexora-medical-booking-pro'); ?></label>
                            <div id="nmbp-modal-slots-container" class="nmbp-slots-grid">
                                <div class="nmbp-text-muted" style="grid-column:1/-1; padding:12px; font-size:13px; text-align:center;">
                                    <?php esc_html_e('Loading real-time available slots...', 'nexora-medical-booking-pro'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Patient Info Form -->
                        <div class="nmbp-form-row">
                            <div class="nmbp-form-col">
                                <label class="nmbp-label"><?php esc_html_e('First Name *', 'nexora-medical-booking-pro'); ?></label>
                                <input type="text" id="nmbp-modal-first-name" class="nmbp-input" placeholder="e.g. John" required>
                            </div>
                            <div class="nmbp-form-col">
                                <label class="nmbp-label"><?php esc_html_e('Last Name *', 'nexora-medical-booking-pro'); ?></label>
                                <input type="text" id="nmbp-modal-last-name" class="nmbp-input" placeholder="e.g. Doe" required>
                            </div>
                        </div>

                        <div class="nmbp-form-row">
                            <div class="nmbp-form-col">
                                <label class="nmbp-label"><?php esc_html_e('Phone Number *', 'nexora-medical-booking-pro'); ?></label>
                                <input type="tel" id="nmbp-modal-phone" class="nmbp-input" placeholder="+1 (555) 000-0000" required>
                            </div>
                            <div class="nmbp-form-col">
                                <label class="nmbp-label"><?php esc_html_e('Email Address *', 'nexora-medical-booking-pro'); ?></label>
                                <input type="email" id="nmbp-modal-email" class="nmbp-input" placeholder="john.doe@example.com" required>
                            </div>
                        </div>

                        <div class="nmbp-form-row">
                            <div class="nmbp-form-col">
                                <label class="nmbp-label"><?php esc_html_e('Consultation Type', 'nexora-medical-booking-pro'); ?></label>
                                <select id="nmbp-modal-app-type" class="nmbp-input">
                                    <option value="in_person"><?php esc_html_e('In-Person Visit', 'nexora-medical-booking-pro'); ?></option>
                                    <option value="video_consultation"><?php esc_html_e('Video Consultation', 'nexora-medical-booking-pro'); ?></option>
                                    <option value="follow_up"><?php esc_html_e('Follow-up Visit', 'nexora-medical-booking-pro'); ?></option>
                                </select>
                            </div>
                            <div class="nmbp-form-col">
                                <label class="nmbp-label"><?php esc_html_e('Gender', 'nexora-medical-booking-pro'); ?></label>
                                <select id="nmbp-modal-gender" class="nmbp-input">
                                    <option value="male"><?php esc_html_e('Male', 'nexora-medical-booking-pro'); ?></option>
                                    <option value="female"><?php esc_html_e('Female', 'nexora-medical-booking-pro'); ?></option>
                                    <option value="other"><?php esc_html_e('Other', 'nexora-medical-booking-pro'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="nmbp-form-group" style="margin-bottom:16px;">
                            <label class="nmbp-label"><?php esc_html_e('Reason for Visit / Symptoms (Optional)', 'nexora-medical-booking-pro'); ?></label>
                            <textarea id="nmbp-modal-notes" rows="2" class="nmbp-input" placeholder="<?php esc_attr_e('Any notes or medical symptoms...', 'nexora-medical-booking-pro'); ?>"></textarea>
                        </div>

                        <!-- Payment Selection in Modal -->
                        <div class="nmbp-payment-selection-box" style="margin-top:14px;">
                            <label class="nmbp-label" style="margin-bottom:8px;"><?php esc_html_e('Payment Method *', 'nexora-medical-booking-pro'); ?></label>
                            <div class="nmbp-payment-methods">
                                <?php if ($enable_cash || (!$enable_stripe && !$enable_paypal)): ?>
                                    <label class="nmbp-radio-card active">
                                        <input type="radio" name="modal_payment_method" value="cash" checked>
                                        <div class="nmbp-radio-content">
                                            <strong>&#128181; <?php echo esc_html($cash_title); ?></strong>
                                            <span><?php echo esc_html($cash_desc); ?></span>
                                        </div>
                                    </label>
                                <?php endif; ?>

                                <?php if ($enable_stripe): ?>
                                    <label class="nmbp-radio-card <?php echo (!$enable_cash) ? 'active' : ''; ?>">
                                        <input type="radio" name="modal_payment_method" value="stripe" <?php echo (!$enable_cash) ? 'checked' : ''; ?>>
                                        <div class="nmbp-radio-content">
                                            <strong>&#128179; <?php echo esc_html($stripe_title); ?></strong>
                                            <span><?php echo esc_html($stripe_desc); ?></span>
                                        </div>
                                    </label>
                                <?php endif; ?>

                                <?php if ($enable_paypal): ?>
                                    <label class="nmbp-radio-card <?php echo (!$enable_cash && !$enable_stripe) ? 'active' : ''; ?>">
                                        <input type="radio" name="modal_payment_method" value="paypal" <?php echo (!$enable_cash && !$enable_stripe) ? 'checked' : ''; ?>>
                                        <div class="nmbp-radio-content">
                                            <strong>&#127380; <?php echo esc_html($paypal_title); ?></strong>
                                            <span><?php echo esc_html($paypal_desc); ?></span>
                                        </div>
                                    </label>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="nmbp-modal-footer-actions" style="margin-top:20px; display:flex; gap:12px; justify-content:flex-end;">
                            <button type="button" class="nmbp-btn nmbp-btn-outline" id="nmbp-cancel-modal-btn"><?php esc_html_e('Cancel', 'nexora-medical-booking-pro'); ?></button>
                            <button type="button" class="nmbp-btn nmbp-btn-primary nmbp-btn-lg" id="nmbp-modal-confirm-btn">&#128197; <?php esc_html_e('Confirm & Book Consultation Now', 'nexora-medical-booking-pro'); ?></button>
                        </div>
                    </div>

                    <!-- Confirmation View in Modal -->
                    <div id="nmbp-modal-success-view" style="display:none; text-align:center; padding:24px 10px;">
                        <div class="nmbp-success-icon" style="margin:0 auto 16px auto; width:64px; height:64px; font-size:32px; background:#dcfce7; color:#16a34a; border-radius:50%; display:flex; align-items:center; justify-content:center;">&#10004;</div>
                        <h2 style="margin:0 0 8px 0; color:#0f172a; font-size:22px; font-weight:800;"><?php esc_html_e('Appointment Confirmed & Booked!', 'nexora-medical-booking-pro'); ?></h2>
                        <p style="margin:0 0 16px 0; color:#64748b; font-size:14px;"><?php esc_html_e('Your booking has been registered in the hospital system.', 'nexora-medical-booking-pro'); ?></p>
                        <div class="nmbp-ref-code" id="nmbp-modal-ref-code" style="font-size:22px; font-weight:800; background:#f0fdfa; color:#0d9488; border:2px dashed #0d9488; padding:12px 20px; border-radius:10px; display:inline-block; margin-bottom:16px;">NMBP-0000</div>
                        <p style="font-size:13px; color:#64748b; margin:0 0 24px 0;">&#9432; <?php esc_html_e('Your appointment is currently in Pending verification status. You will receive an email confirmation once verified by the clinic.', 'nexora-medical-booking-pro'); ?></p>
                        <button type="button" class="nmbp-btn nmbp-btn-secondary" id="nmbp-modal-done-btn"><?php esc_html_e('Done & Close', 'nexora-medical-booking-pro'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

