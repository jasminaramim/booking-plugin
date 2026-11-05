import React from 'react';
import ReactDOM from 'react-dom/client';
import { App } from './App';
import './styles/admin.css';

const rootElement = document.getElementById('nmbp-admin-root');

if (rootElement) {
  ReactDOM.createRoot(rootElement).render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  );
}

