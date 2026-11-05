import React, { useEffect, useState } from 'react';
import { api } from '../api/client';
import { Doctor, Clinic, Schedule } from '../types';
import { Header } from '../components/Header';

export const SchedulesPage: React.FC = () => {
  const [doctors, setDoctors] = useState<Doctor[]>([]);
  const [clinics, setClinics] = useState<Clinic[]>([]);
  const [selectedDoctorId, setSelectedDoctorId] = useState<number>(0);
  const [selectedClinicId, setSelectedClinicId] = useState<number>(0);

  const [schedules, setSchedules] = useState<Schedule[]>([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);

  const daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const;

  const loadInitial = async () => {
    try {
      const [docRes, clinicRes] = await Promise.all([
        api.get<{ items: Doctor[] }>('/doctors'),
        api.get<{ items: Clinic[] }>('/clinics'),
      ]);
      const docs = docRes.items || [];
      const cls = clinicRes.items || [];
      setDoctors(docs);
      setClinics(cls);

      if (docs.length > 0) setSelectedDoctorId(docs[0].id);
      if (cls.length > 0) setSelectedClinicId(cls[0].id);
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    loadInitial();
  }, []);

  const loadSchedules = async () => {
    if (!selectedDoctorId || !selectedClinicId) return;
    try {
      setLoading(true);
      const data = await api.get<Schedule[]>(`/schedules/doctor/${selectedDoctorId}?clinic_id=${selectedClinicId}`);

      // Initialize empty template for all 7 days if not present
      const fullList: Schedule[] = daysOfWeek.map((day) => {
        const found = (data || []).find((s) => s.day_of_week === day);
        return found || {
          doctor_id: selectedDoctorId,
          clinic_id: selectedClinicId,
          day_of_week: day,
          start_time: '09:00:00',
          end_time: '17:00:00',
          slot_duration: 20,
          buffer_time: 5,
          break_start: '13:00:00',
          break_end: '14:00:00',
          max_capacity: 20,
          status: 'inactive',
        };
      });

      setSchedules(fullList);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadSchedules();
  }, [selectedDoctorId, selectedClinicId]);

  const updateScheduleItem = (index: number, updates: Partial<Schedule>) => {
    const updated = [...schedules];
    updated[index] = { ...updated[index], ...updates };
    setSchedules(updated);
  };

  const handleSave = async () => {
    if (!selectedDoctorId || !selectedClinicId) return;
    try {
      setSaving(true);
      const activeSchedules = schedules.filter((s) => s.status === 'active');
      await api.post(`/schedules/doctor/${selectedDoctorId}`, {
        clinic_id: selectedClinicId,
        schedules: activeSchedules,
      });
      alert('Doctor weekly consultation schedules saved successfully!');
      await loadSchedules();
    } catch (err: any) {
      alert(err.message || 'Failed to save schedules');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div>
      <Header
        title="Doctor Scheduling Engine"
        subtitle="Configure weekly consultation slots, break times, and session capacity"
      >
        <button
          className="nmbp-adm-btn nmbp-adm-btn-primary"
          disabled={saving || !selectedDoctorId}
          onClick={handleSave}
        >
          {saving ? 'Saving...' : 'Save Weekly Schedule'}
        </button>
      </Header>

      <div className="nmbp-content-body">
        {/* Selector Bar */}
        <div className="nmbp-card-table" style={{ padding: '16px 20px', marginBottom: '20px' }}>
          <div style={{ display: 'flex', gap: '20px', alignItems: 'center' }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
              <strong>Select Doctor:</strong>
              <select
                className="nmbp-search-input"
                style={{ width: '220px' }}
                value={selectedDoctorId}
                onChange={(e) => setSelectedDoctorId(parseInt(e.target.value, 10))}
              >
                {doctors.map((d) => (
                  <option key={d.id} value={d.id}>Dr. {d.first_name} {d.last_name}</option>
                ))}
              </select>
            </label>

            <label style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
              <strong>Select Branch:</strong>
              <select
                className="nmbp-search-input"
                style={{ width: '220px' }}
                value={selectedClinicId}
                onChange={(e) => setSelectedClinicId(parseInt(e.target.value, 10))}
              >
                {clinics.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </label>
          </div>
        </div>

        {/* Schedules Table */}
        <div className="nmbp-card-table">
          <div className="nmbp-table-header">
            <h3>Weekly Consultation Hours</h3>
          </div>
          <table className="nmbp-ui-table">
            <thead>
              <tr>
                <th style={{ width: '120px' }}>Day of Week</th>
                <th>Active Session</th>
                <th>Working Hours</th>
                <th>Slot Duration</th>
                <th>Buffer</th>
                <th>Break Time</th>
                <th>Session Cap</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={7} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    Loading schedule records...
                  </td>
                </tr>
              ) : (
                schedules.map((s, idx) => (
                  <tr key={s.day_of_week} style={{ opacity: s.status === 'active' ? 1 : 0.6 }}>
                    <td>
                      <strong style={{ textTransform: 'capitalize' }}>{s.day_of_week}</strong>
                    </td>
                    <td>
                      <input
                        type="checkbox"
                        checked={s.status === 'active'}
                        onChange={(e) => updateScheduleItem(idx, { status: e.target.checked ? 'active' : 'inactive' })}
                      />
                    </td>
                    <td>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                        <input
                          type="time"
                          disabled={s.status !== 'active'}
                          className="nmbp-search-input"
                          style={{ width: '110px' }}
                          value={s.start_time.slice(0, 5)}
                          onChange={(e) => updateScheduleItem(idx, { start_time: e.target.value + ':00' })}
                        />
                        <span>-</span>
                        <input
                          type="time"
                          disabled={s.status !== 'active'}
                          className="nmbp-search-input"
                          style={{ width: '110px' }}
                          value={s.end_time.slice(0, 5)}
                          onChange={(e) => updateScheduleItem(idx, { end_time: e.target.value + ':00' })}
                        />
                      </div>
                    </td>
                    <td>
                      <input
                        type="number"
                        min="5"
                        step="5"
                        disabled={s.status !== 'active'}
                        className="nmbp-search-input"
                        style={{ width: '70px' }}
                        value={s.slot_duration}
                        onChange={(e) => updateScheduleItem(idx, { slot_duration: parseInt(e.target.value, 10) || 20 })}
                      /> mins
                    </td>
                    <td>
                      <input
                        type="number"
                        min="0"
                        disabled={s.status !== 'active'}
                        className="nmbp-search-input"
                        style={{ width: '60px' }}
                        value={s.buffer_time}
                        onChange={(e) => updateScheduleItem(idx, { buffer_time: parseInt(e.target.value, 10) || 0 })}
                      /> mins
                    </td>
                    <td>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                        <input
                          type="time"
                          disabled={s.status !== 'active'}
                          className="nmbp-search-input"
                          style={{ width: '95px' }}
                          value={s.break_start ? s.break_start.slice(0, 5) : ''}
                          onChange={(e) => updateScheduleItem(idx, { break_start: e.target.value ? e.target.value + ':00' : null })}
                        />
                        <span>-</span>
                        <input
                          type="time"
                          disabled={s.status !== 'active'}
                          className="nmbp-search-input"
                          style={{ width: '95px' }}
                          value={s.break_end ? s.break_end.slice(0, 5) : ''}
                          onChange={(e) => updateScheduleItem(idx, { break_end: e.target.value ? e.target.value + ':00' : null })}
                        />
                      </div>
                    </td>
                    <td>
                      <input
                        type="number"
                        min="1"
                        disabled={s.status !== 'active'}
                        className="nmbp-search-input"
                        style={{ width: '65px' }}
                        value={s.max_capacity}
                        onChange={(e) => updateScheduleItem(idx, { max_capacity: parseInt(e.target.value, 10) || 20 })}
                      />
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

