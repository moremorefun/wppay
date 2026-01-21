import { describe, it, expect } from 'vitest';
import { formatCurrency, validateAmount } from '@shared/utils';

describe('formatCurrency', () => {
  it('formats a number with currency', () => {
    expect(formatCurrency(100, 'USDT')).toBe('100.00 USDT');
  });

  it('formats a string amount with currency', () => {
    expect(formatCurrency('50.5', 'BTC')).toBe('50.50 BTC');
  });

  it('handles decimal precision', () => {
    expect(formatCurrency(99.999, 'ETH')).toBe('100.00 ETH');
  });
});

describe('validateAmount', () => {
  it('returns true for valid positive amounts', () => {
    expect(validateAmount('100')).toBe(true);
    expect(validateAmount('0.01')).toBe(true);
  });

  it('returns false for zero', () => {
    expect(validateAmount('0')).toBe(false);
  });

  it('returns false for negative amounts', () => {
    expect(validateAmount('-10')).toBe(false);
  });

  it('returns false for invalid strings', () => {
    expect(validateAmount('abc')).toBe(false);
    expect(validateAmount('')).toBe(false);
  });
});
