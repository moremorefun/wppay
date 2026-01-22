import { test, expect, request } from '@playwright/test';
import { createHash } from 'crypto';

test.describe('Webhook API', () => {
  const baseUrl = 'http://localhost:8888';
  const webhookEndpoint = '/wp-json/paythefly/v1/webhook';

  // Test project configuration
  const projectId = 'test-project-id';
  const projectKey = 'test-secret-key';

  /**
   * Generate valid signature for webhook data.
   */
  function generateSignature(data: string, key: string): string {
    return createHash('md5').update(data + key).digest('hex').toUpperCase();
  }

  test.beforeAll(async () => {
    // Set up test configuration via WP-CLI or API
    const apiContext = await request.newContext({
      baseURL: baseUrl,
    });

    // Login first to get auth cookies
    await apiContext.post('/wp-login.php', {
      form: {
        log: 'admin',
        pwd: 'password',
        'wp-submit': 'Log In',
        redirect_to: '/wp-admin/',
        testcookie: '1',
      },
    });

    // Update settings via REST API (requires authentication)
    // Note: This may need adjustment based on actual auth implementation
  });

  test('rejects request with missing params', async ({ request }) => {
    const response = await request.post(`${baseUrl}${webhookEndpoint}`, {
      data: {},
    });

    expect(response.status()).toBe(400);

    const body = await response.json();
    expect(body.code).toBe('missing_params');
  });

  test('rejects request with missing data', async ({ request }) => {
    const response = await request.post(`${baseUrl}${webhookEndpoint}`, {
      data: {
        sign: 'SOMESIGNATURE12345678901234',
      },
    });

    expect(response.status()).toBe(400);

    const body = await response.json();
    expect(body.code).toBe('missing_params');
  });

  test('rejects request with missing sign', async ({ request }) => {
    const response = await request.post(`${baseUrl}${webhookEndpoint}`, {
      data: {
        data: JSON.stringify({ test: 'data' }),
      },
    });

    expect(response.status()).toBe(400);

    const body = await response.json();
    expect(body.code).toBe('missing_params');
  });

  test('rejects request with invalid signature', async ({ request }) => {
    const payload = JSON.stringify({
      project_id: projectId,
      serial_no: 'PTF-test-123',
      amount: '100.00',
    });

    const response = await request.post(`${baseUrl}${webhookEndpoint}`, {
      data: {
        data: payload,
        sign: 'INVALID_SIGNATURE_1234567890AB',
      },
    });

    // Should be 401 Unauthorized
    expect(response.status()).toBe(401);

    const body = await response.json();
    expect(body.code).toBe('invalid_signature');
  });

  test('rejects request with invalid JSON data', async ({ request }) => {
    const invalidJson = 'not valid json {';

    const response = await request.post(`${baseUrl}${webhookEndpoint}`, {
      data: {
        data: invalidJson,
        sign: generateSignature(invalidJson, projectKey),
      },
    });

    // Could be 400 for invalid JSON
    expect(response.status()).toBe(400);
  });

  test('rejects request with project ID mismatch', async ({ request }) => {
    const payload = JSON.stringify({
      project_id: 'wrong-project-id',
      serial_no: 'PTF-test-123',
      amount: '100.00',
    });

    const response = await request.post(`${baseUrl}${webhookEndpoint}`, {
      data: {
        data: payload,
        sign: generateSignature(payload, projectKey),
      },
    });

    // Should be 403 Forbidden
    expect(response.status()).toBe(403);

    const body = await response.json();
    expect(body.code).toBe('project_mismatch');
  });

  test('accepts valid webhook request', async ({ request }) => {
    const payload = JSON.stringify({
      project_id: projectId,
      serial_no: 'PTF-valid-' + Date.now(),
      amount: '50.00',
      status: 'completed',
      tx_hash: '0xabc123def456',
    });

    const response = await request.post(`${baseUrl}${webhookEndpoint}`, {
      data: {
        data: payload,
        sign: generateSignature(payload, projectKey),
      },
    });

    // Should be 200 OK
    expect(response.status()).toBe(200);

    const body = await response.json();
    expect(body.success).toBe(true);
  });

  test('handles payment completed webhook', async ({ request }) => {
    const serialNo = 'PTF-payment-' + Date.now();

    const payload = JSON.stringify({
      project_id: projectId,
      serial_no: serialNo,
      amount: '100.00',
      currency: 'USDT',
      status: 'completed',
      tx_hash: '0x1234567890abcdef',
      chain_id: 728126428,
      sender: 'TUserAddress123',
      receiver: 'TReceiverAddress456',
      completed_at: new Date().toISOString(),
    });

    const response = await request.post(`${baseUrl}${webhookEndpoint}`, {
      data: {
        data: payload,
        sign: generateSignature(payload, projectKey),
      },
    });

    expect(response.status()).toBe(200);
  });

  test('handles payment failed webhook', async ({ request }) => {
    const serialNo = 'PTF-failed-' + Date.now();

    const payload = JSON.stringify({
      project_id: projectId,
      serial_no: serialNo,
      amount: '25.00',
      status: 'failed',
      error: 'Transaction timeout',
    });

    const response = await request.post(`${baseUrl}${webhookEndpoint}`, {
      data: {
        data: payload,
        sign: generateSignature(payload, projectKey),
      },
    });

    expect(response.status()).toBe(200);
  });

  test('webhook endpoint is accessible without authentication', async ({ request }) => {
    // Webhook should be publicly accessible (uses signature verification instead)
    const response = await request.post(`${baseUrl}${webhookEndpoint}`, {
      data: {
        data: '{}',
        sign: 'test',
      },
    });

    // Should not return 401/403 for missing auth
    // (it will return 401 for invalid signature, but that's different)
    expect(response.status()).not.toBe(403);
  });
});
