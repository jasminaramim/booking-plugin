export interface Clinic {
  id: number;
  name: string;
  slug: string;
  logo_url?: string;
  cover_image_url?: string;
  email: string;
  phone: string;
  address: string;
  city?: string;
  state?: string;
  postal_code?: string;
  country?: string;
  opening_time: string;
  closing_time: string;
  weekly_holidays: string[];
  default_fee: number;
  receptionist_user_ids: number[];
  status: 'active' | 'inactive';
  created_at?: string;
}

export interface Department {
  id: number;
  name: string;
  slug: string;
  description?: string;
  icon?: string;
  image_url?: string;
  status: 'active' | 'inactive';
}

export interface DoctorClinicRel {
  relation_id?: number;
  clinic_id: number;
  clinic_name?: string;
  city?: string;
  clinic_fee?: number | null;
  room_number?: string;
  status?: string;
}

export interface Doctor {
  id: number;
  wp_user_id?: number | null;
  department_id: number;
  department_name?: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  photo_url?: string;
  specialization: string;
  qualifications?: string;
  experience_years: number;
  bmdc_reg_number?: string;
  biography?: string;
  consultation_fee: number;
  appointment_duration: number;
  break_time_start?: string | null;
  break_time_end?: string | null;
  max_patients_per_session: number;
  status: 'active' | 'inactive';
  clinics?: DoctorClinicRel[];
  leaves?: any[];
}

export interface Schedule {
  id?: number;
  doctor_id: number;
  clinic_id: number;
  clinic_name?: string;
  day_of_week: 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday';
  start_time: string;
  end_time: string;
  slot_duration: number;
  buffer_time: number;
  break_start?: string | null;
  break_end?: string | null;
  max_capacity: number;
  status: 'active' | 'inactive';
}

export interface Appointment {
  id: number;
  appointment_number: string;
  clinic_id: number;
  clinic_name?: string;
  clinic_city?: string;
  doctor_id: number;
  doctor_first_name?: string;
  doctor_last_name?: string;
  doctor_photo?: string;
  patient_id: number;
  patient_first_name?: string;
  patient_last_name?: string;
  patient_phone?: string;
  patient_email?: string;
  department_id: number;
  department_name?: string;
  appointment_date: string;
  start_time: string;
  end_time: string;
  appointment_type: 'in_person' | 'video_consultation' | 'follow_up';
  consultation_fee: number;
  payment_method: 'offline' | 'online';
  payment_status: 'unpaid' | 'pending' | 'paid' | 'failed' | 'partially_refunded' | 'refunded';
  appointment_status: 'pending' | 'confirmed' | 'rejected' | 'cancelled' | 'completed' | 'no_show' | 'refunded';
  patient_notes?: string;
  admin_notes?: string;
  cancellation_reason?: string;
  created_at: string;
}

export interface Patient {
  id: number;
  wp_user_id?: number | null;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  gender?: 'male' | 'female' | 'other';
  date_of_birth?: string;
  blood_group?: string;
  address?: string;
  emergency_contact?: string;
  medical_history_notes?: string;
  status: 'active' | 'inactive';
  created_at: string;
}

export interface Payment {
  id: number;
  payment_number: string;
  appointment_id: number;
  appointment_number?: string;
  patient_id: number;
  patient_first_name?: string;
  patient_last_name?: string;
  patient_email?: string;
  amount: number;
  currency: string;
  gateway: string;
  transaction_id?: string;
  receipt_url?: string;
  payment_status: 'unpaid' | 'pending' | 'paid' | 'failed' | 'refunded';
  verified_at?: string;
  created_at: string;
}

export interface DashboardStats {
  counts: {
    clinics: number;
    doctors: number;
    patients: number;
    today_bookings: number;
    pending_approvals: number;
    confirmed: number;
    completed: number;
    cancelled: number;
  };
  revenue: {
    today: number;
    monthly: number;
    total: number;
  };
  branch_stats: Array<{
    id: number;
    name: string;
    total_appointments: number;
    total_revenue: number;
  }>;
  recent_bookings: Appointment[];
}

