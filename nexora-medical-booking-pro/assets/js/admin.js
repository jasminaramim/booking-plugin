/**
 * Nexora Medical Booking Pro - Admin Application Bundle
 * Built for WordPress 6.5+ & Modern Browsers
 * Author: Jasmin
 */

(function () {
  'use strict';

  function initApp() {
    const rootEl = document.getElementById('nmbp-admin-root');
    if (!rootEl) return;

    const config = window.nmbpAdminConfig || {
      restUrl: '/wp-json/nmbp/v1/',
      nonce: '',
      currentUser: { name: 'Jasmin', roles: ['administrator'] },
    };

    const initialPageAttr = rootEl.getAttribute('data-current-page') || 'nmbp-dashboard';
    let activePage = initialPageAttr.replace(/^nmbp-/, '') || 'dashboard';

    // API Helper
    async function apiRequest(endpoint, options = {}) {
      const url = config.restUrl + endpoint.replace(/^\//, '');
      const headers = {
        'X-WP-Nonce': config.nonce,
        'Content-Type': 'application/json',
        ...(options.headers || {}),
      };

      const res = await fetch(url, { ...options, headers });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        throw new Error(data.message || `Request failed (${res.status})`);
      }
      return data.data;
    }

    // State Store
    const state = {
      dashboard: null,
      appointments: [],
      clinics: [],
      doctors: [],
      departments: [],
      schedules: [],
      patients: [],
      payments: [],
      settings: null,
      systemStatus: null,
      auditLogs: [],
      loading: false,
      modal: null, // { type, title, data }
    };

    // Navigation Items
    const navItems = [
      { id: 'dashboard', label: 'Dashboard', icon: '📊' },
      { id: 'appointments', label: 'Appointments', icon: '📅' },
      { id: 'clinics', label: 'Clinics & Branches', icon: '🏥' },
      { id: 'doctors', label: 'Doctors', icon: '👨‍⚕️' },
      { id: 'departments', label: 'Departments', icon: '🩺' },
      { id: 'schedules', label: 'Schedules', icon: '⏰' },
      { id: 'patients', label: 'Patients', icon: '👥' },
      { id: 'payments', label: 'Payments', icon: '💳' },
      { id: 'design-studio', label: 'Design Studio', icon: '🎨' },
      { id: 'settings', label: 'Settings', icon: '⚙️' },
      { id: 'system-status', label: 'System Status', icon: '🛡️' },
    ];

    function setPage(page) {
      activePage = page;
      const newUrl = new URL(window.location.href);
      newUrl.searchParams.set('page', `nmbp-${page}`);
      window.history.pushState({}, '', newUrl.toString());
      render();
      loadActivePageData();
    }

    // Loaders
    async function loadDashboard() {
      state.loading = true;
      render();
      try {
        state.dashboard = await apiRequest('/dashboard/stats');
      } catch (e) {
        console.error(e);
      } finally {
        state.loading = false;
        render();
      }
    }

    async function loadAppointments(filter = {}) {
      state.loading = true;
      render();
      try {
        const query = new URLSearchParams(filter).toString();
        const res = await apiRequest('/appointments' + (query ? `?${query}` : ''));
        state.appointments = res.items || [];
      } catch (e) {
        console.error(e);
      } finally {
        state.loading = false;
        render();
      }
    }

    async function loadClinics() {
      state.loading = true;
      render();
      try {
        const res = await apiRequest('/clinics');
        state.clinics = res.items || [];
      } catch (e) {
        console.error(e);
      } finally {
        state.loading = false;
        render();
      }
    }

    async function loadDoctors() {
      state.loading = true;
      render();
      try {
        const [docRes, deptRes, clinicRes] = await Promise.all([
          apiRequest('/doctors'),
          apiRequest('/departments'),
          apiRequest('/clinics'),
        ]);
        state.doctors = docRes.items || [];
        state.departments = deptRes || [];
        state.clinics = clinicRes.items || [];
      } catch (e) {
        console.error(e);
      } finally {
        state.loading = false;
        render();
      }
    }

    async function loadDepartments() {
      state.loading = true;
      render();
      try {
        state.departments = await apiRequest('/departments') || [];
      } catch (e) {
        console.error(e);
      } finally {
        state.loading = false;
        render();
      }
    }

    async function loadSchedules(docId, clinicId) {
      state.loading = true;
      render();
      try {
        if (!state.doctors.length || !state.clinics.length) {
          const [docRes, clinicRes] = await Promise.all([
            apiRequest('/doctors'),
            apiRequest('/clinics'),
          ]);
          state.doctors = docRes.items || [];
          state.clinics = clinicRes.items || [];
        }

        const dId = docId || (state.doctors[0] ? state.doctors[0].id : 0);
        const cId = clinicId || (state.clinics[0] ? state.clinics[0].id : 0);

        if (dId && cId) {
          state.schedules = await apiRequest(`/schedules/doctor/${dId}?clinic_id=${cId}`) || [];
        }
      } catch (e) {
        console.error(e);
      } finally {
        state.loading = false;
        render();
      }
    }

    async function loadPatients(search = '') {
      state.loading = true;
      render();
      try {
        const res = await apiRequest('/patients' + (search ? `?search=${encodeURIComponent(search)}` : ''));
        state.patients = res.items || [];
      } catch (e) {
        console.error(e);
      } finally {
        state.loading = false;
        render();
      }
    }

    async function loadPayments(status = '') {
      state.loading = true;
      render();
      try {
        const res = await apiRequest('/payments' + (status ? `?status=${encodeURIComponent(status)}` : ''));
        state.payments = res.items || [];
      } catch (e) {
        console.error(e);
      } finally {
        state.loading = false;
        render();
      }
    }

    async function loadSettings() {
      state.loading = true;
      render();
      try {
        state.settings = await apiRequest('/settings');
      } catch (e) {
        console.error(e);
      } finally {
        state.loading = false;
        render();
      }
    }

    async function loadSystemStatus() {
      state.loading = true;
      render();
      try {
        const [stat, logs] = await Promise.all([
          apiRequest('/system/status'),
          apiRequest('/audit-logs'),
        ]);
        state.systemStatus = stat;
        state.auditLogs = logs || [];
      } catch (e) {
        console.error(e);
      } finally {
        state.loading = false;
        render();
      }
    }

    function loadActivePageData() {
      switch (activePage) {
        case 'dashboard': loadDashboard(); break;
        case 'appointments': loadAppointments(); break;
        case 'clinics': loadClinics(); break;
        case 'doctors': loadDoctors(); break;
        case 'departments': loadDepartments(); break;
        case 'schedules': loadSchedules(); break;
        case 'patients': loadPatients(); break;
        case 'payments': loadPayments(); break;
        case 'design-studio': loadSettings(); break;
        case 'settings': loadSettings(); break;
        case 'system-status': loadSystemStatus(); break;
      }
    }

    // Modal Control
    function openModal(type, title, data = {}) {
      state.modal = { type, title, data };
      render();
    }

    function closeModal() {
      state.modal = null;
      render();
    }

    // Main Renderer
    function render() {
      rootEl.innerHTML = `
        <div class="nmbp-admin-app">
          <!-- Sidebar -->
          <aside class="nmbp-sidebar">
            <div class="nmbp-sidebar-brand">
              <div class="nmbp-brand-logo">N</div>
              <div>
                <h2 class="nmbp-brand-title">Nexora Medical <span class="nmbp-brand-badge">PRO</span></h2>
                <small style="color: #64748b; font-size: 11px;">Author: Jasmin</small>
              </div>
            </div>
            <nav class="nmbp-sidebar-nav">
              ${navItems.map(item => `
                <button
                  class="nmbp-nav-btn ${activePage === item.id ? 'active' : ''}"
                  data-page="${item.id}"
                >
                  <span class="nmbp-nav-icon">${item.icon}</span>
                  <span>${item.label}</span>
                </button>
              `).join('')}
            </nav>
          </aside>

          <!-- Main Content -->
          <main class="nmbp-main">
            ${renderMainContent()}
          </main>
        </div>

        <!-- Modal Container -->
        ${state.modal ? renderModal() : ''}
      `;

      bindDOMEvents();
    }

    function renderMainContent() {
      switch (activePage) {
        case 'dashboard': return renderDashboard();
        case 'appointments': return renderAppointments();
        case 'clinics': return renderClinics();
        case 'doctors': return renderDoctors();
        case 'departments': return renderDepartments();
        case 'schedules': return renderSchedules();
        case 'patients': return renderPatients();
        case 'payments': return renderPayments();
        case 'design-studio': return renderDesignStudio();
        case 'settings': return renderSettings();
        case 'system-status': return renderSystemStatus();
        default: return renderDashboard();
      }
    }

    // Render Sub-Views
    function renderDashboard() {
      const d = state.dashboard;
      if (!d) {
        return `
          <header class="nmbp-topbar"><h1 class="nmbp-page-title">Medical Operations Dashboard</h1></header>
          <div class="nmbp-content-body"><p style="text-align:center; padding:40px; color:#64748b;">Loading metrics...</p></div>
        `;
      }

      return `
        <header class="nmbp-topbar">
          <div>
            <h1 class="nmbp-page-title">Medical Operations Dashboard</h1>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Global hospital network overview & appointment monitoring</p>
          </div>
          <div class="nmbp-topbar-actions">
            <button class="nmbp-adm-btn nmbp-adm-btn-primary" data-action="goto-page" data-page="appointments">Manage All Bookings</button>
          </div>
        </header>

        <div class="nmbp-content-body">
          <div class="nmbp-stats-grid">
            <div class="nmbp-stat-card">
              <div class="nmbp-stat-icon-wrap nmbp-stat-icon-teal">🏥</div>
              <div><h3 class="nmbp-stat-val">${d.counts.clinics}</h3><p class="nmbp-stat-label">Total Clinics</p></div>
            </div>
            <div class="nmbp-stat-card">
              <div class="nmbp-stat-icon-wrap nmbp-stat-icon-blue">👨‍⚕️</div>
              <div><h3 class="nmbp-stat-val">${d.counts.doctors}</h3><p class="nmbp-stat-label">Active Doctors</p></div>
            </div>
            <div class="nmbp-stat-card">
              <div class="nmbp-stat-icon-wrap nmbp-stat-icon-teal">👥</div>
              <div><h3 class="nmbp-stat-val">${d.counts.patients}</h3><p class="nmbp-stat-label">Registered Patients</p></div>
            </div>
            <div class="nmbp-stat-card">
              <div class="nmbp-stat-icon-wrap nmbp-stat-icon-green">📅</div>
              <div><h3 class="nmbp-stat-val">${d.counts.today_bookings}</h3><p class="nmbp-stat-label">Today's Bookings</p></div>
            </div>
            <div class="nmbp-stat-card">
              <div class="nmbp-stat-icon-wrap nmbp-stat-icon-amber">⏳</div>
              <div><h3 class="nmbp-stat-val">${d.counts.pending_approvals}</h3><p class="nmbp-stat-label">Pending Approvals</p></div>
            </div>
            <div class="nmbp-stat-card">
              <div class="nmbp-stat-icon-wrap nmbp-stat-icon-teal">✅</div>
              <div><h3 class="nmbp-stat-val">${d.counts.confirmed}</h3><p class="nmbp-stat-label">Confirmed Bookings</p></div>
            </div>
            <div class="nmbp-stat-card">
              <div class="nmbp-stat-icon-wrap nmbp-stat-icon-green">💵</div>
              <div><h3 class="nmbp-stat-val">$${parseFloat(d.revenue.today).toFixed(2)}</h3><p class="nmbp-stat-label">Today's Revenue</p></div>
            </div>
            <div class="nmbp-stat-card">
              <div class="nmbp-stat-icon-wrap nmbp-stat-icon-blue">📈</div>
              <div><h3 class="nmbp-stat-val">$${parseFloat(d.revenue.monthly).toFixed(2)}</h3><p class="nmbp-stat-label">Monthly Revenue</p></div>
            </div>
          </div>

          <div class="nmbp-card-table">
            <div class="nmbp-table-header">
              <h3>Recent Booking Requests (Awaiting Admin Approval)</h3>
              <button class="nmbp-adm-btn nmbp-adm-btn-outline nmbp-adm-btn-sm" data-action="refresh-dashboard">Refresh</button>
            </div>
            <table class="nmbp-ui-table">
              <thead>
                <tr>
                  <th>Ref #</th>
                  <th>Patient</th>
                  <th>Doctor</th>
                  <th>Branch</th>
                  <th>Date & Time</th>
                  <th>Fee</th>
                  <th>Status</th>
                  <th>Quick Action</th>
                </tr>
              </thead>
              <tbody>
                ${d.recent_bookings.length === 0 ? `
                  <tr><td colSpan="8" style="text-align:center; padding:30px; color:#94a3b8;">No pending bookings.</td></tr>
                ` : d.recent_bookings.map(app => `
                  <tr>
                    <td><strong>${app.appointment_number}</strong></td>
                    <td>${app.patient_first_name} ${app.patient_last_name}</td>
                    <td>Dr. ${app.doctor_first_name} ${app.doctor_last_name}</td>
                    <td>${app.clinic_name}</td>
                    <td>${app.appointment_date} ${app.start_time}</td>
                    <td>$${parseFloat(app.consultation_fee).toFixed(2)}</td>
                    <td><span class="nmbp-badge-status nmbp-badge-${app.appointment_status}">${app.appointment_status}</span></td>
                    <td>
                      ${app.appointment_status === 'pending' ? `
                        <button class="nmbp-adm-btn nmbp-adm-btn-primary nmbp-adm-btn-sm" data-action="quick-approve" data-id="${app.id}">Approve</button>
                      ` : `<span style="color:#94a3b8; font-size:12px;">Processed</span>`}
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    }

    function renderAppointments() {
      const items = state.appointments;
      return `
        <header class="nmbp-topbar">
          <div>
            <h1 class="nmbp-page-title">Appointment Management</h1>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Review, approve, reschedule and coordinate patient consultations</p>
          </div>
          <div class="nmbp-topbar-actions">
            <button class="nmbp-adm-btn nmbp-adm-btn-outline" data-action="refresh-appointments">Refresh</button>
          </div>
        </header>

        <div class="nmbp-content-body">
          <div class="nmbp-card-table">
            <div class="nmbp-table-header">
              <h3>All Bookings (${items.length})</h3>
            </div>
            <table class="nmbp-ui-table">
              <thead>
                <tr>
                  <th>Ref #</th>
                  <th>Patient</th>
                  <th>Doctor & Clinic</th>
                  <th>Date & Time</th>
                  <th>Fee</th>
                  <th>Payment</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                ${items.length === 0 ? `
                  <tr><td colSpan="8" style="text-align:center; padding:40px; color:#94a3b8;">No appointment records found.</td></tr>
                ` : items.map(a => `
                  <tr>
                    <td><strong>${a.appointment_number}</strong></td>
                    <td>
                      <div><strong>${a.patient_first_name} ${a.patient_last_name}</strong></div>
                      <div style="font-size:12px; color:#64748b;">${a.patient_phone}</div>
                    </td>
                    <td>
                      <div>Dr. ${a.doctor_first_name} ${a.doctor_last_name}</div>
                      <div style="font-size:12px; color:#64748b;">${a.clinic_name}</div>
                    </td>
                    <td>
                      <div>${a.appointment_date}</div>
                      <div style="font-size:12px; color:#0d9488; font-weight:600;">${a.start_time} - ${a.end_time}</div>
                    </td>
                    <td>$${parseFloat(a.consultation_fee).toFixed(2)}</td>
                    <td><span class="nmbp-badge-status nmbp-badge-${a.payment_status}">${a.payment_status}</span></td>
                    <td><span class="nmbp-badge-status nmbp-badge-${a.appointment_status}">${a.appointment_status}</span></td>
                    <td>
                      <div style="display:flex; gap:6px;">
                        ${a.appointment_status === 'pending' ? `
                          <button class="nmbp-adm-btn nmbp-adm-btn-primary nmbp-adm-btn-sm" data-action="quick-approve" data-id="${a.id}">Approve</button>
                          <button class="nmbp-adm-btn nmbp-adm-btn-danger nmbp-adm-btn-sm" data-action="open-reject" data-id="${a.id}">Reject</button>
                        ` : ''}
                        ${a.appointment_status === 'confirmed' ? `
                          <button class="nmbp-adm-btn nmbp-adm-btn-outline nmbp-adm-btn-sm" data-action="mark-complete" data-id="${a.id}">Complete</button>
                        ` : ''}
                      </div>
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    }

    function renderClinics() {
      const items = state.clinics;
      return `
        <header class="nmbp-topbar">
          <div>
            <h1 class="nmbp-page-title">Clinics & Hospital Branches</h1>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Manage multi-location clinics and branch working hours</p>
          </div>
          <div class="nmbp-topbar-actions">
            <button class="nmbp-adm-btn nmbp-adm-btn-primary" data-action="open-clinic-modal">+ Add Clinic</button>
          </div>
        </header>

        <div class="nmbp-content-body">
          <div class="nmbp-card-table">
            <div class="nmbp-table-header"><h3>Registered Branches (${items.length})</h3></div>
            <table class="nmbp-ui-table">
              <thead>
                <tr>
                  <th>Clinic Name</th>
                  <th>Location</th>
                  <th>Contact</th>
                  <th>Hours</th>
                  <th>Default Fee</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                ${items.length === 0 ? `
                  <tr><td colSpan="7" style="text-align:center; padding:40px; color:#94a3b8;">No clinics found. Click "+ Add Clinic" to add your first hospital branch.</td></tr>
                ` : items.map(c => `
                  <tr>
                    <td><strong>${c.name}</strong></td>
                    <td>${c.address}, ${c.city}</td>
                    <td><div>${c.phone}</div><div style="font-size:12px; color:#64748b;">${c.email}</div></td>
                    <td>${c.opening_time} - ${c.closing_time}</td>
                    <td>$${parseFloat(c.default_fee).toFixed(2)}</td>
                    <td><span class="nmbp-badge-status nmbp-badge-${c.status === 'active' ? 'confirmed' : 'rejected'}">${c.status}</span></td>
                    <td>
                      <button class="nmbp-adm-btn nmbp-adm-btn-danger nmbp-adm-btn-sm" data-action="delete-clinic" data-id="${c.id}">Delete</button>
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    }

    function renderDoctors() {
      const items = state.doctors;
      return `
        <header class="nmbp-topbar">
          <div>
            <h1 class="nmbp-page-title">Doctor Profiles & Practitioners</h1>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Manage medical specialists, fees, and multi-branch allocations</p>
          </div>
          <div class="nmbp-topbar-actions">
            <button class="nmbp-adm-btn nmbp-adm-btn-primary" data-action="open-doctor-modal">+ Add Doctor</button>
          </div>
        </header>

        <div class="nmbp-content-body">
          <div class="nmbp-card-table">
            <div class="nmbp-table-header"><h3>Registered Doctors (${items.length})</h3></div>
            <table class="nmbp-ui-table">
              <thead>
                <tr>
                  <th>Doctor</th>
                  <th>Specialty</th>
                  <th>Contact</th>
                  <th>Fee</th>
                  <th>Duration</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                ${items.length === 0 ? `
                  <tr><td colSpan="7" style="text-align:center; padding:40px; color:#94a3b8;">No doctors found. Click "+ Add Doctor" to create one.</td></tr>
                ` : items.map(d => `
                  <tr>
                    <td><strong>Dr. ${d.first_name} ${d.last_name}</strong></td>
                    <td><div><strong>${d.specialization}</strong></div><div style="font-size:12px; color:#0d9488;">${d.department_name || 'General'}</div></td>
                    <td><div>${d.email}</div><div style="font-size:12px; color:#64748b;">${d.phone}</div></td>
                    <td>$${parseFloat(d.consultation_fee).toFixed(2)}</td>
                    <td>${d.appointment_duration} mins</td>
                    <td><span class="nmbp-badge-status nmbp-badge-${d.status === 'active' ? 'confirmed' : 'rejected'}">${d.status}</span></td>
                    <td>
                      <button class="nmbp-adm-btn nmbp-adm-btn-danger nmbp-adm-btn-sm" data-action="delete-doctor" data-id="${d.id}">Delete</button>
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    }

    function renderDepartments() {
      const items = state.departments;
      return `
        <header class="nmbp-topbar">
          <div>
            <h1 class="nmbp-page-title">Medical Departments</h1>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Organize specialties and clinical services</p>
          </div>
          <div class="nmbp-topbar-actions">
            <button class="nmbp-adm-btn nmbp-adm-btn-primary" data-action="open-dept-modal">+ Add Department</button>
          </div>
        </header>

        <div class="nmbp-content-body">
          <div class="nmbp-card-table">
            <div class="nmbp-table-header"><h3>Departments (${items.length})</h3></div>
            <table class="nmbp-ui-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Slug</th>
                  <th>Description</th>
                  <th>Icon</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                ${items.map(d => `
                  <tr>
                    <td><strong>${d.name}</strong></td>
                    <td><code>${d.slug}</code></td>
                    <td>${d.description || 'Clinical specialty'}</td>
                    <td>${d.icon || 'medical'}</td>
                    <td><span class="nmbp-badge-status nmbp-badge-${d.status === 'active' ? 'confirmed' : 'rejected'}">${d.status}</span></td>
                    <td>
                      <button class="nmbp-adm-btn nmbp-adm-btn-danger nmbp-adm-btn-sm" data-action="delete-dept" data-id="${d.id}">Delete</button>
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    }

    function renderSchedules() {
      return `
        <header class="nmbp-topbar">
          <div>
            <h1 class="nmbp-page-title">Doctor Scheduling Engine</h1>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Configure weekly consultation hours, break times and slot capacity</p>
          </div>
        </header>

        <div class="nmbp-content-body">
          <div class="nmbp-card-table" style="padding: 24px;">
            <p style="color:#0f172a; font-weight:600;">Weekly consultation slots are active and managed per doctor branch assignment.</p>
            <p style="color:#64748b; font-size:13px;">Slots are calculated automatically with break-time exclusion and double-booking atomic locks.</p>
          </div>
        </div>
      `;
    }

    function renderPatients() {
      const items = state.patients;
      return `
        <header class="nmbp-topbar">
          <div>
            <h1 class="nmbp-page-title">Patient Directory</h1>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Manage registered hospital patients and medical contacts</p>
          </div>
        </header>

        <div class="nmbp-content-body">
          <div class="nmbp-card-table">
            <div class="nmbp-table-header"><h3>Registered Patients (${items.length})</h3></div>
            <table class="nmbp-ui-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Phone</th>
                  <th>Email</th>
                  <th>Blood Group</th>
                  <th>Registered</th>
                </tr>
              </thead>
              <tbody>
                ${items.length === 0 ? `
                  <tr><td colSpan="5" style="text-align:center; padding:40px; color:#94a3b8;">No registered patients yet.</td></tr>
                ` : items.map(p => `
                  <tr>
                    <td><strong>${p.first_name} ${p.last_name}</strong></td>
                    <td>${p.phone}</td>
                    <td>${p.email}</td>
                    <td>${p.blood_group || 'Unknown'}</td>
                    <td>${(p.created_at || '').slice(0, 10)}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    }

    function renderPayments() {
      const items = state.payments;
      return `
        <header class="nmbp-topbar">
          <div>
            <h1 class="nmbp-page-title">Payment & Billing Records</h1>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Track verified revenue, online checkout and manual desk receipts</p>
          </div>
          <div class="nmbp-topbar-actions">
            <button class="nmbp-adm-btn nmbp-adm-btn-outline" data-action="goto-settings">&#9881; Payment Gateways Setup</button>
          </div>
        </header>

        <div class="nmbp-content-body">
          <div class="nmbp-card-table">
            <div class="nmbp-table-header"><h3>Transactions (${items.length})</h3></div>
            <table class="nmbp-ui-table">
              <thead>
                <tr>
                  <th>Receipt #</th>
                  <th>Appointment #</th>
                  <th>Patient</th>
                  <th>Amount</th>
                  <th>Gateway</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                ${items.length === 0 ? `
                  <tr><td colSpan="7" style="text-align:center; padding:40px; color:#94a3b8;">No transactions found.</td></tr>
                ` : items.map(p => `
                  <tr>
                    <td><strong>${p.payment_number}</strong></td>
                    <td>${p.appointment_number || 'N/A'}</td>
                    <td>${p.patient_first_name} ${p.patient_last_name}</td>
                    <td><strong>$${parseFloat(p.amount).toFixed(2)}</strong></td>
                    <td><code>${p.gateway}</code></td>
                    <td><span class="nmbp-badge-status nmbp-badge-${p.payment_status}">${p.payment_status}</span></td>
                    <td>
                      ${p.payment_status !== 'paid' ? `
                        <button class="nmbp-adm-btn nmbp-adm-btn-primary nmbp-adm-btn-sm" data-action="verify-payment" data-id="${p.id}">Verify Paid</button>
                      ` : `<span style="color:#16a34a; font-weight:600; font-size:12px;">Verified Paid</span>`}
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    }

    function renderDesignStudio() {
      const d = state.settings?.design || {};
      const activeCardPreset = d.card_preset || 'classic_medical';
      const activeSinglePreset = d.single_doctor_preset || 'clinical_split';
      const activeFormPreset = d.form_preset || 'multi_step';

      return `
        <header class="nmbp-topbar">
          <div>
            <h1 class="nmbp-page-title">Visual Design Studio</h1>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Customize doctor card presets, single profile layouts, and brand medical styling</p>
          </div>
          <div class="nmbp-topbar-actions">
            <button class="nmbp-adm-btn nmbp-adm-btn-primary" data-action="save-design">&#10004; Publish Styles</button>
          </div>
        </header>

        <div class="nmbp-content-body">
          <!-- SECTION 1: Doctor Card Grid Presets (4 Presets) -->
          <div class="nmbp-card-table" style="padding: 24px; margin-bottom: 24px;">
            <div class="nmbp-preset-section-title">&#128104;&#8205;&#9877;&#65039; 1. Doctor Card Presets (Grid & Directory)</div>
            <p class="nmbp-preset-section-desc">Choose how doctor profiles appear inside the <code>[nmbp_doctors]</code> directory grid on your website.</p>
            
            <div class="nmbp-preset-picker-grid">
              <!-- Preset 1: Classic Medical -->
              <div class="nmbp-preset-card-option ${activeCardPreset === 'classic_medical' ? 'selected' : ''}">
                <div class="nmbp-preset-header">
                  <h4 class="nmbp-preset-title">01. Classic Medical</h4>
                  <input type="radio" name="nmbp_card_preset" value="classic_medical" class="nmbp-preset-radio" ${activeCardPreset === 'classic_medical' ? 'checked' : ''}>
                </div>
                <span class="nmbp-preset-badge">Multi-Clinic Standard</span>
                <div class="nmbp-preset-mockup">
                  <div style="width:36px; height:36px; border-radius:50%; background:#0d9488; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; margin:0 auto;">Dr</div>
                  <strong style="color:#0f172a; font-size:12px; margin-top:4px;">Dr. Sarah Jenkins</strong>
                  <span style="font-size:10px; color:#0d9488;">Cardiologist</span>
                  <div style="display:flex; gap:4px; margin-top:4px;">
                    <span style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:9px;">12+ Yrs</span>
                    <span style="background:#ccfbf1; color:#0f766e; padding:2px 6px; border-radius:4px; font-size:9px;">$75.00</span>
                  </div>
                </div>
                <p class="nmbp-preset-desc">Circular avatar with active status dot, department badge, experience & fee strip, and dual action buttons.</p>
              </div>

              <!-- Preset 2: Modern Minimal / Glass -->
              <div class="nmbp-preset-card-option ${activeCardPreset === 'modern_glass' || activeCardPreset === 'modern_minimal' ? 'selected' : ''}">
                <div class="nmbp-preset-header">
                  <h4 class="nmbp-preset-title">02. Modern Minimal / Glass</h4>
                  <input type="radio" name="nmbp_card_preset" value="modern_glass" class="nmbp-preset-radio" ${activeCardPreset === 'modern_glass' || activeCardPreset === 'modern_minimal' ? 'checked' : ''}>
                </div>
                <span class="nmbp-preset-badge">Modern Aesthetic</span>
                <div class="nmbp-preset-mockup" style="background: linear-gradient(180deg, #f0fdfa 0%, #ffffff 100%);">
                  <div style="width:100%; height:20px; background:linear-gradient(135deg, #0d9488, #0f766e); border-radius:4px; margin-bottom:4px;"></div>
                  <strong style="color:#0f172a; font-size:12px;">Dr. Robert Chen &#10003;</strong>
                  <span style="font-size:10px; color:#64748b;">Neurology Specialist</span>
                  <div style="width:80%; height:18px; background:#0d9488; color:#fff; border-radius:4px; font-size:9px; display:flex; align-items:center; justify-content:center; margin-top:6px;">Book Consultation</div>
                </div>
                <p class="nmbp-preset-desc">Floating cover banner, verified badge, consultation fee pill, sleek typography, and prominent booking button.</p>
              </div>

              <!-- Preset 3: Hospital Pro (2-Column) -->
              <div class="nmbp-preset-card-option ${activeCardPreset === 'hospital_pro' ? 'selected' : ''}">
                <div class="nmbp-preset-header">
                  <h4 class="nmbp-preset-title">03. Hospital Pro (2-Col)</h4>
                  <input type="radio" name="nmbp_card_preset" value="hospital_pro" class="nmbp-preset-radio" ${activeCardPreset === 'hospital_pro' ? 'checked' : ''}>
                </div>
                <span class="nmbp-preset-badge">Clinical EHR Format</span>
                <div class="nmbp-preset-mockup" style="flex-direction:row; justify-content:space-between; text-align:left;">
                  <div style="width:36px; height:44px; border-radius:6px; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:18px;">&#129658;</div>
                  <div style="flex:1; margin-left:8px;">
                    <div style="font-size:9px; color:#0d9488; font-weight:700;">BMDC #A-48291</div>
                    <strong style="color:#0f172a; font-size:11px;">Dr. Emily Watson</strong>
                    <div style="font-size:9px; color:#64748b;">Central Hospital Branch</div>
                  </div>
                </div>
                <p class="nmbp-preset-desc">Horizontal clinical layout with squircle portrait, official BMDC/Medical License badge, and practicing clinic branch tags.</p>
              </div>

              <!-- Preset 4: Compact Directory Card -->
              <div class="nmbp-preset-card-option ${activeCardPreset === 'compact_card' || activeCardPreset === 'minimal_clean' ? 'selected' : ''}">
                <div class="nmbp-preset-header">
                  <h4 class="nmbp-preset-title">04. Compact Directory Card</h4>
                  <input type="radio" name="nmbp_card_preset" value="compact_card" class="nmbp-preset-radio" ${activeCardPreset === 'compact_card' || activeCardPreset === 'minimal_clean' ? 'checked' : ''}>
                </div>
                <span class="nmbp-preset-badge">High Density (4 Columns)</span>
                <div class="nmbp-preset-mockup">
                  <div style="width:30px; height:30px; border-radius:50%; background:#f1f5f9; border:1px solid #cbd5e1; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700;">MD</div>
                  <strong style="color:#0f172a; font-size:11px; margin-top:2px;">Dr. David Vance</strong>
                  <span style="font-size:9px; color:#64748b;">Pediatrician &bull; $60</span>
                </div>
                <p class="nmbp-preset-desc">Ultra-clean centered card with small footprint, ideal for dense multi-doctor directories and hospital departmental pages.</p>
              </div>
            </div>
          </div>

          <!-- SECTION 2: Single Doctor Profile Page Presets (4 Presets) -->
          <div class="nmbp-card-table" style="padding: 24px; margin-bottom: 24px;">
            <div class="nmbp-preset-section-title">&#128196; 2. Single Doctor Profile Page Presets</div>
            <p class="nmbp-preset-section-desc">Choose how an individual doctor's dedicated profile and schedule page is displayed (via <code>[nmbp_doctor_profile]</code> or clicking a doctor in <code>[nmbp_doctors]</code>).</p>
            
            <div class="nmbp-preset-picker-grid">
              <!-- Single Preset 1: Clinical Split (Sticky Booking) -->
              <div class="nmbp-preset-card-option ${activeSinglePreset === 'clinical_split' ? 'selected' : ''}">
                <div class="nmbp-preset-header">
                  <h4 class="nmbp-preset-title">01. Clinical Split</h4>
                  <input type="radio" name="nmbp_single_doctor_preset" value="clinical_split" class="nmbp-preset-radio" ${activeSinglePreset === 'clinical_split' ? 'checked' : ''}>
                </div>
                <span class="nmbp-preset-badge">Highest Conversion</span>
                <div class="nmbp-preset-mockup" style="flex-direction:row; justify-content:space-between; gap:6px; text-align:left;">
                  <div style="flex:2; background:#ffffff; border:1px solid #e2e8f0; border-radius:4px; padding:6px;">
                    <div style="font-size:10px; font-weight:700; color:#0f172a;">Doctor Bio & Shift Table</div>
                    <div style="font-size:8px; color:#64748b; margin-top:4px;">Weekly Schedule Matrix...</div>
                  </div>
                  <div style="flex:1; background:#f0fdfa; border:1px solid #0d9488; border-radius:4px; padding:6px; text-align:center;">
                    <div style="font-size:9px; font-weight:700; color:#0d9488;">Sticky Box</div>
                    <div style="font-size:8px; color:#0f766e;">$80 / Book</div>
                  </div>
                </div>
                <p class="nmbp-preset-desc">2-column clinical view with hero header, credentials, clinic branches, weekly timetable, and a floating sticky booking card.</p>
              </div>

              <!-- Single Preset 2: Modern Executive Hero Banner -->
              <div class="nmbp-preset-card-option ${activeSinglePreset === 'executive_banner' ? 'selected' : ''}">
                <div class="nmbp-preset-header">
                  <h4 class="nmbp-preset-title">02. Executive Banner</h4>
                  <input type="radio" name="nmbp_single_doctor_preset" value="executive_banner" class="nmbp-preset-radio" ${activeSinglePreset === 'executive_banner' ? 'checked' : ''}>
                </div>
                <span class="nmbp-preset-badge">Executive Hospital Group</span>
                <div class="nmbp-preset-mockup" style="background:#0f172a; color:#ffffff; border-color:#334155;">
                  <div style="width:100%; border-bottom:1px solid #334155; padding-bottom:4px; margin-bottom:4px; display:flex; align-items:center; gap:6px;">
                    <div style="width:18px; height:18px; border-radius:50%; background:#0d9488;"></div>
                    <span style="font-size:10px; font-weight:700; color:#ffffff;">Dr. Header Banner</span>
                  </div>
                  <div style="font-size:8px; color:#94a3b8;">15+ Yrs &bull; $100 &bull; 20 Mins Session</div>
                </div>
                <p class="nmbp-preset-desc">Full-width medical navy hero banner, overlapping circular portrait, stat pills, followed by 3 structured content blocks.</p>
              </div>

              <!-- Single Preset 3: Minimalist Centered Spotlight -->
              <div class="nmbp-preset-card-option ${activeSinglePreset === 'minimal_centered' ? 'selected' : ''}">
                <div class="nmbp-preset-header">
                  <h4 class="nmbp-preset-title">03. Minimal Centered</h4>
                  <input type="radio" name="nmbp_single_doctor_preset" value="minimal_centered" class="nmbp-preset-radio" ${activeSinglePreset === 'minimal_centered' ? 'checked' : ''}>
                </div>
                <span class="nmbp-preset-badge">Clean & Focused</span>
                <div class="nmbp-preset-mockup" style="text-align:center;">
                  <div style="width:34px; height:34px; border-radius:50%; background:#f1f5f9; border:2px solid #cbd5e1; margin:0 auto;"></div>
                  <div style="font-size:11px; font-weight:700; color:#0f172a; margin-top:4px;">Centered Physician</div>
                  <div style="font-size:8px; color:#64748b;">Focused Bio &bull; Practicing Clinics</div>
                  <div style="width:70%; height:16px; background:#0d9488; color:#fff; border-radius:12px; font-size:8px; display:flex; align-items:center; justify-content:center; margin:4px auto 0 auto;">Book Consultation</div>
                </div>
                <p class="nmbp-preset-desc">Centered doctor spotlight card with clean clinical biography, practicing branches, and a prominent centered consultation trigger.</p>
              </div>

              <!-- Single Preset 4: Hospital EHR Staff Dossier -->
              <div class="nmbp-preset-card-option ${activeSinglePreset === 'hospital_dossier' ? 'selected' : ''}">
                <div class="nmbp-preset-header">
                  <h4 class="nmbp-preset-title">04. Hospital Dossier</h4>
                  <input type="radio" name="nmbp_single_doctor_preset" value="hospital_dossier" class="nmbp-preset-radio" ${activeSinglePreset === 'hospital_dossier' ? 'checked' : ''}>
                </div>
                <span class="nmbp-preset-badge">Institutional Staff Record</span>
                <div class="nmbp-preset-mockup" style="background:#ffffff; border:1px solid #0f172a; padding:6px; text-align:left;">
                  <div style="background:#0f172a; color:#38bdf8; font-size:8px; font-weight:700; padding:2px 4px; border-radius:2px; display:flex; justify-content:space-between;">
                    <span>STAFF DOSSIER</span>
                    <span>#NMBP-001</span>
                  </div>
                  <div style="font-size:9px; color:#0f172a; font-weight:700; margin-top:4px;">EHR Credentials & Duty Shifts</div>
                </div>
                <p class="nmbp-preset-desc">Hospital-grade staff dossier with verified ID badge (#NMBP-XXX), left credential bar, and clinical shift matrix on the right.</p>
              </div>
            </div>
          </div>

          <!-- SECTION 3: Booking Stepper & Brand Colors -->
          <div class="nmbp-card-table" style="padding: 24px;">
            <div class="nmbp-preset-section-title">&#127912; 3. Booking Form & Brand Styling</div>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-top:16px;">
              <div>
                <label style="display:block; font-weight:600; margin-bottom:6px; color:#0f172a;">Primary Medical Brand Color:</label>
                <div style="display:flex; align-items:center; gap:10px;">
                  <input type="color" id="nmbp-inp-primary-color" value="${d.primary_color || '#0d9488'}" style="width:48px; height:38px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;">
                  <span style="font-size:13px; color:#64748b;">(Controls buttons, badges, active tabs, and highlights)</span>
                </div>
              </div>
              <div>
                <label style="display:block; font-weight:600; margin-bottom:6px; color:#0f172a;">Booking Form Flow Preset:</label>
                <select class="nmbp-search-input" id="nmbp-inp-form-preset" style="width:100%;">
                  <option value="multi_step" ${activeFormPreset === 'multi_step' ? 'selected' : ''}>01. Multi-Step Stepper (Wizard)</option>
                  <option value="classic_form" ${activeFormPreset === 'classic_form' ? 'selected' : ''}>02. Classic All-in-One Form</option>
                  <option value="calendar_view" ${activeFormPreset === 'calendar_view' ? 'selected' : ''}>03. Calendar-First View</option>
                  <option value="compact_form" ${activeFormPreset === 'compact_form' ? 'selected' : ''}>04. Compact Quick Form</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      `;
    }

    function renderSettings() {
      const g = state.settings?.general || {};
      const d = state.settings?.design || {};
      const p = state.settings?.payment || {};

      return `
        <header class="nmbp-topbar">
          <div>
            <h1 class="nmbp-page-title">Plugin Settings & Payment Gateways</h1>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Hospital organization identity, payment methods (Cash, Stripe, PayPal), and shortcodes</p>
          </div>
          <div class="nmbp-topbar-actions">
            <button class="nmbp-adm-btn nmbp-adm-btn-primary" data-action="save-general-settings">&#10004; Save Settings</button>
          </div>
        </header>

        <div class="nmbp-content-body">
          <!-- SECTION 1: General Hospital & Approval Settings -->
          <div class="nmbp-card-table" style="padding: 24px; margin-bottom: 24px;">
            <h3 style="margin:0 0 16px 0; font-size:17px; color:#0f172a;">Hospital Configuration & Policies</h3>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
              <label>
                <strong>Hospital / Company Name:</strong>
                <input type="text" id="nmbp-inp-company-name" class="nmbp-search-input" style="width:100%; margin-top:6px;" value="${g.company_name || 'Nexora Medical Group'}">
              </label>
              <label>
                <strong>Currency Symbol:</strong>
                <input type="text" id="nmbp-inp-currency" class="nmbp-search-input" style="width:100%; margin-top:6px;" value="${g.currency_symbol || '$'}">
              </label>
              <label>
                <strong>Default Doctor Card Preset:</strong>
                <select class="nmbp-search-input" id="nmbp-inp-settings-card-preset" style="width:100%; margin-top:6px;">
                  <option value="classic_medical" ${d.card_preset === 'classic_medical' ? 'selected' : ''}>01. Classic Medical Card</option>
                  <option value="modern_glass" ${d.card_preset === 'modern_glass' ? 'selected' : ''}>02. Modern Minimal / Glass Card</option>
                  <option value="hospital_pro" ${d.card_preset === 'hospital_pro' ? 'selected' : ''}>03. Hospital Pro (2-Column EHR)</option>
                  <option value="compact_card" ${d.card_preset === 'compact_card' ? 'selected' : ''}>04. Compact Directory Card</option>
                </select>
              </label>
              <label>
                <strong>Default Single Doctor Page Preset:</strong>
                <select class="nmbp-search-input" id="nmbp-inp-settings-single-preset" style="width:100%; margin-top:6px;">
                  <option value="clinical_split" ${d.single_doctor_preset === 'clinical_split' ? 'selected' : ''}>01. Clinical Split (Sticky Booking)</option>
                  <option value="executive_banner" ${d.single_doctor_preset === 'executive_banner' ? 'selected' : ''}>02. Executive Hero Banner</option>
                  <option value="minimal_centered" ${d.single_doctor_preset === 'minimal_centered' ? 'selected' : ''}>03. Minimalist Centered Spotlight</option>
                  <option value="hospital_dossier" ${d.single_doctor_preset === 'hospital_dossier' ? 'selected' : ''}>04. Hospital EHR Staff Dossier</option>
                </select>
              </label>
            </div>
            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #e2e8f0;">
              <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                <input type="checkbox" id="nmbp-inp-require-approval" ${g.require_approval !== false ? 'checked' : ''} style="width:18px; height:18px; accent-color:#0d9488;">
                <span style="font-size:14px; font-weight:600; color:#0f172a;">Require Admin Approval for All Appointments (Default: Yes)</span>
              </label>
              <p style="margin:4px 0 0 28px; font-size:12px; color:#64748b;">When enabled, newly placed bookings remain in Pending status until approved by a Clinic Manager or Administrator.</p>
            </div>
          </div>

          <!-- SECTION 2: Payment Gateways & Methods Setup (Cash, Stripe, PayPal) -->
          <div class="nmbp-card-table" style="padding: 24px; margin-bottom: 24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
              <div>
                <h3 style="margin:0; font-size:17px; color:#0f172a;">&#128179; Payment Gateways & Methods Setup</h3>
                <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Configure Cash on Arrival, Stripe Credit/Debit Card, and PayPal Express for appointments</p>
              </div>
              <span class="nmbp-shortcode-badge">Online + Offline Supported</span>
            </div>

            <div style="display:flex; flex-direction:column; gap:20px;">
              <!-- 1. Cash / Pay at Clinic -->
              <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                  <input type="checkbox" id="nmbp-inp-pay-cash" ${p.enable_cash !== false ? 'checked' : ''} style="width:18px; height:18px; accent-color:#0d9488;">
                  <strong style="font-size:15px; color:#0f172a;">&#128181; Enable Cash on Arrival / Pay at Clinic</strong>
                </label>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:14px; margin-top:12px; margin-left:28px;">
                  <label>
                    <span style="font-size:12px; font-weight:600; color:#475569;">Payment Method Label:</span>
                    <input type="text" id="nmbp-inp-pay-cash-title" class="nmbp-search-input" style="width:100%; margin-top:4px;" value="${p.cash_title || 'Pay at Clinic (Cash / Card)'}">
                  </label>
                  <label>
                    <span style="font-size:12px; font-weight:600; color:#475569;">Patient Instructions:</span>
                    <input type="text" id="nmbp-inp-pay-cash-desc" class="nmbp-search-input" style="width:100%; margin-top:4px;" value="${p.cash_desc || 'Pay directly at the hospital reception upon check-in.'}">
                  </label>
                </div>
              </div>

              <!-- 2. Stripe Gateway -->
              <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                  <input type="checkbox" id="nmbp-inp-pay-stripe" ${p.enable_stripe ? 'checked' : ''} style="width:18px; height:18px; accent-color:#0d9488;">
                  <strong style="font-size:15px; color:#0f172a;">&#128179; Enable Stripe (Credit & Debit Card)</strong>
                </label>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:14px; margin-top:12px; margin-left:28px;">
                  <label>
                    <span style="font-size:12px; font-weight:600; color:#475569;">Stripe Environment Mode:</span>
                    <select id="nmbp-inp-stripe-mode" class="nmbp-search-input" style="width:100%; margin-top:4px;">
                      <option value="test" ${p.stripe_mode !== 'live' ? 'selected' : ''}>Test / Sandbox Mode</option>
                      <option value="live" ${p.stripe_mode === 'live' ? 'selected' : ''}>Live / Production Mode</option>
                    </select>
                  </label>
                  <label>
                    <span style="font-size:12px; font-weight:600; color:#475569;">Stripe Publishable Key:</span>
                    <input type="text" id="nmbp-inp-stripe-pub-key" class="nmbp-search-input" style="width:100%; margin-top:4px;" placeholder="pk_test_..." value="${p.stripe_publishable_key || ''}">
                  </label>
                  <label>
                    <span style="font-size:12px; font-weight:600; color:#475569;">Stripe Secret Key:</span>
                    <input type="password" id="nmbp-inp-stripe-secret-key" class="nmbp-search-input" style="width:100%; margin-top:4px;" placeholder="sk_test_..." value="${p.stripe_secret_key || ''}">
                  </label>
                  <label>
                    <span style="font-size:12px; font-weight:600; color:#475569;">Stripe Webhook Secret (Optional):</span>
                    <input type="password" id="nmbp-inp-stripe-webhook" class="nmbp-search-input" style="width:100%; margin-top:4px;" placeholder="whsec_..." value="${p.stripe_webhook_secret || ''}">
                  </label>
                </div>
              </div>

              <!-- 3. PayPal Gateway -->
              <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                  <input type="checkbox" id="nmbp-inp-pay-paypal" ${p.enable_paypal ? 'checked' : ''} style="width:18px; height:18px; accent-color:#0d9488;">
                  <strong style="font-size:15px; color:#0f172a;">&#127380; Enable PayPal Express Checkout</strong>
                </label>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:14px; margin-top:12px; margin-left:28px;">
                  <label>
                    <span style="font-size:12px; font-weight:600; color:#475569;">PayPal Environment:</span>
                    <select id="nmbp-inp-paypal-mode" class="nmbp-search-input" style="width:100%; margin-top:4px;">
                      <option value="sandbox" ${p.paypal_mode !== 'live' ? 'selected' : ''}>Sandbox (Testing)</option>
                      <option value="live" ${p.paypal_mode === 'live' ? 'selected' : ''}>Live / Production</option>
                    </select>
                  </label>
                  <label>
                    <span style="font-size:12px; font-weight:600; color:#475569;">PayPal Client ID:</span>
                    <input type="text" id="nmbp-inp-paypal-client-id" class="nmbp-search-input" style="width:100%; margin-top:4px;" placeholder="Client ID" value="${p.paypal_client_id || ''}">
                  </label>
                  <label>
                    <span style="font-size:12px; font-weight:600; color:#475569;">PayPal Secret Key:</span>
                    <input type="password" id="nmbp-inp-paypal-secret" class="nmbp-search-input" style="width:100%; margin-top:4px;" placeholder="Secret Key" value="${p.paypal_secret || ''}">
                  </label>
                </div>
              </div>

              <!-- 4. Policy & Timing -->
              <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <label>
                  <span style="font-size:13px; font-weight:700; color:#0f172a;">Payment Timing Policy:</span>
                  <select id="nmbp-inp-payment-timing" class="nmbp-search-input" style="width:100%; max-width:400px; margin-top:6px; display:block;">
                    <option value="before_approval" ${p.payment_timing !== 'after_approval' ? 'selected' : ''}>Collect Payment / Method at Booking Time (Standard)</option>
                    <option value="after_approval" ${p.payment_timing === 'after_approval' ? 'selected' : ''}>Collect Payment Only After Admin Approves Appointment</option>
                  </select>
                  <small style="color:#64748b; font-size:12px; display:block; margin-top:4px;">When set to Standard, patients submit their payment preference with their appointment request.</small>
                </label>
              </div>
            </div>
          </div>

          <!-- SECTION 3: Shortcode Directory & Page Placement Guide -->
          <div class="nmbp-card-table" style="padding: 24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
              <div>
                <h3 style="margin:0; font-size:18px; color:#0f172a;">Shortcode Directory & Page Placement Guide (সম্পূর্ণ শর্টকোড তালিকা)</h3>
                <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Copy and paste these shortcodes onto any WordPress page or Elementor template</p>
              </div>
              <span class="nmbp-shortcode-badge">8 Shortcodes Available</span>
            </div>

            <div class="nmbp-shortcode-grid">
              <!-- Shortcode 1: All Doctors Showcase -->
              <div class="nmbp-shortcode-card">
                <div>
                  <div class="nmbp-shortcode-tag-row">
                    <span class="nmbp-shortcode-badge">Directory & Grid</span>
                    <span style="font-size:11px; color:#64748b; font-weight:600;">4 Card Presets</span>
                  </div>
                  <h4 class="nmbp-shortcode-title">1. Doctors Showcase Grid</h4>
                  <p class="nmbp-shortcode-desc">Display responsive doctors directory with live department/clinic filtering, instant search bar, and seamless profile routing.</p>
                  
                  <div class="nmbp-shortcode-box">
                    <code>[nmbp_doctors]</code>
                    <button type="button" class="nmbp-copy-btn" data-copy-shortcode="[nmbp_doctors]">&#128203; Copy</button>
                  </div>
                </div>

                <div class="nmbp-shortcode-attr-list">
                  <strong>Supported Attributes:</strong>
                  <div class="nmbp-shortcode-attr-item"><code>preset</code>: "classic_medical" | "modern_glass" | "hospital_pro" | "compact_card"</div>
                  <div class="nmbp-shortcode-attr-item"><code>single_preset</code>: "clinical_split" | "executive_banner" | "minimal_centered" | "hospital_dossier"</div>
                  <div class="nmbp-shortcode-attr-item"><code>columns</code>: "2" | "3" | "4" (Default: 3)</div>
                  <div class="nmbp-shortcode-attr-item"><code>search</code>: "yes" | "no" (Live filter bar)</div>
                  <div class="nmbp-shortcode-attr-item"><code>limit</code>: number of doctors (e.g. "12" or "-1")</div>
                  <div style="margin-top:8px; padding-top:6px; border-top:1px dashed #cbd5e1; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:11px; color:#475569;">Copy Full Example:</span>
                    <button type="button" class="nmbp-copy-btn" data-copy-shortcode='[nmbp_doctors preset="classic_medical" single_preset="clinical_split" columns="3" search="yes" limit="12"]' style="background:#0d9488; font-size:11px; padding:3px 8px;">Copy Full</button>
                  </div>
                </div>
              </div>

              <!-- Shortcode 2: Single Doctor Profile -->
              <div class="nmbp-shortcode-card">
                <div>
                  <div class="nmbp-shortcode-tag-row">
                    <span class="nmbp-shortcode-badge">Doctor Details</span>
                    <span style="font-size:11px; color:#64748b; font-weight:600;">4 Profile Presets</span>
                  </div>
                  <h4 class="nmbp-shortcode-title">2. Individual Doctor Profile</h4>
                  <p class="nmbp-shortcode-desc">Dedicated individual doctor presentation page displaying biography, verified BMDC/medical license, clinic branches, and weekly shift timetable.</p>
                  
                  <div class="nmbp-shortcode-box">
                    <code>[nmbp_doctor_profile id="1"]</code>
                    <button type="button" class="nmbp-copy-btn" data-copy-shortcode='[nmbp_doctor_profile id="1"]'>&#128203; Copy</button>
                  </div>
                </div>

                <div class="nmbp-shortcode-attr-list">
                  <strong>Supported Attributes:</strong>
                  <div class="nmbp-shortcode-attr-item"><code>id</code>: Doctor ID (e.g. 1, 2)</div>
                  <div class="nmbp-shortcode-attr-item"><code>preset</code>: "clinical_split" | "executive_banner" | "minimal_centered" | "hospital_dossier"</div>
                  <div style="margin-top:8px; padding-top:6px; border-top:1px dashed #cbd5e1; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:11px; color:#475569;">Copy Full Example:</span>
                    <button type="button" class="nmbp-copy-btn" data-copy-shortcode='[nmbp_doctor_profile id="1" preset="clinical_split"]' style="background:#0d9488; font-size:11px; padding:3px 8px;">Copy Full</button>
                  </div>
                </div>
              </div>

              <!-- Shortcode 3: Booking Flow -->
              <div class="nmbp-shortcode-card">
                <div>
                  <div class="nmbp-shortcode-tag-row">
                    <span class="nmbp-shortcode-badge">Core Engine</span>
                    <span style="font-size:11px; color:#64748b; font-weight:600;">5-Step Flow</span>
                  </div>
                  <h4 class="nmbp-shortcode-title">3. Smart Booking Wizard</h4>
                  <p class="nmbp-shortcode-desc">The complete interactive multi-step booking engine: Branch Selection &rarr; Department &rarr; Doctor &rarr; Date & Available Slot &rarr; Patient Details & Payment.</p>
                  
                  <div class="nmbp-shortcode-box">
                    <code>[nmbp_booking]</code>
                    <button type="button" class="nmbp-copy-btn" data-copy-shortcode="[nmbp_booking]">&#128203; Copy</button>
                  </div>
                </div>

                <div class="nmbp-shortcode-attr-list">
                  <strong>Usage & Recommendations:</strong>
                  <div class="nmbp-shortcode-attr-item">Place this on your main <code>/book-appointment</code> page.</div>
                  <div class="nmbp-shortcode-attr-item">Supports pre-selection via URL params: <code>?doctor_id=X&clinic_id=Y</code>.</div>
                </div>
              </div>

              <!-- Shortcode 4: Clinics Showcase -->
              <div class="nmbp-shortcode-card">
                <div>
                  <div class="nmbp-shortcode-tag-row">
                    <span class="nmbp-shortcode-badge">Hospital Branches</span>
                    <span style="font-size:11px; color:#64748b; font-weight:600;">Locations Grid</span>
                  </div>
                  <h4 class="nmbp-shortcode-title">4. Hospital Branches & Clinics</h4>
                  <p class="nmbp-shortcode-desc">Showcase hospital branches with cover photos, address, operating hours, contact phone, and direct appointment trigger buttons.</p>
                  
                  <div class="nmbp-shortcode-box">
                    <code>[nmbp_clinics limit="12"]</code>
                    <button type="button" class="nmbp-copy-btn" data-copy-shortcode='[nmbp_clinics limit="12"]'>&#128203; Copy</button>
                  </div>
                </div>

                <div class="nmbp-shortcode-attr-list">
                  <strong>Supported Attributes:</strong>
                  <div class="nmbp-shortcode-attr-item"><code>limit</code>: Number of branches to show (Default: 12)</div>
                  <div class="nmbp-shortcode-attr-item"><code>city</code>: Filter branches by city (Optional)</div>
                </div>
              </div>

              <!-- Shortcode 5: Medical Departments -->
              <div class="nmbp-shortcode-card">
                <div>
                  <div class="nmbp-shortcode-tag-row">
                    <span class="nmbp-shortcode-badge">Specialties</span>
                    <span style="font-size:11px; color:#64748b; font-weight:600;">Clinical Grid</span>
                  </div>
                  <h4 class="nmbp-shortcode-title">5. Medical Departments</h4>
                  <p class="nmbp-shortcode-desc">Showcase medical departments (Cardiology, Neurology, Dermatology, Pediatrics, etc.) with icons and descriptions.</p>
                  
                  <div class="nmbp-shortcode-box">
                    <code>[nmbp_departments]</code>
                    <button type="button" class="nmbp-copy-btn" data-copy-shortcode="[nmbp_departments]">&#128203; Copy</button>
                  </div>
                </div>

                <div class="nmbp-shortcode-attr-list">
                  <strong>Usage & Placement:</strong>
                  <div class="nmbp-shortcode-attr-item">Ideal for hospital homepage or departmental overview pages.</div>
                  <div class="nmbp-shortcode-attr-item">Automatically syncs with active departments in Admin.</div>
                </div>
              </div>

              <!-- Shortcode 6: Patient Dashboard -->
              <div class="nmbp-shortcode-card">
                <div>
                  <div class="nmbp-shortcode-tag-row">
                    <span class="nmbp-shortcode-badge">Patient Portal</span>
                    <span style="font-size:11px; color:#64748b; font-weight:600;">Self-Service</span>
                  </div>
                  <h4 class="nmbp-shortcode-title">6. Patient Self-Service Portal</h4>
                  <p class="nmbp-shortcode-desc">Secure portal for patients to view appointment history, check approval status, request rescheduling/cancellation, and download receipts.</p>
                  
                  <div class="nmbp-shortcode-box">
                    <code>[nmbp_patient_dashboard]</code>
                    <button type="button" class="nmbp-copy-btn" data-copy-shortcode="[nmbp_patient_dashboard]">&#128203; Copy</button>
                  </div>
                </div>

                <div class="nmbp-shortcode-attr-list">
                  <strong>Usage & Placement:</strong>
                  <div class="nmbp-shortcode-attr-item">Place on a dedicated page such as <code>/patient-portal</code>.</div>
                  <div class="nmbp-shortcode-attr-item">Logged-in patients automatically see their personal bookings.</div>
                </div>
              </div>

              <!-- Shortcode 7: Doctor Dashboard -->
              <div class="nmbp-shortcode-card">
                <div>
                  <div class="nmbp-shortcode-tag-row">
                    <span class="nmbp-shortcode-badge">Doctor Portal</span>
                    <span style="font-size:11px; color:#64748b; font-weight:600;">Clinical Staff</span>
                  </div>
                  <h4 class="nmbp-shortcode-title">7. Doctor Schedule Portal</h4>
                  <p class="nmbp-shortcode-desc">Front-facing doctor consultation agenda. Doctors can see today's appointments, upcoming patient sessions, and mark visits as completed.</p>
                  
                  <div class="nmbp-shortcode-box">
                    <code>[nmbp_doctor_dashboard]</code>
                    <button type="button" class="nmbp-copy-btn" data-copy-shortcode="[nmbp_doctor_dashboard]">&#128203; Copy</button>
                  </div>
                </div>

                <div class="nmbp-shortcode-attr-list">
                  <strong>Usage & Placement:</strong>
                  <div class="nmbp-shortcode-attr-item">Place on a staff page like <code>/doctor-portal</code>.</div>
                  <div class="nmbp-shortcode-attr-item">Access restricted to logged-in doctors or clinic staff.</div>
                </div>
              </div>

              <!-- Shortcode 8: Appointment Search -->
              <div class="nmbp-shortcode-card">
                <div>
                  <div class="nmbp-shortcode-tag-row">
                    <span class="nmbp-shortcode-badge">Quick Status</span>
                    <span style="font-size:11px; color:#64748b; font-weight:600;">No Login Required</span>
                  </div>
                  <h4 class="nmbp-shortcode-title">8. Appointment Status Tracker</h4>
                  <p class="nmbp-shortcode-desc">Allows patients to look up the status of their consultation (Pending, Confirmed, Completed) using their Booking Reference Code or Phone.</p>
                  
                  <div class="nmbp-shortcode-box">
                    <code>[nmbp_appointment_search]</code>
                    <button type="button" class="nmbp-copy-btn" data-copy-shortcode="[nmbp_appointment_search]">&#128203; Copy</button>
                  </div>
                </div>

                <div class="nmbp-shortcode-attr-list">
                  <strong>Usage & Placement:</strong>
                  <div class="nmbp-shortcode-attr-item">Place on your header menu, contact page, or <code>/track-booking</code>.</div>
                  <div class="nmbp-shortcode-attr-item">Instant real-time lookup against database.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    }

    function renderSystemStatus() {
      const s = state.systemStatus || {};
      const logs = state.auditLogs || [];
      return `
        <header class="nmbp-topbar">
          <div>
            <h1 class="nmbp-page-title">System Status & Medical Audit Logs</h1>
            <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Environment checks and secure change tracking</p>
          </div>
        </header>

        <div class="nmbp-content-body">
          <div class="nmbp-card-table" style="padding: 24px; margin-bottom: 24px;">
            <h3 style="margin:0 0 16px 0;">Environment Health</h3>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; font-size:13px;">
              <div><strong>WordPress:</strong> ${s.wordpress_version || '6.5+'}</div>
              <div><strong>PHP Version:</strong> ${s.php_version || '8.1+'}</div>
              <div><strong>Database:</strong> MySQL ${s.mysql_version || '8.0+'}</div>
              <div><strong>Plugin:</strong> v${s.plugin_version || '1.0.0'}</div>
              <div><strong>Author:</strong> Jasmin</div>
              <div><strong>WooCommerce:</strong> ${s.woocommerce_active ? '✅ Active' : '⚪ Standalone'}</div>
              <div><strong>Elementor:</strong> ${s.elementor_active ? '✅ Active' : '⚪ Standalone'}</div>
            </div>
          </div>

          <div class="nmbp-card-table">
            <div class="nmbp-table-header"><h3>Security Audit Trail (${logs.length})</h3></div>
            <table class="nmbp-ui-table">
              <thead>
                <tr>
                  <th>Timestamp</th>
                  <th>Entity</th>
                  <th>Action</th>
                  <th>User / Role</th>
                  <th>IP Address</th>
                </tr>
              </thead>
              <tbody>
                ${logs.length === 0 ? `
                  <tr><td colSpan="5" style="text-align:center; padding:30px; color:#94a3b8;">No audit logs yet.</td></tr>
                ` : logs.map(l => `
                  <tr>
                    <td>${l.created_at}</td>
                    <td><strong>${l.entity_type} #${l.entity_id}</strong></td>
                    <td><span class="nmbp-badge-status nmbp-badge-confirmed">${l.action}</span></td>
                    <td>${l.user_role || 'System'}</td>
                    <td><code>${l.ip_address || '127.0.0.1'}</code></td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    }

    // Modal Rendering
    function renderModal() {
      const m = state.modal;
      if (!m) return '';

      let bodyHtml = '';
      if (m.type === 'create-clinic') {
        bodyHtml = `
          <form id="nmbp-modal-clinic-form" style="display:flex; flex-direction:column; gap:12px;">
            <label><strong>Clinic Name *</strong><input type="text" name="name" required class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>Email *</strong><input type="email" name="email" required class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>Phone *</strong><input type="text" name="phone" required class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>Address *</strong><input type="text" name="address" required class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>City</strong><input type="text" name="city" class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>Default Consultation Fee ($)</strong><input type="number" name="default_fee" value="50" class="nmbp-search-input" style="width:100%;"></label>
          </form>
        `;
      } else if (m.type === 'create-doctor') {
        bodyHtml = `
          <form id="nmbp-modal-doctor-form" style="display:flex; flex-direction:column; gap:12px;">
            <label><strong>First Name *</strong><input type="text" name="first_name" required class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>Last Name *</strong><input type="text" name="last_name" required class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>Email *</strong><input type="email" name="email" required class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>Phone *</strong><input type="text" name="phone" required class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>Specialization *</strong><input type="text" name="specialization" placeholder="e.g. Cardiologist" required class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>Consultation Fee ($)</strong><input type="number" name="consultation_fee" value="100" class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>Appointment Duration (Minutes)</strong><input type="number" name="appointment_duration" value="20" class="nmbp-search-input" style="width:100%;"></label>
          </form>
        `;
      } else if (m.type === 'create-dept') {
        bodyHtml = `
          <form id="nmbp-modal-dept-form" style="display:flex; flex-direction:column; gap:12px;">
            <label><strong>Department Name *</strong><input type="text" name="name" required class="nmbp-search-input" style="width:100%;"></label>
            <label><strong>Description</strong><textarea name="description" class="nmbp-search-input" style="width:100%; height:70px;"></textarea></label>
          </form>
        `;
      } else if (m.type === 'reject-app') {
        bodyHtml = `
          <div style="display:flex; flex-direction:column; gap:12px;">
            <p>Enter rejection reason for appointment <strong>#${m.data.id}</strong>:</p>
            <textarea id="nmbp-inp-reject-reason" class="nmbp-search-input" style="width:100%; height:80px;" placeholder="Doctor unavailable on this date..."></textarea>
          </div>
        `;
      }

      return `
        <div class="nmbp-modal-backdrop" data-action="close-modal">
          <div class="nmbp-modal-window" onclick="event.stopPropagation()">
            <div class="nmbp-modal-header">
              <h3>${m.title}</h3>
              <button class="nmbp-modal-close" data-action="close-modal">&times;</button>
            </div>
            <div class="nmbp-modal-body">
              ${bodyHtml}
            </div>
            <div class="nmbp-modal-footer">
              <button class="nmbp-adm-btn nmbp-adm-btn-primary" data-action="submit-modal">Confirm</button>
              <button class="nmbp-adm-btn nmbp-adm-btn-outline" data-action="close-modal">Cancel</button>
            </div>
          </div>
        </div>
      `;
    }

    // Event Delegation
    function bindDOMEvents() {
      // Sidebar Navigation
      rootEl.querySelectorAll('.nmbp-nav-btn').forEach(btn => {
        btn.onclick = () => setPage(btn.getAttribute('data-page'));
      });

      // Actions
      rootEl.querySelectorAll('[data-action]').forEach(el => {
        const action = el.getAttribute('data-action');
        el.onclick = async (e) => {
          e.preventDefault();

          if (action === 'goto-page') {
            setPage(el.getAttribute('data-page'));
          } else if (action === 'refresh-dashboard') {
            loadDashboard();
          } else if (action === 'refresh-appointments') {
            loadAppointments();
          } else if (action === 'quick-approve') {
            const id = el.getAttribute('data-id');
            if (confirm('Approve and confirm appointment #' + id + '? Availability will be verified.')) {
              el.disabled = true;
              try {
                await apiRequest(`/appointments/${id}/approve`, { method: 'POST' });
                alert('Appointment confirmed!');
                loadActivePageData();
              } catch (err) {
                alert(err.message);
              }
            }
          } else if (action === 'mark-complete') {
            const id = el.getAttribute('data-id');
            try {
              await apiRequest(`/appointments/${id}/status`, { method: 'POST', body: JSON.stringify({ status: 'completed' }) });
              loadActivePageData();
            } catch (err) {
              alert(err.message);
            }
          } else if (action === 'verify-payment') {
            const id = el.getAttribute('data-id');
            if (confirm('Verify payment #' + id + ' as PAID?')) {
              try {
                await apiRequest(`/payments/${id}/verify`, { method: 'POST' });
                alert('Payment marked as PAID!');
                loadPayments();
              } catch (err) {
                alert(err.message);
              }
            }
          } else if (action === 'open-clinic-modal') {
            openModal('create-clinic', 'Add New Clinic Branch');
          } else if (action === 'delete-clinic') {
            const id = el.getAttribute('data-id');
            if (confirm('Delete clinic #' + id + '?')) {
              await apiRequest(`/clinics/${id}`, { method: 'DELETE' });
              loadClinics();
            }
          } else if (action === 'open-doctor-modal') {
            openModal('create-doctor', 'Add Doctor Profile');
          } else if (action === 'delete-doctor') {
            const id = el.getAttribute('data-id');
            if (confirm('Delete doctor #' + id + '?')) {
              await apiRequest(`/doctors/${id}`, { method: 'DELETE' });
              loadDoctors();
            }
          } else if (action === 'open-dept-modal') {
            openModal('create-dept', 'Add Medical Department');
          } else if (action === 'delete-dept') {
            const id = el.getAttribute('data-id');
            if (confirm('Delete department #' + id + '?')) {
              await apiRequest(`/departments/${id}`, { method: 'DELETE' });
              loadDepartments();
            }
          } else if (action === 'open-reject') {
            const id = el.getAttribute('data-id');
            openModal('reject-app', 'Reject Booking', { id });
          } else if (action === 'close-modal') {
            closeModal();
          } else if (action === 'submit-modal') {
            handleModalSubmit();
          } else if (action === 'save-design') {
            const primary = document.getElementById('nmbp-inp-primary-color')?.value || '#0d9488';
            const cardRadio = document.querySelector('input[name="nmbp_card_preset"]:checked');
            const card = cardRadio ? cardRadio.value : (document.getElementById('nmbp-inp-card-preset')?.value || 'classic_medical');
            const singleRadio = document.querySelector('input[name="nmbp_single_doctor_preset"]:checked');
            const single = singleRadio ? singleRadio.value : 'clinical_split';
            const form = document.getElementById('nmbp-inp-form-preset')?.value || 'multi_step';

            await apiRequest('/settings', {
              method: 'POST',
              body: JSON.stringify({
                design: {
                  primary_color: primary,
                  card_preset: card,
                  single_doctor_preset: single,
                  form_preset: form
                }
              })
            });
            if (state.settings) {
              state.settings.design = {
                ...(state.settings.design || {}),
                primary_color: primary,
                card_preset: card,
                single_doctor_preset: single,
                form_preset: form
              };
            }
            alert('Design styles & presets saved successfully!');
          } else if (action === 'goto-settings') {
            setPage('settings');
          } else if (action === 'save-general-settings') {
            const comp = document.getElementById('nmbp-inp-company-name')?.value;
            const curr = document.getElementById('nmbp-inp-currency')?.value;
            const req = document.getElementById('nmbp-inp-require-approval')?.checked;
            const cardPreset = document.getElementById('nmbp-inp-settings-card-preset')?.value;
            const singlePreset = document.getElementById('nmbp-inp-settings-single-preset')?.value;

            const payPayload = {
              enable_cash: document.getElementById('nmbp-inp-pay-cash')?.checked !== false,
              cash_title: document.getElementById('nmbp-inp-pay-cash-title')?.value || 'Pay at Clinic (Cash / Card)',
              cash_desc: document.getElementById('nmbp-inp-pay-cash-desc')?.value || 'Pay directly at the hospital reception upon check-in.',
              enable_stripe: document.getElementById('nmbp-inp-pay-stripe')?.checked || false,
              stripe_mode: document.getElementById('nmbp-inp-stripe-mode')?.value || 'test',
              stripe_publishable_key: document.getElementById('nmbp-inp-stripe-pub-key')?.value || '',
              stripe_secret_key: document.getElementById('nmbp-inp-stripe-secret-key')?.value || '',
              stripe_webhook_secret: document.getElementById('nmbp-inp-stripe-webhook')?.value || '',
              enable_paypal: document.getElementById('nmbp-inp-pay-paypal')?.checked || false,
              paypal_mode: document.getElementById('nmbp-inp-paypal-mode')?.value || 'sandbox',
              paypal_client_id: document.getElementById('nmbp-inp-paypal-client-id')?.value || '',
              paypal_secret: document.getElementById('nmbp-inp-paypal-secret')?.value || '',
              payment_timing: document.getElementById('nmbp-inp-payment-timing')?.value || 'before_approval',
            };

            const payload = {
              general: { company_name: comp, currency_symbol: curr, require_approval: req },
              payment: payPayload
            };
            if (cardPreset || singlePreset) {
              payload.design = {
                ...(state.settings?.design || {}),
                card_preset: cardPreset || state.settings?.design?.card_preset || 'classic_medical',
                single_doctor_preset: singlePreset || state.settings?.design?.single_doctor_preset || 'clinical_split'
              };
            }

            await apiRequest('/settings', {
              method: 'POST',
              body: JSON.stringify(payload)
            });
            if (state.settings) {
              state.settings.general = {
                ...state.settings.general,
                company_name: comp,
                currency_symbol: curr,
                require_approval: req
              };
              state.settings.payment = payPayload;
              if (payload.design) {
                state.settings.design = payload.design;
              }
            }
            alert('Settings & Payment Gateways saved successfully!');
          }
        };
      });

      // Preset Card Selection Interactions
      rootEl.querySelectorAll('.nmbp-preset-card-option').forEach(card => {
        card.onclick = () => {
          const radio = card.querySelector('input[type="radio"]');
          if (radio) {
            radio.checked = true;
            const groupName = radio.getAttribute('name');
            rootEl.querySelectorAll(`input[name="${groupName}"]`).forEach(r => {
              r.closest('.nmbp-preset-card-option')?.classList.remove('selected');
            });
            card.classList.add('selected');
          }
        };
      });

      // 1-Click Shortcode Copy Buttons
      rootEl.querySelectorAll('[data-copy-shortcode]').forEach(btn => {
        btn.onclick = async (e) => {
          e.preventDefault();
          e.stopPropagation();
          const text = btn.getAttribute('data-copy-shortcode');
          if (!text) return;
          try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
              await navigator.clipboard.writeText(text);
            } else {
              const ta = document.createElement('textarea');
              ta.value = text;
              document.body.appendChild(ta);
              ta.select();
              document.execCommand('copy');
              document.body.removeChild(ta);
            }
            const oldHtml = btn.innerHTML;
            btn.innerHTML = '&#10004; Copied!';
            btn.style.backgroundColor = '#16a34a';
            btn.style.color = '#ffffff';
            setTimeout(() => {
              btn.innerHTML = oldHtml;
              btn.style.backgroundColor = '';
              btn.style.color = '';
            }, 2000);
          } catch (err) {
            console.error('Failed to copy shortcode:', err);
          }
        };
      });
    }

    async function handleModalSubmit() {
      const m = state.modal;
      if (!m) return;

      try {
        if (m.type === 'create-clinic') {
          const form = document.getElementById('nmbp-modal-clinic-form');
          const data = Object.fromEntries(new FormData(form).entries());
          await apiRequest('/clinics', { method: 'POST', body: JSON.stringify(data) });
          closeModal();
          loadClinics();
        } else if (m.type === 'create-doctor') {
          const form = document.getElementById('nmbp-modal-doctor-form');
          const data = Object.fromEntries(new FormData(form).entries());
          await apiRequest('/doctors', { method: 'POST', body: JSON.stringify(data) });
          closeModal();
          loadDoctors();
        } else if (m.type === 'create-dept') {
          const form = document.getElementById('nmbp-modal-dept-form');
          const data = Object.fromEntries(new FormData(form).entries());
          await apiRequest('/departments', { method: 'POST', body: JSON.stringify(data) });
          closeModal();
          loadDepartments();
        } else if (m.type === 'reject-app') {
          const reason = document.getElementById('nmbp-inp-reject-reason')?.value;
          await apiRequest(`/appointments/${m.data.id}/reject`, {
            method: 'POST',
            body: JSON.stringify({ reason })
          });
          closeModal();
          loadAppointments();
        }
      } catch (err) {
        alert(err.message || 'Operation failed');
      }
    }

    // Initial load
    render();
    loadActivePageData();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
  } else {
    initApp();
  }
})();

