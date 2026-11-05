import React, { useEffect, useState } from 'react';
import { api } from '../api/client';
import { Doctor, Department, Clinic } from '../types';
import { Header } from '../components/Header';
import { Modal } from '../components/Modal';

export const DoctorsPage: React.FC = () => {
  const [doctors, setDoctors] = useState<Doctor[]>([]);
  const [departments, setDepartments] = useState<Department[]>([]);
  const [clinics, setClinics] = useState<Clinic[]>([]);
  const [loading, setLoading] = useState(true);

  const [showModal, setShowModal] = useState(false);
  const [editingDoc, setEditingDoc] = useState<Partial<Doctor> | null>(null);
  const [formLoading, setFormLoading] = useState(false);

  const loadData = async () => {
    try {
      setLoading(true);
      const [docRes, deptRes, clinicRes] = await Promise.all([
        api.get<{ items: Doctor[] }>('/doctors'),
        api.get<Department[]>('/departments'),
        api.get<{ items: Clinic[] }>('/clinics'),
      ]);
      setDoctors(docRes.items || []);
      setDepartments(deptRes || []);
      setClinics(clinicRes.items || []);
    } catch (err) {
      console.error('Failed to load doctors data', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleOpenCreate = () => {
    setEditingDoc({
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      specialization: 'Cardiologist',
      department_id: departments[0]?.id || 1,
      experience_years: 5,
      consultation_fee: 100,
      appointment_duration: 20,
      max_patients_per_session: 20,
      bmdc_reg_number: '',
      biography: '',
      status: 'active',
      clinics: clinics.map(c => ({ clinic_id: c.id, clinic_name: c.name, clinic_fee: null })),
    });
    setShowModal(true);
  };

  const handleOpenEdit = (doc: Doctor) => {
    setEditingDoc({ ...doc });
    setShowModal(true);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingDoc || !editingDoc.first_name || !editingDoc.last_name) {
      alert('First name and last name are required.');
      return;
    }

    try {
      setFormLoading(true);
      if (editingDoc.id) {
        await api.put(`/doctors/${editingDoc.id}`, editingDoc);
      } else {
        await api.post('/doctors', editingDoc);
      }
      setShowModal(false);
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Failed to save doctor');
    } finally {
      setFormLoading(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this doctor profile?')) {
      return;
    }
    try {
      await api.delete(`/doctors/${id}`);
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Failed to delete doctor');
    }
  };

  return (
    <div>
      <Header
        title="Doctor Profiles & Practitioners"
        subtitle="Manage doctors, qualifications, multi-branch assignments and consultation fees"
      >
        <button className="nmbp-adm-btn nmbp-adm-btn-primary" onClick={handleOpenCreate}>
          + Add New Doctor
        </button>
      </Header>

      <div className="nmbp-content-body">
        <div className="nmbp-card-table">
          <div className="nmbp-table-header">
            <h3>Registered Doctors ({doctors.length})</h3>
          </div>
          <table className="nmbp-ui-table">
            <thead>
              <tr>
                <th>Doctor</th>
                <th>Specialization & Dept</th>
                <th>Contact</th>
                <th>Exp / Reg</th>
                <th>Fee</th>
                <th>Duration</th>
                <th>Assigned Branches</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={9} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    Loading doctors...
                  </td>
                </tr>
              ) : doctors.length === 0 ? (
                <tr>
                  <td colSpan={9} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    No doctor profiles found. Click "+ Add New Doctor" to create one.
                  </td>
                </tr>
              ) : (
                doctors.map((d) => (
                  <tr key={d.id}>
                    <td>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <img
                          src={d.photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(d.first_name + '+' + d.last_name)}&background=0d9488&color=fff&size=80`}
                          alt="Doctor"
                          style={{ width: '40px', height: '40px', borderRadius: '50%', objectFit: 'cover' }}
                        />
                        <div>
                          <strong>Dr. {d.first_name} {d.last_name}</strong>
                          <div style={{ fontSize: '11px', color: '#64748b' }}>ID: #{d.id}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div><strong>{d.specialization}</strong></div>
                      <div style={{ fontSize: '12px', color: '#0d9488' }}>{d.department_name || 'General'}</div>
                    </td>
                    <td>
                      <div>{d.email}</div>
                      <div style={{ fontSize: '12px', color: '#64748b' }}>{d.phone}</div>
                    </td>
                    <td>
                      <div>{d.experience_years} yrs</div>
                      <div style={{ fontSize: '11px', color: '#64748b' }}>{d.bmdc_reg_number || 'N/A'}</div>
                    </td>
                    <td>${parseFloat(String(d.consultation_fee)).toFixed(2)}</td>
                    <td>{d.appointment_duration} mins</td>
                    <td>
                      <div style={{ fontSize: '12px' }}>
                        {d.clinics && d.clinics.length > 0 ? (
                          d.clinics.map(c => c.clinic_name).join(', ')
                        ) : (
                          <span style={{ color: '#94a3b8' }}>Unassigned</span>
                        )}
                      </div>
                    </td>
                    <td>
                      <span className={`nmbp-badge-status nmbp-badge-${d.status === 'active' ? 'confirmed' : 'rejected'}`}>
                        {d.status}
                      </span>
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '6px' }}>
                        <button className="nmbp-adm-btn nmbp-adm-btn-outline nmbp-adm-btn-sm" onClick={() => handleOpenEdit(d)}>
                          Edit
                        </button>
                        <button className="nmbp-adm-btn nmbp-adm-btn-danger nmbp-adm-btn-sm" onClick={() => handleDelete(d.id)}>
                          Delete
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

      {/* Doctor Modal */}
      {showModal && editingDoc && (
        <Modal
          isOpen={showModal}
          onClose={() => setShowModal(false)}
          title={editingDoc.id ? 'Edit Doctor Profile' : 'Add New Doctor Profile'}
          footer={
            <>
              <button className="nmbp-adm-btn nmbp-adm-btn-primary" disabled={formLoading} onClick={handleSave}>
                {formLoading ? 'Saving...' : 'Save Doctor'}
              </button>
              <button className="nmbp-adm-btn nmbp-adm-btn-outline" onClick={() => setShowModal(false)}>
                Cancel
              </button>
            </>
          }
        >
          <form onSubmit={handleSave} style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <div style={{ display: 'flex', gap: '12px' }}>
              <label style={{ flex: 1 }}>
                <strong>First Name *</strong>
                <input
                  type="text"
                  required
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingDoc.first_name || ''}
                  onChange={(e) => setEditingDoc({ ...editingDoc, first_name: e.target.value })}
                />
              </label>
              <label style={{ flex: 1 }}>
                <strong>Last Name *</strong>
                <input
                  type="text"
                  required
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingDoc.last_name || ''}
                  onChange={(e) => setEditingDoc({ ...editingDoc, last_name: e.target.value })}
                />
              </label>
            </div>

            <div style={{ display: 'flex', gap: '12px' }}>
              <label style={{ flex: 1 }}>
                <strong>Email Address *</strong>
                <input
                  type="email"
                  required
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingDoc.email || ''}
                  onChange={(e) => setEditingDoc({ ...editingDoc, email: e.target.value })}
                />
              </label>
              <label style={{ flex: 1 }}>
                <strong>Phone Number *</strong>
                <input
                  type="text"
                  required
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingDoc.phone || ''}
                  onChange={(e) => setEditingDoc({ ...editingDoc, phone: e.target.value })}
                />
              </label>
            </div>

            <div style={{ display: 'flex', gap: '12px' }}>
              <label style={{ flex: 1 }}>
                <strong>Department</strong>
                <select
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingDoc.department_id || 1}
                  onChange={(e) => setEditingDoc({ ...editingDoc, department_id: parseInt(e.target.value, 10) })}
                >
                  {departments.map((d) => (
                    <option key={d.id} value={d.id}>{d.name}</option>
                  ))}
                </select>
              </label>
              <label style={{ flex: 1 }}>
                <strong>Specialization</strong>
                <input
                  type="text"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  placeholder="e.g. Senior Interventional Cardiologist"
                  value={editingDoc.specialization || ''}
                  onChange={(e) => setEditingDoc({ ...editingDoc, specialization: e.target.value })}
                />
              </label>
            </div>

            <div style={{ display: 'flex', gap: '12px' }}>
              <label style={{ flex: 1 }}>
                <strong>Standard Consultation Fee ($)</strong>
                <input
                  type="number"
                  step="0.01"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingDoc.consultation_fee || 0}
                  onChange={(e) => setEditingDoc({ ...editingDoc, consultation_fee: parseFloat(e.target.value) || 0 })}
                />
              </label>
              <label style={{ flex: 1 }}>
                <strong>Slot Duration (Minutes)</strong>
                <input
                  type="number"
                  min="5"
                  step="5"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingDoc.appointment_duration || 20}
                  onChange={(e) => setEditingDoc({ ...editingDoc, appointment_duration: parseInt(e.target.value, 10) || 20 })}
                />
              </label>
            </div>

            <div style={{ display: 'flex', gap: '12px' }}>
              <label style={{ flex: 1 }}>
                <strong>BMDC / Medical Reg Number</strong>
                <input
                  type="text"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  placeholder="e.g. A-48291"
                  value={editingDoc.bmdc_reg_number || ''}
                  onChange={(e) => setEditingDoc({ ...editingDoc, bmdc_reg_number: e.target.value })}
                />
              </label>
              <label style={{ flex: 1 }}>
                <strong>Experience (Years)</strong>
                <input
                  type="number"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingDoc.experience_years || 0}
                  onChange={(e) => setEditingDoc({ ...editingDoc, experience_years: parseInt(e.target.value, 10) || 0 })}
                />
              </label>
            </div>

            <label>
              <strong>Qualifications</strong>
              <input
                type="text"
                className="nmbp-search-input"
                style={{ width: '100%', marginTop: '4px' }}
                placeholder="e.g. MBBS, FCPS (Cardiology), FACC (USA)"
                value={editingDoc.qualifications || ''}
                onChange={(e) => setEditingDoc({ ...editingDoc, qualifications: e.target.value })}
              />
            </label>

            <label>
              <strong>Biography & Clinical Background</strong>
              <textarea
                className="nmbp-search-input"
                style={{ width: '100%', height: '70px', marginTop: '4px' }}
                value={editingDoc.biography || ''}
                onChange={(e) => setEditingDoc({ ...editingDoc, biography: e.target.value })}
              />
            </label>

            <label>
              <strong>Status</strong>
              <select
                className="nmbp-search-input"
                style={{ width: '100%', marginTop: '4px' }}
                value={editingDoc.status || 'active'}
                onChange={(e) => setEditingDoc({ ...editingDoc, status: e.target.value as any })}
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </label>
          </form>
        </Modal>
      )}
    </div>
  );
};

