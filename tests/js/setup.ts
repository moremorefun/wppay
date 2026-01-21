import '@testing-library/jest-dom';

// Mock WordPress globals
global.wp = {
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
global.paytheflyAdmin = {
  apiUrl: 'http://localhost:8888/wp-json/paythefly/v1',
  nonce: 'test-nonce',
  version: '1.0.0',
};

global.paytheflyFrontend = {
  apiUrl: 'http://localhost:8888/wp-json/paythefly/v1',
  nonce: 'test-nonce',
};
