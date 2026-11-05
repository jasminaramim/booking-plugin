<?php
/**
 * HTML Email Notification System
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Email_Manager {

    /**
     * Send event email based on action
     */
    public static function send_event_email(string $event, array $appointment): bool {
        $patient_email = $appointment['patient_email'] ?? '';
        if (empty($patient_email) || !is_email($patient_email)) {
            return false;
        }

        $general_settings = get_option('nmbp_general_settings', []);
        $company_name = $general_settings['company_name'] ?? get_bloginfo('name');

        $subjects = [
            'booking_submitted'       => sprintf(__('[%s] Appointment Request Received - %s', 'nexora-medical-booking-pro'), $company_name, $appointment['appointment_number']),
            'appointment_approved'    => sprintf(__('[%s] Appointment Confirmed - %s', 'nexora-medical-booking-pro'), $company_name, $appointment['appointment_number']),
            'appointment_rejected'    => sprintf(__('[%s] Appointment Update - %s', 'nexora-medical-booking-pro'), $company_name, $appointment['appointment_number']),
            'appointment_cancelled'   => sprintf(__('[%s] Appointment Cancelled - %s', 'nexora-medical-booking-pro'), $company_name, $appointment['appointment_number']),
            'appointment_rescheduled' => sprintf(__('[%s] Appointment Rescheduled - %s', 'nexora-medical-booking-pro'), $company_name, $appointment['appointment_number']),
            'payment_received'        => sprintf(__('[%s] Payment Received - %s', 'nexora-medical-booking-pro'), $company_name, $appointment['appointment_number']),
        ];

        $subject = $subjects[$event] ?? sprintf(__('[%s] Medical Appointment Notice', 'nexora-medical-booking-pro'), $company_name);
        $message = self::render_email_template($event, $appointment, $company_name);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $company_name . ' <' . (get_option('admin_email')) . '>',
        ];

        $sent = wp_mail($patient_email, $subject, $message, $headers);

        NMBP_Notification_Manager::log_notification([
            'appointment_id'    => $appointment['id'],
            'recipient_email'   => $patient_email,
            'recipient_phone'   => $appointment['patient_phone'] ?? '',
            'notification_type' => $event,
            'channel'           => 'email',
            'subject'           => $subject,
            'message_body'      => $message,
            'status'            => $sent ? 'sent' : 'failed',
            'error_message'     => $sent ? null : 'wp_mail returned false',
        ]);

        return $sent;
    }

    /**
     * Render responsive medical HTML email
     */
    public static function render_email_template(string $event, array $app, string $company_name): string {
        $doctor_name = 'Dr. ' . trim(($app['doctor_first_name'] ?? '') . ' ' . ($app['doctor_last_name'] ?? ''));
        $patient_name = trim(($app['patient_first_name'] ?? '') . ' ' . ($app['patient_last_name'] ?? ''));
        $date = date_i18n(get_option('date_format'), strtotime($app['appointment_date']));
        $time = date_i18n(get_option('time_format'), strtotime($app['appointment_date'] . ' ' . $app['start_time']));

        $event_titles = [
            'booking_submitted'       => __('Appointment Request Awaiting Approval', 'nexora-medical-booking-pro'),
            'appointment_approved'    => __('Your Appointment is Confirmed!', 'nexora-medical-booking-pro'),
            'appointment_rejected'    => __('Appointment Request Status', 'nexora-medical-booking-pro'),
            'appointment_cancelled'   => __('Appointment Cancelled', 'nexora-medical-booking-pro'),
            'appointment_rescheduled' => __('Your Appointment Has Been Rescheduled', 'nexora-medical-booking-pro'),
            'payment_received'        => __('Payment Confirmation & Receipt', 'nexora-medical-booking-pro'),
        ];

        $event_messages = [
            'booking_submitted'       => __('Thank you for booking with us. Your appointment request has been received and is currently waiting for admin confirmation. You will receive an update once approved.', 'nexora-medical-booking-pro'),
            'appointment_approved'    => __('Great news! Your medical appointment has been approved and confirmed. Please review your appointment schedule details below.', 'nexora-medical-booking-pro'),
            'appointment_rejected'    => __('We regret to inform you that your appointment request could not be approved at this time. Please contact our reception desk for assistance or choose another time slot.', 'nexora-medical-booking-pro'),
            'appointment_cancelled'   => __('Your appointment has been cancelled as requested.', 'nexora-medical-booking-pro'),
            'appointment_rescheduled' => __('Your appointment schedule has been updated to the new date and time shown below.', 'nexora-medical-booking-pro'),
            'payment_received'        => __('We have successfully verified and received your consultation payment.', 'nexora-medical-booking-pro'),
        ];

        $title = $event_titles[$event] ?? __('Appointment Notification', 'nexora-medical-booking-pro');
        $desc = $event_messages[$event] ?? '';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($title); ?></title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; padding: 40px 10px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: #0f172a; padding: 30px; text-align: center;">
                                    <h1 style="color: #ffffff; font-size: 22px; margin: 0; font-weight: 700; letter-spacing: -0.5px;">
                                        <span style="color: #14b8a6;">Nexora</span> <?php echo esc_html($company_name); ?>
                                    </h1>
                                </td>
                            </tr>
                            <!-- Subheader / Status Banner -->
                            <tr>
                                <td style="background-color: #0d9488; padding: 18px 30px; text-align: center; color: #ffffff;">
                                    <h2 style="margin: 0; font-size: 18px; font-weight: 600;"><?php echo esc_html($title); ?></h2>
                                </td>
                            </tr>
                            <!-- Body Content -->
                            <tr>
                                <td style="padding: 35px 30px;">
                                    <p style="font-size: 15px; line-height: 1.6; margin-top: 0;">
                                        <?php esc_html_e('Hello', 'nexora-medical-booking-pro'); ?> <strong><?php echo esc_html($patient_name); ?></strong>,
                                    </p>
                                    <p style="font-size: 15px; line-height: 1.6; color: #475569;">
                                        <?php echo esc_html($desc); ?>
                                    </p>

                                    <!-- Appointment Details Table -->
                                    <table width="100%" cellpadding="10" cellspacing="0" style="margin: 25px 0; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                                        <tr>
                                            <td style="color: #64748b; width: 40%; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e('Reference Number', 'nexora-medical-booking-pro'); ?></td>
                                            <td style="font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0;"><?php echo esc_html($app['appointment_number']); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="color: #64748b; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e('Doctor', 'nexora-medical-booking-pro'); ?></td>
                                            <td style="font-weight: 600; color: #0f172a; border-bottom: 1px solid #e2e8f0;"><?php echo esc_html($doctor_name); ?> (<?php echo esc_html($app['doctor_specialization'] ?? ''); ?>)</td>
                                        </tr>
                                        <tr>
                                            <td style="color: #64748b; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e('Clinic Location', 'nexora-medical-booking-pro'); ?></td>
                                            <td style="color: #0f172a; border-bottom: 1px solid #e2e8f0;"><?php echo esc_html($app['clinic_name']); ?> - <?php echo esc_html($app['clinic_address'] ?? ''); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="color: #64748b; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e('Date & Time', 'nexora-medical-booking-pro'); ?></td>
                                            <td style="font-weight: 600; color: #0d9488; border-bottom: 1px solid #e2e8f0;"><?php echo esc_html($date); ?> at <?php echo esc_html($time); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="color: #64748b; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e('Consultation Fee', 'nexora-medical-booking-pro'); ?></td>
                                            <td style="color: #0f172a; border-bottom: 1px solid #e2e8f0;">$<?php echo number_format($app['consultation_fee'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="color: #64748b;"><?php esc_html_e('Booking Status', 'nexora-medical-booking-pro'); ?></td>
                                            <td style="font-weight: 700; text-transform: uppercase; color: <?php echo $app['appointment_status'] === 'confirmed' ? '#0d9488' : ($app['appointment_status'] === 'pending' ? '#eab308' : '#ef4444'); ?>;">
                                                <?php echo esc_html($app['appointment_status']); ?>
                                            </td>
                                        </tr>
                                    </table>

                                    <?php if (!empty($app['cancellation_reason'])): ?>
                                        <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px; margin-top: 15px; color: #991b1b; font-size: 14px;">
                                            <strong><?php esc_html_e('Note:', 'nexora-medical-booking-pro'); ?></strong> <?php echo esc_html($app['cancellation_reason']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <p style="font-size: 13px; color: #94a3b8; margin-top: 25px; line-height: 1.5;">
                                        <?php esc_html_e('If you have any questions or need to modify your schedule, please reach out to our clinic team.', 'nexora-medical-booking-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 30px; text-align: center; font-size: 12px; color: #94a3b8;">
                                    &copy; <?php echo date('Y'); ?> <?php echo esc_html($company_name); ?>. <?php esc_html_e('All rights reserved.', 'nexora-medical-booking-pro'); ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}

