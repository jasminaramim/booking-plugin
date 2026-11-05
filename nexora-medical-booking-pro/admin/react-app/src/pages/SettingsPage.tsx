import React, { useEffect, useState } from 'react';
import { api } from '../api/client';
import { Header } from '../components/Header';

export const SettingsPage: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'general' | 'appointment' | 'payment' | 'privacy'>('general');
  const [settings, setSettings] = useState<any>({
    general: {},
    appointment: {},
    payment: {},
    privacy: {},
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    api.get<any>('/settings').then((data) => {
      setSettings(data);
      setLoading(false);
    }).catch(console.error);
  }, []);

  const handleSave = async () => {
    try {
      setSaving(true);
      await api.post('/settings', settings);
      alert('Settings saved successfully!');
    } catch (err: any) {
      alert(err.message || 'Failed to save settings');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div>
        <Header title="System Settings" />
        <div className="nmbp-content-body"><p>Loading settings...</p></div>
      </div>
    );
  }

  return (
    <div>
      <Header
        title="Plugin Settings & Configuration"
        subtitle="Manage hospital policies, booking approvals, payment gateways and data retention"
      >
        <button className="nmbp-adm-btn nmbp-adm-btn-primary" disabled={saving} onClick={handleSave}>
          {saving ? 'Saving...' : 'Save Settings'}
        </button>
      </Header>

      <div className="nmbp-content-body">
        {/* Tab navigation */}
        <div className="nmbp-tabs-bar">
          <button className={`nmbp-tab-btn ${activeTab === 'general' ? 'active' : ''}`} onClick={() => setActiveTab('general')}>
            General Settings
          </button>
          <button className={`nmbp-tab-btn ${activeTab === 'appointment' ? 'active' : ''}`} onClick={() => setActiveTab('appointment')}>
            Appointment & Approval
          </button>
          <button className={`nmbp-tab-btn ${activeTab === 'payment' ? 'active' : ''}`} onClick={() => setActiveTab('payment')}>
            Payments & Gateways
          </button>
          <button className={`nmbp-tab-btn ${activeTab === 'privacy' ? 'active' : ''}`} onClick={() => setActiveTab('privacy')}>
            Privacy & Uninstall
          </button>
        </div>

        {/* General Settings Tab */}
        {activeTab === 'general' && (
          <div className="nmbp-card-table" style={{ padding: '24px' }}>
            <h3 style={{ margin: '0 0 16px 0' }}>Hospital & Organization Identity</h3>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '16px', maxWidth: '600px' }}>
              <label>
                <strong>Company / Hospital Name:</strong>
                <input
                  type="text"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={settings.general?.company_name || ''}
                  onChange={(e) => setSettings({
                    ...settings,
                    general: { ...settings.general, company_name: e.target.value }
                  })}
                />
              </label>

              <label>
                <strong>Currency Symbol:</strong>
                <input
                  type="text"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={settings.general?.currency_symbol || '$'}
                  onChange={(e) => setSettings({
                    ...settings,
                    general: { ...settings.general, currency_symbol: e.target.value }
                  })}
                />
              </label>

              <label style={{ display: 'flex', alignItems: 'center', gap: '10px', marginTop: '10px' }}>
                <input
                  type="checkbox"
                  checked={!!settings.general?.require_approval}
                  onChange={(e) => setSettings({
                    ...settings,
                    general: { ...settings.general, require_approval: e.target.checked }
                  })}
                />
                <div>
                  <strong>Require Admin Approval for All Bookings (Default)</strong>
                  <div style={{ fontSize: '12px', color: '#64748b' }}>
                    Every patient booking request will start in PENDING state until an administrator or receptionist confirms it.
                  </div>
                </div>
              </label>

              <label style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <input
                  type="checkbox"
                  checked={!!settings.general?.auto_create_patient_account}
                  onChange={(e) => setSettings({
                    ...settings,
                    general: { ...settings.general, auto_create_patient_account: e.target.checked }
                  })}
                />
                <div>
                  <strong>Automatically create WordPress user account for new patients</strong>
                  <div style={{ fontSize: '12px', color: '#64748b' }}>
                    Creates a login for patients to review their appointments on the Patient Portal.
                  </div>
                </div>
              </label>
            </div>
          </div>
        )}

        {/* Appointment Settings Tab */}
        {activeTab === 'appointment' && (
          <div className="nmbp-card-table" style={{ padding: '24px' }}>
            <h3 style={{ margin: '0 0 16px 0' }}>Booking Rules & Slot Constraints</h3>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '16px', maxWidth: '600px' }}>
              <label>
                <strong>Minimum Advance Booking Notice (Hours):</strong>
                <input
                  type="number"
                  min="0"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={settings.appointment?.min_advance_hours || 2}
                  onChange={(e) => setSettings({
                    ...settings,
                    appointment: { ...settings.appointment, min_advance_hours: parseInt(e.target.value, 10) }
                  })}
                />
                <span style={{ fontSize: '12px', color: '#64748b' }}>Prevents sudden bookings within this timeframe of the session.</span>
              </label>

              <label>
                <strong>Maximum Advance Booking Limit (Days):</strong>
                <input
                  type="number"
                  min="1"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={settings.appointment?.max_advance_days || 30}
                  onChange={(e) => setSettings({
                    ...settings,
                    appointment: { ...settings.appointment, max_advance_days: parseInt(e.target.value, 10) }
                  })}
                />
              </label>

              <label>
                <strong>Temporary Slot Reservation Lock (Minutes):</strong>
                <input
                  type="number"
                  min="5"
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={settings.appointment?.temp_lock_duration_minutes || 10}
                  onChange={(e) => setSettings({
                    ...settings,
                    appointment: { ...settings.appointment, temp_lock_duration_minutes: parseInt(e.target.value, 10) }
                  })}
                />
                <span style={{ fontSize: '12px', color: '#64748b' }}>Temporary lock held while a patient completes submission to prevent concurrent booking conflicts.</span>
              </label>
            </div>
          </div>
        )}

        {/* Payment Settings Tab */}
        {activeTab === 'payment' && (
          <div className="nmbp-card-table" style={{ padding: '24px' }}>
            <h3 style={{ margin: '0 0 16px 0' }}>Payment Modes & Gateway Integration</h3>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '16px', maxWidth: '600px' }}>
              <label style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <input
                  type="checkbox"
                  checked={!!settings.payment?.enable_offline}
                  onChange={(e) => setSettings({
                    ...settings,
                    payment: { ...settings.payment, enable_offline: e.target.checked }
                  })}
                />
                <div>
                  <strong>Enable Pay at Clinic / Cash Desk</strong>
                  <div style={{ fontSize: '12px', color: '#64748b' }}>Patients can pay in cash or POS card upon arrival at the hospital reception.</div>
                </div>
              </label>

              <label style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <input
                  type="checkbox"
                  checked={!!settings.payment?.enable_online}
                  onChange={(e) => setSettings({
                    ...settings,
                    payment: { ...settings.payment, enable_online: e.target.checked }
                  })}
                />
                <div>
                  <strong>Enable Online Payments (WooCommerce Integration)</strong>
                  <div style={{ fontSize: '12px', color: '#64748b' }}>Integrates with WooCommerce payment gateways (Stripe, PayPal, bKash, etc.).</div>
                </div>
              </label>

              <label>
                <strong>Payment Timing Policy:</strong>
                <select
                  className="nmbp-search-input"
                  style={{ width: '100%', marginTop: '4px' }}
                  value={settings.payment?.payment_timing || 'before_approval'}
                  onChange={(e) => setSettings({
                    ...settings,
                    payment: { ...settings.payment, payment_timing: e.target.value }
                  })}
                >
                  <option value="before_approval">Require Payment / Selection During Booking Submission</option>
                  <option value="after_approval">Request Payment Only After Admin Confirms Booking</option>
                </select>
              </label>
            </div>
          </div>
        )}

        {/* Privacy & Uninstall Tab */}
        {activeTab === 'privacy' && (
          <div className="nmbp-card-table" style={{ padding: '24px' }}>
            <h3 style={{ margin: '0 0 16px 0' }}>Data Retention & Plugin Removal</h3>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '16px', maxWidth: '600px' }}>
              <div style={{ background: '#fef2f2', border: '1px solid #fecaca', borderRadius: '8px', padding: '14px', color: '#991b1b', fontSize: '13px' }}>
                <strong>Important Notice:</strong> By default, deactivating or uninstalling this plugin preserves all patient and appointment tables so medical records are never lost.
              </div>

              <label style={{ display: 'flex', alignItems: 'center', gap: '10px', marginTop: '10px' }}>
                <input
                  type="checkbox"
                  checked={!!settings.privacy?.delete_data_on_uninstall}
                  onChange={(e) => setSettings({
                    ...settings,
                    privacy: { ...settings.privacy, delete_data_on_uninstall: e.target.checked }
                  })}
                />
                <div>
                  <strong>Delete all plugin data & tables upon uninstall</strong>
                  <div style={{ fontSize: '12px', color: '#64748b' }}>
                    WARNING: Checking this will permanently drop all `wp_nmbp_*` database tables if the plugin is deleted via the WordPress Plugins menu.
                  </div>
                </div>
              </label>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

