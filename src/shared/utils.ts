// Shared utilities and components
export const formatCurrency = (amount: string | number, currency: string): string => {
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
  return `${numAmount.toFixed(2)} ${currency}`;
};

export const validateAmount = (amount: string): boolean => {
  const num = parseFloat(amount);
  return !isNaN(num) && num > 0;
};
