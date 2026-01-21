import { createRoot } from 'react-dom/client';
import App from './App';
import './styles.css';

const container = document.getElementById('paythefly-admin-app');
if (container) {
  const root = createRoot(container);
  root.render(<App />);
}
