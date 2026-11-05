import React, { useEffect, useState } from 'react';
import { api } from '../api/client';
import { Patient } from '../types';
import { Header } from '../components/Header';
import { Modal } from '../components/Modal';

export const PatientsPage: React.FC = () => {
  const [patients, setPatients] = useState<Patient[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [selectedPatient, setSelectedPatient] = useState<Patient | null>(null);

  const loadPatients = async () => {
    try {
      setLoading(true);
      const res = await api.get<{ items: Patient[] }>('/patients', { search });
      setPatients(res.items || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadPatients();
  }, []);

  return (
    <div>
      <Header
        title="Patient Directory"
        subtitle="Manage registered hospital patients, medical profiles and contact details"
      />

      <div className="nmbp-content-body">
        <div className="nmbp-card-table" style={{ padding: '16px 20px', marginBottom: '20px' }}>
          <form
            onSubmit={(e) => { e.preventDefault(); loadPatients(); }}
            style={{ display: 'flex', gap: '12px' }}
          >
            <input
              type="text"
              className="nmbp-search-input"
              placeholder="Search patient by name, phone, email..."
              style={{ width: '320px' }}
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
            <button type="submit" className="nmbp-adm-btn nmbp-adm-btn-primary">Search</button>
          </form>
        </div>

        <div className="nmbp-card-table">
          <div className="nmbp-table-header">
            <h3>Registered Patients ({patients.length})</h3>
          </div>
          <table className="nmbp-ui-table">
            <thead>
              <tr>
                <th>Patient Name</th>
                <th>Contact Phone</th>
                <th>Email Address</th>
                <th>Gender / Blood Group</th>
                <th>Registered Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={6} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    Loading patient records...
                  </td>
                </tr>
              ) : patients.length === 0 ? (
                <tr>
                  <td colSpan={6} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    No patients registered yet. Patients are created automatically during appointment booking.
                  </td>
                </tr>
              ) : (
                patients.map((p) => (
                  <tr key={p.id}>
                    <td><strong>{p.first_name} {p.last_name}</strong></td>
                    <td>{p.phone}</td>
                    <td>{p.email}</td>
                    <td>
                      <span style={{ textTransform: 'capitalize' }}>{p.gender || 'Not specified'}</span>
                      {p.blood_group && <span style={{ marginLeft: '6px', color: '#b91c1c', fontWeight: 700 }}>({p.blood_group})</span>}
                    </td>
                    <td>{p.created_at?.slice(0, 10)}</td>
                    <td>
                      <button
                        className="nmbp-adm-btn nmbp-adm-btn-outline nmbp-adm-btn-sm"
                        onClick={() => setSelectedPatient(p)}
                      >
                        Profile
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {selectedPatient && (
        <Modal
          isOpen={!!selectedPatient}
          onClose={() => setSelectedPatient(null)}
          title={`Patient Profile: ${selectedPatient.first_name} ${selectedPatient.last_name}`}
          footer={
            <button className="nmbp-adm-btn nmbp-adm-btn-outline" onClick={() => setSelectedPatient(null)}>
              Close
            </button>
          }
        >
          <div style={{ fontSize: '14px', lineHeight: 1.6 }}>
            <p><strong>Full Name:</strong> {selectedPatient.first_name} {selectedPatient.last_name}</p>
            <p><strong>Email:</strong> {selectedPatient.email}</p>
            <p><strong>Phone:</strong> {selectedPatient.phone}</p>
            <p><strong>Blood Group:</strong> {selectedPatient.blood_group || 'Unknown'}</p>
            <p><strong>Gender:</strong> {selectedPatient.gender || 'Unknown'}</p>
            <p><strong>Emergency Contact:</strong> {selectedPatient.emergency_contact || 'None provided'}</p>
            <p><strong>Address:</strong> {selectedPatient.address || 'None provided'}</p>
            <p><strong>Medical History / Notes:</strong> {selectedPatient.medical_history_notes || 'No critical notes recorded.'}</p>
          </div>
        </Modal>
      )}
    </div>
  );
};

