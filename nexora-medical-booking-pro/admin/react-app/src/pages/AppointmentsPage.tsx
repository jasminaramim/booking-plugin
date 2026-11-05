import React, { useEffect, useState } from 'react';
import { api } from '../api/client';
import { Appointment, Clinic, Doctor } from '../types';
import { Header } from '../components/Header';
import { Modal } from '../components/Modal';

export const AppointmentsPage: React.FC = () => {
  const [appointments, setAppointments] = useState<Appointment[]>([]);
  const [clinics, setClinics] = useState<Clinic[]>([]);
  const [doctors, setDoctors] = useState<Doctor[]>([]);
  const [loading, setLoading] = useState(true);

  // Filters
  const [statusFilter, setStatusFilter] = useState('');
  const [clinicFilter, setClinicFilter] = useState('');
  const [doctorFilter, setDoctorFilter] = useState('');
  const [search, setSearch] = useState('');

  // Modals
  const [selectedApp, setSelectedApp] = useState<Appointment | null>(null);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [showRescheduleModal, setShowRescheduleModal] = useState(false);
  const [rejectReason, setRejectReason] = useState('');
  const [rescheduleData, setRescheduleData] = useState({ date: '', startTime: '', endTime: '' });
  const [actionLoading, setActionLoading] = useState(false);

  const loadData = async () => {
    try {
      setLoading(true);
      const [appRes, clinicRes, docRes] = await Promise.all([
        api.get<{ items: Appointment[] }>('/appointments', {
          status: statusFilter,
          clinic_id: clinicFilter,
          doctor_id: doctorFilter,
          search: search,
          per_page: 50,
        }),
        api.get<{ items: Clinic[] }>('/clinics'),
        api.get<{ items: Doctor[] }>('/doctors'),
      ]);

      setAppointments(appRes.items || []);
      setClinics(clinicRes.items || []);
      setDoctors(docRes.items || []);
    } catch (err) {
      console.error('Failed to load appointments', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [statusFilter, clinicFilter, doctorFilter]);

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    loadData();
  };

  const handleApprove = async (appId: number) => {
    if (!confirm('Approve and confirm this appointment? The system will atomically re-verify doctor availability.')) {
      return;
    }
    try {
      setActionLoading(true);
      await api.post(`/appointments/${appId}/approve`);
      alert('Appointment confirmed successfully!');
      await loadData();
      if (showDetailModal) setShowDetailModal(false);
    } catch (err: any) {
      alert(err.message || 'Approval failed');
    } finally {
      setActionLoading(false);
    }
  };

  const handleReject = async () => {
    if (!selectedApp) return;
    try {
      setActionLoading(true);
      await api.post(`/appointments/${selectedApp.id}/reject`, { reason: rejectReason });
      alert('Appointment has been rejected.');
      setShowRejectModal(false);
      setRejectReason('');
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Rejection failed');
    } finally {
      setActionLoading(false);
    }
  };

  const handleReschedule = async () => {
    if (!selectedApp) return;
    if (!rescheduleData.date || !rescheduleData.startTime || !rescheduleData.endTime) {
      alert('Please fill all date and time fields.');
      return;
    }
    try {
      setActionLoading(true);
      await api.post(`/appointments/${selectedApp.id}/reschedule`, {
        appointment_date: rescheduleData.date,
        start_time: rescheduleData.startTime,
        end_time: rescheduleData.endTime,
      });
      alert('Appointment rescheduled successfully.');
      setShowRescheduleModal(false);
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Rescheduling failed');
    } finally {
      setActionLoading(false);
    }
  };

  const handleStatusChange = async (appId: number, newStatus: string) => {
    try {
      await api.post(`/appointments/${appId}/status`, { status: newStatus });
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Status change failed');
    }
  };

  return (
    <div>
      <Header
        title="Appointment Management"
        subtitle="Review, approve, reschedule and coordinate patient consultations"
      >
        <button className="nmbp-adm-btn nmbp-adm-btn-outline" onClick={loadData}>
          Refresh List
        </button>
      </Header>

      <div className="nmbp-content-body">
        {/* Filter Bar */}
        <div className="nmbp-card-table" style={{ padding: '16px 20px', marginBottom: '20px' }}>
          <form onSubmit={handleSearchSubmit} style={{ display: 'flex', gap: '14px', flexWrap: 'wrap', alignItems: 'center' }}>
            <input
              type="text"
              className="nmbp-search-input"
              placeholder="Search ref #, patient, phone..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              style={{ width: '240px' }}
            />

            <select
              className="nmbp-search-input"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              style={{ width: '160px' }}
            >
              <option value="">All Statuses</option>
              <option value="pending">Pending Approval</option>
              <option value="confirmed">Confirmed</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
              <option value="rejected">Rejected</option>
              <option value="no_show">No Show</option>
            </select>

            <select
              className="nmbp-search-input"
              value={clinicFilter}
              onChange={(e) => setClinicFilter(e.target.value)}
              style={{ width: '180px' }}
            >
              <option value="">All Clinics</option>
              {clinics.map((c) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>

            <select
              className="nmbp-search-input"
              value={doctorFilter}
              onChange={(e) => setDoctorFilter(e.target.value)}
              style={{ width: '180px' }}
            >
              <option value="">All Doctors</option>
              {doctors.map((d) => (
                <option key={d.id} value={d.id}>Dr. {d.first_name} {d.last_name}</option>
              ))}
            </select>

            <button type="submit" className="nmbp-adm-btn nmbp-adm-btn-primary">
              Filter
            </button>
          </form>
        </div>

        {/* Appointments Table */}
        <div className="nmbp-card-table">
          <div className="nmbp-table-header">
            <h3>Bookings ({appointments.length})</h3>
          </div>
          <table className="nmbp-ui-table">
            <thead>
              <tr>
                <th>Ref #</th>
                <th>Patient Details</th>
                <th>Doctor & Branch</th>
                <th>Date & Slot</th>
                <th>Fee</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Workflow Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={8} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    Loading appointments...
                  </td>
                </tr>
              ) : appointments.length === 0 ? (
                <tr>
                  <td colSpan={8} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    No appointments match your filters.
                  </td>
                </tr>
              ) : (
                appointments.map((a) => (
                  <tr key={a.id}>
                    <td>
                      <button
                        style={{ background: 'none', border: 'none', color: '#0d9488', fontWeight: 700, cursor: 'pointer', padding: 0 }}
                        onClick={() => { setSelectedApp(a); setShowDetailModal(true); }}
                      >
                        {a.appointment_number}
                      </button>
                    </td>
                    <td>
                      <strong>{a.patient_first_name} {a.patient_last_name}</strong>
                      <div style={{ fontSize: '12px', color: '#64748b' }}>{a.patient_phone}</div>
                    </td>
                    <td>
                      <div>Dr. {a.doctor_first_name} {a.doctor_last_name}</div>
                      <div style={{ fontSize: '12px', color: '#64748b' }}>{a.clinic_name}</div>
                    </td>
                    <td>
                      <div>{a.appointment_date}</div>
                      <div style={{ fontSize: '12px', color: '#0d9488', fontWeight: 600 }}>{a.start_time} - {a.end_time}</div>
                    </td>
                    <td>${parseFloat(String(a.consultation_fee)).toFixed(2)}</td>
                    <td>
                      <span className={`nmbp-badge-status nmbp-badge-${a.payment_status}`}>
                        {a.payment_status}
                      </span>
                    </td>
                    <td>
                      <span className={`nmbp-badge-status nmbp-badge-${a.appointment_status}`}>
                        {a.appointment_status}
                      </span>
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '6px' }}>
                        {a.appointment_status === 'pending' && (
                          <>
                            <button
                              className="nmbp-adm-btn nmbp-adm-btn-primary nmbp-adm-btn-sm"
                              onClick={() => handleApprove(a.id)}
                            >
                              Approve
                            </button>
                            <button
                              className="nmbp-adm-btn nmbp-adm-btn-danger nmbp-adm-btn-sm"
                              onClick={() => { setSelectedApp(a); setShowRejectModal(true); }}
                            >
                              Reject
                            </button>
                          </>
                        )}
                        {a.appointment_status === 'confirmed' && (
                          <>
                            <button
                              className="nmbp-adm-btn nmbp-adm-btn-outline nmbp-adm-btn-sm"
                              onClick={() => {
                                setSelectedApp(a);
                                setRescheduleData({ date: a.appointment_date, startTime: a.start_time, endTime: a.end_time });
                                setShowRescheduleModal(true);
                              }}
                            >
                              Reschedule
                            </button>
                            <button
                              className="nmbp-adm-btn nmbp-adm-btn-outline nmbp-adm-btn-sm"
                              onClick={() => handleStatusChange(a.id, 'completed')}
                            >
                              Complete
                            </button>
                          </>
                        )}
                        <button
                          className="nmbp-adm-btn nmbp-adm-btn-outline nmbp-adm-btn-sm"
                          onClick={() => { setSelectedApp(a); setShowDetailModal(true); }}
                        >
                          View
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Appointment Details Modal */}
      {selectedApp && (
        <Modal
          isOpen={showDetailModal}
          onClose={() => setShowDetailModal(false)}
          title={`Appointment Details: ${selectedApp.appointment_number}`}
          footer={
            <>
              {selectedApp.appointment_status === 'pending' && (
                <button className="nmbp-adm-btn nmbp-adm-btn-primary" onClick={() => handleApprove(selectedApp.id)}>
                  Approve Appointment
                </button>
              )}
              <button className="nmbp-adm-btn nmbp-adm-btn-outline" onClick={() => setShowDetailModal(false)}>
                Close
              </button>
            </>
          }
        >
          <div style={{ fontSize: '14px', lineHeight: 1.6 }}>
            <p><strong>Patient Name:</strong> {selectedApp.patient_first_name} {selectedApp.patient_last_name}</p>
            <p><strong>Patient Email:</strong> {selectedApp.patient_email}</p>
            <p><strong>Patient Phone:</strong> {selectedApp.patient_phone}</p>
            <p><strong>Doctor:</strong> Dr. {selectedApp.doctor_first_name} {selectedApp.doctor_last_name}</p>
            <p><strong>Hospital Branch:</strong> {selectedApp.clinic_name} ({selectedApp.clinic_city})</p>
            <p><strong>Date & Time:</strong> {selectedApp.appointment_date} from {selectedApp.start_time} to {selectedApp.end_time}</p>
            <p><strong>Consultation Type:</strong> {selectedApp.appointment_type.replace('_', ' ')}</p>
            <p><strong>Consultation Fee:</strong> ${parseFloat(String(selectedApp.consultation_fee)).toFixed(2)}</p>
            <p><strong>Payment Status:</strong> <span className={`nmbp-badge-status nmbp-badge-${selectedApp.payment_status}`}>{selectedApp.payment_status}</span></p>
            <p><strong>Booking Status:</strong> <span className={`nmbp-badge-status nmbp-badge-${selectedApp.appointment_status}`}>{selectedApp.appointment_status}</span></p>
            {selectedApp.patient_notes && (
              <p><strong>Patient Notes:</strong> {selectedApp.patient_notes}</p>
            )}
            {selectedApp.cancellation_reason && (
              <p style={{ color: '#b91c1c' }}><strong>Rejection/Cancellation Reason:</strong> {selectedApp.cancellation_reason}</p>
            )}
          </div>
        </Modal>
      )}

      {/* Reject Modal */}
      {selectedApp && (
        <Modal
          isOpen={showRejectModal}
          onClose={() => setShowRejectModal(false)}
          title="Reject Appointment Request"
          footer={
            <>
              <button className="nmbp-adm-btn nmbp-adm-btn-danger" disabled={actionLoading} onClick={handleReject}>
                {actionLoading ? 'Rejecting...' : 'Confirm Rejection'}
              </button>
              <button className="nmbp-adm-btn nmbp-adm-btn-outline" onClick={() => setShowRejectModal(false)}>
                Cancel
              </button>
            </>
          }
        >
          <p>Please enter the reason for rejecting booking <strong>{selectedApp.appointment_number}</strong>. This note will be sent to the patient.</p>
          <textarea
            className="nmbp-search-input"
            style={{ width: '100%', height: '100px', marginTop: '10px' }}
            placeholder="e.g. Doctor is called for emergency surgery..."
            value={rejectReason}
            onChange={(e) => setRejectReason(e.target.value)}
          />
        </Modal>
      )}

      {/* Reschedule Modal */}
      {selectedApp && (
        <Modal
          isOpen={showRescheduleModal}
          onClose={() => setShowRescheduleModal(false)}
          title="Reschedule Appointment"
          footer={
            <>
              <button className="nmbp-adm-btn nmbp-adm-btn-primary" disabled={actionLoading} onClick={handleReschedule}>
                {actionLoading ? 'Saving...' : 'Confirm New Schedule'}
              </button>
              <button className="nmbp-adm-btn nmbp-adm-btn-outline" onClick={() => setShowRescheduleModal(false)}>
                Cancel
              </button>
            </>
          }
        >
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <label>
              <strong>New Date:</strong>
              <input
                type="date"
                className="nmbp-search-input"
                style={{ width: '100%', marginTop: '4px' }}
                value={rescheduleData.date}
                onChange={(e) => setRescheduleData({ ...rescheduleData, date: e.target.value })}
              />
            </label>
            <label>
              <strong>New Start Time:</strong>
              <input
                type="time"
                className="nmbp-search-input"
                style={{ width: '100%', marginTop: '4px' }}
                value={rescheduleData.startTime}
                onChange={(e) => setRescheduleData({ ...rescheduleData, startTime: e.target.value })}
              />
            </label>
            <label>
              <strong>New End Time:</strong>
              <input
                type="time"
                className="nmbp-search-input"
                style={{ width: '100%', marginTop: '4px' }}
                value={rescheduleData.endTime}
                onChange={(e) => setRescheduleData({ ...rescheduleData, endTime: e.target.value })}
              />
            </label>
          </div>
        </Modal>
      )}
    </div>
  );
};

