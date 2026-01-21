import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './Edit';
import Save from './Save';
import './styles.css';

registerBlockType('paythefly/payment-widget', {
  title: __('PayTheFly Payment', 'paythefly'),
  description: __('Accept cryptocurrency payments with PayTheFly.', 'paythefly'),
  category: 'widgets',
  icon: 'money-alt',
  keywords: [__('payment', 'paythefly'), __('crypto', 'paythefly'), __('bitcoin', 'paythefly')],
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
