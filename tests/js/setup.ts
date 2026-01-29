import '@testing-library/jest-dom';
import { afterAll, afterEach, beforeAll, beforeEach } from 'vitest';
import { setupServer } from 'msw/node';
import { handlers } from './mocks/handlers';

// Setup MSW server
export const server = setupServer(...handlers);

// Store original location
const originalLocation = window.location;

beforeAll(() => server.listen({ onUnhandledRequest: 'bypass' }));

beforeEach(() => {
  // Mock window.location to avoid jsdom navigation errors
  Object.defineProperty(window, 'location', {
    value: {
      ...originalLocation,
      href: 'http://localhost:8888/',
      origin: 'http://localhost:8888',
      assign: () => {},
      replace: () => {},
      reload: () => {},
    },
    writable: true,
    configurable: true,
  });
});

afterEach(() => {
  server.resetHandlers();
  // Restore original location
  Object.defineProperty(window, 'location', {
    value: originalLocation,
    writable: true,
    configurable: true,
  });
});

afterAll(() => server.close());

// Extend global types for test environment
declare global {
  var wp: {
    i18n: {
      __: (text: string, domain?: string) => string;
      _x: (text: string, context?: string, domain?: string) => string;
      _n: (single: string, plural?: string, number?: number, domain?: string) => string;
      sprintf: (format: string, ...args: unknown[]) => string;
    };
    element: {
      createElement: () => null;
      Fragment: () => null;
    };
  };
  var paytheflyAdmin: {
    apiUrl: string;
    nonce: string;
    version: string;
  };
  var paytheflyFrontend: {
    apiUrl: string;
    nonce: string;
  };
}

// Mock WordPress globals
globalThis.wp = {
  i18n: {
    __: (text: string) => text,
    _x: (text: string) => text,
    _n: (single: string) => single,
    sprintf: (format: string, ...args: unknown[]) =>
      format.replace(/%s/g, () => String(args.shift())),
  },
  element: {
    createElement: () => null,
    Fragment: () => null,
  },
};

// Mock paytheflyAdmin/paytheflyFrontend globals
globalThis.paytheflyAdmin = {
  apiUrl: 'http://localhost:8888/wp-json/paythefly/v1',
  nonce: 'test-nonce',
  version: '1.0.0',
};

globalThis.paytheflyFrontend = {
  apiUrl: 'http://localhost:8888/wp-json/paythefly/v1',
  nonce: 'test-nonce',
};

export {};
