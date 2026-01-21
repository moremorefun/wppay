import type { NetworkKey } from './constants';

/**
 * Frontend configuration injected by WordPress.
 */
export interface PayTheFlyConfig {
  apiUrl: string;
  nonce: string;
  projectId: string;
  brand: string;
  fabEnabled: boolean;
  recipientName: string;
  recipientAvatar: string;
}

/**
 * Order creation response from API.
 */
export interface CreateOrderResponse {
  serialNo: string;
  projectId: string;
  brand: string;
  token: string;
  redirect: string;
}

/**
 * Donation modal props.
 */
export interface DonationModalProps {
  isOpen: boolean;
  onClose: () => void;
  recipientName: string;
  recipientAvatar: string;
}

/**
 * Network selection state.
 */
export interface NetworkState {
  key: NetworkKey;
  chainId: number;
}
