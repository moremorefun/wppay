import { createRoot } from 'react-dom/client';
import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { ShadowContainer } from '../shared/components/ShadowContainer';
import { DonationModal } from '../shared/components/DonationModal';
import './styles.css';

// Inline button component that opens the donation modal
function InlineButton() {
  const [isModalOpen, setIsModalOpen] = useState(false);

  const config = window.paytheflyFrontend;
  const recipientName = config?.recipientName || '';
  const recipientAvatar = config?.recipientAvatar || '';

  return (
    <>
      <button className="paythefly-inline-button" onClick={() => setIsModalOpen(true)}>
        {__('Support the Author', 'paythefly-crypto-gateway')}
      </button>

      {isModalOpen && (
        <ShadowContainer>
          <DonationModal
            isOpen={isModalOpen}
            onClose={() => setIsModalOpen(false)}
            recipientName={recipientName}
            recipientAvatar={recipientAvatar}
          />
        </ShadowContainer>
      )}
    </>
  );
}

// Initialize all payment widgets on the page
document.addEventListener('DOMContentLoaded', () => {
  // Initialize inline buttons (from content filter)
  const inlineButtons = document.querySelectorAll<HTMLElement>('.paythefly-inline-button-wrapper');
  inlineButtons.forEach((container) => {
    const root = createRoot(container);
    root.render(<InlineButton />);
  });

  // Initialize payment buttons (from shortcode)
  const buttons = document.querySelectorAll<HTMLElement>('.paythefly-button');
  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const config = window.paytheflyFrontend;

      // Create modal container
      const modalHost = document.createElement('div');
      modalHost.className = 'paythefly-modal-host';
      document.body.appendChild(modalHost);

      const root = createRoot(modalHost);

      const closeModal = () => {
        root.unmount();
        modalHost.remove();
      };

      root.render(
        <ShadowContainer>
          <DonationModal
            isOpen={true}
            onClose={closeModal}
            recipientName={config?.recipientName || ''}
            recipientAvatar={config?.recipientAvatar || ''}
          />
        </ShadowContainer>
      );
    });
  });
});
