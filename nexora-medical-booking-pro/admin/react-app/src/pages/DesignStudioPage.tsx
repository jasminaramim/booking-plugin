import React, { useEffect, useState } from 'react';
import { api } from '../api/client';
import { Header } from '../components/Header';

export const DesignStudioPage: React.FC = () => {
  const [design, setDesign] = useState({
    primary_color: '#0d9488',
    secondary_color: '#0f172a',
    accent_color: '#0284c7',
    card_preset: 'classic_medical',
    form_preset: 'multi_step',
    border_radius: '8px',
    custom_css: '',
  });

  const [saving, setSaving] = useState(false);

  useEffect(() => {
    api.get<any>('/settings').then((data) => {
      if (data && data.design) {
        setDesign({ ...design, ...data.design });
      }
    }).catch(console.error);
  }, []);

  const handleSave = async () => {
    try {
      setSaving(true);
      await api.post('/settings', { design });
      alert('Design Studio settings published successfully!');
    } catch (err: any) {
      alert(err.message || 'Failed to save design');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div>
      <Header
        title="Visual Design Studio"
        subtitle="Customize booking card presets, stepper themes, medical color schemes and custom styling"
      >
        <button className="nmbp-adm-btn nmbp-adm-btn-primary" disabled={saving} onClick={handleSave}>
          {saving ? 'Publishing...' : 'Publish Styles'}
        </button>
      </Header>

      <div className="nmbp-content-body">
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 340px', gap: '24px' }}>
          {/* Controls Column */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
            {/* Doctor Card Presets */}
            <div className="nmbp-card-table" style={{ padding: '24px' }}>
              <h3 style={{ margin: '0 0 16px 0', fontSize: '16px' }}>1. Doctor Card Visual Preset</h3>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '14px' }}>
                {[
                  { id: 'classic_medical', title: '01. Classic Medical', desc: 'Balanced medical clinic card with clean borders' },
                  { id: 'modern_glass', title: '02. Modern Glass', desc: 'Frosted glassmorphism with subtle glow' },
                  { id: 'hospital_pro', title: '03. Hospital Pro', desc: 'Horizontal hospital layout with status indicator' },
                  { id: 'minimal_clean', title: '04. Minimal Clean', desc: 'Ultra minimal typography with compact spacing' },
                ].map((p) => (
                  <div
                    key={p.id}
                    onClick={() => setDesign({ ...design, card_preset: p.id })}
                    style={{
                      border: design.card_preset === p.id ? '2px solid #0d9488' : '1px solid #cbd5e1',
                      background: design.card_preset === p.id ? 'rgba(13, 148, 136, 0.05)' : '#ffffff',
                      borderRadius: '8px',
                      padding: '14px',
                      cursor: 'pointer',
                    }}
                  >
                    <strong style={{ display: 'block', fontSize: '13px', color: '#0f172a' }}>{p.title}</strong>
                    <span style={{ fontSize: '11px', color: '#64748b' }}>{p.desc}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Booking Form Layout Presets */}
            <div className="nmbp-card-table" style={{ padding: '24px' }}>
              <h3 style={{ margin: '0 0 16px 0', fontSize: '16px' }}>2. Booking Form Preset</h3>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '14px' }}>
                {[
                  { id: 'multi_step', title: '01. Multi-step Stepper', desc: 'Guided 5-step interactive wizard' },
                  { id: 'classic_form', title: '02. Classic Form', desc: 'Traditional structured medical form' },
                  { id: 'calendar_view', title: '03. Calendar View', desc: 'Focus on date selection and daily time schedule' },
                  { id: 'compact_form', title: '04. Compact Form', desc: 'Space-saving widget for sidebars' },
                ].map((p) => (
                  <div
                    key={p.id}
                    onClick={() => setDesign({ ...design, form_preset: p.id })}
                    style={{
                      border: design.form_preset === p.id ? '2px solid #0d9488' : '1px solid #cbd5e1',
                      background: design.form_preset === p.id ? 'rgba(13, 148, 136, 0.05)' : '#ffffff',
                      borderRadius: '8px',
                      padding: '14px',
                      cursor: 'pointer',
                    }}
                  >
                    <strong style={{ display: 'block', fontSize: '13px', color: '#0f172a' }}>{p.title}</strong>
                    <span style={{ fontSize: '11px', color: '#64748b' }}>{p.desc}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Colors & Typography */}
            <div className="nmbp-card-table" style={{ padding: '24px' }}>
              <h3 style={{ margin: '0 0 16px 0', fontSize: '16px' }}>3. Medical Color Scheme</h3>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '16px' }}>
                <label>
                  <strong style={{ display: 'block', fontSize: '13px', marginBottom: '6px' }}>Primary Accent</strong>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <input
                      type="color"
                      value={design.primary_color}
                      onChange={(e) => setDesign({ ...design, primary_color: e.target.value })}
                      style={{ width: '40px', height: '36px', border: 'none', cursor: 'pointer' }}
                    />
                    <input
                      type="text"
                      className="nmbp-search-input"
                      value={design.primary_color}
                      onChange={(e) => setDesign({ ...design, primary_color: e.target.value })}
                      style={{ width: '100px' }}
                    />
                  </div>
                </label>

                <label>
                  <strong style={{ display: 'block', fontSize: '13px', marginBottom: '6px' }}>Navy / Secondary</strong>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <input
                      type="color"
                      value={design.secondary_color}
                      onChange={(e) => setDesign({ ...design, secondary_color: e.target.value })}
                      style={{ width: '40px', height: '36px', border: 'none', cursor: 'pointer' }}
                    />
                    <input
                      type="text"
                      className="nmbp-search-input"
                      value={design.secondary_color}
                      onChange={(e) => setDesign({ ...design, secondary_color: e.target.value })}
                      style={{ width: '100px' }}
                    />
                  </div>
                </label>

                <label>
                  <strong style={{ display: 'block', fontSize: '13px', marginBottom: '6px' }}>Border Radius</strong>
                  <select
                    className="nmbp-search-input"
                    value={design.border_radius}
                    onChange={(e) => setDesign({ ...design, border_radius: e.target.value })}
                    style={{ width: '100%' }}
                  >
                    <option value="4px">4px - Crisp / Square</option>
                    <option value="8px">8px - Modern Smooth</option>
                    <option value="12px">12px - Rounded Hospital</option>
                    <option value="16px">16px - Pill / Soft</option>
                  </select>
                </label>
              </div>
            </div>

            {/* Custom CSS */}
            <div className="nmbp-card-table" style={{ padding: '24px' }}>
              <h3 style={{ margin: '0 0 8px 0', fontSize: '16px' }}>4. Custom CSS Injection</h3>
              <p style={{ margin: '0 0 12px 0', fontSize: '13px', color: '#64748b' }}>
                Add overrides for specific theme typography or custom container paddings.
              </p>
              <textarea
                className="nmbp-search-input"
                style={{ width: '100%', height: '100px', fontFamily: 'monospace', fontSize: '12px' }}
                placeholder="/* .nmbp-btn-primary { ... } */"
                value={design.custom_css}
                onChange={(e) => setDesign({ ...design, custom_css: e.target.value })}
              />
            </div>
          </div>

          {/* Live Preview Column */}
          <div>
            <div className="nmbp-card-table" style={{ padding: '20px', position: 'sticky', top: '20px' }}>
              <h3 style={{ margin: '0 0 14px 0', fontSize: '15px' }}>Live Card Preview</h3>
              <div
                style={{
                  background: '#ffffff',
                  border: '1px solid #e2e8f0',
                  borderRadius: design.border_radius,
                  padding: '20px',
                  textAlign: 'center',
                  boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.05)',
                }}
              >
                <div
                  style={{
                    width: '80px',
                    height: '80px',
                    borderRadius: '50%',
                    background: '#0d9488',
                    color: 'white',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontSize: '28px',
                    margin: '0 auto 12px auto',
                  }}
                >
                  👨‍⚕️
                </div>
                <span
                  style={{
                    fontSize: '11px',
                    background: 'rgba(13, 148, 136, 0.1)',
                    color: design.primary_color,
                    padding: '3px 8px',
                    borderRadius: '999px',
                    fontWeight: 700,
                  }}
                >
                  CARDIOLOGY
                </span>
                <h4 style={{ margin: '8px 0 2px 0', color: design.secondary_color, fontSize: '16px' }}>
                  Dr. Sarah Jenkins
                </h4>
                <p style={{ margin: '0 0 10px 0', fontSize: '12px', color: '#64748b' }}>
                  MBBS, MD - Senior Cardiologist
                </p>
                <div style={{ display: 'flex', justifyContent: 'center', gap: '12px', fontSize: '12px', marginBottom: '14px' }}>
                  <span><strong>12</strong> Yrs Exp.</span>
                  <span><strong style={{ color: design.primary_color }}>$120.00</strong> Fee</span>
                </div>
                <button
                  style={{
                    width: '100%',
                    padding: '8px 14px',
                    backgroundColor: design.primary_color,
                    color: '#ffffff',
                    border: 'none',
                    borderRadius: design.border_radius,
                    fontWeight: 600,
                    fontSize: '13px',
                    cursor: 'pointer',
                  }}
                >
                  Book Consultation
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

