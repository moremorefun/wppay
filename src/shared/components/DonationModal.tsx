import { useState, useEffect, useCallback, useRef } from 'react';
import { __ } from '@wordpress/i18n';
import { NETWORKS, PAYTHEFLY_PAY_URL, type NetworkKey } from '../constants';
import type { CreateOrderResponse } from '../types';

interface DonationModalProps {
  isOpen: boolean;
  onClose: () => void;
  recipientName: string;
  recipientAvatar: string;
}

export function DonationModal({
  isOpen,
  onClose,
  recipientName,
  recipientAvatar,
}: DonationModalProps) {
  const [amount, setAmount] = useState('');
  const [selectedNetwork, setSelectedNetwork] = useState<NetworkKey>('TRC20');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const cardRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  // Focus input when modal opens
  useEffect(() => {
    if (isOpen && inputRef.current) {
      setTimeout(() => inputRef.current?.focus(), 100);
    }
  }, [isOpen]);

  // Handle ESC key
  useEffect(() => {
    if (!isOpen) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  // Prevent body scroll when modal is open
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [isOpen]);

  const shakeCard = useCallback(() => {
    if (cardRef.current) {
      cardRef.current.classList.add('ptf-shake');
      setTimeout(() => cardRef.current?.classList.remove('ptf-shake'), 500);
    }
  }, []);

  const handlePay = async () => {
    // Validate amount
    const numAmount = parseFloat(amount);
    if (!amount || isNaN(numAmount) || numAmount <= 0) {
      shakeCard();
      setError(__('Please enter a valid amount', 'paythefly-crypto-gateway'));
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const config = window.paytheflyFrontend;
      if (!config?.apiUrl) {
        throw new Error(__('Configuration error', 'paythefly-crypto-gateway'));
      }

      const response = await fetch(`${config.apiUrl}/orders/create`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': config.nonce,
        },
        body: JSON.stringify({
          amount,
          chainId: NETWORKS[selectedNetwork].chainId,
          redirect: window.location.href,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(
          errorData.message || __('Failed to create order', 'paythefly-crypto-gateway')
        );
      }

      const data: CreateOrderResponse = await response.json();

      // Build payment URL
      const payUrl = new URL(PAYTHEFLY_PAY_URL);
      payUrl.searchParams.set('chainId', NETWORKS[selectedNetwork].chainId.toString());
      payUrl.searchParams.set('projectId', data.projectId);
      payUrl.searchParams.set('amount', amount);
      payUrl.searchParams.set('serialNo', data.serialNo);
      payUrl.searchParams.set('token', data.token);
      if (data.redirect) {
        payUrl.searchParams.set('redirect', data.redirect);
      }
      if (data.brand) {
        payUrl.searchParams.set('brand', data.brand);
      }
      payUrl.searchParams.set('in_wallet', '1');

      // Redirect to payment page
      window.location.href = payUrl.toString();
    } catch (err) {
      setError(
        err instanceof Error ? err.message : __('An error occurred', 'paythefly-crypto-gateway')
      );
      setIsLoading(false);
    }
  };

  const handleOverlayClick = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget) {
      onClose();
    }
  };

  if (!isOpen) {
    return null;
  }

  return (
    // eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
    <div className="ptf-overlay" onClick={handleOverlayClick}>
      <div className="ptf-bg-glow" />

      <div className="ptf-card" ref={cardRef}>
        <button
          className="ptf-close"
          onClick={onClose}
          aria-label={__('Close', 'paythefly-crypto-gateway')}
        >
          ×
        </button>

        <div className="ptf-header">
          <div className="ptf-avatar-ring">
            {recipientAvatar ? (
              <img src={recipientAvatar} alt={recipientName} className="ptf-avatar" />
            ) : (
              <div className="ptf-avatar ptf-avatar-placeholder" />
            )}
          </div>
          <h2 className="ptf-name">
            {recipientName || __('Anonymous', 'paythefly-crypto-gateway')}
          </h2>
          <p className="ptf-subtitle">
            {__('Thank you for your support', 'paythefly-crypto-gateway')}
          </p>
        </div>

        <div className="ptf-section">
          <label htmlFor="ptf-amount" className="ptf-label">
            {__('Enter Amount', 'paythefly-crypto-gateway')}
          </label>
          <div className="ptf-input-box">
            <input
              ref={inputRef}
              type="number"
              id="ptf-amount"
              className="ptf-input"
              placeholder="0.00"
              value={amount}
              onChange={(e) => {
                setAmount(e.target.value);
                setError(null);
              }}
              min="0"
              step="0.01"
            />
            <span className="ptf-currency">USDT</span>
          </div>
        </div>

        <div className="ptf-section">
          <span className="ptf-label">{__('Select Network', 'paythefly-crypto-gateway')}</span>
          <div className="ptf-chain-grid">
            {(Object.keys(NETWORKS) as NetworkKey[]).map((key) => (
              <button
                key={key}
                className={`ptf-chain-btn ${selectedNetwork === key ? 'active' : ''}`}
                onClick={() => setSelectedNetwork(key)}
              >
                {NETWORKS[key].name}
              </button>
            ))}
          </div>
        </div>

        {error && <div className="ptf-error">{error}</div>}

        <button className="ptf-pay-btn" onClick={handlePay} disabled={isLoading}>
          {isLoading ? (
            <span className="ptf-loading">
              <span className="ptf-spinner" />
              {__('Processing…', 'paythefly-crypto-gateway')}
            </span>
          ) : (
            <>
              <span>{__('Pay Now', 'paythefly-crypto-gateway')}</span>
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="3"
                strokeLinecap="round"
                strokeLinejoin="round"
              >
                <path d="M5 12h14M12 5l7 7-7 7" />
              </svg>
            </>
          )}
        </button>

        <div className="ptf-footer">
          <p className="ptf-footer-text">PayTheFly.com</p>
        </div>
      </div>
    </div>
  );
}
