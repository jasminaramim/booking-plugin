import React, { useEffect, useState } from 'react';
import { api } from '../api/client';
import { Department } from '../types';
import { Header } from '../components/Header';
import { Modal } from '../components/Modal';

export const DepartmentsPage: React.FC = () => {
  const [departments, setDepartments] = useState<Department[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingDept, setEditingDept] = useState<Partial<Department> | null>(null);

  const loadDepartments = async () => {
    try {
      setLoading(true);
      const data = await api.get<Department[]>('/departments');
      setDepartments(data || []);
    } catch (err) {
      console.error('Failed to load departments', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadDepartments();
  }, []);

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingDept || !editingDept.name) return;

    try {
      if (editingDept.id) {
        await api.put(`/departments/${editingDept.id}`, editingDept);
      } else {
        await api.post('/departments', editingDept);
      }
      setShowModal(false);
      await loadDepartments();
    } catch (err: any) {
      alert(err.message || 'Failed to save department');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this department?')) return;
    try {
      await api.delete(`/departments/${id}`);
      await loadDepartments();
    } catch (err: any) {
      alert(err.message || 'Failed to delete department');
    }
  };

  return (
    <div>
      <Header
        title="Medical Departments"
        subtitle="Organize clinical specialties and medical disciplines"
      >
        <button
          className="nmbp-adm-btn nmbp-adm-btn-primary"
          onClick={() => {
            setEditingDept({ name: '', description: '', icon: 'heart', status: 'active' });
            setShowModal(true);
          }}
        >
          + Add Department
        </button>
      </Header>

      <div className="nmbp-content-body">
        <div className="nmbp-card-table">
          <div className="nmbp-table-header">
            <h3>Specialties ({departments.length})</h3>
          </div>
          <table className="nmbp-ui-table">
            <thead>
              <tr>
                <th>Department Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Icon Code</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={6} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    Loading departments...
                  </td>
                </tr>
              ) : departments.map((d) => (
                <tr key={d.id}>
                  <td><strong>{d.name}</strong></td>
                  <td><code>{d.slug}</code></td>
                  <td>{d.description || 'Specialized clinical branch'}</td>
                  <td>{d.icon || 'medical'}</td>
                  <td>
                    <span className={`nmbp-badge-status nmbp-badge-${d.status === 'active' ? 'confirmed' : 'rejected'}`}>
                      {d.status}
                    </span>
                  </td>
                  <td>
                    <div style={{ display: 'flex', gap: '6px' }}>
                      <button
                        className="nmbp-adm-btn nmbp-adm-btn-outline nmbp-adm-btn-sm"
                        onClick={() => { setEditingDept({ ...d }); setShowModal(true); }}
                      >
                        Edit
                      </button>
                      <button
                        className="nmbp-adm-btn nmbp-adm-btn-danger nmbp-adm-btn-sm"
                        onClick={() => handleDelete(d.id)}
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {showModal && editingDept && (
        <Modal
          isOpen={showModal}
          onClose={() => setShowModal(false)}
          title={editingDept.id ? 'Edit Department' : 'Add New Department'}
          footer={
            <>
              <button className="nmbp-adm-btn nmbp-adm-btn-primary" onClick={handleSave}>Save Department</button>
              <button className="nmbp-adm-btn nmbp-adm-btn-outline" onClick={() => setShowModal(false)}>Cancel</button>
            </>
          }
        >
          <form onSubmit={handleSave} style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <label>
              <strong>Department Name *</strong>
              <input
                type="text"
                required
                className="nmbp-search-input"
                style={{ width: '100%', marginTop: '4px' }}
                value={editingDept.name || ''}
                onChange={(e) => setEditingDept({ ...editingDept, name: e.target.value })}
              />
            </label>
            <label>
              <strong>Description</strong>
              <textarea
                className="nmbp-search-input"
                style={{ width: '100%', height: '80px', marginTop: '4px' }}
                value={editingDept.description || ''}
                onChange={(e) => setEditingDept({ ...editingDept, description: e.target.value })}
              />
            </label>
            <label>
              <strong>Icon Keyword</strong>
              <input
                type="text"
                className="nmbp-search-input"
                style={{ width: '100%', marginTop: '4px' }}
                placeholder="heart, brain, smile, activity..."
                value={editingDept.icon || ''}
                onChange={(e) => setEditingDept({ ...editingDept, icon: e.target.value })}
              />
            </label>
          </form>
        </Modal>
      )}
    </div>
  );
};

