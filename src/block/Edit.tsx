import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl } from '@wordpress/components';

interface EditProps {
  attributes: {
    amount: string;
    currency: string;
    description: string;
  };
  setAttributes: (attrs: Partial<EditProps['attributes']>) => void;
}

const CURRENCY_OPTIONS = [
  { label: 'USDT', value: 'USDT' },
  { label: 'USDC', value: 'USDC' },
  { label: 'BTC', value: 'BTC' },
  { label: 'ETH', value: 'ETH' },
];

export default function Edit({ attributes, setAttributes }: EditProps) {
  const { amount, currency, description } = attributes;
  const blockProps = useBlockProps({ className: 'paythefly-block-editor' });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Payment Settings', 'paythefly-crypto-gateway')}>
          <TextControl
            label={__('Amount', 'paythefly-crypto-gateway')}
            value={amount}
            onChange={(value: string) => setAttributes({ amount: value })}
            placeholder={__('Enter amount', 'paythefly-crypto-gateway')}
          />
          <SelectControl
            label={__('Currency', 'paythefly-crypto-gateway')}
            value={currency}
            options={CURRENCY_OPTIONS}
            onChange={(value: string) => setAttributes({ currency: value })}
          />
          <TextControl
            label={__('Description', 'paythefly-crypto-gateway')}
            value={description}
            onChange={(value: string) => setAttributes({ description: value })}
            placeholder={__('Payment description', 'paythefly-crypto-gateway')}
          />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <div className="paythefly-block-preview">
          <div className="paythefly-block-preview__icon">💰</div>
          <div className="paythefly-block-preview__title">
            {__('PayTheFly Payment', 'paythefly-crypto-gateway')}
          </div>
          <div className="paythefly-block-preview__info">
            {amount ? (
              <span>
                {amount} {currency}
              </span>
            ) : (
              <span className="paythefly-block-preview__placeholder">
                {__('Configure payment in sidebar', 'paythefly-crypto-gateway')}
              </span>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
