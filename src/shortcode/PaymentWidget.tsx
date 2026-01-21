import { useState } from 'react';
import { __ } from '@wordpress/i18n';

interface PaymentWidgetProps {
  amount: string;
  currency: string;
  description: string;
  isModal?: boolean;
  onClose?: () => void;
}

type PaymentStatus = 'idle' | 'loading' | 'pending' | 'completed' | 'failed';

export default function PaymentWidget({
  amount,
  currency,
  description,
  isModal = false,
  onClose,
}: PaymentWidgetProps) {
  const [status, setStatus] = useState<PaymentStatus>('idle');
  const [paymentAmount, setPaymentAmount] = useState(amount);
  const [error, setError] = useState<string | null>(null);

  const handlePayment = async () => {
    if (!paymentAmount) {
      setError(__('Please enter an amount', 'paythefly'));
      return;
    }

    setStatus('loading');
    setError(null);

    try {
      const response = await fetch('/wp-json/paythefly/v1/payments/create', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          amount: paymentAmount,
          currency,
          description,
        }),
      });

      if (!response.ok) {
        throw new Error(__('Payment creation failed', 'paythefly'));
      }

      const data = await response.json();
      setStatus('pending');

      // TODO: Handle payment flow (show QR code, redirect, etc.)
      console.log('Payment created:', data);
    } catch (err) {
      setError(err instanceof Error ? err.message : __('An error occurred', 'paythefly'));
      setStatus('failed');
    }
  };

  const containerClass = isModal
    ? 'paythefly-payment paythefly-payment--modal'
    : 'paythefly-payment';

  return (
    <div className={containerClass}>
      {isModal && (
        <div className="paythefly-payment__overlay" onClick={onClose} />
      )}

      <div className="paythefly-payment__content">
        {isModal && (
          <button className="paythefly-payment__close" onClick={onClose}>
            ×
          </button>
        )}

        <h3 className="paythefly-payment__title">
          {__('Pay with Cryptocurrency', 'paythefly')}
        </h3>

        {status === 'idle' && (
          <div className="paythefly-payment__form">
            {!amount && (
              <div className="paythefly-payment__field">
                <label>{__('Amount', 'paythefly')}</label>
                <input
                  type="number"
                  value={paymentAmount}
                  onChange={(e) => setPaymentAmount(e.target.value)}
                  placeholder="0.00"
                  step="0.01"
                />
                <span className="paythefly-payment__currency">{currency}</span>
              </div>
            )}

            {amount && (
              <div className="paythefly-payment__amount">
                <span className="paythefly-payment__amount-value">{amount}</span>
                <span className="paythefly-payment__amount-currency">{currency}</span>
              </div>
            )}

            {error && <div className="paythefly-payment__error">{error}</div>}

            <button
              className="paythefly-payment__submit"
              onClick={handlePayment}
            >
              {__('Pay Now', 'paythefly')}
            </button>
          </div>
        )}

        {status === 'loading' && (
          <div className="paythefly-payment__loading">
            <div className="paythefly-payment__spinner" />
            <p>{__('Creating payment...', 'paythefly')}</p>
          </div>
        )}

        {status === 'pending' && (
          <div className="paythefly-payment__pending">
            <p>{__('Payment pending. Please complete the transaction.', 'paythefly')}</p>
          </div>
        )}

        {status === 'completed' && (
          <div className="paythefly-payment__success">
            <p>{__('Payment completed successfully!', 'paythefly')}</p>
          </div>
        )}

        {status === 'failed' && (
          <div className="paythefly-payment__failed">
            <p>{error || __('Payment failed. Please try again.', 'paythefly')}</p>
            <button
              className="paythefly-payment__retry"
              onClick={() => setStatus('idle')}
            >
              {__('Try Again', 'paythefly')}
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
