import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { DonationButton } from '@shared/components/DonationButton';

// Mock ShadowContainer to avoid shadow DOM complexity in tests
vi.mock('@shared/components/ShadowContainer', () => ({
  ShadowContainer: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="shadow-container">{children}</div>
  ),
}));

// Mock DonationModal
vi.mock('@shared/components/DonationModal', () => ({
  DonationModal: ({
    isOpen,
    onClose,
    recipientName,
    recipientAvatar,
  }: {
    isOpen: boolean;
    onClose: () => void;
    recipientName: string;
    recipientAvatar: string;
  }) =>
    isOpen ? (
      <div data-testid="donation-modal">
        <span data-testid="recipient-name">{recipientName}</span>
        <span data-testid="recipient-avatar">{recipientAvatar}</span>
        <button data-testid="close-modal" onClick={onClose}>
          Close
        </button>
      </div>
    ) : null,
}));

describe('DonationButton', () => {
  beforeEach(() => {
    // Setup default paytheflyFrontend config
    globalThis.paytheflyFrontend = {
      apiUrl: 'http://localhost:8888/wp-json/paythefly/v1',
      nonce: 'test-nonce',
      recipientName: 'Test Recipient',
      recipientAvatar: 'https://example.com/avatar.jpg',
    } as typeof globalThis.paytheflyFrontend;
  });

  it('renders with default label', () => {
    render(<DonationButton />);

    const button = screen.getByRole('button');
    expect(button).toBeInTheDocument();
    expect(button).toHaveTextContent('Support');
  });

  it('renders with custom label', () => {
    render(<DonationButton label="Donate Now" />);

    const button = screen.getByRole('button');
    expect(button).toHaveTextContent('Donate Now');
  });

  it('renders with custom className', () => {
    render(<DonationButton className="custom-class" />);

    const button = screen.getByRole('button');
    expect(button).toHaveClass('custom-class');
    expect(button).toHaveClass('paythefly-donation-btn');
  });

  it('opens modal on click', () => {
    render(<DonationButton />);

    const button = screen.getByRole('button', { name: 'Support' });
    expect(screen.queryByTestId('donation-modal')).not.toBeInTheDocument();

    fireEvent.click(button);

    expect(screen.getByTestId('donation-modal')).toBeInTheDocument();
  });

  it('passes recipient info to modal', () => {
    render(<DonationButton />);

    const button = screen.getByRole('button');
    fireEvent.click(button);

    expect(screen.getByTestId('recipient-name')).toHaveTextContent('Test Recipient');
    expect(screen.getByTestId('recipient-avatar')).toHaveTextContent('https://example.com/avatar.jpg');
  });

  it('closes modal when onClose is called', () => {
    render(<DonationButton />);

    // Open modal
    const button = screen.getByRole('button', { name: 'Support' });
    fireEvent.click(button);
    expect(screen.getByTestId('donation-modal')).toBeInTheDocument();

    // Close modal
    const closeButton = screen.getByTestId('close-modal');
    fireEvent.click(closeButton);

    expect(screen.queryByTestId('donation-modal')).not.toBeInTheDocument();
  });

  it('handles missing recipient info gracefully', () => {
    globalThis.paytheflyFrontend = {
      apiUrl: 'http://localhost:8888/wp-json/paythefly/v1',
      nonce: 'test-nonce',
    } as typeof globalThis.paytheflyFrontend;

    render(<DonationButton />);

    const button = screen.getByRole('button');
    fireEvent.click(button);

    expect(screen.getByTestId('recipient-name')).toHaveTextContent('');
    expect(screen.getByTestId('recipient-avatar')).toHaveTextContent('');
  });

  it('handles undefined paytheflyFrontend', () => {
    // @ts-expect-error Testing undefined config
    globalThis.paytheflyFrontend = undefined;

    render(<DonationButton />);

    const button = screen.getByRole('button');
    fireEvent.click(button);

    // Should still render without crashing
    expect(screen.getByTestId('donation-modal')).toBeInTheDocument();
  });
});
