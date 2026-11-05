<?php
/**
 * Frontend Shortcodes Registration & Renderers
 *
 * @package Nexora_Medical_Booking_Pro
 */

defined('ABSPATH') || exit;

class NMBP_Shortcodes {

    private static ?NMBP_Shortcodes $instance = null;

    public static function instance(): NMBP_Shortcodes {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('nmbp_clinics', [$this, 'render_clinics']);
        add_shortcode('nmbp_doctors', [$this, 'render_doctors']);
        add_shortcode('nmbp_departments', [$this, 'render_departments']);
        add_shortcode('nmbp_booking', [$this, 'render_booking']);
        add_shortcode('nmbp_doctor_profile', [$this, 'render_doctor_profile']);
        add_shortcode('nmbp_patient_dashboard', [$this, 'render_patient_dashboard']);
        add_shortcode('nmbp_doctor_dashboard', [$this, 'render_doctor_dashboard']);
        add_shortcode('nmbp_appointment_search', [$this, 'render_appointment_search']);
    }

    /**
     * [nmbp_clinics]
     */
    public function render_clinics(array $atts = []): string {
        $atts = shortcode_atts([
            'limit' => 12,
            'city'  => '',
        ], $atts);

        $clinics = NMBP_Clinic_Manager::get_clinics([
            'per_page' => absint($atts['limit']),
            'status'   => 'active',
        ])['items'];

        ob_start();
        ?>
        <div class="nmbp-container nmbp-clinics-grid">
            <?php if (empty($clinics)): ?>
                <div class="nmbp-empty-state"><?php esc_html_e('No clinics available at this time.', 'nexora-medical-booking-pro'); ?></div>
            <?php else: ?>
                <div class="nmbp-grid nmbp-grid-3">
                    <?php foreach ($clinics as $clinic): ?>
                        <div class="nmbp-card nmbp-clinic-card">
                            <?php if (!empty($clinic['cover_image_url'])): ?>
                                <div class="nmbp-clinic-cover" style="background-image: url('<?php echo esc_url($clinic['cover_image_url']); ?>');"></div>
                            <?php endif; ?>
                            <div class="nmbp-card-body">
                                <h3 class="nmbp-card-title"><?php echo esc_html($clinic['name']); ?></h3>
                                <p class="nmbp-clinic-address"><span class="dashicons dashicons-location"></span> <?php echo esc_html($clinic['address']); ?>, <?php echo esc_html($clinic['city']); ?></p>
                                <p class="nmbp-clinic-hours"><span class="dashicons dashicons-clock"></span> <?php echo esc_html(date('h:i A', strtotime($clinic['opening_time']))); ?> - <?php echo esc_html(date('h:i A', strtotime($clinic['closing_time']))); ?></p>
                                <p class="nmbp-clinic-phone"><span class="dashicons dashicons-phone"></span> <?php echo esc_html($clinic['phone']); ?></p>
                                <div class="nmbp-card-footer">
                                    <a href="#nmbp-booking-app" class="nmbp-btn nmbp-btn-primary nmbp-select-clinic-btn" data-clinic-id="<?php echo esc_attr($clinic['id']); ?>">
                                        <?php esc_html_e('Book Appointment', 'nexora-medical-booking-pro'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [nmbp_doctors] - Multi-Preset Doctor Grid with Live Search & Profile Routing
     */
    public function render_doctors(array $atts = []): string {
        $atts = shortcode_atts([
            'department_id' => 0,
            'clinic_id'     => 0,
            'preset'        => '',
            'single_preset' => '',
            'columns'       => 3,
            'limit'         => 12,
            'search'        => 'yes',
            'profile_url'   => '',
        ], $atts);

        // If a specific doctor profile is requested via URL on this page, render the full details view
        if (!empty($_GET['doctor_id']) && absint($_GET['doctor_id']) > 0) {
            return $this->render_doctor_profile([
                'id'     => absint($_GET['doctor_id']),
                'preset' => !empty($atts['single_preset']) ? sanitize_text_field($atts['single_preset']) : '',
            ]);
        }

        $design = get_option('nmbp_design_settings', []);
        $preset = !empty($atts['preset']) ? sanitize_text_field($atts['preset']) : ($design['card_preset'] ?? 'classic_medical');
        $columns = in_array((int) $atts['columns'], [2, 3, 4], true) ? (int) $atts['columns'] : 3;

        $doctors = NMBP_Doctor_Manager::get_doctors([
            'department_id' => absint($atts['department_id']),
            'clinic_id'     => absint($atts['clinic_id']),
            'per_page'      => $atts['limit'] == -1 ? 999 : absint($atts['limit']),
            'status'        => 'active',
        ])['items'];

        global $wpdb;
        $dept_table = NMBP_Database::table('departments');
        $clinic_table = NMBP_Database::table('clinics');
        $all_departments = $wpdb->get_results("SELECT id, name FROM {$dept_table} WHERE status = 'active' ORDER BY name ASC", ARRAY_A);
        $all_clinics = $wpdb->get_results("SELECT id, name FROM {$clinic_table} WHERE status = 'active' ORDER BY name ASC", ARRAY_A);

        ob_start();
        ?>
        <div class="nmbp-container nmbp-doctors-wrapper nmbp-preset-<?php echo esc_attr($preset); ?>">
            <?php if ($atts['search'] === 'yes'): ?>
                <!-- Live Search and Filter Bar -->
                <div class="nmbp-doctors-filter-bar">
                    <div class="nmbp-filter-field nmbp-filter-search-box">
                        <span class="dashicons dashicons-search nmbp-filter-icon"></span>
                        <input type="text" class="nmbp-filter-search-input" placeholder="<?php esc_attr_e('Search doctor by name, specialty, etc...', 'nexora-medical-booking-pro'); ?>">
                    </div>
                    <?php if (!empty($all_departments)): ?>
                        <div class="nmbp-filter-field">
                            <select class="nmbp-filter-dept-select">
                                <option value=""><?php esc_html_e('All Departments', 'nexora-medical-booking-pro'); ?></option>
                                <?php foreach ($all_departments as $d): ?>
                                    <option value="<?php echo esc_attr($d['id']); ?>"><?php echo esc_html($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($all_clinics)): ?>
                        <div class="nmbp-filter-field">
                            <select class="nmbp-filter-clinic-select">
                                <option value=""><?php esc_html_e('All Hospital Branches', 'nexora-medical-booking-pro'); ?></option>
                                <?php foreach ($all_clinics as $c): ?>
                                    <option value="<?php echo esc_attr($c['id']); ?>"><?php echo esc_html($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="nmbp-filter-stats">
                        <span class="nmbp-filter-count-badge"><strong id="nmbp-filter-count"><?php echo count($doctors); ?></strong> <?php esc_html_e('Doctors', 'nexora-medical-booking-pro'); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($doctors)): ?>
                <div class="nmbp-empty-state">
                    <span class="dashicons dashicons-id" style="font-size: 40px; width: 40px; height: 40px; color: #94a3b8; margin-bottom: 12px;"></span>
                    <p><?php esc_html_e('No doctors found matching your criteria.', 'nexora-medical-booking-pro'); ?></p>
                </div>
            <?php else: ?>
                <div class="nmbp-doctors-grid nmbp-grid nmbp-grid-cols-<?php echo esc_attr($columns); ?>">
                    <?php foreach ($doctors as $doc):
                        $photo_url = $doc['photo_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($doc['first_name'] . '+' . $doc['last_name']) . '&background=0d9488&color=fff&size=160';
                        $full_name = 'Dr. ' . $doc['first_name'] . ' ' . $doc['last_name'];
                        $dept_name = $doc['department_name'] ?? __('Specialist', 'nexora-medical-booking-pro');
                        $clinic_ids = !empty($doc['clinics']) ? implode(',', array_column($doc['clinics'], 'clinic_id')) : '';

                        // Determine Details Link
                        if (!empty($atts['profile_url'])) {
                            $details_link = add_query_arg('doctor_id', $doc['id'], $atts['profile_url']);
                        } else {
                            $details_link = add_query_arg('doctor_id', $doc['id']);
                        }
                    ?>
                        <div class="nmbp-doctor-card nmbp-card-<?php echo esc_attr($preset); ?>"
                             data-doctor-id="<?php echo esc_attr($doc['id']); ?>"
                             data-name="<?php echo esc_attr(strtolower($full_name)); ?>"
                             data-dept-id="<?php echo esc_attr($doc['department_id']); ?>"
                             data-specialty="<?php echo esc_attr(strtolower($doc['specialization'])); ?>"
                             data-clinics="<?php echo esc_attr($clinic_ids); ?>">

                            <?php if ($preset === 'modern_minimal' || $preset === 'modern_glass'): ?>
                                <!-- Preset 02: Modern Minimal / Glass Card -->
                                <div class="nmbp-modern-cover">
                                    <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($full_name); ?>" class="nmbp-modern-img">
                                    <span class="nmbp-badge nmbp-badge-floating"><?php echo esc_html($dept_name); ?></span>
                                    <div class="nmbp-modern-fee-pill">$<?php echo number_format($doc['consultation_fee'], 2); ?></div>
                                </div>
                                <div class="nmbp-doctor-content">
                                    <div class="nmbp-doctor-title-row">
                                        <h3 class="nmbp-doctor-name"><?php echo esc_html($full_name); ?></h3>
                                        <span class="nmbp-verified-badge" title="<?php esc_attr_e('Verified Specialist', 'nexora-medical-booking-pro'); ?>">&#10003;</span>
                                    </div>
                                    <p class="nmbp-doctor-spec"><?php echo esc_html($doc['specialization']); ?></p>
                                    <div class="nmbp-modern-meta-row">
                                        <span>&#128337; <?php echo esc_html($doc['appointment_duration'] ?? 20); ?> <?php esc_html_e('mins', 'nexora-medical-booking-pro'); ?></span>
                                        <span>&#127942; <?php echo esc_html($doc['experience_years']); ?>+ <?php esc_html_e('Yrs Exp.', 'nexora-medical-booking-pro'); ?></span>
                                    </div>
                                    <div class="nmbp-card-actions">
                                        <a href="<?php echo esc_url($details_link); ?>" class="nmbp-btn nmbp-btn-outline nmbp-view-doc-btn" data-doc-id="<?php echo esc_attr($doc['id']); ?>">
                                            <?php esc_html_e('Profile', 'nexora-medical-booking-pro'); ?>
                                        </a>
                                        <button type="button" class="nmbp-btn nmbp-btn-primary nmbp-book-doctor-btn" data-doctor-id="<?php echo esc_attr($doc['id']); ?>">
                                            <?php esc_html_e('Book Now &rarr;', 'nexora-medical-booking-pro'); ?>
                                        </button>
                                    </div>
                                </div>

                            <?php elseif ($preset === 'hospital_pro'): ?>
                                <!-- Preset 03: Hospital Professional Structured -->
                                <div class="nmbp-hospital-left">
                                    <div class="nmbp-hospital-avatar">
                                        <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($full_name); ?>">
                                    </div>
                                    <?php if (!empty($doc['bmdc_reg_number'])): ?>
                                        <span class="nmbp-reg-tag"><?php echo esc_html($doc['bmdc_reg_number']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="nmbp-hospital-right">
                                    <div class="nmbp-hospital-header">
                                        <span class="nmbp-badge nmbp-badge-hospital"><?php echo esc_html($dept_name); ?></span>
                                        <span class="nmbp-hospital-fee">$<?php echo number_format($doc['consultation_fee'], 2); ?></span>
                                    </div>
                                    <h3 class="nmbp-doctor-name"><?php echo esc_html($full_name); ?></h3>
                                    <p class="nmbp-doctor-spec"><?php echo esc_html($doc['specialization']); ?></p>
                                    <?php if (!empty($doc['clinics'])): ?>
                                        <p class="nmbp-hospital-clinics">&#127973; <?php echo esc_html(implode(', ', array_column($doc['clinics'], 'clinic_name'))); ?></p>
                                    <?php endif; ?>
                                    <div class="nmbp-hospital-footer">
                                        <span class="nmbp-exp-badge">&#127942; <?php echo esc_html($doc['experience_years']); ?>+ <?php esc_html_e('Yrs Exp', 'nexora-medical-booking-pro'); ?></span>
                                        <div class="nmbp-hospital-btns">
                                            <a href="<?php echo esc_url($details_link); ?>" class="nmbp-btn nmbp-btn-sm nmbp-btn-outline nmbp-view-doc-btn" data-doc-id="<?php echo esc_attr($doc['id']); ?>">
                                                <?php esc_html_e('Details', 'nexora-medical-booking-pro'); ?>
                                            </a>
                                            <button type="button" class="nmbp-btn nmbp-btn-sm nmbp-btn-primary nmbp-book-doctor-btn" data-doctor-id="<?php echo esc_attr($doc['id']); ?>">
                                                <?php esc_html_e('Book Now', 'nexora-medical-booking-pro'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($preset === 'compact_card' || $preset === 'minimal_clean'): ?>
                                <!-- Preset 04: Compact Circular Directory Card -->
                                <div class="nmbp-compact-header">
                                    <span class="nmbp-badge nmbp-badge-compact"><?php echo esc_html($dept_name); ?></span>
                                    <span class="nmbp-compact-fee">$<?php echo number_format($doc['consultation_fee'], 2); ?></span>
                                </div>
                                <div class="nmbp-compact-avatar">
                                    <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($full_name); ?>">
                                </div>
                                <h3 class="nmbp-doctor-name"><?php echo esc_html($full_name); ?></h3>
                                <p class="nmbp-doctor-spec"><?php echo esc_html($doc['specialization']); ?></p>
                                <p class="nmbp-compact-exp">&#127942; <?php echo esc_html($doc['experience_years']); ?>+ <?php esc_html_e('Years Experience', 'nexora-medical-booking-pro'); ?></p>
                                <div class="nmbp-compact-actions">
                                    <a href="<?php echo esc_url($details_link); ?>" class="nmbp-btn nmbp-btn-sm nmbp-btn-outline nmbp-view-doc-btn" data-doc-id="<?php echo esc_attr($doc['id']); ?>">
                                        <?php esc_html_e('View Profile', 'nexora-medical-booking-pro'); ?>
                                    </a>
                                    <button type="button" class="nmbp-btn nmbp-btn-sm nmbp-btn-primary nmbp-book-doctor-btn" data-doctor-id="<?php echo esc_attr($doc['id']); ?>">
                                        <?php esc_html_e('Quick Book', 'nexora-medical-booking-pro'); ?>
                                    </button>
                                </div>

                            <?php else: ?>
                                <!-- Preset 01: Classic Medical Card (Default) -->
                                <div class="nmbp-card-top-badge">
                                    <span class="nmbp-badge nmbp-dept-badge"><?php echo esc_html($dept_name); ?></span>
                                </div>
                                <div class="nmbp-doctor-avatar-wrap">
                                    <div class="nmbp-doctor-avatar">
                                        <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($full_name); ?>">
                                        <span class="nmbp-status-dot" title="<?php esc_attr_e('Available for booking', 'nexora-medical-booking-pro'); ?>"></span>
                                    </div>
                                </div>
                                <div class="nmbp-doctor-content">
                                    <h3 class="nmbp-doctor-name"><?php echo esc_html($full_name); ?></h3>
                                    <p class="nmbp-doctor-spec"><?php echo esc_html($doc['specialization']); ?></p>
                                    <?php if (!empty($doc['qualifications'])): ?>
                                        <p class="nmbp-doctor-qual"><?php echo esc_html($doc['qualifications']); ?></p>
                                    <?php endif; ?>
                                    <div class="nmbp-doctor-meta">
                                        <span class="nmbp-meta-item"><strong><?php echo esc_html($doc['experience_years']); ?>+</strong> <?php esc_html_e('Yrs Exp.', 'nexora-medical-booking-pro'); ?></span>
                                        <span class="nmbp-meta-item"><strong>$<?php echo number_format($doc['consultation_fee'], 2); ?></strong> <?php esc_html_e('Fee', 'nexora-medical-booking-pro'); ?></span>
                                    </div>
                                    <div class="nmbp-card-actions">
                                        <a href="<?php echo esc_url($details_link); ?>" class="nmbp-btn nmbp-btn-outline nmbp-view-doc-btn" data-doc-id="<?php echo esc_attr($doc['id']); ?>">
                                            <?php esc_html_e('View Profile', 'nexora-medical-booking-pro'); ?>
                                        </a>
                                        <button type="button" class="nmbp-btn nmbp-btn-primary nmbp-book-doctor-btn" data-doctor-id="<?php echo esc_attr($doc['id']); ?>">
                                            <?php esc_html_e('Book Appointment', 'nexora-medical-booking-pro'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [nmbp_departments]
     */
    public function render_departments(array $atts = []): string {
        global $wpdb;
        $table = NMBP_Database::table('departments');
        $depts = $wpdb->get_results("SELECT * FROM {$table} WHERE status = 'active' ORDER BY name ASC", ARRAY_A);

        ob_start();
        ?>
        <div class="nmbp-container nmbp-departments-grid">
            <div class="nmbp-grid nmbp-grid-4">
                <?php foreach ($depts as $dept): ?>
                    <div class="nmbp-card nmbp-dept-card" data-department-id="<?php echo esc_attr($dept['id']); ?>">
                        <div class="nmbp-dept-icon">
                            <span class="dashicons dashicons-heart"></span>
                        </div>
                        <h4 class="nmbp-dept-title"><?php echo esc_html($dept['name']); ?></h4>
                        <p class="nmbp-dept-desc"><?php echo esc_html($dept['description'] ?: __('Specialized clinical department', 'nexora-medical-booking-pro')); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [nmbp_booking] - Smart Multi-Step Booking Flow
     */
    public function render_booking(array $atts = []): string {
        $design = get_option('nmbp_design_settings', []);
        $form_preset = $design['form_preset'] ?? 'multi_step';

        ob_start();
        ?>
        <div id="nmbp-booking-app" class="nmbp-booking-wrapper nmbp-form-preset-<?php echo esc_attr($form_preset); ?>">
            <div class="nmbp-booking-container">
                <!-- Multi-step Wizard Header -->
                <div class="nmbp-stepper-header">
                    <div class="nmbp-step-item active" data-step="1">
                        <span class="nmbp-step-num">1</span>
                        <span class="nmbp-step-label"><?php esc_html_e('Clinic', 'nexora-medical-booking-pro'); ?></span>
                    </div>
                    <div class="nmbp-step-item" data-step="2">
                        <span class="nmbp-step-num">2</span>
                        <span class="nmbp-step-label"><?php esc_html_e('Department', 'nexora-medical-booking-pro'); ?></span>
                    </div>
                    <div class="nmbp-step-item" data-step="3">
                        <span class="nmbp-step-num">3</span>
                        <span class="nmbp-step-label"><?php esc_html_e('Doctor', 'nexora-medical-booking-pro'); ?></span>
                    </div>
                    <div class="nmbp-step-item" data-step="4">
                        <span class="nmbp-step-num">4</span>
                        <span class="nmbp-step-label"><?php esc_html_e('Date & Slot', 'nexora-medical-booking-pro'); ?></span>
                    </div>
                    <div class="nmbp-step-item" data-step="5">
                        <span class="nmbp-step-num">5</span>
                        <span class="nmbp-step-label"><?php esc_html_e('Details & Pay', 'nexora-medical-booking-pro'); ?></span>
                    </div>
                </div>

                <!-- Wizard Steps Content -->
                <div class="nmbp-wizard-body">
                    <!-- Step 1: Clinic Selection -->
                    <div class="nmbp-step-pane active" id="nmbp-step-1">
                        <h3 class="nmbp-step-title"><?php esc_html_e('Select Hospital Branch or Clinic', 'nexora-medical-booking-pro'); ?></h3>
                        <p class="nmbp-step-subtitle"><?php esc_html_e('Choose your preferred clinic location for your consultation.', 'nexora-medical-booking-pro'); ?></p>
                        <div class="nmbp-clinic-options-grid" id="nmbp-clinic-select-list">
                            <div class="nmbp-loading-spinner"><?php esc_html_e('Loading clinics...', 'nexora-medical-booking-pro'); ?></div>
                        </div>
                    </div>

                    <!-- Step 2: Department Selection -->
                    <div class="nmbp-step-pane" id="nmbp-step-2">
                        <h3 class="nmbp-step-title"><?php esc_html_e('Select Medical Department', 'nexora-medical-booking-pro'); ?></h3>
                        <div class="nmbp-dept-options-grid" id="nmbp-dept-select-list">
                            <div class="nmbp-loading-spinner"><?php esc_html_e('Loading departments...', 'nexora-medical-booking-pro'); ?></div>
                        </div>
                    </div>

                    <!-- Step 3: Doctor Selection -->
                    <div class="nmbp-step-pane" id="nmbp-step-3">
                        <h3 class="nmbp-step-title"><?php esc_html_e('Choose Your Doctor', 'nexora-medical-booking-pro'); ?></h3>
                        <div class="nmbp-doctor-options-grid" id="nmbp-doctor-select-list">
                            <div class="nmbp-loading-spinner"><?php esc_html_e('Loading doctors...', 'nexora-medical-booking-pro'); ?></div>
                        </div>
                    </div>

                    <!-- Step 4: Date & Available Slots Selection -->
                    <div class="nmbp-step-pane" id="nmbp-step-4">
                        <h3 class="nmbp-step-title"><?php esc_html_e('Choose Appointment Date & Time Slot', 'nexora-medical-booking-pro'); ?></h3>
                        <div class="nmbp-datetime-layout">
                            <div class="nmbp-date-selector">
                                <label for="nmbp-booking-date" style="display:block; margin-bottom:8px; font-weight:600; color:#1e293b;"><?php esc_html_e('Select Consultation Date:', 'nexora-medical-booking-pro'); ?></label>
                                <input type="date" id="nmbp-booking-date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" class="nmbp-input">
                            </div>
                            <div class="nmbp-slot-selector">
                                <label style="display:block; margin-bottom:8px; font-weight:600; color:#1e293b;"><?php esc_html_e('Available Time Slots:', 'nexora-medical-booking-pro'); ?></label>
                                <div id="nmbp-slots-container" class="nmbp-slots-grid">
                                    <div class="nmbp-text-muted"><?php esc_html_e('Select a date to view available time slots.', 'nexora-medical-booking-pro'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Patient Details & Payment Selection -->
                    <div class="nmbp-step-pane" id="nmbp-step-5">
                        <h3 class="nmbp-step-title"><?php esc_html_e('Patient Information & Payment', 'nexora-medical-booking-pro'); ?></h3>
                        <form id="nmbp-booking-form" onsubmit="return false;">
                            <div class="nmbp-form-row">
                                <div class="nmbp-form-col">
                                    <label class="nmbp-label"><?php esc_html_e('First Name *', 'nexora-medical-booking-pro'); ?></label>
                                    <input type="text" name="first_name" required class="nmbp-input" placeholder="e.g. John">
                                </div>
                                <div class="nmbp-form-col">
                                    <label class="nmbp-label"><?php esc_html_e('Last Name *', 'nexora-medical-booking-pro'); ?></label>
                                    <input type="text" name="last_name" required class="nmbp-input" placeholder="e.g. Doe">
                                </div>
                            </div>
                            <div class="nmbp-form-row">
                                <div class="nmbp-form-col">
                                    <label class="nmbp-label"><?php esc_html_e('Email Address *', 'nexora-medical-booking-pro'); ?></label>
                                    <input type="email" name="email" required class="nmbp-input" placeholder="john.doe@example.com">
                                </div>
                                <div class="nmbp-form-col">
                                    <label class="nmbp-label"><?php esc_html_e('Phone Number *', 'nexora-medical-booking-pro'); ?></label>
                                    <input type="tel" name="phone" required class="nmbp-input" placeholder="+1 (555) 000-0000">
                                </div>
                            </div>
                            <div class="nmbp-form-row">
                                <div class="nmbp-form-col">
                                    <label class="nmbp-label"><?php esc_html_e('Gender', 'nexora-medical-booking-pro'); ?></label>
                                    <select name="gender" class="nmbp-input">
                                        <option value="male"><?php esc_html_e('Male', 'nexora-medical-booking-pro'); ?></option>
                                        <option value="female"><?php esc_html_e('Female', 'nexora-medical-booking-pro'); ?></option>
                                        <option value="other"><?php esc_html_e('Other', 'nexora-medical-booking-pro'); ?></option>
                                    </select>
                                </div>
                                <div class="nmbp-form-col">
                                    <label class="nmbp-label"><?php esc_html_e('Consultation Type', 'nexora-medical-booking-pro'); ?></label>
                                    <select name="appointment_type" class="nmbp-input">
                                        <option value="in_person"><?php esc_html_e('In-Person Visit', 'nexora-medical-booking-pro'); ?></option>
                                        <option value="video_consultation"><?php esc_html_e('Video Consultation', 'nexora-medical-booking-pro'); ?></option>
                                        <option value="follow_up"><?php esc_html_e('Follow-up Checkup', 'nexora-medical-booking-pro'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="nmbp-form-group">
                                <label class="nmbp-label"><?php esc_html_e('Patient Symptoms / Medical Notes (Optional)', 'nexora-medical-booking-pro'); ?></label>
                                <textarea name="patient_notes" rows="3" class="nmbp-input" placeholder="<?php esc_attr_e('Describe your symptoms or reason for visit...', 'nexora-medical-booking-pro'); ?>"></textarea>
                            </div>

                            <!-- Payment Method Selection -->
                            <?php
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
                            <div class="nmbp-payment-selection-box">
                                <h4 style="margin: 0 0 10px 0; color: #0f172a; font-size: 15px;"><?php esc_html_e('Select Payment Method', 'nexora-medical-booking-pro'); ?></h4>
                                <div class="nmbp-payment-methods">
                                    <?php if ($enable_cash || (!$enable_stripe && !$enable_paypal)): ?>
                                        <label class="nmbp-radio-card active">
                                            <input type="radio" name="payment_method" value="cash" checked>
                                            <div class="nmbp-radio-content">
                                                <strong>&#128181; <?php echo esc_html($cash_title); ?></strong>
                                                <span><?php echo esc_html($cash_desc); ?></span>
                                            </div>
                                        </label>
                                    <?php endif; ?>

                                    <?php if ($enable_stripe): ?>
                                        <label class="nmbp-radio-card <?php echo (!$enable_cash) ? 'active' : ''; ?>">
                                            <input type="radio" name="payment_method" value="stripe" <?php echo (!$enable_cash) ? 'checked' : ''; ?>>
                                            <div class="nmbp-radio-content">
                                                <strong>&#128179; <?php echo esc_html($stripe_title); ?></strong>
                                                <span><?php echo esc_html($stripe_desc); ?></span>
                                            </div>
                                        </label>
                                    <?php endif; ?>

                                    <?php if ($enable_paypal): ?>
                                        <label class="nmbp-radio-card <?php echo (!$enable_cash && !$enable_stripe) ? 'active' : ''; ?>">
                                            <input type="radio" name="payment_method" value="paypal" <?php echo (!$enable_cash && !$enable_stripe) ? 'checked' : ''; ?>>
                                            <div class="nmbp-radio-content">
                                                <strong>&#127380; <?php echo esc_html($paypal_title); ?></strong>
                                                <span><?php echo esc_html($paypal_desc); ?></span>
                                            </div>
                                        </label>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Honeypot anti-spam trap (invisible) -->
                            <div style="display:none !important;" aria-hidden="true">
                                <input type="text" name="fax_number" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="nmbp-booking-summary-box" id="nmbp-summary-box">
                                <div class="nmbp-summary-header"><?php esc_html_e('Appointment Review', 'nexora-medical-booking-pro'); ?></div>
                                <div class="nmbp-summary-details">
                                    <p><strong><?php esc_html_e('Branch:', 'nexora-medical-booking-pro'); ?></strong> <span id="nmbp-sum-clinic">-</span></p>
                                    <p><strong><?php esc_html_e('Doctor:', 'nexora-medical-booking-pro'); ?></strong> <span id="nmbp-sum-doctor">-</span></p>
                                    <p><strong><?php esc_html_e('Date & Time:', 'nexora-medical-booking-pro'); ?></strong> <span id="nmbp-sum-datetime">-</span></p>
                                    <p><strong><?php esc_html_e('Estimated Fee:', 'nexora-medical-booking-pro'); ?></strong> <span id="nmbp-sum-fee" style="color:#0d9488; font-weight:700;">-</span></p>
                                    <p style="font-size:12px; color:#64748b; margin-top:8px;">* <?php esc_html_e('All appointment requests require admin approval before final confirmation.', 'nexora-medical-booking-pro'); ?></p>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Step 6: Confirmation Screen -->
                    <div class="nmbp-step-pane" id="nmbp-step-success">
                        <div class="nmbp-success-banner">
                            <div class="nmbp-success-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                            <h2><?php esc_html_e('Appointment Request Submitted!', 'nexora-medical-booking-pro'); ?></h2>
                            <p><?php esc_html_e('Your appointment reference number is:', 'nexora-medical-booking-pro'); ?></p>
                            <div class="nmbp-ref-code" id="nmbp-final-ref-code">NMBP-0000</div>
                            <p class="nmbp-success-note"><?php esc_html_e('Your request has been submitted for admin verification. You will receive an email confirmation once approved.', 'nexora-medical-booking-pro'); ?></p>
                            <button type="button" class="nmbp-btn nmbp-btn-secondary" onclick="window.location.reload();"><?php esc_html_e('Book Another Appointment', 'nexora-medical-booking-pro'); ?></button>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="nmbp-wizard-footer">
                    <button type="button" class="nmbp-btn nmbp-btn-outline" id="nmbp-btn-prev" style="display:none;"><?php esc_html_e('&larr; Back', 'nexora-medical-booking-pro'); ?></button>
                    <button type="button" class="nmbp-btn nmbp-btn-primary" id="nmbp-btn-next"><?php esc_html_e('Continue &rarr;', 'nexora-medical-booking-pro'); ?></button>
                    <button type="button" class="nmbp-btn nmbp-btn-success" id="nmbp-btn-submit" style="display:none;"><?php esc_html_e('Confirm & Submit Request', 'nexora-medical-booking-pro'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [nmbp_doctor_profile id="123"] - Comprehensive Individual Doctor Profile Page (4 Presets)
     */
    public function render_doctor_profile(array $atts = []): string {
        $id = absint($atts['id'] ?? ($_GET['doctor_id'] ?? ($_GET['id'] ?? 0)));
        if (!$id) {
            return '<div class="nmbp-container"><div class="nmbp-empty-state"><p>' . esc_html__('No doctor specified. Please select a doctor from the directory.', 'nexora-medical-booking-pro') . '</p></div></div>';
        }

        $doc = NMBP_Doctor_Manager::get_doctor($id);
        if (!$doc) {
            return '<div class="nmbp-container"><div class="nmbp-empty-state"><p>' . esc_html__('Doctor profile not found.', 'nexora-medical-booking-pro') . '</p></div></div>';
        }

        $design = get_option('nmbp_design_settings', []);
        $preset = !empty($atts['preset']) ? sanitize_text_field($atts['preset']) : ($design['single_doctor_preset'] ?? 'clinical_split');

        $schedules = NMBP_Schedule_Manager::get_schedules($id);
        $back_url = remove_query_arg(['doctor_id', 'id']);
        $photo_url = $doc['photo_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($doc['first_name'] . '+' . $doc['last_name']) . '&background=0d9488&color=fff&size=200';
        $full_name = 'Dr. ' . $doc['first_name'] . ' ' . $doc['last_name'];
        $dept_name = $doc['department_name'] ?? __('Medical Specialist', 'nexora-medical-booking-pro');

        ob_start();
        ?>
        <div class="nmbp-container nmbp-doctor-profile-wrapper nmbp-single-preset-<?php echo esc_attr($preset); ?>">
            <!-- Navigation Back Button -->
            <div class="nmbp-profile-nav">
                <a href="<?php echo esc_url($back_url); ?>" class="nmbp-btn nmbp-btn-outline nmbp-back-btn">
                    &larr; <?php esc_html_e('Back to All Doctors', 'nexora-medical-booking-pro'); ?>
                </a>
            </div>

            <?php if ($preset === 'executive_banner'): ?>
                <!-- ============================================================
                     SINGLE PRESET 02: Modern Executive Hero Banner
                     ============================================================ -->
                <div class="nmbp-exec-hero-banner">
                    <div class="nmbp-exec-banner-content">
                        <div class="nmbp-exec-avatar-wrap">
                            <img src="<?php echo esc_url($photo_url); ?>" class="nmbp-exec-avatar" alt="<?php echo esc_attr($full_name); ?>">
                            <span class="nmbp-profile-status-dot" title="<?php esc_attr_e('Available', 'nexora-medical-booking-pro'); ?>"></span>
                        </div>
                        <div class="nmbp-exec-info">
                            <div class="nmbp-profile-badge-row">
                                <span class="nmbp-badge nmbp-badge-dept"><?php echo esc_html($dept_name); ?></span>
                                <span class="nmbp-verified-pill">&#10003; <?php esc_html_e('Verified Specialist', 'nexora-medical-booking-pro'); ?></span>
                                <?php if (!empty($doc['bmdc_reg_number'])): ?>
                                    <span class="nmbp-reg-pill"><?php esc_html_e('BMDC:', 'nexora-medical-booking-pro'); ?> <strong><?php echo esc_html($doc['bmdc_reg_number']); ?></strong></span>
                                <?php endif; ?>
                            </div>
                            <h1 class="nmbp-exec-title"><?php echo esc_html($full_name); ?></h1>
                            <p class="nmbp-exec-specialty"><?php echo esc_html($doc['specialization']); ?></p>
                            <div class="nmbp-exec-quick-stats">
                                <span>&#127942; <strong><?php echo esc_html($doc['experience_years']); ?>+</strong> <?php esc_html_e('Years Experience', 'nexora-medical-booking-pro'); ?></span>
                                <span>&#128179; <strong>$<?php echo number_format($doc['consultation_fee'], 2); ?></strong> <?php esc_html_e('Consultation Fee', 'nexora-medical-booking-pro'); ?></span>
                                <span>&#128337; <strong><?php echo esc_html($doc['appointment_duration'] ?? 20); ?></strong> <?php esc_html_e('Mins Session', 'nexora-medical-booking-pro'); ?></span>
                            </div>
                        </div>
                        <div class="nmbp-exec-action-box">
                            <button type="button" class="nmbp-btn nmbp-btn-primary nmbp-btn-lg nmbp-book-doctor-btn" data-doctor-id="<?php echo esc_attr($doc['id']); ?>">
                                &#128197; <?php esc_html_e('Book Consultation', 'nexora-medical-booking-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="nmbp-exec-sections-grid">
                    <div class="nmbp-profile-card-block">
                        <h3 class="nmbp-block-heading">&#128100; <?php esc_html_e('Biography & Credentials', 'nexora-medical-booking-pro'); ?></h3>
                        <div class="nmbp-block-content">
                            <p><?php echo nl2br(esc_html($doc['biography'] ?: __('Experienced medical specialist committed to evidence-based healthcare and patient wellness.', 'nexora-medical-booking-pro'))); ?></p>
                            <?php if (!empty($doc['qualifications'])): ?>
                                <h4 style="margin: 18px 0 8px 0; color: #0f172a; font-size: 15px;">&#127891; <?php esc_html_e('Qualifications', 'nexora-medical-booking-pro'); ?></h4>
                                <p><?php echo nl2br(esc_html($doc['qualifications'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="nmbp-profile-card-block">
                        <h3 class="nmbp-block-heading">&#127973; <?php esc_html_e('Practicing Hospital Branches', 'nexora-medical-booking-pro'); ?></h3>
                        <div class="nmbp-profile-clinics-grid">
                            <?php foreach (($doc['clinics'] ?? []) as $c): ?>
                                <div class="nmbp-profile-clinic-item">
                                    <div class="nmbp-clinic-icon-circle">&#127973;</div>
                                    <div class="nmbp-clinic-item-details">
                                        <h4><?php echo esc_html($c['clinic_name']); ?></h4>
                                        <p class="nmbp-clinic-addr">&#128205; <?php echo esc_html($c['address']); ?>, <?php echo esc_html($c['city']); ?></p>
                                        <div class="nmbp-clinic-fee-tag">
                                            <strong>$<?php echo number_format($c['clinic_fee'] ?: $doc['consultation_fee'], 2); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="nmbp-profile-card-block">
                        <h3 class="nmbp-block-heading">&#128197; <?php esc_html_e('Weekly Availability Timetable', 'nexora-medical-booking-pro'); ?></h3>
                        <?php if (empty($schedules)): ?>
                            <p class="nmbp-text-muted"><?php esc_html_e('Standard weekly schedule applies.', 'nexora-medical-booking-pro'); ?></p>
                        <?php else: ?>
                            <div class="nmbp-schedule-table-wrap">
                                <table class="nmbp-schedule-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Day', 'nexora-medical-booking-pro'); ?></th>
                                            <th><?php esc_html_e('Branch', 'nexora-medical-booking-pro'); ?></th>
                                            <th><?php esc_html_e('Hours', 'nexora-medical-booking-pro'); ?></th>
                                            <th><?php esc_html_e('Action', 'nexora-medical-booking-pro'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedules as $s): ?>
                                            <tr>
                                                <td><strong><?php echo esc_html(ucfirst($s['day_of_week'])); ?></strong></td>
                                                <td><?php echo esc_html($s['clinic_name'] ?? __('Main Branch', 'nexora-medical-booking-pro')); ?></td>
                                                <td><span class="nmbp-shift-pill"><?php echo esc_html(date('h:i A', strtotime($s['start_time']))); ?> - <?php echo esc_html(date('h:i A', strtotime($s['end_time']))); ?></span></td>
                                                <td>
                                                    <button type="button" class="nmbp-btn nmbp-btn-sm nmbp-btn-primary nmbp-book-doctor-btn" data-doctor-id="<?php echo esc_attr($doc['id']); ?>">
                                                        <?php esc_html_e('Book', 'nexora-medical-booking-pro'); ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($preset === 'minimal_centered'): ?>
                <!-- ============================================================
                     SINGLE PRESET 03: Minimal Centered Spotlight
                     ============================================================ -->
                <div class="nmbp-minimal-spotlight-card">
                    <div class="nmbp-minimal-avatar-wrap">
                        <img src="<?php echo esc_url($photo_url); ?>" class="nmbp-minimal-avatar" alt="<?php echo esc_attr($full_name); ?>">
                        <span class="nmbp-profile-status-dot"></span>
                    </div>
                    <span class="nmbp-badge nmbp-badge-dept" style="margin-bottom:8px;"><?php echo esc_html($dept_name); ?></span>
                    <h1 class="nmbp-profile-title" style="margin:4px 0;"><?php echo esc_html($full_name); ?></h1>
                    <p class="nmbp-profile-specialty"><?php echo esc_html($doc['specialization']); ?></p>

                    <div class="nmbp-minimal-meta-strip">
                        <span>&#127942; <?php echo esc_html($doc['experience_years']); ?>+ <?php esc_html_e('Yrs Exp', 'nexora-medical-booking-pro'); ?></span>
                        <span>&#128179; $<?php echo number_format($doc['consultation_fee'], 2); ?></span>
                        <?php if (!empty($doc['bmdc_reg_number'])): ?>
                            <span>&#128203; <?php echo esc_html($doc['bmdc_reg_number']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="nmbp-minimal-bio">
                        <p><?php echo nl2br(esc_html($doc['biography'] ?: __('Dedicated medical specialist delivering focused and compassionate patient care.', 'nexora-medical-booking-pro'))); ?></p>
                    </div>

                    <?php if (!empty($doc['clinics'])): ?>
                        <div class="nmbp-minimal-clinics">
                            <h4>&#127973; <?php esc_html_e('Practicing Branches', 'nexora-medical-booking-pro'); ?></h4>
                            <div class="nmbp-profile-clinics-grid">
                                <?php foreach ($doc['clinics'] as $c): ?>
                                    <div class="nmbp-profile-clinic-item" style="text-align:left;">
                                        <div class="nmbp-clinic-icon-circle">&#127973;</div>
                                        <div class="nmbp-clinic-item-details">
                                            <h4><?php echo esc_html($c['clinic_name']); ?></h4>
                                            <p class="nmbp-clinic-addr">&#128205; <?php echo esc_html($c['address']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="nmbp-minimal-cta-box">
                        <button type="button" class="nmbp-btn nmbp-btn-primary nmbp-btn-lg nmbp-book-doctor-btn" data-doctor-id="<?php echo esc_attr($doc['id']); ?>" style="padding:14px 40px; font-size:16px;">
                            &#128197; <?php esc_html_e('Book Consultation with Dr. ' . $doc['last_name'], 'nexora-medical-booking-pro'); ?>
                        </button>
                    </div>
                </div>

            <?php elseif ($preset === 'hospital_dossier'): ?>
                <!-- ============================================================
                     SINGLE PRESET 04: Hospital Clinical Staff Dossier
                     ============================================================ -->
                <div class="nmbp-dossier-card">
                    <div class="nmbp-dossier-header-bar">
                        <div>
                            <span class="nmbp-dossier-tag">&#127973; <?php esc_html_e('HOSPITAL MEDICAL STAFF DOSSIER', 'nexora-medical-booking-pro'); ?></span>
                            <h2 style="margin:6px 0 0 0; color:#ffffff; font-size:22px;"><?php echo esc_html($full_name); ?></h2>
                        </div>
                        <div class="nmbp-dossier-id-badge">
                            STAFF ID: #NMBP-<?php echo str_pad($doc['id'], 3, '0', STR_PAD_LEFT); ?>
                        </div>
                    </div>

                    <div class="nmbp-dossier-body">
                        <div class="nmbp-dossier-left">
                            <div class="nmbp-dossier-avatar-wrap">
                                <img src="<?php echo esc_url($photo_url); ?>" class="nmbp-dossier-avatar" alt="<?php echo esc_attr($full_name); ?>">
                            </div>
                            <div class="nmbp-dossier-credentials-list">
                                <p><strong><?php esc_html_e('Specialty:', 'nexora-medical-booking-pro'); ?></strong><br><?php echo esc_html($doc['specialization']); ?></p>
                                <p><strong><?php esc_html_e('Department:', 'nexora-medical-booking-pro'); ?></strong><br><?php echo esc_html($dept_name); ?></p>
                                <?php if (!empty($doc['bmdc_reg_number'])): ?>
                                    <p><strong><?php esc_html_e('Medical License:', 'nexora-medical-booking-pro'); ?></strong><br><?php echo esc_html($doc['bmdc_reg_number']); ?></p>
                                <?php endif; ?>
                                <p><strong><?php esc_html_e('Clinical Fee:', 'nexora-medical-booking-pro'); ?></strong><br><span style="color:#0d9488; font-weight:700; font-size:16px;">$<?php echo number_format($doc['consultation_fee'], 2); ?></span></p>
                                <button type="button" class="nmbp-btn nmbp-btn-primary nmbp-btn-block nmbp-book-doctor-btn" data-doctor-id="<?php echo esc_attr($doc['id']); ?>" style="margin-top:14px;">
                                    <?php esc_html_e('Schedule Visit', 'nexora-medical-booking-pro'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="nmbp-dossier-right">
                            <div class="nmbp-dossier-section">
                                <h4><?php esc_html_e('Medical Scope & Biography', 'nexora-medical-booking-pro'); ?></h4>
                                <p><?php echo nl2br(esc_html($doc['biography'] ?: __('Board-certified specialist practicing across hospital clinical departments.', 'nexora-medical-booking-pro'))); ?></p>
                            </div>

                            <?php if (!empty($doc['qualifications'])): ?>
                                <div class="nmbp-dossier-section">
                                    <h4><?php esc_html_e('Degrees & Qualifications', 'nexora-medical-booking-pro'); ?></h4>
                                    <p><?php echo nl2br(esc_html($doc['qualifications'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="nmbp-dossier-section">
                                <h4><?php esc_html_e('Weekly Duty Shifts', 'nexora-medical-booking-pro'); ?></h4>
                                <div class="nmbp-schedule-table-wrap">
                                    <table class="nmbp-schedule-table">
                                        <thead>
                                            <tr>
                                                <th><?php esc_html_e('Day', 'nexora-medical-booking-pro'); ?></th>
                                                <th><?php esc_html_e('Hospital Branch', 'nexora-medical-booking-pro'); ?></th>
                                                <th><?php esc_html_e('Shift Time', 'nexora-medical-booking-pro'); ?></th>
                                                <th><?php esc_html_e('Action', 'nexora-medical-booking-pro'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (($schedules ?: []) as $s): ?>
                                                <tr>
                                                    <td><strong><?php echo esc_html(ucfirst($s['day_of_week'])); ?></strong></td>
                                                    <td><?php echo esc_html($s['clinic_name'] ?? __('Central Hospital', 'nexora-medical-booking-pro')); ?></td>
                                                    <td><span class="nmbp-shift-pill"><?php echo esc_html(date('h:i A', strtotime($s['start_time']))); ?> - <?php echo esc_html(date('h:i A', strtotime($s['end_time']))); ?></span></td>
                                                    <td>
                                                        <button type="button" class="nmbp-btn nmbp-btn-sm nmbp-btn-primary nmbp-book-doctor-btn" data-doctor-id="<?php echo esc_attr($doc['id']); ?>">
                                                            <?php esc_html_e('Book', 'nexora-medical-booking-pro'); ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- ============================================================
                     SINGLE PRESET 01: Clinical Split (Default)
                     ============================================================ -->
                <div class="nmbp-profile-hero">
                    <div class="nmbp-profile-avatar-wrap">
                        <img src="<?php echo esc_url($photo_url); ?>" class="nmbp-profile-avatar" alt="<?php echo esc_attr($full_name); ?>">
                        <span class="nmbp-profile-status-dot" title="<?php esc_attr_e('Available for Consultations', 'nexora-medical-booking-pro'); ?>"></span>
                    </div>
                    <div class="nmbp-profile-info">
                        <div class="nmbp-profile-badge-row">
                            <span class="nmbp-badge nmbp-badge-dept"><?php echo esc_html($dept_name); ?></span>
                            <span class="nmbp-verified-pill">&#10003; <?php esc_html_e('Verified Specialist', 'nexora-medical-booking-pro'); ?></span>
                            <?php if (!empty($doc['bmdc_reg_number'])): ?>
                                <span class="nmbp-reg-pill"><?php esc_html_e('Reg / BMDC:', 'nexora-medical-booking-pro'); ?> <strong><?php echo esc_html($doc['bmdc_reg_number']); ?></strong></span>
                            <?php endif; ?>
                        </div>
                        <h1 class="nmbp-profile-title"><?php echo esc_html($full_name); ?></h1>
                        <p class="nmbp-profile-specialty"><?php echo esc_html($doc['specialization']); ?></p>

                        <!-- Quick Highlight Stats -->
                        <div class="nmbp-profile-stats-grid">
                            <div class="nmbp-profile-stat-box">
                                <span class="nmbp-stat-icon">&#127942;</span>
                                <div>
                                    <strong><?php echo esc_html($doc['experience_years']); ?>+ <?php esc_html_e('Years', 'nexora-medical-booking-pro'); ?></strong>
                                    <small><?php esc_html_e('Clinical Exp.', 'nexora-medical-booking-pro'); ?></small>
                                </div>
                            </div>
                            <div class="nmbp-profile-stat-box">
                                <span class="nmbp-stat-icon">&#128179;</span>
                                <div>
                                    <strong>$<?php echo number_format($doc['consultation_fee'], 2); ?></strong>
                                    <small><?php esc_html_e('Consultation Fee', 'nexora-medical-booking-pro'); ?></small>
                                </div>
                            </div>
                            <div class="nmbp-profile-stat-box">
                                <span class="nmbp-stat-icon">&#128337;</span>
                                <div>
                                    <strong><?php echo esc_html($doc['appointment_duration'] ?? 20); ?> <?php esc_html_e('Mins', 'nexora-medical-booking-pro'); ?></strong>
                                    <small><?php esc_html_e('Session Duration', 'nexora-medical-booking-pro'); ?></small>
                                </div>
                            </div>
                            <div class="nmbp-profile-stat-box">
                                <span class="nmbp-stat-icon">&#127973;</span>
                                <div>
                                    <strong><?php echo count($doc['clinics'] ?? []); ?> <?php esc_html_e('Branches', 'nexora-medical-booking-pro'); ?></strong>
                                    <small><?php esc_html_e('Practicing Locations', 'nexora-medical-booking-pro'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two-Column Profile Layout -->
                <div class="nmbp-profile-layout">
                    <!-- Left Main Content -->
                    <div class="nmbp-profile-main">
                        <!-- About Doctor -->
                        <div class="nmbp-profile-card-block">
                            <h3 class="nmbp-block-heading">&#128100; <?php esc_html_e('About the Doctor', 'nexora-medical-booking-pro'); ?></h3>
                            <div class="nmbp-block-content">
                                <p><?php echo nl2br(esc_html($doc['biography'] ?: __('Experienced medical consultant dedicated to patient safety, diagnostic precision, and advanced clinical healthcare.', 'nexora-medical-booking-pro'))); ?></p>
                            </div>
                        </div>

                        <!-- Qualifications -->
                        <div class="nmbp-profile-card-block">
                            <h3 class="nmbp-block-heading">&#127891; <?php esc_html_e('Qualifications & Educational Background', 'nexora-medical-booking-pro'); ?></h3>
                            <div class="nmbp-block-content">
                                <p><?php echo nl2br(esc_html($doc['qualifications'] ?: __('MBBS, Specialized Fellowship & Board Certification', 'nexora-medical-booking-pro'))); ?></p>
                            </div>
                        </div>

                        <!-- Practicing Hospital Branches -->
                        <div class="nmbp-profile-card-block">
                            <h3 class="nmbp-block-heading">&#127973; <?php esc_html_e('Practicing Hospital Branches & Clinics', 'nexora-medical-booking-pro'); ?></h3>
                            <div class="nmbp-block-content">
                                <?php if (empty($doc['clinics'])): ?>
                                    <p class="nmbp-text-muted"><?php esc_html_e('Doctor is currently practicing across central medical locations.', 'nexora-medical-booking-pro'); ?></p>
                                <?php else: ?>
                                    <div class="nmbp-profile-clinics-grid">
                                        <?php foreach ($doc['clinics'] as $c): ?>
                                            <div class="nmbp-profile-clinic-item">
                                                <div class="nmbp-clinic-icon-circle">&#127973;</div>
                                                <div class="nmbp-clinic-item-details">
                                                    <h4><?php echo esc_html($c['clinic_name']); ?></h4>
                                                    <p class="nmbp-clinic-addr">&#128205; <?php echo esc_html($c['address']); ?>, <?php echo esc_html($c['city']); ?></p>
                                                    <?php if (!empty($c['phone'])): ?>
                                                        <p class="nmbp-clinic-contact">&#128222; <?php echo esc_html($c['phone']); ?></p>
                                                    <?php endif; ?>
                                                    <div class="nmbp-clinic-fee-tag">
                                                        <?php esc_html_e('Consultation Fee:', 'nexora-medical-booking-pro'); ?>
                                                        <strong>$<?php echo number_format($c['clinic_fee'] ?: $doc['consultation_fee'], 2); ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Weekly Consultation Timetable -->
                        <div class="nmbp-profile-card-block">
                            <h3 class="nmbp-block-heading">&#128197; <?php esc_html_e('Weekly Consultation Timetable', 'nexora-medical-booking-pro'); ?></h3>
                            <div class="nmbp-block-content">
                                <?php if (empty($schedules)): ?>
                                    <p class="nmbp-text-muted"><?php esc_html_e('Standard weekly schedules apply. Please initiate a booking to see live available slots.', 'nexora-medical-booking-pro'); ?></p>
                                <?php else: ?>
                                    <div class="nmbp-schedule-table-wrap">
                                        <table class="nmbp-schedule-table">
                                            <thead>
                                                <tr>
                                                    <th><?php esc_html_e('Day', 'nexora-medical-booking-pro'); ?></th>
                                                    <th><?php esc_html_e('Branch / Clinic', 'nexora-medical-booking-pro'); ?></th>
                                                    <th><?php esc_html_e('Consultation Shift', 'nexora-medical-booking-pro'); ?></th>
                                                    <th><?php esc_html_e('Break Time', 'nexora-medical-booking-pro'); ?></th>
                                                    <th><?php esc_html_e('Action', 'nexora-medical-booking-pro'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($schedules as $s): ?>
                                                    <tr>
                                                        <td><strong><?php echo esc_html(ucfirst($s['day_of_week'])); ?></strong></td>
                                                        <td><?php echo esc_html($s['clinic_name'] ?? __('Main Branch', 'nexora-medical-booking-pro')); ?></td>
                                                        <td>
                                                            <span class="nmbp-shift-pill">
                                                                <?php echo esc_html(date('h:i A', strtotime($s['start_time']))); ?> - <?php echo esc_html(date('h:i A', strtotime($s['end_time']))); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($s['break_start']) && !empty($s['break_end'])): ?>
                                                                <?php echo esc_html(date('h:i A', strtotime($s['break_start']))); ?> - <?php echo esc_html(date('h:i A', strtotime($s['break_end']))); ?>
                                                            <?php else: ?>
                                                                <span class="nmbp-text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="nmbp-btn nmbp-btn-sm nmbp-btn-primary nmbp-book-doctor-btn" data-doctor-id="<?php echo esc_attr($doc['id']); ?>">
                                                                <?php esc_html_e('Book', 'nexora-medical-booking-pro'); ?>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sticky Booking Sidebar -->
                    <div class="nmbp-profile-sidebar">
                        <div class="nmbp-sticky-booking-box">
                            <div class="nmbp-booking-box-header">
                                <h4><?php esc_html_e('Book Appointment', 'nexora-medical-booking-pro'); ?></h4>
                                <p><?php esc_html_e('Direct booking with Dr. ' . $doc['last_name'], 'nexora-medical-booking-pro'); ?></p>
                            </div>
                            <div class="nmbp-booking-box-fee">
                                <span class="nmbp-fee-label"><?php esc_html_e('Consultation Fee', 'nexora-medical-booking-pro'); ?></span>
                                <span class="nmbp-fee-amount">$<?php echo number_format($doc['consultation_fee'], 2); ?></span>
                            </div>
                            <div class="nmbp-booking-box-body">
                                <button type="button" class="nmbp-btn nmbp-btn-primary nmbp-btn-block nmbp-btn-lg nmbp-book-doctor-btn" data-doctor-id="<?php echo esc_attr($doc['id']); ?>">
                                    &#128197; <?php esc_html_e('Book Consultation Now', 'nexora-medical-booking-pro'); ?>
                                </button>
                                <ul class="nmbp-booking-features">
                                    <li>&#10003; <?php esc_html_e('Doctor availability verified in real-time', 'nexora-medical-booking-pro'); ?></li>
                                    <li>&#10003; <?php esc_html_e('Admin approval verification system', 'nexora-medical-booking-pro'); ?></li>
                                    <li>&#10003; <?php esc_html_e('Instant email & SMS notifications', 'nexora-medical-booking-pro'); ?></li>
                                    <li>&#10003; <?php esc_html_e('Pay online or pay cash at the clinic', 'nexora-medical-booking-pro'); ?></li>
                                </ul>
                            </div>
                            <div class="nmbp-booking-box-footer">
                                <small><?php esc_html_e('Questions or urgent inquiry?', 'nexora-medical-booking-pro'); ?></small>
                                <p class="nmbp-helpline-text">&#128222; <?php echo esc_html($doc['phone'] ?: __('Hospital Reception Desk', 'nexora-medical-booking-pro')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [nmbp_patient_dashboard]
     */
    public function render_patient_dashboard(array $atts = []): string {
        if (!is_user_logged_in()) {
            return '<div class="nmbp-login-notice"><p>' . sprintf(
                __('Please <a href="%s">log in</a> to access your patient dashboard.', 'nexora-medical-booking-pro'),
                esc_url(wp_login_url(get_permalink()))
            ) . '</p></div>';
        }

        $user_id = get_current_user_id();
        $patient_id = NMBP_Permissions::get_patient_id_by_wp_user($user_id);

        $appointments = $patient_id ? NMBP_Appointment_Manager::get_appointments([
            'patient_id' => $patient_id,
            'per_page'   => 50,
        ])['items'] : [];

        $patient = $patient_id ? NMBP_Patient_Manager::get_patient($patient_id) : null;

        ob_start();
        ?>
        <div class="nmbp-dashboard-container nmbp-patient-dashboard">
            <div class="nmbp-dash-header">
                <h2><?php esc_html_e('Patient Medical Portal', 'nexora-medical-booking-pro'); ?></h2>
                <p><?php echo esc_html($patient ? ($patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['email'] . ')') : wp_get_current_user()->display_name); ?></p>
            </div>

            <div class="nmbp-dash-table-card">
                <div class="nmbp-dash-card-header">
                    <h3><?php esc_html_e('My Appointments', 'nexora-medical-booking-pro'); ?></h3>
                    <a href="#nmbp-booking-app" class="nmbp-btn nmbp-btn-sm nmbp-btn-primary"><?php esc_html_e('+ New Booking', 'nexora-medical-booking-pro'); ?></a>
                </div>

                <?php if (empty($appointments)): ?>
                    <div class="nmbp-empty-state"><?php esc_html_e('You have no appointments booked yet.', 'nexora-medical-booking-pro'); ?></div>
                <?php else: ?>
                    <table class="nmbp-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Ref #', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Doctor', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Clinic', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Date & Time', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Fee', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Payment', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Status', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Action', 'nexora-medical-booking-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $a): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($a['appointment_number']); ?></strong></td>
                                    <td>Dr. <?php echo esc_html($a['doctor_first_name'] . ' ' . $a['doctor_last_name']); ?></td>
                                    <td><?php echo esc_html($a['clinic_name']); ?></td>
                                    <td><?php echo esc_html($a['appointment_date']); ?> <?php echo esc_html(date('h:i A', strtotime($a['start_time']))); ?></td>
                                    <td>$<?php echo number_format($a['consultation_fee'], 2); ?></td>
                                    <td>
                                        <span class="nmbp-badge nmbp-pay-<?php echo esc_attr($a['payment_status']); ?>">
                                            <?php echo esc_html(strtoupper($a['payment_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="nmbp-badge nmbp-status-<?php echo esc_attr($a['appointment_status']); ?>">
                                            <?php echo esc_html(strtoupper($a['appointment_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (in_array($a['appointment_status'], ['pending', 'confirmed'], true)): ?>
                                            <button class="nmbp-btn nmbp-btn-sm nmbp-btn-danger nmbp-patient-cancel-btn" data-app-id="<?php echo esc_attr($a['id']); ?>">
                                                <?php esc_html_e('Cancel', 'nexora-medical-booking-pro'); ?>
                                            </button>
                                        <?php else: ?>
                                            <span style="color:#94a3b8; font-size:12px;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [nmbp_doctor_dashboard]
     */
    public function render_doctor_dashboard(array $atts = []): string {
        if (!is_user_logged_in()) {
            return '<div class="nmbp-login-notice"><p>' . sprintf(
                __('Please <a href="%s">log in</a> as a doctor.', 'nexora-medical-booking-pro'),
                esc_url(wp_login_url(get_permalink()))
            ) . '</p></div>';
        }

        $user_id = get_current_user_id();
        $doctor_id = NMBP_Permissions::get_doctor_id_by_wp_user($user_id);
        if (!$doctor_id && !current_user_can('manage_options')) {
            return '<div class="nmbp-empty-state"><p>' . esc_html__('No doctor profile is attached to your user account.', 'nexora-medical-booking-pro') . '</p></div>';
        }

        $doctor = $doctor_id ? NMBP_Doctor_Manager::get_doctor($doctor_id) : null;
        $today = current_time('Y-m-d');

        $today_appointments = $doctor_id ? NMBP_Appointment_Manager::get_appointments([
            'doctor_id' => $doctor_id,
            'date_from' => $today,
            'date_to'   => $today,
            'per_page'  => 50,
        ])['items'] : [];

        ob_start();
        ?>
        <div class="nmbp-dashboard-container nmbp-doctor-dashboard">
            <div class="nmbp-dash-header">
                <h2><?php esc_html_e('Doctor Consultation Portal', 'nexora-medical-booking-pro'); ?></h2>
                <p>Dr. <?php echo esc_html($doctor ? ($doctor['first_name'] . ' ' . $doctor['last_name'] . ' - ' . $doctor['specialization']) : 'Admin View'); ?></p>
            </div>

            <div class="nmbp-dash-table-card">
                <div class="nmbp-dash-card-header">
                    <h3><?php esc_html_e('Today\'s Appointments', 'nexora-medical-booking-pro'); ?> (<?php echo esc_html(date_i18n(get_option('date_format'))); ?>)</h3>
                </div>

                <?php if (empty($today_appointments)): ?>
                    <div class="nmbp-empty-state"><?php esc_html_e('No patient appointments scheduled for today.', 'nexora-medical-booking-pro'); ?></div>
                <?php else: ?>
                    <table class="nmbp-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Time', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Patient Name', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Phone', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Clinic', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Status', 'nexora-medical-booking-pro'); ?></th>
                                <th><?php esc_html_e('Actions', 'nexora-medical-booking-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($today_appointments as $a): ?>
                                <tr>
                                    <td><strong><?php echo esc_html(date('h:i A', strtotime($a['start_time']))); ?></strong></td>
                                    <td><?php echo esc_html($a['patient_first_name'] . ' ' . $a['patient_last_name']); ?></td>
                                    <td><?php echo esc_html($a['patient_phone']); ?></td>
                                    <td><?php echo esc_html($a['clinic_name']); ?></td>
                                    <td>
                                        <span class="nmbp-badge nmbp-status-<?php echo esc_attr($a['appointment_status']); ?>">
                                            <?php echo esc_html(strtoupper($a['appointment_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($a['appointment_status'] === 'confirmed'): ?>
                                            <button class="nmbp-btn nmbp-btn-sm nmbp-btn-success nmbp-doc-status-btn" data-app-id="<?php echo esc_attr($a['id']); ?>" data-status="completed"><?php esc_html_e('Complete', 'nexora-medical-booking-pro'); ?></button>
                                            <button class="nmbp-btn nmbp-btn-sm nmbp-btn-outline nmbp-doc-status-btn" data-app-id="<?php echo esc_attr($a['id']); ?>" data-status="no_show"><?php esc_html_e('No Show', 'nexora-medical-booking-pro'); ?></button>
                                        <?php else: ?>
                                            <span>-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [nmbp_appointment_search]
     */
    public function render_appointment_search(array $atts = []): string {
        ob_start();
        ?>
        <div class="nmbp-container nmbp-search-wrapper">
            <div class="nmbp-search-card">
                <h3><?php esc_html_e('Check Appointment Status', 'nexora-medical-booking-pro'); ?></h3>
                <p><?php esc_html_e('Enter your appointment reference number to look up your booking details.', 'nexora-medical-booking-pro'); ?></p>
                <form id="nmbp-track-form" onsubmit="return false;" style="display:flex; gap:10px; margin-top:15px;">
                    <input type="text" id="nmbp-track-ref" placeholder="e.g. NMBP-202611-A8F9" required class="nmbp-input" style="flex:1;">
                    <button type="submit" class="nmbp-btn nmbp-btn-primary" id="nmbp-track-btn"><?php esc_html_e('Search', 'nexora-medical-booking-pro'); ?></button>
                </form>
                <div id="nmbp-track-result" style="margin-top:20px; display:none;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

