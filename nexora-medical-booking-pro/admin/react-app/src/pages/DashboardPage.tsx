import React, { useEffect, useState } from 'react';
import { api } from '../api/client';
import { DashboardStats } from '../types';
import { Header } from '../components/Header';
import { StatCard } from '../components/StatCard';

interface DashboardPageProps {
  onNavigate: (page: string) => void;
}

export const DashboardPage: React.FC<DashboardPageProps> = ({ onNavigate }) => {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState<number | null>(null);

  const loadStats = async () => {
    try {
      setLoading(true);
      const data = await api.get<DashboardStats>('/dashboard/stats');
      setStats(data);
    } catch (err) {
      console.error('Failed to load dashboard statistics', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadStats();
  }, []);

  const handleQuickApprove = async (appId: number) => {
    try {
      setActionLoading(appId);
      await api.post(`/appointments/${appId}/approve`);
      await loadStats();
    } catch (err: any) {
      alert(err.message || 'Approval failed');
    } finally {
      setActionLoading(null);
    }
  };

  if (loading || !stats) {
    return (
      <div>
        <Header title="Medical Operations Dashboard" />
        <div className="nmbp-content-body">
          <div style={{ textAlign: 'center', padding: '60px 0', color: '#64748b' }}>
            Loading hospital metrics...
          </div>
        </div>
      </div>
    );
  }

  return (
    <div>
      <Header
        title="Medical Operations Dashboard"
        subtitle="Global hospital network overview & appointment monitoring"
      >
        <button className="nmbp-adm-btn nmbp-adm-btn-primary" onClick={() => onNavigate('appointments')}>
          Manage All Bookings
        </button>
      </Header>

      <div className="nmbp-content-body">
        {/* Metric Cards */}
        <div className="nmbp-stats-grid">
          <StatCard label="Total Clinics" value={stats.counts.clinics} icon="🏥" variant="teal" />
          <StatCard label="Active Doctors" value={stats.counts.doctors} icon="👨‍⚕️" variant="blue" />
          <StatCard label="Registered Patients" value={stats.counts.patients} icon="👥" variant="teal" />
          <StatCard label="Today's Bookings" value={stats.counts.today_bookings} icon="📅" variant="green" />
          <StatCard label="Pending Approvals" value={stats.counts.pending_approvals} icon="⏳" variant="amber" />
          <StatCard label="Confirmed Appointments" value={stats.counts.confirmed} icon="✅" variant="teal" />
          <StatCard label="Today's Revenue" value={`$${stats.revenue.today.toFixed(2)}`} icon="💵" variant="green" />
          <StatCard label="Monthly Revenue" value={`$${stats.revenue.monthly.toFixed(2)}`} icon="📈" variant="blue" />
        </div>

        {/* Branch-wise Analytics */}
        <div className="nmbp-card-table">
          <div className="nmbp-table-header">
            <h3>Branch-wise Performance & Revenue</h3>
            <button className="nmbp-adm-btn nmbp-adm-btn-outline nmbp-adm-btn-sm" onClick={() => onNavigate('clinics')}>
              View Branches
            </button>
          </div>
          <table className="nmbp-ui-table">
            <thead>
              <tr>
                <th>Branch Name</th>
                <th>Total Appointments</th>
                <th>Verified Revenue</th>
              </tr>
            </thead>
            <tbody>
              {stats.branch_stats.length === 0 ? (
                <tr>
                  <td colSpan={3} style={{ textAlign: 'center', color: '#94a3b8' }}>No branch data recorded yet.</td>
                </tr>
              ) : (
                stats.branch_stats.map((b) => (
                  <tr key={b.id}>
                    <td><strong>{b.name}</strong></td>
                    <td>{b.total_appointments} consultations</td>
                    <td style={{ color: '#0d9488', fontWeight: 600 }}>${parseFloat(String(b.total_revenue)).toFixed(2)}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Recent Bookings */}
        <div className="nmbp-card-table">
          <div className="nmbp-table-header">
            <h3>Recent Booking Requests (Awaiting Admin Approval)</h3>
            <button className="nmbp-adm-btn nmbp-adm-btn-outline nmbp-adm-btn-sm" onClick={loadStats}>
              Refresh
            </button>
          </div>
          <table className="nmbp-ui-table">
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
              {stats.recent_bookings.length === 0 ? (
                <tr>
                  <td colSpan={8} style={{ textAlign: 'center', color: '#94a3b8' }}>No recent appointments found.</td>
                </tr>
              ) : (
                stats.recent_bookings.map((app) => (
                  <tr key={app.id}>
                    <td><strong>{app.appointment_number}</strong></td>
                    <td>{app.patient_first_name} {app.patient_last_name}</td>
                    <td>Dr. {app.doctor_first_name} {app.doctor_last_name}</td>
                    <td>{app.clinic_name}</td>
                    <td>{app.appointment_date} {app.start_time}</td>
                    <td>${parseFloat(String(app.consultation_fee)).toFixed(2)}</td>
                    <td>
                      <span className={`nmbp-badge-status nmbp-badge-${app.appointment_status}`}>
                        {app.appointment_status}
                      </span>
                    </td>
                    <td>
                      {app.appointment_status === 'pending' ? (
                        <button
                          className="nmbp-adm-btn nmbp-adm-btn-primary nmbp-adm-btn-sm"
                          disabled={actionLoading === app.id}
                          onClick={() => handleQuickApprove(app.id)}
                        >
                          {actionLoading === app.id ? 'Approving...' : 'Approve'}
                        </button>
                      ) : (
                        <span style={{ color: '#94a3b8', fontSize: '12px' }}>Processed</span>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

