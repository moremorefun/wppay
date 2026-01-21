import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Dashboard from './components/Dashboard';
import Settings from './components/Settings';

type Tab = 'dashboard' | 'settings';

interface PaymentStats {
  total: number;
  completed: number;
  pending: number;
  failed: number;
}

export default function App() {
  const [activeTab, setActiveTab] = useState<Tab>('dashboard');
  const [stats, setStats] = useState<PaymentStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadStats();
  }, []);

  const loadStats = async () => {
    try {
      setLoading(true);
      const response = await apiFetch<{ payments: unknown[] }>({
        path: '/paythefly/v1/payments',
      });
      // Calculate stats from payments
      setStats({
        total: response.payments?.length ?? 0,
        completed: 0,
        pending: 0,
        failed: 0,
      });
    } catch (error) {
      console.error('Failed to load stats:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="paythefly-admin">
      <header className="paythefly-admin__header">
        <h1>{__('PayTheFly', 'paythefly')}</h1>
        <nav className="paythefly-admin__nav">
          <button
            className={`paythefly-admin__nav-item ${activeTab === 'dashboard' ? 'is-active' : ''}`}
            onClick={() => setActiveTab('dashboard')}
          >
            {__('Dashboard', 'paythefly')}
          </button>
          <button
            className={`paythefly-admin__nav-item ${activeTab === 'settings' ? 'is-active' : ''}`}
            onClick={() => setActiveTab('settings')}
          >
            {__('Settings', 'paythefly')}
          </button>
        </nav>
      </header>

      <main className="paythefly-admin__content">
        {activeTab === 'dashboard' && <Dashboard stats={stats} loading={loading} />}
        {activeTab === 'settings' && <Settings />}
      </main>
    </div>
  );
}
