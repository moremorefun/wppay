import { __ } from '@wordpress/i18n';
import Settings from './components/Settings';

export default function App() {
  return (
    <div className="paythefly-admin">
      <header className="paythefly-admin__header">
        <h1>{__('PayTheFly', 'paythefly-crypto-gateway')}</h1>
      </header>

      <main className="paythefly-admin__content">
        <Settings />
      </main>
    </div>
  );
}
