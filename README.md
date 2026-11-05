<p align="center">
  <img src="./Nexora%20Pro%20Medical%20Booking%20Logo.png" alt="Nexora Medical Booking Pro" width="680" style="max-width: 100%; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);" />
</p>

<h1 align="center">Nexora Medical Booking Pro</h1>

<p align="center">
  <strong>Enterprise Multi-Clinic, Multi-Doctor WordPress Appointment Booking & Hospital Management System</strong>
</p>

<p align="center">
  <a href="#features"><img src="https://img.shields.io/badge/WordPress-6.5%2B-21759b.svg?logo=wordpress&logoColor=white" alt="WordPress 6.5+"></a>
  <a href="#requirements"><img src="https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg?logo=php&logoColor=white" alt="PHP 8.1+"></a>
  <a href="#license"><img src="https://img.shields.io/badge/License-GPLv2%2B-blue.svg" alt="License GPLv2"></a>
  <a href="#author"><img src="https://img.shields.io/badge/Author-Jasmin-0ea5e9.svg" alt="Author Jasmin"></a>
  <img src="https://img.shields.io/badge/Version-1.0.0-emerald.svg" alt="Version 1.0.0">
</p>

---

## 📌 Overview

**Nexora Medical Booking Pro** is an all-in-one, enterprise-grade medical appointment booking and clinic management plugin built for WordPress. Whether operating a single specialized clinic, a multi-branch diagnostic facility, or a large multi-department hospital network, Nexora provides complete operational control from slot scheduling to consultation completion.

### 🌟 Core Highlights
* **Multi-Clinic & Branch Architecture:** Manage unlimited branches with individual working hours, weekly holidays, emergency contacts, and dedicated staff.
* **Multi-Doctor Practice Management:** Doctors can practice across multiple clinics simultaneously with independent schedules, consultation fees, and appointment durations per branch.
* **Atomic Double-Booking Prevention:** High-concurrency slot locking engine using database transaction isolation to eliminate conflicting bookings.
* **Admin Approval Workflow:** Two-stage booking verification system giving administrators and clinic managers complete control before confirmation.
* **Instant "Book Consultation Now" Modal:** Interactive universal booking modal callable from any doctor card or single profile without page reloads.
* **Multi-Gateway Payment Setup:** Built-in support for **Cash on Arrival (Pay at Clinic)**, **Stripe (Credit / Debit Card)**, and **PayPal Express** with sandbox & live modes.
* **Role-Based Portals:** Independent dashboards tailored for Super Admins, Clinic Managers, Doctors, Receptionists, and Patients.

---

## 🏥 Architecture & User Roles

| Role | Access & Permissions |
| :--- | :--- |
| **Super Admin** | Full global control over all clinics, doctors, appointments, settings, and financial reports. |
| **Clinic Manager** | Branch-specific management (appointments, schedules, receptionists, and doctor rosters). |
| **Doctor** | Dedicated portal to view appointments, manage personal availability, mark attendance, and consultation notes. |
| **Receptionist** | Front-desk patient check-in, quick appointment booking, and cash payment collection. |
| **Patient** | Self-service portal to view booking history, status updates, invoices, and reschedule requests. |

---

## ⚡ Key Features

### 1. Smart Scheduling Engine
* Clinic-level operating hours with weekly holiday exclusions.
* Doctor-level custom availability schedules per clinic branch.
* Smart fallback to clinic operating hours if a doctor has not set an override.
* Configurable slot intervals (e.g. 15m, 20m, 30m, 45m, 60m) with buffer times.
* Automatic break time exclusion (e.g., lunch breaks).

### 2. Multi-Channel Payment Management
* **Cash on Arrival:** Enable manual pay-at-clinic with customizable customer instructions.
* **Stripe Integration:** Secure credit/debit card processing with Test and Live mode switches, publishable keys, and webhook validation.
* **PayPal Express:** Direct sandbox & live PayPal client integration.
* **Flexible Payment Policy:** Option to mandate payment before booking approval or request payment post-confirmation.

### 3. Frontend Design Studio & Presets
* 4 Ready-to-use Doctor Card Presets:
  * `classic_medical` - Clean, professional hospital layout.
  * `modern_minimal` - Sleek glassmorphism card style.
  * `hospital_pro` - Informative badge-focused presentation.
  * `compact_card` - High-density directory layout.
* Individual Doctor Profile view with qualifications, BMDC registration number, weekly timetable, and direct booking CTA.

---

## 🔌 Shortcodes Reference

| Shortcode | Description | Key Attributes |
| :--- | :--- | :--- |
| `[nmbp_booking]` | Multi-step interactive booking wizard | `preset="multi_step"`, `clinic_id="1"` |
| `[nmbp_doctors]` | Responsive doctor showcase grid | `preset="classic_medical"`, `columns="3"`, `limit="12"`, `department_id="1"`, `search="yes"` |
| `[nmbp_doctor_profile]` | Comprehensive single doctor profile & timetable | `id="1"` (or automatic `?doctor_id=X` query param) |
| `[nmbp_clinics]` | Hospital branches and clinic directory | `columns="3"`, `limit="6"` |
| `[nmbp_departments]` | Medical specialties grid | `columns="4"`, `limit="8"` |
| `[nmbp_patient_dashboard]` | Logged-in patient portal | — |
| `[nmbp_doctor_dashboard]` | Doctor schedule & consultation manager | — |
| `[nmbp_appointment_search]` | Public booking reference search widget | — |

---

## 💻 Tech Stack & Requirements

* **WordPress:** 6.5 or higher
* **PHP:** 8.1 or higher (Tested with PHP 8.2 & 8.3)
* **Database:** MySQL 5.7+ / MariaDB 10.3+ (InnoDB engine required for atomic transactions)
* **Frontend:** Modern Vanilla JS + Responsive Flexbox/Grid CSS + REST API
* **Security:** Nonce verification, strict capability authorization (`current_user_can`), and SQL prepared statements.

---

## 📦 Installation

1. Clone or download this repository:
   ```bash
   git clone https://github.com/jasminaramim/booking-plugin.git
   ```
2. Copy the `nexora-medical-booking-pro` folder into your WordPress installation:
   ```
   wp-content/plugins/nexora-medical-booking-pro/
   ```
   *(Or directly upload `nexora-medical-booking-pro.zip` via **Plugins > Add New > Upload Plugin** in WP Admin).*
3. Activate the plugin via the **Plugins** menu.
4. Navigate to **Nexora Medical** in your WordPress admin menu to configure your Clinics, Departments, Doctors, and Payment Gateways.
5. Add the shortcode `[nmbp_booking]` or `[nmbp_doctors]` to any page or post.

---

## 👤 Author & Support

* **Plugin Author:** Jasmin
* **Repository:** [https://github.com/jasminaramim/booking-plugin](https://github.com/jasminaramim/booking-plugin)
* **License:** [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html)

---
<p align="center">
  <sub>Built with care for modern healthcare institutions.</sub>
</p>
