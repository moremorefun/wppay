import { useBlockProps } from '@wordpress/block-editor';

interface SaveProps {
  attributes: {
    amount: string;
    currency: string;
    description: string;
  };
}

export default function Save({ attributes }: SaveProps) {
  const { amount, currency, description } = attributes;
  const blockProps = useBlockProps.save({ className: 'paythefly-widget' });

  return (
    <div
      {...blockProps}
      data-amount={amount}
      data-currency={currency}
      data-description={description}
    />
  );
}
