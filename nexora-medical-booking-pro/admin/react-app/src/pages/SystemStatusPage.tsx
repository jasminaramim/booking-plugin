import React, { useEffect, useState } from 'react';
import { api } from '../api/client';
import { Header } from '../components/Header';

export const SystemStatusPage: React.FC = () => {
  const [status, setStatus] = useState<any>(null);
  const [logs, setLogs] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const loadData = async () => {
    try {
      setLoading(true);
      const [statusRes, logsRes] = await Promise.all([
        api.get<any>('/system/status'),
        api.get<any[]>('/audit-logs'),
      ]);
      setStatus(statusRes);
      setLogs(logsRes || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  return (
    <div>
      <Header
        title="System Status & Security Audit Trail"
        subtitle="Verify server environments, integrations, database integrity and review medical change logs"
      >
        <button className="nmbp-adm-btn nmbp-adm-btn-outline" onClick={loadData}>
          Refresh Audit Logs
        </button>
      </Header>

      <div className="nmbp-content-body">
        {/* Environment Specs */}
        {status && (
          <div className="nmbp-card-table" style={{ padding: '24px', marginBottom: '24px' }}>
            <h3 style={{ margin: '0 0 16px 0', fontSize: '16px' }}>Environment Health Check</h3>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '16px', fontSize: '13px' }}>
              <div><strong>WordPress:</strong> {status.wordpress_version}</div>
              <div><strong>PHP Version:</strong> {status.php_version}</div>
              <div><strong>Database Engine:</strong> MySQL {status.mysql_version}</div>
              <div><strong>Plugin Version:</strong> v{status.plugin_version}</div>
              <div><strong>DB Migration:</strong> v{status.db_version}</div>
              <div><strong>WooCommerce:</strong> {status.woocommerce_active ? '✅ Active' : '⚪ Not Active'}</div>
              <div><strong>Elementor:</strong> {status.elementor_active ? '✅ Active' : '⚪ Not Active'}</div>
              <div><strong>Server Memory:</strong> {status.memory_limit}</div>
            </div>
          </div>
        )}

        {/* Security Audit Trail */}
        <div className="nmbp-card-table">
          <div className="nmbp-table-header">
            <h3>Medical Audit Trail (Latest Actions)</h3>
          </div>
          <table className="nmbp-ui-table">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>Entity Type</th>
                <th>Entity ID</th>
                <th>Action</th>
                <th>User / Role</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={6} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    Loading audit trail...
                  </td>
                </tr>
              ) : logs.length === 0 ? (
                <tr>
                  <td colSpan={6} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    No audit records logged yet.
                  </td>
                </tr>
              ) : (
                logs.map((log) => (
                  <tr key={log.id}>
                    <td>{log.created_at}</td>
                    <td><strong style={{ textTransform: 'capitalize' }}>{log.entity_type}</strong></td>
                    <td>#{log.entity_id}</td>
                    <td>
                      <span className="nmbp-badge-status nmbp-badge-confirmed" style={{ fontSize: '11px' }}>
                        {log.action}
                      </span>
                    </td>
                    <td>{log.user_role} {log.user_id ? `(User #${log.user_id})` : ''}</td>
                    <td><code>{log.ip_address || '127.0.0.1'}</code></td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

