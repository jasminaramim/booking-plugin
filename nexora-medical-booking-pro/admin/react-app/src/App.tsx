import React, { useState, useEffect } from 'react';
import { Sidebar } from './components/Sidebar';
import { DashboardPage } from './pages/DashboardPage';
import { AppointmentsPage } from './pages/AppointmentsPage';
import { ClinicsPage } from './pages/ClinicsPage';
import { DoctorsPage } from './pages/DoctorsPage';
import { DepartmentsPage } from './pages/DepartmentsPage';
import { SchedulesPage } from './pages/SchedulesPage';
import { PatientsPage } from './pages/PatientsPage';
import { PaymentsPage } from './pages/PaymentsPage';
import { DesignStudioPage } from './pages/DesignStudioPage';
import { SettingsPage } from './pages/SettingsPage';
import { SystemStatusPage } from './pages/SystemStatusPage';

export const App: React.FC = () => {
  const getInitialPage = (): string => {
    const el = document.getElementById('nmbp-admin-root');
    const pageAttr = el?.getAttribute('data-current-page') || 'nmbp-dashboard';
    const slug = pageAttr.replace(/^nmbp-/, '');
    const valid = [
      'dashboard',
      'appointments',
      'clinics',
      'doctors',
      'departments',
      'schedules',
      'patients',
      'payments',
      'design-studio',
      'settings',
      'system-status',
    ];
    return valid.includes(slug) ? slug : 'dashboard';
  };

  const [activePage, setActivePage] = useState<string>(getInitialPage());

  useEffect(() => {
    // Sync browser history state if user clicks submenu
    window.addEventListener('popstate', () => {
      setActivePage(getInitialPage());
    });
  }, []);

  const handleNavigate = (page: string) => {
    setActivePage(page);
    const newUrl = new URL(window.location.href);
    newUrl.searchParams.set('page', `nmbp-${page}`);
    window.history.pushState({}, '', newUrl.toString());
  };

  const renderContent = () => {
    switch (activePage) {
      case 'dashboard':
        return <DashboardPage onNavigate={handleNavigate} />;
      case 'appointments':
        return <AppointmentsPage />;
      case 'clinics':
        return <ClinicsPage />;
      case 'doctors':
        return <DoctorsPage />;
      case 'departments':
        return <DepartmentsPage />;
      case 'schedules':
        return <SchedulesPage />;
      case 'patients':
        return <PatientsPage />;
      case 'payments':
        return <PaymentsPage />;
      case 'design-studio':
        return <DesignStudioPage />;
      case 'settings':
        return <SettingsPage />;
      case 'system-status':
        return <SystemStatusPage />;
      default:
        return <DashboardPage onNavigate={handleNavigate} />;
    }
  };

  return (
    <div className="nmbp-admin-app">
      <Sidebar activePage={activePage} onNavigate={handleNavigate} />
      <main className="nmbp-main">
        {renderContent()}
      </main>
    </div>
  );
};

