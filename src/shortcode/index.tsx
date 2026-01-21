import { createRoot } from 'react-dom/client';
import PaymentWidget from './PaymentWidget';
import './styles.css';

// Initialize all payment widgets on the page
document.addEventListener('DOMContentLoaded', () => {
  const widgets = document.querySelectorAll<HTMLElement>('.paythefly-widget');

  widgets.forEach((container) => {
    const amount = container.dataset.amount ?? '';
    const currency = container.dataset.currency ?? 'USDT';
    const description = container.dataset.description ?? '';

    const root = createRoot(container);
    root.render(
      <PaymentWidget amount={amount} currency={currency} description={description} />
    );
  });

  // Initialize payment buttons
  const buttons = document.querySelectorAll<HTMLElement>('.paythefly-button');

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const amount = button.dataset.amount ?? '';
      const currency = button.dataset.currency ?? 'USDT';

      // Create modal container
      const modal = document.createElement('div');
      modal.className = 'paythefly-modal';
      document.body.appendChild(modal);

      const root = createRoot(modal);
      root.render(
        <PaymentWidget
          amount={amount}
          currency={currency}
          description=""
          isModal={true}
          onClose={() => {
            root.unmount();
            modal.remove();
          }}
        />
      );
    });
  });
});
