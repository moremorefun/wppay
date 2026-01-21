import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { ShadowContainer } from '../shared/components/ShadowContainer';
import { DonationModal } from '../shared/components/DonationModal';

export function FloatingButton() {
  const [isModalOpen, setIsModalOpen] = useState(false);

  const config = window.paytheflyFrontend;
  const recipientName = config?.recipientName || '';
  const recipientAvatar = config?.recipientAvatar || '';

  return (
    <>
      <button
        className="paythefly-fab"
        onClick={() => setIsModalOpen(true)}
        aria-label={__('Support', 'paythefly')}
        title={__('Support', 'paythefly')}
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
        </svg>
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
