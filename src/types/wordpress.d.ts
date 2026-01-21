// WordPress global type declarations
declare module '@wordpress/block-editor' {
  export const useBlockProps: {
    (props?: Record<string, unknown>): Record<string, unknown>;
    save(props?: Record<string, unknown>): Record<string, unknown>;
  };
  export const InspectorControls: React.FC<{ children: React.ReactNode }>;
}

declare module '@wordpress/blocks' {
  export function registerBlockType(
    name: string,
    settings: Record<string, unknown>
  ): void;
}

declare module '@wordpress/components' {
  export const PanelBody: React.FC<{
    title: string;
    children: React.ReactNode;
  }>;
  export const TextControl: React.FC<{
    label: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
  }>;
  export const SelectControl: React.FC<{
    label: string;
    value: string;
    options: Array<{ label: string; value: string }>;
    onChange: (value: string) => void;
  }>;
}

declare module '@wordpress/i18n' {
  export function __(text: string, domain?: string): string;
  export function _x(text: string, context: string, domain?: string): string;
  export function _n(
    single: string,
    plural: string,
    number: number,
    domain?: string
  ): string;
  export function sprintf(format: string, ...args: unknown[]): string;
}

declare module '@wordpress/api-fetch' {
  interface ApiFetchOptions {
    path: string;
    method?: string;
    data?: unknown;
    headers?: Record<string, string>;
  }
  function apiFetch<T>(options: ApiFetchOptions): Promise<T>;
  export default apiFetch;
}

declare module '@wordpress/data' {
  export function select(store: string): Record<string, unknown>;
  export function dispatch(store: string): Record<string, unknown>;
  export function useSelect<T>(
    selector: (select: (store: string) => Record<string, unknown>) => T,
    deps?: unknown[]
  ): T;
  export function useDispatch(
    store?: string
  ): Record<string, (...args: unknown[]) => unknown>;
}

declare module '@wordpress/element' {
  export const createElement: typeof React.createElement;
  export const Fragment: typeof React.Fragment;
  export const useState: typeof React.useState;
  export const useEffect: typeof React.useEffect;
  export const useCallback: typeof React.useCallback;
  export const useMemo: typeof React.useMemo;
  export const useRef: typeof React.useRef;
  export const createRoot: (container: Element) => {
    render(element: React.ReactNode): void;
    unmount(): void;
  };
}

// Global window extensions
interface Window {
  paytheflyAdmin?: {
    apiUrl: string;
    nonce: string;
    version: string;
  };
  paytheflyFrontend?: {
    apiUrl: string;
    nonce: string;
    projectId: string;
    brand: string;
    fabEnabled: boolean;
    recipientName: string;
    recipientAvatar: string;
  };
  wp?: {
    i18n: {
      __: (text: string, domain?: string) => string;
      _x: (text: string, context: string, domain?: string) => string;
      _n: (
        single: string,
        plural: string,
        number: number,
        domain?: string
      ) => string;
      sprintf: (format: string, ...args: unknown[]) => string;
    };
    element: {
      createElement: typeof React.createElement;
      Fragment: typeof React.Fragment;
    };
  };
}

// Global declarations for test setup
declare const paytheflyAdmin: Window['paytheflyAdmin'];
declare const paytheflyFrontend: Window['paytheflyFrontend'];
