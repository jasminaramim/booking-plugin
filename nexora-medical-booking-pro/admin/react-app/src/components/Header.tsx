import React from 'react';

interface HeaderProps {
  title: string;
  subtitle?: string;
  children?: React.ReactNode;
}

export const Header: React.FC<HeaderProps> = ({ title, subtitle, children }) => {
  return (
    <header className="nmbp-topbar">
      <div>
        <h1 className="nmbp-page-title">{title}</h1>
        {subtitle && <p style={{ margin: '4px 0 0 0', fontSize: '13px', color: '#64748b' }}>{subtitle}</p>}
      </div>
      <div className="nmbp-topbar-actions">
        {children}
      </div>
    </header>
  );
};

