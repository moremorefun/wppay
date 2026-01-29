import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './Edit';
import Save from './Save';
import './styles.css';

registerBlockType('paythefly/payment-widget', {
  title: __('PayTheFly Payment', 'paythefly-crypto-gateway'),
  description: __('Accept cryptocurrency payments with PayTheFly.', 'paythefly-crypto-gateway'),
  category: 'widgets',
  icon: 'money-alt',
  keywords: [
    __('payment', 'paythefly-crypto-gateway'),
    __('crypto', 'paythefly-crypto-gateway'),
    __('bitcoin', 'paythefly-crypto-gateway'),
  ],
  attributes: {
    amount: {
      type: 'string',
      default: '',
    },
    currency: {
      type: 'string',
      default: 'USDT',
    },
    description: {
      type: 'string',
      default: '',
    },
  },
  edit: Edit,
  save: Save,
});
