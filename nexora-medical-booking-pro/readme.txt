=== Nexora Medical Booking Pro ===
Contributors: jasmin
Tags: medical booking, doctor appointment, hospital management, clinic booking, multi clinic, appointment scheduling
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional, multi-clinic, multi-doctor WordPress medical appointment booking and healthcare facility management system.

== Description ==

Nexora Medical Booking Pro is an enterprise-grade appointment booking and management platform designed specifically for single clinics, multi-branch healthcare centers, diagnostic facilities, and large multi-specialty hospitals.

= Key Features =
* **Multi-Clinic & Branch Management:** Support for unlimited clinic locations, individual opening hours, holiday schedules, branch consultation fees, and receptionist assignments.
* **Multi-Doctor Portals & Multi-Branch Assignment:** Doctors can practice across multiple clinics with independent schedules, custom consultation fees, and working hours per location.
* **Double-Booking Prevention Engine:** Atomic slot locking, database transaction isolation, and dual-layer availability validation during booking submission and admin approval.
* **Admin Approval Workflow:** Complete workflow for reviewing, approving, rescheduling, or rejecting appointment requests.
* **Hybrid Payment Processing:** Support for Offline Cash / Pay at Clinic and Online WooCommerce gateways (Stripe, PayPal, etc.).
* **Dedicated Patient, Doctor & Receptionist Portals:** Role-based dashboards for patients to manage bookings, doctors to view sessions and track consultations, and receptionists to coordinate walk-ins.
* **Design Studio & Presets:** Multiple doctor card presets (Classic Medical, Modern Glass, Hospital Pro, Minimal Clean) and booking form presets (Multi-step, Classic Form, Calendar View, Compact Form).
* **Native Elementor Widgets & Shortcodes:** Complete set of Elementor drag-and-drop widgets and flexible shortcodes for easy page builder integration.

= Available Shortcodes & Usage Guide =
* `[nmbp_doctors]` - Responsive doctor grid with 4 design presets, live search filter, and automatic individual profile page routing.
  Attributes:
  - `preset`: `classic_medical` (Default), `modern_minimal`, `hospital_pro`, `compact_card`
  - `columns`: `2`, `3` (Default), `4`
  - `limit`: Number of doctors to display (e.g. `limit="12"`, or `-1` for all)
  - `department_id`: Filter by department ID (e.g. `department_id="1"`)
  - `clinic_id`: Filter by clinic ID (e.g. `clinic_id="2"`)
  - `search`: `yes` (Default) or `no` (shows/hides the live search and department filter bar)
  - `profile_url`: Optional custom doctor profile page URL (e.g. `profile_url="/doctor-details/"`)

* `[nmbp_doctor_profile]` - Comprehensive individual doctor profile page displaying bio, qualifications, BMDC reg number, practicing hospital branches, weekly consultation schedule timetable, and direct booking CTA.
  Attributes:
  - `id`: Doctor ID (e.g. `[nmbp_doctor_profile id="1"]`). If omitted, it automatically reads `?doctor_id=X` from the URL.

* `[nmbp_booking]` - Smart multi-step appointment booking wizard with real-time slot checking.
* `[nmbp_clinics]` - Interactive hospital branches and clinics grid.
* `[nmbp_departments]` - Medical departments showcase grid.
* `[nmbp_patient_dashboard]` - Logged-in patient portal with appointment history and rescheduling.
* `[nmbp_doctor_dashboard]` - Doctor portal for managing sessions, consultations, and schedules.
* `[nmbp_appointment_search]` - Public appointment reference code tracking widget.

== Installation ==

1. Upload the `nexora-medical-booking-pro` folder to the `/wp-content/plugins/` directory, or upload the ZIP file through Plugins > Add New > Upload Plugin.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the 'Nexora Medical' admin menu to configure your clinics, departments, doctors, and schedules.
4. Place the shortcode `[nmbp_booking]` or use the Elementor Appointment Booking widget on your booking page.

== Frequently Asked Questions ==

= Does this prevent doctor double bookings? =
Yes! Nexora utilizes atomic database locking and verifies schedule availability both at temporary reservation time and during final admin confirmation.

= Can one doctor work at multiple hospital branches? =
Yes. Nexora supports many-to-many branch assignments, allowing doctors to have separate consulting fees, days of week, and time slots at each branch.

== Changelog ==

= 1.0.0 =
* Initial release of Nexora Medical Booking Pro.

