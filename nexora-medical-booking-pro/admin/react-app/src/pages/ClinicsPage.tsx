import React, { useEffect, useState } from 'react';
import { api } from '../api/client';
import { Clinic } from '../types';
import { Header } from '../components/Header';
import { Modal } from '../components/Modal';

export const ClinicsPage: React.FC = () => {
  const [clinics, setClinics] = useState<Clinic[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingClinic, setEditingClinic] = useState<Partial<Clinic> | null>(null);
  const [formLoading, setFormLoading] = useState(false);

  const loadClinics = async () => {
    try {
      setLoading(true);
      const res = await api.get<{ items: Clinic[] }>('/clinics');
      setClinics(res.items || []);
    } catch (err) {
      console.error('Failed to load clinics', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadClinics();
  }, []);

  const handleOpenCreate = () => {
    setEditingClinic({
      name: '',
      email: '',
      phone: '',
      address: '',
      city: '',
      opening_time: '08:00:00',
      closing_time: '20:00:00',
      weekly_holidays: ['Friday'],
      default_fee: 50,
      status: 'active',
    });
    setShowModal(true);
  };

  const handleOpenEdit = (clinic: Clinic) => {
    setEditingClinic({ ...clinic });
    setShowModal(true);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingClinic || !editingClinic.name) {
      alert('Clinic name is required.');
      return;
    }

    try {
      setFormLoading(true);
      if (editingClinic.id) {
        await api.put(`/clinics/${editingClinic.id}`, editingClinic);
      } else {
        await api.post('/clinics', editingClinic);
      }
      setShowModal(false);
      await loadClinics();
    } catch (err: any) {
      alert(err.message || 'Failed to save clinic');
    } finally {
      setFormLoading(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this clinic? Associated appointments will be preserved.')) {
      return;
    }
    try {
      await api.delete(`/clinics/${id}`);
      await loadClinics();
    } catch (err: any) {
      alert(err.message || 'Failed to delete clinic');
    }
  };

  return (
    <div>
      <Header
        title="Clinics & Hospital Branches"
        subtitle="Manage multi-location clinics, working hours, and branch consultation fees"
      >
        <button className="nmbp-adm-btn nmbp-adm-btn-primary" onClick={handleOpenCreate}>
          + Add New Clinic
        </button>
      </Header>

      <div className="nmbp-content-body">
        <div className="nmbp-card-table">
          <div className="nmbp-table-header">
            <h3>Registered Branches ({clinics.length})</h3>
          </div>
          <table className="nmbp-ui-table">
            <thead>
              <tr>
                <th>Clinic Name</th>
                <th>City & Address</th>
                <th>Contact Info</th>
                <th>Working Hours</th>
                <th>Holidays</th>
                <th>Default Fee</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={8} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    Loading clinics...
                  </td>
                </tr>
              ) : clinics.length === 0 ? (
                <tr>
                  <td colSpan={8} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    No clinics created yet. Click "+ Add New Clinic" to add your first branch.
                  </td>
                </tr>
              ) : (
                clinics.map((c) => (
                  <tr key={c.id}>
                    <td><strong>{c.name}</strong></td>
                    <td>{c.address}, {c.city}</td>
                    <td>
                      <div>{c.phone}</div>
                      <div style={{ fontSize: '12px', color: '#64748b' }}>{c.email}</div>
                    </td>
                    <td>{c.opening_time} - {c.closing_time}</td>
                    <td>{Array.isArray(c.weekly_holidays) ? c.weekly_holidays.join(', ') : 'None'}</td>
                    <td>${parseFloat(String(c.default_fee)).toFixed(2)}</td>
                    <td>
                      <span className={`nmbp-badge-status nmbp-badge-${c.status === 'active' ? 'confirmed' : 'rejected'}`}>
                        {c.status}
                      </span>
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '6px' }}>
                        <button className="nmbp-adm-btn nmbp-adm-btn-outline nmbp-adm-btn-sm" onClick={() => handleOpenEdit(c)}>
                          Edit
                        </button>
                        <button className="nmbp-adm-btn nmbp-adm-btn-danger nmbp-adm-btn-sm" onClick={() => handleDelete(c.id)}>
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

      {/* Add / Edit Clinic Modal */}
      {showModal && editingClinic && (
        <Modal
          isOpen={showModal}
          onClose={() => setShowModal(false)}
          title={editingClinic.id ? 'Edit Clinic Branch' : 'Add New Clinic Branch'}
          footer={
            <>
              <button className="nmbp-adm-btn nmbp-adm-btn-primary" disabled={formLoading} onClick={handleSave}>
                {formLoading ? 'Saving...' : 'Save Clinic'}
              </button>
              <button className="nmbp-adm-btn nmbp-adm-btn-outline" onClick={() => setShowModal(false)}>
                Cancel
              </button>
            </>
          }
        >
          <form onSubmit={handleSave} style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
            <label>
              <strong>Clinic / Branch Name *</strong>
              <input
                type="text"
                required
                className="nmbp-search-input"
                style={{ width: '100%', marginTop: '4px' }}
                value={editingClinic.name || ''}
                onChange={(e) => setEditingClinic({ ...editingClinic, name: e.target.value })}
              />
            </label>

            <div style={{ display: 'flex', gap: '12px' }}>
              <label style={{ flex: 1 }}>
                <strong>Contact Email *</strong>
                <input
                  type="email"
                  required
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingClinic.email || ''}
                  onChange={(e) => setEditingClinic({ ...editingClinic, email: e.target.value })}
                />
              </label>
              <label style={{ flex: 1 }}>
                <strong>Phone Number *</strong>
                <input
                  type="text"
                  required
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingClinic.phone || ''}
                  onChange={(e) => setEditingClinic({ ...editingClinic, phone: e.target.value })}
                />
              </label>
            </div>

            <label>
              <strong>Address *</strong>
              <input
                type="text"
                required
                className="nmbp-search-input"
                style={{ width: '100%', marginTop: '4px' }}
                value={editingClinic.address || ''}
                onChange={(e) => setEditingClinic({ ...editingClinic, address: e.target.value })}
              />
            </label>

            <div style={{ display: 'flex', gap: '12px' }}>
              <label style={{ flex: 1 }}>
                <strong>City</strong>
                <input
                  type="text"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingClinic.city || ''}
                  onChange={(e) => setEditingClinic({ ...editingClinic, city: e.target.value })}
                />
              </label>
              <label style={{ flex: 1 }}>
                <strong>Branch Default Fee ($)</strong>
                <input
                  type="number"
                  step="0.01"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingClinic.default_fee || 0}
                  onChange={(e) => setEditingClinic({ ...editingClinic, default_fee: parseFloat(e.target.value) || 0 })}
                />
              </label>
            </div>

            <div style={{ display: 'flex', gap: '12px' }}>
              <label style={{ flex: 1 }}>
                <strong>Opening Time</strong>
                <input
                  type="time"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingClinic.opening_time || '08:00'}
                  onChange={(e) => setEditingClinic({ ...editingClinic, opening_time: e.target.value })}
                />
              </label>
              <label style={{ flex: 1 }}>
                <strong>Closing Time</strong>
                <input
                  type="time"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={editingClinic.closing_time || '20:00'}
                  onChange={(e) => setEditingClinic({ ...editingClinic, closing_time: e.target.value })}
                />
              </label>
            </div>

            <label>
              <strong>Status</strong>
              <select
                className="nmbp-search-input"
                style={{ width: '100%', marginTop: '4px' }}
                value={editingClinic.status || 'active'}
                onChange={(e) => setEditingClinic({ ...editingClinic, status: e.target.value as any })}
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

