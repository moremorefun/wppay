import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { DonationModal } from '@shared/components/DonationModal';
import { server } from '../setup';
import { http, HttpResponse } from 'msw';

describe('DonationModal', () => {
  const defaultProps = {
    isOpen: true,
    onClose: vi.fn(),
    recipientName: 'Test Author',
    recipientAvatar: 'https://example.com/avatar.jpg',
  };

  beforeEach(() => {
    vi.clearAllMocks();
    globalThis.paytheflyFrontend = {
      apiUrl: 'http://localhost:8888/wp-json/paythefly/v1',
      nonce: 'test-nonce',
    };
  });

  afterEach(() => {
    document.body.style.overflow = '';
  });

  describe('Rendering', () => {
    it('renders when isOpen is true', () => {
      render(<DonationModal {...defaultProps} />);

      expect(screen.getByText('Test Author')).toBeInTheDocument();
      expect(screen.getByPlaceholderText('0.00')).toBeInTheDocument();
    });

    it('does not render when isOpen is false', () => {
      render(<DonationModal {...defaultProps} isOpen={false} />);

      expect(screen.queryByText('Test Author')).not.toBeInTheDocument();
    });

    it('displays recipient name', () => {
      render(<DonationModal {...defaultProps} recipientName="Jane Doe" />);

      expect(screen.getByText('Jane Doe')).toBeInTheDocument();
    });

    it('displays Anonymous when no name provided', () => {
      render(<DonationModal {...defaultProps} recipientName="" />);

      expect(screen.getByText('Anonymous')).toBeInTheDocument();
    });

    it('displays recipient avatar when provided', () => {
      render(<DonationModal {...defaultProps} />);

      const avatar = screen.getByAltText('Test Author');
      expect(avatar).toBeInTheDocument();
      expect(avatar).toHaveAttribute('src', 'https://example.com/avatar.jpg');
    });

    it('displays placeholder when no avatar provided', () => {
      render(<DonationModal {...defaultProps} recipientAvatar="" />);

      expect(screen.queryByRole('img')).not.toBeInTheDocument();
      expect(screen.getByText('Test Author')).toBeInTheDocument();
    });
  });

  describe('Form Validation', () => {
    it('validates empty amount', async () => {
      render(<DonationModal {...defaultProps} />);

      const payButton = screen.getByRole('button', { name: /pay now/i });
      fireEvent.click(payButton);

      await waitFor(() => {
        expect(screen.getByText(/please enter a valid amount/i)).toBeInTheDocument();
      });
    });

    it('validates invalid amount', async () => {
      render(<DonationModal {...defaultProps} />);

      const input = screen.getByPlaceholderText('0.00');
      await userEvent.type(input, 'abc');

      const payButton = screen.getByRole('button', { name: /pay now/i });
      fireEvent.click(payButton);

      await waitFor(() => {
        expect(screen.getByText(/please enter a valid amount/i)).toBeInTheDocument();
      });
    });

    it('validates zero amount', async () => {
      render(<DonationModal {...defaultProps} />);

      const input = screen.getByPlaceholderText('0.00');
      await userEvent.type(input, '0');

      const payButton = screen.getByRole('button', { name: /pay now/i });
      fireEvent.click(payButton);

      await waitFor(() => {
        expect(screen.getByText(/please enter a valid amount/i)).toBeInTheDocument();
      });
    });

    it('validates negative amount', async () => {
      render(<DonationModal {...defaultProps} />);

      const input = screen.getByPlaceholderText('0.00');
      await userEvent.type(input, '-10');

      const payButton = screen.getByRole('button', { name: /pay now/i });
      fireEvent.click(payButton);

      await waitFor(() => {
        expect(screen.getByText(/please enter a valid amount/i)).toBeInTheDocument();
      });
    });

    it('clears error when user types', async () => {
      render(<DonationModal {...defaultProps} />);

      const payButton = screen.getByRole('button', { name: /pay now/i });
      fireEvent.click(payButton);

      await waitFor(() => {
        expect(screen.getByText(/please enter a valid amount/i)).toBeInTheDocument();
      });

      const input = screen.getByPlaceholderText('0.00');
      await userEvent.type(input, '10');

      expect(screen.queryByText(/please enter a valid amount/i)).not.toBeInTheDocument();
    });
  });

  describe('Network Selection', () => {
    it('has TRC20 selected by default', () => {
      render(<DonationModal {...defaultProps} />);

      const trc20Button = screen.getByRole('button', { name: /tron/i });
      expect(trc20Button).toHaveClass('active');
    });

    it('can switch to BEP20 network', async () => {
      render(<DonationModal {...defaultProps} />);

      const bep20Button = screen.getByRole('button', { name: /bnb/i });
      fireEvent.click(bep20Button);

      expect(bep20Button).toHaveClass('active');

      const trc20Button = screen.getByRole('button', { name: /tron/i });
      expect(trc20Button).not.toHaveClass('active');
    });
  });

  describe('Modal Closing', () => {
    it('closes on ESC key', () => {
      render(<DonationModal {...defaultProps} />);

      fireEvent.keyDown(document, { key: 'Escape' });

      expect(defaultProps.onClose).toHaveBeenCalledTimes(1);
    });

    it('closes on overlay click', () => {
      render(<DonationModal {...defaultProps} />);

      const overlay = document.querySelector('.ptf-overlay');
      if (overlay) {
        fireEvent.click(overlay);
        expect(defaultProps.onClose).toHaveBeenCalledTimes(1);
      }
    });

    it('does not close when clicking inside card', () => {
      render(<DonationModal {...defaultProps} />);

      const card = document.querySelector('.ptf-card');
      if (card) {
        fireEvent.click(card);
        expect(defaultProps.onClose).not.toHaveBeenCalled();
      }
    });

    it('closes on close button click', () => {
      render(<DonationModal {...defaultProps} />);

      const closeButton = screen.getByLabelText('Close');
      fireEvent.click(closeButton);

      expect(defaultProps.onClose).toHaveBeenCalledTimes(1);
    });
  });

  describe('API Integration', () => {
    it('shows loading state during submit', async () => {
      render(<DonationModal {...defaultProps} />);

      const input = screen.getByPlaceholderText('0.00');
      await userEvent.type(input, '50');

      const payButton = screen.getByRole('button', { name: /pay now/i });
      fireEvent.click(payButton);

      await waitFor(() => {
        expect(screen.getByText(/processing/i)).toBeInTheDocument();
      });
    });

    it('calls API with correct parameters', async () => {
      let capturedRequest: { amount: string; chainId: number } | null = null;

      server.use(
        http.post(
          'http://localhost:8888/wp-json/paythefly/v1/orders/create',
          async ({ request }) => {
            capturedRequest = (await request.json()) as { amount: string; chainId: number };
            return HttpResponse.json(
              {
                serialNo: 'PTF-test-123',
                projectId: 'test-project',
                brand: 'Test',
                token: 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
                redirect: '',
              },
              { status: 201 }
            );
          }
        )
      );

      render(<DonationModal {...defaultProps} />);

      const input = screen.getByPlaceholderText('0.00');
      await userEvent.type(input, '100');

      const payButton = screen.getByRole('button', { name: /pay now/i });
      fireEvent.click(payButton);

      await waitFor(() => {
        expect(capturedRequest).not.toBeNull();
        expect(capturedRequest?.amount).toBe('100');
        expect(capturedRequest?.chainId).toBe(728126428); // TRC20 default
      });
    });

    it('displays error on API failure', async () => {
      server.use(
        http.post('http://localhost:8888/wp-json/paythefly/v1/orders/create', () => {
          return HttpResponse.json(
            { code: 'error', message: 'Server error occurred' },
            { status: 500 }
          );
        })
      );

      render(<DonationModal {...defaultProps} />);

      const input = screen.getByPlaceholderText('0.00');
      await userEvent.type(input, '50');

      const payButton = screen.getByRole('button', { name: /pay now/i });
      fireEvent.click(payButton);

      await waitFor(() => {
        expect(screen.getByText(/server error occurred/i)).toBeInTheDocument();
      });
    });

    it('handles network error gracefully', async () => {
      server.use(
        http.post('http://localhost:8888/wp-json/paythefly/v1/orders/create', () => {
          return HttpResponse.error();
        })
      );

      render(<DonationModal {...defaultProps} />);

      const input = screen.getByPlaceholderText('0.00');
      await userEvent.type(input, '50');

      const payButton = screen.getByRole('button', { name: /pay now/i });
      fireEvent.click(payButton);

      await waitFor(() => {
        // Network errors show "Failed to fetch" message
        expect(screen.getByText(/failed to fetch/i)).toBeInTheDocument();
      });
    });
  });

  describe('Accessibility', () => {
    it('focuses input when modal opens', async () => {
      render(<DonationModal {...defaultProps} />);

      await waitFor(() => {
        expect(screen.getByPlaceholderText('0.00')).toHaveFocus();
      });
    });

    it('prevents body scroll when open', () => {
      render(<DonationModal {...defaultProps} />);

      expect(document.body.style.overflow).toBe('hidden');
    });

    it('restores body scroll when closed', () => {
      const { rerender } = render(<DonationModal {...defaultProps} />);

      expect(document.body.style.overflow).toBe('hidden');

      rerender(<DonationModal {...defaultProps} isOpen={false} />);

      expect(document.body.style.overflow).toBe('');
    });
  });
});
