import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { ShadowContainer } from './ShadowContainer';
import { DonationModal } from './DonationModal';

interface DonationButtonProps {
  label?: string;
  className?: string;
}

export function DonationButton({
  label = __('Support', 'paythefly-crypto-gateway'),
  className = '',
}: DonationButtonProps) {
  const [isModalOpen, setIsModalOpen] = useState(false);

  const config = window.paytheflyFrontend;
  const recipientName = config?.recipientName || '';
  const recipientAvatar = config?.recipientAvatar || '';

  return (
    <>
      <button
        className={`paythefly-donation-btn ${className}`}
        onClick={() => setIsModalOpen(true)}
      >
        {label}
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
