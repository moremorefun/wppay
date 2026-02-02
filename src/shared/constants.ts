/**
 * Network configurations for supported blockchains.
 */
export const NETWORKS = {
  TRC20: {
    chainId: 728126428,
    name: 'TRON (TRC20)',
    usdtAddress: 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
  },
  BEP20: {
    chainId: 56,
    name: 'BNB (BEP20)',
    usdtAddress: '0x55d398326f99059fF775485246999027B3197955',
  },
} as const;

export type NetworkKey = keyof typeof NETWORKS;

/**
 * PayTheFly Pro payment base URL.
 */
export const PAYTHEFLY_PAY_URL = 'https://pro.paythefly.com/pay';

/**
 * Default currency.
 */
export const DEFAULT_CURRENCY = 'USDT';
