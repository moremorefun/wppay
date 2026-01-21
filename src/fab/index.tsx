import { createRoot } from 'react-dom/client';
import { FloatingButton } from './FloatingButton';
import './styles.css';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
  // Check if FAB is enabled
  const config = window.paytheflyFrontend;
  if (!config?.fabEnabled) {
    return;
  }

  // Create FAB container
  const container = document.createElement('div');
  container.id = 'paythefly-fab-container';
  document.body.appendChild(container);

  // Render FAB
  const root = createRoot(container);
  root.render(<FloatingButton />);
});
