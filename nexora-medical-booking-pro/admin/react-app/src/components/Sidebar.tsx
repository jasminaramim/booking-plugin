import React from 'react';

interface SidebarProps {
  activePage: string;
  onNavigate: (page: string) => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ activePage, onNavigate }) => {
  const navItems = [
    { id: 'dashboard', label: 'Dashboard', icon: '📊' },
    { id: 'appointments', label: 'Appointments', icon: '📅' },
    { id: 'clinics', label: 'Clinics & Branches', icon: '🏥' },
    { id: 'doctors', label: 'Doctors', icon: '👨‍⚕️' },
    { id: 'departments', label: 'Departments', icon: '🩺' },
    { id: 'schedules', label: 'Schedules', icon: '⏰' },
    { id: 'patients', label: 'Patients', icon: '👥' },
    { id: 'payments', label: 'Payments', icon: '💳' },
    { id: 'design-studio', label: 'Design Studio', icon: '🎨' },
    { id: 'settings', label: 'Settings', icon: '⚙️' },
    { id: 'system-status', label: 'System Status', icon: '🛡️' },
  ];

  return (
    <aside className="nmbp-sidebar">
      <div className="nmbp-sidebar-brand">
        <div className="nmbp-brand-logo">N</div>
        <div>
          <h2 className="nmbp-brand-title">Nexora Medical <span className="nmbp-brand-badge">PRO</span></h2>
          <small style={{ color: '#64748b', fontSize: '11px' }}>Healthcare Booking Suite</small>
        </div>
      </div>
      <nav className="nmbp-sidebar-nav">
        {navItems.map((item) => (
          <button
            key={item.id}
            className={`nmbp-nav-btn ${activePage === item.id ? 'active' : ''}`}
            onClick={() => onNavigate(item.id)}
          >
            <span className="nmbp-nav-icon">{item.icon}</span>
            <span>{item.label}</span>
          </button>
        ))}
      </nav>
    </aside>
  );
};

