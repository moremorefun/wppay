import { http, HttpResponse } from 'msw';

const API_BASE = 'http://localhost:8888/wp-json/paythefly/v1';

export const handlers = [
  // Create order endpoint
  http.post(`${API_BASE}/orders/create`, async ({ request }) => {
    const body = await request.json() as { amount?: string; chainId?: number };
    const amount = body?.amount;
    const chainId = body?.chainId;

    // Validate amount
    if (!amount || isNaN(parseFloat(amount)) || parseFloat(amount) <= 0) {
      return HttpResponse.json(
        { code: 'invalid_amount', message: 'Invalid amount.' },
        { status: 400 }
      );
    }

    // Validate chainId
    const validChainIds = [728126428, 56]; // TRON, BSC
    if (!chainId || !validChainIds.includes(chainId)) {
      return HttpResponse.json(
        { code: 'invalid_chain', message: 'Invalid chain ID.' },
        { status: 400 }
      );
    }

    // Return successful order response
    const tokenAddresses: Record<number, string> = {
      728126428: 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
      56: '0x55d398326f99059fF775485246999027B3197955',
    };

    return HttpResponse.json(
      {
        serialNo: `PTF-${crypto.randomUUID()}`,
        projectId: 'test-project-id',
        brand: 'Test Brand',
        token: tokenAddresses[chainId],
        redirect: '',
      },
      { status: 201 }
    );
  }),

  // Get settings endpoint
  http.get(`${API_BASE}/settings`, () => {
    return HttpResponse.json({
      project_id: 'test-project-id',
      project_key: '********',
      brand: 'Test Brand',
      webhook_url: '',
      fab_enabled: true,
      inline_button_auto: false,
    });
  }),

  // Update settings endpoint
  http.post(`${API_BASE}/settings`, async () => {
    // Simulate successful settings update
    return HttpResponse.json({ success: true });
  }),

  // Webhook endpoint
  http.post(`${API_BASE}/webhook`, async ({ request }) => {
    const body = await request.json() as { data?: string; sign?: string };

    if (!body?.data || !body?.sign) {
      return HttpResponse.json(
        { code: 'missing_params', message: 'Missing data or sign parameter.' },
        { status: 400 }
      );
    }

    // For testing, accept any valid signature format
    if (body.sign.length !== 32) {
      return HttpResponse.json(
        { code: 'invalid_signature', message: 'Invalid signature.' },
        { status: 401 }
      );
    }

    return HttpResponse.json({ success: true });
  }),
];
