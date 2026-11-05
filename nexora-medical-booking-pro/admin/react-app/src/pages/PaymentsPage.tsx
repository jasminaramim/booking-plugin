import React, { useEffect, useState } from 'react';
import { api } from '../api/client';
import { Payment } from '../types';
import { Header } from '../components/Header';

export const PaymentsPage: React.FC = () => {
  const [payments, setPayments] = useState<Payment[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [actionLoading, setActionLoading] = useState<number | null>(null);

  const loadPayments = async () => {
    try {
      setLoading(true);
      const res = await api.get<{ items: Payment[] }>('/payments', { status: statusFilter });
      setPayments(res.items || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadPayments();
  }, [statusFilter]);

  const handleVerify = async (paymentId: number) => {
    if (!confirm('Verify and mark this payment as PAID in cash/desk?')) return;
    try {
      setActionLoading(paymentId);
      await api.post(`/payments/${paymentId}/verify`);
      alert('Payment verified successfully!');
      await loadPayments();
    } catch (err: any) {
      alert(err.message || 'Payment verification failed');
    } finally {
      setActionLoading(null);
    }
  };

  const handleRefund = async (paymentId: number) => {
    const reason = prompt('Enter refund reason:');
    if (!reason) return;
    try {
      setActionLoading(paymentId);
      await api.post(`/payments/${paymentId}/refund`, { reason });
      alert('Payment refunded successfully.');
      await loadPayments();
    } catch (err: any) {
      alert(err.message || 'Refund failed');
    } finally {
      setActionLoading(null);
    }
  };

  return (
    <div>
      <Header
        title="Payment & Billing Records"
        subtitle="Track online transactions, manual cash receipts and refund logs"
      >
        <button className="nmbp-adm-btn nmbp-adm-btn-outline" onClick={loadPayments}>
          Refresh
        </button>
      </Header>

      <div className="nmbp-content-body">
        <div className="nmbp-card-table" style={{ padding: '16px 20px', marginBottom: '20px' }}>
          <div style={{ display: 'flex', gap: '12px', alignItems: 'center' }}>
            <strong>Filter by Status:</strong>
            <select
              className="nmbp-search-input"
              style={{ width: '160px' }}
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
            >
              <option value="">All Statuses</option>
              <option value="paid">Paid</option>
              <option value="pending">Pending</option>
              <option value="unpaid">Unpaid</option>
              <option value="refunded">Refunded</option>
            </select>
          </div>
        </div>

        <div className="nmbp-card-table">
          <div className="nmbp-table-header">
            <h3>Transactions ({payments.length})</h3>
          </div>
          <table className="nmbp-ui-table">
            <thead>
              <tr>
                <th>Receipt #</th>
                <th>Appointment #</th>
                <th>Patient</th>
                <th>Amount</th>
                <th>Gateway</th>
                <th>Transaction ID</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={8} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    Loading payment records...
                  </td>
                </tr>
              ) : payments.length === 0 ? (
                <tr>
                  <td colSpan={8} style={{ textAlign: 'center', padding: '40px', color: '#64748b' }}>
                    No payment records found.
                  </td>
                </tr>
              ) : (
                payments.map((p) => (
                  <tr key={p.id}>
                    <td><strong>{p.payment_number}</strong></td>
                    <td>{p.appointment_number || 'N/A'}</td>
                    <td>{p.patient_first_name} {p.patient_last_name}</td>
                    <td style={{ fontWeight: 700, color: '#0f172a' }}>
                      ${parseFloat(String(p.amount)).toFixed(2)} {p.currency}
                    </td>
                    <td>
                      <code style={{ fontSize: '11px', background: '#f1f5f9', padding: '2px 6px', borderRadius: '4px' }}>
                        {p.gateway}
                      </code>
                    </td>
                    <td>{p.transaction_id || <span style={{ color: '#94a3b8' }}>None</span>}</td>
                    <td>
                      <span className={`nmbp-badge-status nmbp-badge-${p.payment_status}`}>
                        {p.payment_status}
                      </span>
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '6px' }}>
                        {p.payment_status !== 'paid' && p.payment_status !== 'refunded' && (
                          <button
                            className="nmbp-adm-btn nmbp-adm-btn-primary nmbp-adm-btn-sm"
                            disabled={actionLoading === p.id}
                            onClick={() => handleVerify(p.id)}
                          >
                            Verify Paid
                          </button>
                        )}
                        {p.payment_status === 'paid' && (
                          <button
                            className="nmbp-adm-btn nmbp-adm-btn-danger nmbp-adm-btn-sm"
                            disabled={actionLoading === p.id}
                            onClick={() => handleRefund(p.id)}
                          >
                            Refund
                          </button>
                        )}
                      </div>
                    </td>
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

