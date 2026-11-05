import React from 'react';

interface StatCardProps {
  label: string;
  value: string | number;
  icon: string;
  variant?: 'teal' | 'amber' | 'blue' | 'green';
}

export const StatCard: React.FC<StatCardProps> = ({ label, value, icon, variant = 'teal' }) => {
  return (
    <div className="nmbp-stat-card">
      <div className={`nmbp-stat-icon-wrap nmbp-stat-icon-${variant}`}>
        {icon}
      </div>
      <div>
        <h3 className="nmbp-stat-val">{value}</h3>
        <p className="nmbp-stat-label">{label}</p>
      </div>
    </div>
  );
};

