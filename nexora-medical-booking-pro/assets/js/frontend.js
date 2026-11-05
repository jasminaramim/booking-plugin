/**
 * Nexora Medical Booking Pro - Interactive Frontend Engine
 */

(function ($) {
    'use strict';

    const config = window.nmbpConfig || {
        restUrl: '/wp-json/nmbp/v1/',
        nonce: '',
        currencySymbol: '$',
        i18n: {}
    };

    const BookingEngine = {
        state: {
            currentStep: 1,
            clinicId: null,
            clinicName: '',
            clinicFee: 0,
            departmentId: null,
            departmentName: '',
            doctorId: null,
            doctorName: '',
            doctorFee: 0,
            date: '',
            slotStartTime: '',
            slotEndTime: '',
            slotDisplay: '',
            paymentMethod: 'cash'
        },

        modalState: {
            doctorId: null,
            doctorName: '',
            doctorFee: 0,
            departmentId: 0,
            clinicId: null,
            clinicName: '',
            date: '',
            slotStartTime: '',
            slotEndTime: '',
            slotDisplay: '',
            paymentMethod: 'cash'
        },

        init: function () {
            this.bindEvents();
            this.loadClinics();
            this.loadDepartments();
        },

        bindEvents: function () {
            const self = this;

            // Step Navigation
            $('#nmbp-btn-next').on('click', function () {
                self.handleNextStep();
            });

            $('#nmbp-btn-prev').on('click', function () {
                self.goToStep(self.state.currentStep - 1);
            });

            $('#nmbp-btn-submit').on('click', function () {
                self.submitBooking();
            });

            // Date picker change
            $('#nmbp-booking-date').on('change', function () {
                self.state.date = $(this).val();
                self.loadSlots();
            });

            // Payment radio change
            $('input[name="payment_method"]').on('change', function () {
                self.state.paymentMethod = $(this).val();
                $('.nmbp-radio-card').removeClass('active');
                $(this).closest('.nmbp-radio-card').addClass('active');
            });

            // Doctor button clicks on directory cards & single profile
            $(document).on('click', '.nmbp-book-doctor-btn', function (e) {
                e.preventDefault();
                const docId = $(this).data('doctor-id');
                if (docId) {
                    if ($('#nmbp-booking-app').length) {
                        self.selectDoctorDirectly(docId);
                    } else {
                        self.openQuickBookingModal(docId);
                    }
                }
            });

            // Quick Booking Modal Events
            $(document).on('click', '#nmbp-close-quick-modal, #nmbp-cancel-modal-btn, #nmbp-modal-done-btn', function (e) {
                e.preventDefault();
                $('#nmbp-quick-booking-modal').fadeOut(200);
            });

            $(document).on('click', '#nmbp-quick-booking-modal', function (e) {
                if ($(e.target).is('#nmbp-quick-booking-modal')) {
                    $('#nmbp-quick-booking-modal').fadeOut(200);
                }
            });

            $(document).on('change', '#nmbp-modal-date', function () {
                self.modalState.date = $(this).val();
                self.loadModalSlots();
            });

            $(document).on('change', '#nmbp-modal-clinic-select', function () {
                self.modalState.clinicId = $(this).val();
                self.loadModalSlots();
            });

            $(document).on('change', 'input[name="modal_payment_method"]', function () {
                self.modalState.paymentMethod = $(this).val();
                $('input[name="modal_payment_method"]').closest('.nmbp-radio-card').removeClass('active');
                $(this).closest('.nmbp-radio-card').addClass('active');
            });

            $(document).on('click', '#nmbp-modal-confirm-btn', function (e) {
                e.preventDefault();
                self.submitModalBooking();
            });

            // Live Doctor Grid Filter
            function applyDoctorFilter() {
                const searchTxt = $('.nmbp-filter-search-input').val() ? $('.nmbp-filter-search-input').val().toLowerCase().trim() : '';
                const deptId = $('.nmbp-filter-dept-select').val();
                const clinicId = $('.nmbp-filter-clinic-select').val();

                let visibleCount = 0;
                $('.nmbp-doctor-card').each(function () {
                    const $card = $(this);
                    const name = String($card.data('name') || '');
                    const specialty = String($card.data('specialty') || '');
                    const cardDept = String($card.data('dept-id') || '');
                    const cardClinics = String($card.data('clinics') || '').split(',');

                    const matchesSearch = !searchTxt || name.includes(searchTxt) || specialty.includes(searchTxt);
                    const matchesDept = !deptId || cardDept === String(deptId);
                    const matchesClinic = !clinicId || cardClinics.includes(String(clinicId));

                    if (matchesSearch && matchesDept && matchesClinic) {
                        $card.fadeIn(150);
                        visibleCount++;
                    } else {
                        $card.fadeOut(150);
                    }
                });

                $('#nmbp-filter-count').text(visibleCount);
            }

            $(document).on('input', '.nmbp-filter-search-input', applyDoctorFilter);
            $(document).on('change', '.nmbp-filter-dept-select, .nmbp-filter-clinic-select', applyDoctorFilter);

            // Clinic button clicks on directory cards
            $(document).on('click', '.nmbp-select-clinic-btn', function (e) {
                e.preventDefault();
                const clinicId = $(this).data('clinic-id');
                if (clinicId) {
                    self.selectClinicDirectly(clinicId);
                }
            });

            // Patient cancellation button
            $(document).on('click', '.nmbp-patient-cancel-btn', function (e) {
                e.preventDefault();
                const appId = $(this).data('app-id');
                if (confirm('Are you sure you want to cancel this appointment?')) {
                    self.cancelAppointment(appId, $(this));
                }
            });

            // Doctor action buttons (Complete, No Show)
            $(document).on('click', '.nmbp-doc-status-btn', function (e) {
                e.preventDefault();
                const appId = $(this).data('app-id');
                const status = $(this).data('status');
                self.updateAppointmentStatus(appId, status, $(this));
            });

            // Search / Tracker Form
            $('#nmbp-track-form').on('submit', function (e) {
                e.preventDefault();
                self.trackAppointment();
            });
        },

        goToStep: function (step) {
            this.state.currentStep = step;

            $('.nmbp-step-item').removeClass('active');
            $(`.nmbp-step-item[data-step="${step}"]`).addClass('active');

            $('.nmbp-step-pane').removeClass('active');
            $(`#nmbp-step-${step}`).addClass('active');

            if (step === 1) {
                $('#nmbp-btn-prev').hide();
                $('#nmbp-btn-next').show();
                $('#nmbp-btn-submit').hide();
            } else if (step === 5) {
                $('#nmbp-btn-prev').show();
                $('#nmbp-btn-next').hide();
                $('#nmbp-btn-submit').show();
                this.updateSummary();
            } else {
                $('#nmbp-btn-prev').show();
                $('#nmbp-btn-next').show();
                $('#nmbp-btn-submit').hide();
            }
        },

        handleNextStep: function () {
            const step = this.state.currentStep;

            if (step === 1) {
                if (!this.state.clinicId) {
                    alert('Please select a hospital branch or clinic to continue.');
                    return;
                }
                this.goToStep(2);
            } else if (step === 2) {
                // If department selected, load doctors for this clinic and department
                this.loadDoctors();
                this.goToStep(3);
            } else if (step === 3) {
                if (!this.state.doctorId) {
                    alert('Please select a doctor for your consultation.');
                    return;
                }
                this.state.date = $('#nmbp-booking-date').val();
                this.loadSlots();
                this.goToStep(4);
            } else if (step === 4) {
                if (!this.state.slotStartTime) {
                    alert('Please choose an available appointment time slot.');
                    return;
                }
                this.goToStep(5);
            }
        },

        loadClinics: function () {
            const self = this;
            const $list = $('#nmbp-clinic-select-list');
            if (!$list.length) return;

            $.ajax({
                url: config.restUrl + 'clinics?status=active',
                method: 'GET',
                success: function (res) {
                    const items = res.data ? res.data.items : [];
                    if (!items.length) {
                        $list.html('<p class="nmbp-text-muted">No active clinics available.</p>');
                        return;
                    }

                    let html = '<div class="nmbp-grid nmbp-grid-3">';
                    items.forEach(function (c) {
                        html += `
                            <div class="nmbp-selectable-card nmbp-clinic-opt" data-id="${c.id}" data-name="${c.name}" data-fee="${c.default_fee}">
                                <h4 style="margin:0 0 4px 0; color:#0f172a;">${c.name}</h4>
                                <p style="margin:0; font-size:12px; color:#64748b;">${c.address}, ${c.city}</p>
                                <p style="margin:6px 0 0 0; font-size:12px; color:#0d9488; font-weight:600;">Open: ${c.opening_time} - ${c.closing_time}</p>
                            </div>
                        `;
                    });
                    html += '</div>';
                    $list.html(html);

                    $('.nmbp-clinic-opt').on('click', function () {
                        $('.nmbp-clinic-opt').removeClass('selected');
                        $(this).addClass('selected');
                        self.state.clinicId = $(this).data('id');
                        self.state.clinicName = $(this).data('name');
                        self.state.clinicFee = parseFloat($(this).data('fee')) || 0;
                    });
                }
            });
        },

        loadDepartments: function () {
            const self = this;
            const $list = $('#nmbp-dept-select-list');
            if (!$list.length) return;

            $.ajax({
                url: config.restUrl + 'departments',
                method: 'GET',
                success: function (res) {
                    const items = res.data || [];
                    let html = '<div class="nmbp-grid nmbp-grid-3">';
                    html += `
                        <div class="nmbp-selectable-card nmbp-dept-opt selected" data-id="0" data-name="All Departments">
                            <h4 style="margin:0; color:#0f172a;">All Specialties</h4>
                            <p style="margin:4px 0 0 0; font-size:12px; color:#64748b;">View all available doctors</p>
                        </div>
                    `;
                    items.forEach(function (d) {
                        html += `
                            <div class="nmbp-selectable-card nmbp-dept-opt" data-id="${d.id}" data-name="${d.name}">
                                <h4 style="margin:0; color:#0f172a;">${d.name}</h4>
                                <p style="margin:4px 0 0 0; font-size:12px; color:#64748b;">${d.description || ''}</p>
                            </div>
                        `;
                    });
                    html += '</div>';
                    $list.html(html);

                    $('.nmbp-dept-opt').on('click', function () {
                        $('.nmbp-dept-opt').removeClass('selected');
                        $(this).addClass('selected');
                        self.state.departmentId = $(this).data('id') || null;
                        self.state.departmentName = $(this).data('name');
                    });
                }
            });
        },

        loadDoctors: function () {
            const self = this;
            const $list = $('#nmbp-doctor-select-list');
            $list.html('<div class="nmbp-loading-spinner">Loading available doctors...</div>');

            let url = config.restUrl + `doctors?status=active&clinic_id=${self.state.clinicId || 0}`;
            if (self.state.departmentId) {
                url += `&department_id=${self.state.departmentId}`;
            }

            $.ajax({
                url: url,
                method: 'GET',
                success: function (res) {
                    const items = res.data ? res.data.items : [];
                    if (!items.length) {
                        $list.html('<p class="nmbp-text-muted">No doctors found matching this specialty at this branch.</p>');
                        return;
                    }

                    let html = '<div class="nmbp-grid nmbp-grid-3">';
                    items.forEach(function (doc) {
                        const avatar = doc.photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(doc.first_name + '+' + doc.last_name)}&background=0d9488&color=fff&size=100`;
                        const fee = doc.consultation_fee || self.state.clinicFee || 0;
                        html += `
                            <div class="nmbp-selectable-card nmbp-doc-opt" data-id="${doc.id}" data-name="Dr. ${doc.first_name} ${doc.last_name}" data-fee="${fee}">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <img src="${avatar}" style="width:50px; height:50px; border-radius:50%; object-fit:cover;">
                                    <div>
                                        <h4 style="margin:0; font-size:15px; color:#0f172a;">Dr. ${doc.first_name} ${doc.last_name}</h4>
                                        <p style="margin:2px 0 0 0; font-size:12px; color:#0d9488; font-weight:600;">${doc.specialization}</p>
                                        <p style="margin:2px 0 0 0; font-size:12px; color:#64748b;">Fee: ${config.currencySymbol}${parseFloat(fee).toFixed(2)}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    $list.html(html);

                    $('.nmbp-doc-opt').on('click', function () {
                        $('.nmbp-doc-opt').removeClass('selected');
                        $(this).addClass('selected');
                        self.state.doctorId = $(this).data('id');
                        self.state.doctorName = $(this).data('name');
                        self.state.doctorFee = parseFloat($(this).data('fee')) || 0;
                    });
                }
            });
        },

        loadSlots: function () {
            const self = this;
            const $container = $('#nmbp-slots-container');
            if (!self.state.doctorId || !self.state.clinicId || !self.state.date) {
                return;
            }

            $container.html('<div class="nmbp-text-muted">Loading available slots...</div>');
            self.state.slotStartTime = '';
            self.state.slotEndTime = '';

            $.ajax({
                url: config.restUrl + `booking/slots?doctor_id=${self.state.doctorId}&clinic_id=${self.state.clinicId}&date=${self.state.date}`,
                method: 'GET',
                success: function (res) {
                    const data = res.data || {};
                    const slots = data.slots || [];

                    if (!slots.length) {
                        $container.html('<div style="grid-column: 1 / -1; padding: 20px; color:#94a3b8; font-size:13px; text-align:center;">No appointment slots available on this date. Please pick another date.</div>');
                        return;
                    }

                    let html = '';
                    slots.forEach(function (s) {
                        html += `
                            <button type="button" class="nmbp-slot-btn" data-start="${s.start_time}" data-end="${s.end_time}" data-display="${s.display}">
                                ${s.display}
                            </button>
                        `;
                    });
                    $container.html(html);

                    $('.nmbp-slot-btn').on('click', function () {
                        $('.nmbp-slot-btn').removeClass('selected');
                        $(this).addClass('selected');
                        self.state.slotStartTime = $(this).data('start');
                        self.state.slotEndTime = $(this).data('end');
                        self.state.slotDisplay = $(this).data('display');
                    });
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No consultation slots available on this date.';
                    $container.html(`<div style="grid-column: 1 / -1; padding: 15px; color:#ef4444; font-size:13px;">${msg}</div>`);
                }
            });
        },

        updateSummary: function () {
            $('#nmbp-sum-clinic').text(this.state.clinicName || '-');
            $('#nmbp-sum-doctor').text(this.state.doctorName || '-');
            $('#nmbp-sum-datetime').text(`${this.state.date} (${this.state.slotDisplay || '-'})`);
            const fee = this.state.doctorFee || this.state.clinicFee || 0;
            $('#nmbp-sum-fee').text(`${config.currencySymbol}${parseFloat(fee).toFixed(2)}`);
        },

        submitBooking: function () {
            const self = this;
            const $form = $('#nmbp-booking-form');

            const firstName = $form.find('input[name="first_name"]').val().trim();
            const lastName = $form.find('input[name="last_name"]').val().trim();
            const email = $form.find('input[name="email"]').val().trim();
            const phone = $form.find('input[name="phone"]').val().trim();

            if (!firstName || !lastName || !email || !phone) {
                alert('Please fill in all required patient fields (*).');
                return;
            }

            const paymentMethod = $form.find('input[name="payment_method"]:checked').val() || self.state.paymentMethod || 'cash';

            const payload = {
                clinic_id: self.state.clinicId,
                doctor_id: self.state.doctorId,
                department_id: self.state.departmentId,
                appointment_date: self.state.date,
                start_time: self.state.slotStartTime,
                end_time: self.state.slotEndTime,
                appointment_type: $form.find('select[name="appointment_type"]').val(),
                payment_method: paymentMethod,
                patient_notes: $form.find('textarea[name="patient_notes"]').val(),
                patient: {
                    first_name: firstName,
                    last_name: lastName,
                    email: email,
                    phone: phone,
                    gender: $form.find('select[name="gender"]').val()
                },
                // Honeypot spam check field
                fax_number: $form.find('input[name="fax_number"]').val()
            };

            const $submitBtn = $('#nmbp-btn-submit');
            $submitBtn.prop('disabled', true).text('Reserving Slot & Submitting...');

            $.ajax({
                url: config.restUrl + 'booking/submit',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': config.nonce
                },
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function (res) {
                    $submitBtn.prop('disabled', false).text('Confirm & Submit Request');

                    const data = res.data || {};
                    const refCode = data.appointment_number || 'NMBP-SUBMITTED';

                    $('#nmbp-final-ref-code').text(refCode);

                    $('.nmbp-step-pane').removeClass('active');
                    $('#nmbp-step-success').addClass('active');

                    $('.nmbp-stepper-header').hide();
                    $('.nmbp-wizard-footer').hide();
                },
                error: function (xhr) {
                    $submitBtn.prop('disabled', false).text('Confirm & Submit Request');
                    const err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error submitting booking request. Please check slot availability.';
                    alert(err);
                }
            });
        },

        selectDoctorDirectly: function (docId) {
            const self = this;
            const $app = $('#nmbp-booking-app');

            if ($app.length) {
                $('html, body').animate({
                    scrollTop: $app.offset().top - 40
                }, 500);
            } else {
                self.openQuickBookingModal(docId);
                return;
            }

            self.state.date = $('#nmbp-booking-date').val() || new Date().toISOString().split('T')[0];

            $.ajax({
                url: config.restUrl + `doctors/${docId}`,
                method: 'GET',
                success: function (res) {
                    const doc = res.data;
                    if (!doc) return;

                    self.state.doctorId = doc.id;
                    self.state.doctorName = `Dr. ${doc.first_name} ${doc.last_name}`;
                    self.state.doctorFee = doc.consultation_fee;
                    self.state.departmentId = doc.department_id;

                    if (doc.clinics && doc.clinics.length > 0) {
                        self.state.clinicId = doc.clinics[0].clinic_id;
                        self.state.clinicName = doc.clinics[0].clinic_name;
                        self.goToStep(4);
                        self.loadSlots();
                    } else {
                        self.goToStep(1);
                    }
                }
            });
        },

        openQuickBookingModal: function (docId) {
            const self = this;
            const $modal = $('#nmbp-quick-booking-modal');
            if (!$modal.length) return;

            // Reset modal states
            $('#nmbp-modal-form-view').show();
            $('#nmbp-modal-success-view').hide();
            $('#nmbp-modal-slots-container').html('<div class="nmbp-text-muted" style="grid-column:1/-1; padding:12px; font-size:13px; text-align:center;">Loading available slots...</div>');

            const today = new Date().toISOString().split('T')[0];
            $('#nmbp-modal-date').val(today);
            self.modalState.date = today;
            self.modalState.paymentMethod = $('input[name="modal_payment_method"]:checked').val() || 'cash';

            $modal.fadeIn(200);

            $.ajax({
                url: config.restUrl + `doctors/${docId}`,
                method: 'GET',
                success: function (res) {
                    const doc = res.data;
                    if (!doc) return;

                    self.modalState.doctorId = doc.id;
                    self.modalState.doctorName = `Dr. ${doc.first_name} ${doc.last_name}`;
                    self.modalState.doctorFee = parseFloat(doc.consultation_fee) || 0;
                    self.modalState.departmentId = doc.department_id;

                    const avatar = doc.photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(doc.first_name + '+' + doc.last_name)}&background=0d9488&color=fff&size=120`;
                    $('#nmbp-modal-doc-img').attr('src', avatar);
                    $('#nmbp-modal-doc-name').text(`Dr. ${doc.first_name} ${doc.last_name}`);
                    $('#nmbp-modal-doc-dept').text(doc.department_name || doc.specialization || 'Medical Specialist');
                    $('#nmbp-modal-doc-fee').text(`Fee: ${config.currencySymbol}${parseFloat(self.modalState.doctorFee).toFixed(2)}`);

                    const $clinicSelect = $('#nmbp-modal-clinic-select');
                    $clinicSelect.empty();

                    if (doc.clinics && doc.clinics.length > 0) {
                        doc.clinics.forEach(function (c, idx) {
                            $clinicSelect.append(`<option value="${c.clinic_id}" ${idx === 0 ? 'selected' : ''}>${c.clinic_name} (${c.city || 'Main Branch'})</option>`);
                        });
                        self.modalState.clinicId = doc.clinics[0].clinic_id;
                        self.modalState.clinicName = doc.clinics[0].clinic_name;

                        if (doc.clinics.length > 1) {
                            $('#nmbp-modal-clinic-group').show();
                        } else {
                            $('#nmbp-modal-clinic-group').hide();
                        }
                    } else {
                        $('#nmbp-modal-clinic-group').hide();
                        self.modalState.clinicId = 0;
                    }

                    self.loadModalSlots();
                }
            });
        },

        loadModalSlots: function () {
            const self = this;
            const $container = $('#nmbp-modal-slots-container');
            const docId = self.modalState.doctorId;
            const clinicId = self.modalState.clinicId;
            const date = self.modalState.date;

            if (!docId || !date) return;

            $container.html('<div class="nmbp-text-muted" style="grid-column:1/-1; padding:12px; font-size:13px; text-align:center;">Checking real-time doctor availability...</div>');
            self.modalState.slotStartTime = '';
            self.modalState.slotEndTime = '';

            $.ajax({
                url: config.restUrl + `booking/slots?doctor_id=${docId}&clinic_id=${clinicId || 0}&date=${date}`,
                method: 'GET',
                success: function (res) {
                    const data = res.data || {};
                    const slots = data.slots || [];

                    if (!slots.length) {
                        $container.html('<div style="grid-column:1/-1; padding:14px; color:#94a3b8; font-size:13px; text-align:center;">No appointment slots available on this date. Please pick another date.</div>');
                        return;
                    }

                    let html = '';
                    slots.forEach(function (s) {
                        html += `
                            <button type="button" class="nmbp-slot-btn nmbp-modal-slot-btn" data-start="${s.start_time}" data-end="${s.end_time}" data-display="${s.display}">
                                ${s.display}
                            </button>
                        `;
                    });
                    $container.html(html);

                    $('.nmbp-modal-slot-btn').on('click', function () {
                        $('.nmbp-modal-slot-btn').removeClass('selected');
                        $(this).addClass('selected');
                        self.modalState.slotStartTime = $(this).data('start');
                        self.modalState.slotEndTime = $(this).data('end');
                        self.modalState.slotDisplay = $(this).data('display');
                    });
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No consultation slots available on this date.';
                    $container.html(`<div style="grid-column:1/-1; padding:14px; color:#ef4444; font-size:13px; text-align:center;">${msg}</div>`);
                }
            });
        },

        submitModalBooking: function () {
            const self = this;
            const firstName = $('#nmbp-modal-first-name').val().trim();
            const lastName = $('#nmbp-modal-last-name').val().trim();
            const phone = $('#nmbp-modal-phone').val().trim();
            const email = $('#nmbp-modal-email').val().trim();

            if (!firstName || !lastName || !phone || !email) {
                alert('Please enter patient First Name, Last Name, Phone and Email (*).');
                return;
            }

            if (!self.modalState.slotStartTime) {
                alert('Please select an available consultation time slot.');
                return;
            }

            const paymentMethod = $('input[name="modal_payment_method"]:checked').val() || 'cash';

            const payload = {
                clinic_id: self.modalState.clinicId,
                doctor_id: self.modalState.doctorId,
                department_id: self.modalState.departmentId,
                appointment_date: self.modalState.date,
                start_time: self.modalState.slotStartTime,
                end_time: self.modalState.slotEndTime,
                appointment_type: $('#nmbp-modal-app-type').val(),
                payment_method: paymentMethod,
                patient_notes: $('#nmbp-modal-notes').val(),
                patient: {
                    first_name: firstName,
                    last_name: lastName,
                    phone: phone,
                    email: email,
                    gender: $('#nmbp-modal-gender').val()
                }
            };

            const $btn = $('#nmbp-modal-confirm-btn');
            $btn.prop('disabled', true).text('Confirming Consultation...');

            $.ajax({
                url: config.restUrl + 'booking/submit',
                method: 'POST',
                headers: { 'X-WP-Nonce': config.nonce },
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function (res) {
                    $btn.prop('disabled', false).text('Confirm & Book Consultation Now');
                    const data = res.data || {};
                    const refCode = data.appointment_number || 'NMBP-CONFIRMED';

                    $('#nmbp-modal-ref-code').text(refCode);
                    $('#nmbp-modal-form-view').hide();
                    $('#nmbp-modal-success-view').fadeIn(200);
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).text('Confirm & Book Consultation Now');
                    const err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error submitting booking request.';
                    alert(err);
                }
            });
        },

        selectClinicDirectly: function (clinicId) {
            this.state.clinicId = clinicId;
            $('html, body').animate({
                scrollTop: $('#nmbp-booking-app').offset().top - 40
            }, 500);
            this.goToStep(2);
        },

        cancelAppointment: function (appId, $btn) {
            $btn.prop('disabled', true).text('Cancelling...');
            $.ajax({
                url: config.restUrl + `appointments/${appId}/cancel`,
                method: 'POST',
                headers: { 'X-WP-Nonce': config.nonce },
                contentType: 'application/json',
                data: JSON.stringify({ reason: 'Cancelled by patient from portal' }),
                success: function (res) {
                    alert('Appointment cancelled successfully.');
                    window.location.reload();
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).text('Cancel');
                    alert(xhr.responseJSON?.message || 'Failed to cancel appointment.');
                }
            });
        },

        updateAppointmentStatus: function (appId, status, $btn) {
            $btn.prop('disabled', true);
            $.ajax({
                url: config.restUrl + `appointments/${appId}/status`,
                method: 'POST',
                headers: { 'X-WP-Nonce': config.nonce },
                contentType: 'application/json',
                data: JSON.stringify({ status: status }),
                success: function (res) {
                    window.location.reload();
                },
                error: function (xhr) {
                    $btn.prop('disabled', false);
                    alert(xhr.responseJSON?.message || 'Failed to update consultation status.');
                }
            });
        },

        trackAppointment: function () {
            const ref = $('#nmbp-track-ref').val().trim();
            const $resBox = $('#nmbp-track-result');
            if (!ref) return;

            $resBox.show().html('<div style="color:#64748b;">Searching appointment records...</div>');

            $.ajax({
                url: config.restUrl + `appointments?search=${encodeURIComponent(ref)}`,
                method: 'GET',
                success: function (res) {
                    const items = res.data ? res.data.items : [];
                    if (!items.length) {
                        $resBox.html('<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px; color:#991b1b;">No appointment found with this reference number. Please check the spelling.</div>');
                        return;
                    }

                    const app = items[0];
                    $resBox.html(`
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                                <strong style="font-size:16px; color:#0f172a;">${app.appointment_number}</strong>
                                <span class="nmbp-badge nmbp-status-${app.appointment_status}">${app.appointment_status.toUpperCase()}</span>
                            </div>
                            <p style="margin:4px 0; font-size:13px;"><strong>Patient:</strong> ${app.patient_first_name} ${app.patient_last_name}</p>
                            <p style="margin:4px 0; font-size:13px;"><strong>Doctor:</strong> Dr. ${app.doctor_first_name} ${app.doctor_last_name}</p>
                            <p style="margin:4px 0; font-size:13px;"><strong>Clinic:</strong> ${app.clinic_name}</p>
                            <p style="margin:4px 0; font-size:13px;"><strong>Date & Time:</strong> ${app.appointment_date} at ${app.start_time}</p>
                            <p style="margin:4px 0; font-size:13px;"><strong>Payment:</strong> <span class="nmbp-badge nmbp-pay-${app.payment_status}">${app.payment_status.toUpperCase()}</span></p>
                        </div>
                    `);
                },
                error: function () {
                    $resBox.html('<div style="color:#ef4444;">Failed to retrieve appointment information.</div>');
                }
            });
        }
    };

    $(document).ready(function () {
        BookingEngine.init();
    });

})(jQuery);

