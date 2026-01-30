import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
// eslint-disable-next-line import/no-unresolved
import modalStyles from '../styles/modal.css?inline';

interface ShadowContainerProps {
  children: React.ReactNode;
}

export function ShadowContainer({ children }: ShadowContainerProps) {
  const hostRef = useRef<HTMLDivElement | null>(null);
  const [shadowRoot, setShadowRoot] = useState<ShadowRoot | null>(null);
  const [container, setContainer] = useState<HTMLDivElement | null>(null);

  useEffect(() => {
    // Create host element
    const host = document.createElement('div');
    host.id = 'paythefly-shadow-host';
    host.style.cssText = 'position: fixed; top: 0; left: 0; width: 0; height: 0; z-index: 999999;';
    document.body.appendChild(host);
    hostRef.current = host;

    // Attach shadow DOM
    const shadow = host.attachShadow({ mode: 'open' });
    setShadowRoot(shadow);

    // Inject styles
    const style = document.createElement('style');
    style.textContent = modalStyles;
    shadow.appendChild(style);

    // Create render container
    const renderContainer = document.createElement('div');
    renderContainer.className = 'paythefly-shadow-container';
    shadow.appendChild(renderContainer);
    setContainer(renderContainer);

    return () => {
      host.remove();
    };
  }, []);

  if (!shadowRoot || !container) {
    return null;
  }

  return createPortal(children, container);
}
