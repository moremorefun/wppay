import { __ } from '@wordpress/i18n';

interface PaymentStats {
  total: number;
  completed: number;
  pending: number;
  failed: number;
}

interface DashboardProps {
  stats: PaymentStats | null;
  loading: boolean;
}

export default function Dashboard({ stats, loading }: DashboardProps) {
  if (loading) {
    return (
      <div className="paythefly-loading">
        <div className="paythefly-loading__spinner" />
      </div>
    );
  }

  return (
    <div className="paythefly-dashboard">
      <h2>{__('Dashboard', 'paythefly')}</h2>

      <div className="paythefly-dashboard__stats">
        <div className="paythefly-stat-card">
          <div className="paythefly-stat-card__value">{stats?.total ?? 0}</div>
          <div className="paythefly-stat-card__label">{__('Total Payments', 'paythefly')}</div>
        </div>
        <div className="paythefly-stat-card">
          <div className="paythefly-stat-card__value">{stats?.completed ?? 0}</div>
          <div className="paythefly-stat-card__label">{__('Completed', 'paythefly')}</div>
        </div>
        <div className="paythefly-stat-card">
          <div className="paythefly-stat-card__value">{stats?.pending ?? 0}</div>
          <div className="paythefly-stat-card__label">{__('Pending', 'paythefly')}</div>
        </div>
        <div className="paythefly-stat-card">
          <div className="paythefly-stat-card__value">{stats?.failed ?? 0}</div>
          <div className="paythefly-stat-card__label">{__('Failed', 'paythefly')}</div>
        </div>
      </div>

      <div className="paythefly-dashboard__recent">
        <h3>{__('Recent Payments', 'paythefly')}</h3>
        <p>{__('No payments yet.', 'paythefly')}</p>
      </div>
    </div>
  );
}
