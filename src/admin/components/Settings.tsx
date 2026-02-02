import { useState, useEffect, useCallback } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

interface ChainConfig {
  project_id: string;
  project_key: string;
  contract_address: string;
}

interface SettingsData {
  private_key_encrypted: string;
  evm_address: string;
  tron_address: string;
  tron: ChainConfig;
  bsc: ChainConfig;
  brand: string;
  webhook_url: string;
  fab_enabled: boolean;
  inline_button_auto: boolean;
  debug_log: boolean;
}

type ChainKey = 'tron' | 'bsc';

const defaultChainConfig: ChainConfig = {
  project_id: '',
  project_key: '',
  contract_address: '',
};

const defaultSettings: SettingsData = {
  private_key_encrypted: '',
  evm_address: '',
  tron_address: '',
  tron: { ...defaultChainConfig },
  bsc: { ...defaultChainConfig },
  brand: '',
  webhook_url: '',
  fab_enabled: true,
  inline_button_auto: false,
  debug_log: false,
};

export default function Settings() {
  const [settings, setSettings] = useState<SettingsData>(defaultSettings);
  const [activeChain, setActiveChain] = useState<ChainKey>('tron');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [generatingKey, setGeneratingKey] = useState(false);
  const [copied, setCopied] = useState<string | null>(null);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      setLoading(true);
      const response = await apiFetch<SettingsData>({
        path: '/paythefly/v1/settings',
      });
      setSettings({
        private_key_encrypted: response.private_key_encrypted || '',
        evm_address: response.evm_address || '',
        tron_address: response.tron_address || '',
        tron: response.tron || { ...defaultChainConfig },
        bsc: response.bsc || { ...defaultChainConfig },
        brand: response.brand || '',
        webhook_url: response.webhook_url || '',
        fab_enabled: response.fab_enabled ?? true,
        inline_button_auto: response.inline_button_auto ?? false,
        debug_log: response.debug_log ?? false,
      });
    } catch (error) {
      console.error('Failed to load settings:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setMessage(null);

    try {
      await apiFetch({
        path: '/paythefly/v1/settings',
        method: 'POST',
        data: settings,
      });
      setMessage({
        type: 'success',
        text: __('Settings saved successfully.', 'paythefly-crypto-gateway'),
      });
    } catch (error) {
      setMessage({
        type: 'error',
        text: __('Failed to save settings.', 'paythefly-crypto-gateway'),
      });
    } finally {
      setSaving(false);
    }
  };

  const handleGenerateKey = async () => {
    if (settings.private_key_encrypted && !window.confirm(
      __('This will generate a new signing key. Your existing key will be replaced. Continue?', 'paythefly-crypto-gateway')
    )) {
      return;
    }

    setGeneratingKey(true);
    setMessage(null);

    try {
      const response = await apiFetch<{ success: boolean; evm_address: string; tron_address: string }>({
        path: '/paythefly/v1/settings/generate-key',
        method: 'POST',
      });
      setSettings((prev) => ({
        ...prev,
        private_key_encrypted: '********',
        evm_address: response.evm_address,
        tron_address: response.tron_address,
      }));
      setMessage({
        type: 'success',
        text: __('Signing key generated successfully.', 'paythefly-crypto-gateway'),
      });
    } catch (error) {
      setMessage({
        type: 'error',
        text: __('Failed to generate signing key.', 'paythefly-crypto-gateway'),
      });
    } finally {
      setGeneratingKey(false);
    }
  };

  const handleChange = (field: keyof SettingsData, value: string | boolean) => {
    setSettings((prev) => ({ ...prev, [field]: value }));
  };

  const handleChainChange = (chain: ChainKey, field: keyof ChainConfig, value: string) => {
    setSettings((prev) => ({
      ...prev,
      [chain]: { ...prev[chain], [field]: value },
    }));
  };

  const copyToClipboard = useCallback(async (text: string, id: string) => {
    try {
      await navigator.clipboard.writeText(text);
      setCopied(id);
      setTimeout(() => setCopied(null), 2000);
    } catch (error) {
      console.error('Failed to copy:', error);
    }
  }, []);

  const chainLabels: Record<ChainKey, string> = {
    tron: 'TRON (TRC20)',
    bsc: 'BSC (BEP20)',
  };

  const isChainConfigured = (chain: ChainKey): boolean => {
    const config = settings[chain];
    return !!(config.project_id && config.project_key && config.contract_address);
  };

  if (loading) {
    return (
      <div className="paythefly-loading">
        <div className="paythefly-loading__spinner" />
      </div>
    );
  }

  const currentChainConfig = settings[activeChain];
  const signingAddress = activeChain === 'tron' ? settings.tron_address : settings.evm_address;

  return (
    <div className="paythefly-settings">
      <h2>{__('Settings', 'paythefly-crypto-gateway')}</h2>

      {message && (
        <div className={`paythefly-notice paythefly-notice--${message.type}`}>{message.text}</div>
      )}

      <form className="paythefly-settings__form" onSubmit={handleSubmit}>
        {/* Signing Key Section */}
        <div className="paythefly-settings__section">
          <h3>{__('Signing Key', 'paythefly-crypto-gateway')}</h3>
          <p className="paythefly-settings__intro">
            {__(
              'Generate a signing key to enable secure payment signatures. Copy the signing address to your PayTheFly Pro project settings.',
              'paythefly-crypto-gateway'
            )}
          </p>

          <div className="paythefly-settings__field">
            <label>{__('Status', 'paythefly-crypto-gateway')}</label>
            <div className="paythefly-settings__key-status">
              {settings.private_key_encrypted ? (
                <span className="paythefly-badge paythefly-badge--success">
                  {__('Key Generated', 'paythefly-crypto-gateway')}
                </span>
              ) : (
                <span className="paythefly-badge paythefly-badge--warning">
                  {__('Key Not Generated', 'paythefly-crypto-gateway')}
                </span>
              )}
              <button
                type="button"
                className="paythefly-button paythefly-button--secondary"
                onClick={handleGenerateKey}
                disabled={generatingKey}
              >
                {generatingKey
                  ? __('Generating...', 'paythefly-crypto-gateway')
                  : settings.private_key_encrypted
                    ? __('Regenerate Key', 'paythefly-crypto-gateway')
                    : __('Generate Key', 'paythefly-crypto-gateway')}
              </button>
            </div>
          </div>

          {settings.evm_address && (
            <div className="paythefly-settings__field">
              <label>{__('EVM Signing Address', 'paythefly-crypto-gateway')}</label>
              <div className="paythefly-settings__address">
                <code>{settings.evm_address}</code>
                <button
                  type="button"
                  className="paythefly-button paythefly-button--small"
                  onClick={() => copyToClipboard(settings.evm_address, 'evm')}
                >
                  {copied === 'evm' ? __('Copied!', 'paythefly-crypto-gateway') : __('Copy', 'paythefly-crypto-gateway')}
                </button>
              </div>
              <p className="description">
                {__('Use this address for BSC projects on PayTheFly Pro.', 'paythefly-crypto-gateway')}
              </p>
            </div>
          )}

          {settings.tron_address && (
            <div className="paythefly-settings__field">
              <label>{__('TRON Signing Address', 'paythefly-crypto-gateway')}</label>
              <div className="paythefly-settings__address">
                <code>{settings.tron_address}</code>
                <button
                  type="button"
                  className="paythefly-button paythefly-button--small"
                  onClick={() => copyToClipboard(settings.tron_address, 'tron')}
                >
                  {copied === 'tron' ? __('Copied!', 'paythefly-crypto-gateway') : __('Copy', 'paythefly-crypto-gateway')}
                </button>
              </div>
              <p className="description">
                {__('Use this address for TRON projects on PayTheFly Pro.', 'paythefly-crypto-gateway')}
              </p>
            </div>
          )}
        </div>

        {/* Chain Configuration Section */}
        <div className="paythefly-settings__section">
          <h3>{__('Chain Configuration', 'paythefly-crypto-gateway')}</h3>

          {/* Configuration Status Summary */}
          <div className="paythefly-settings__status-grid">
            <div className={`paythefly-settings__status-item ${isChainConfigured('tron') ? 'paythefly-settings__status-item--ok' : ''}`}>
              <span className="paythefly-settings__status-label">TRON</span>
              {isChainConfigured('tron') ? (
                <span className="paythefly-badge paythefly-badge--success">{__('Configured', 'paythefly-crypto-gateway')}</span>
              ) : (
                <span className="paythefly-badge paythefly-badge--warning">{__('Not configured', 'paythefly-crypto-gateway')}</span>
              )}
            </div>
            <div className={`paythefly-settings__status-item ${isChainConfigured('bsc') ? 'paythefly-settings__status-item--ok' : ''}`}>
              <span className="paythefly-settings__status-label">BSC</span>
              {isChainConfigured('bsc') ? (
                <span className="paythefly-badge paythefly-badge--success">{__('Configured', 'paythefly-crypto-gateway')}</span>
              ) : (
                <span className="paythefly-badge paythefly-badge--warning">{__('Not configured', 'paythefly-crypto-gateway')}</span>
              )}
            </div>
          </div>

          <p className="paythefly-settings__intro">
            {__(
              'Configure your PayTheFly Pro project settings for each blockchain. Get your credentials from',
              'paythefly-crypto-gateway'
            )}{' '}
            <a href="https://pro.paythefly.com/" target="_blank" rel="noopener noreferrer">
              pro.paythefly.com
            </a>
          </p>

          {/* Chain Tabs */}
          <div className="paythefly-tabs">
            {(Object.keys(chainLabels) as ChainKey[]).map((chain) => (
              <button
                key={chain}
                type="button"
                className={`paythefly-tabs__tab ${activeChain === chain ? 'paythefly-tabs__tab--active' : ''}`}
                onClick={() => setActiveChain(chain)}
              >
                {chainLabels[chain]}
                {isChainConfigured(chain) ? (
                  <span className="paythefly-tabs__status paythefly-tabs__status--ok" title={__('Configured', 'paythefly-crypto-gateway')}>✓</span>
                ) : (
                  <span className="paythefly-tabs__status paythefly-tabs__status--pending" title={__('Not configured', 'paythefly-crypto-gateway')}>○</span>
                )}
              </button>
            ))}
          </div>

          {/* Chain Config Fields */}
          <div className="paythefly-tabs__content">
            <div className="paythefly-settings__field">
              <label htmlFor={`${activeChain}_project_id`}>
                {__('Project ID', 'paythefly-crypto-gateway')}
              </label>
              <input
                type="text"
                id={`${activeChain}_project_id`}
                value={currentChainConfig.project_id}
                onChange={(e) => handleChainChange(activeChain, 'project_id', e.target.value)}
                placeholder={__('Enter your PayTheFly Pro Project ID', 'paythefly-crypto-gateway')}
              />
            </div>

            <div className="paythefly-settings__field">
              <label htmlFor={`${activeChain}_project_key`}>
                {__('Project Key', 'paythefly-crypto-gateway')}
              </label>
              <input
                type="password"
                id={`${activeChain}_project_key`}
                value={currentChainConfig.project_key}
                onChange={(e) => handleChainChange(activeChain, 'project_key', e.target.value)}
                placeholder={__('Enter your PayTheFly Pro Project Key', 'paythefly-crypto-gateway')}
              />
              <p className="description">
                {__('Used for webhook signature verification.', 'paythefly-crypto-gateway')}
              </p>
            </div>

            <div className="paythefly-settings__field">
              <label htmlFor={`${activeChain}_contract_address`}>
                {__('Contract Address', 'paythefly-crypto-gateway')}
              </label>
              <input
                type="text"
                id={`${activeChain}_contract_address`}
                value={currentChainConfig.contract_address}
                onChange={(e) => handleChainChange(activeChain, 'contract_address', e.target.value)}
                placeholder={activeChain === 'tron' ? 'T...' : '0x...'}
              />
              <p className="description">
                {__('The PayTheFly Pro contract address for this chain.', 'paythefly-crypto-gateway')}
              </p>
            </div>

            {signingAddress && (
              <div className="paythefly-settings__info">
                <strong>{__('Signing Address for this chain:', 'paythefly-crypto-gateway')}</strong>
                <code>{signingAddress}</code>
              </div>
            )}
          </div>
        </div>

        {/* Display Options Section */}
        <div className="paythefly-settings__section">
          <h3>{__('Display Options', 'paythefly-crypto-gateway')}</h3>

          <div className="paythefly-settings__field">
            <label htmlFor="brand">{__('Brand Name', 'paythefly-crypto-gateway')}</label>
            <input
              type="text"
              id="brand"
              value={settings.brand}
              onChange={(e) => handleChange('brand', e.target.value)}
              placeholder={__('Enter your brand name (optional)', 'paythefly-crypto-gateway')}
            />
            <p className="description">
              {__(
                'Display name shown to donors. Defaults to admin display name if empty.',
                'paythefly-crypto-gateway'
              )}
            </p>
          </div>

          <div className="paythefly-settings__field paythefly-settings__field--checkbox">
            <label htmlFor="fab_enabled">
              <input
                type="checkbox"
                id="fab_enabled"
                checked={settings.fab_enabled}
                onChange={(e) => handleChange('fab_enabled', e.target.checked)}
              />
              <span>{__('Enable Floating Action Button (FAB)', 'paythefly-crypto-gateway')}</span>
            </label>
            <p className="description">
              {__(
                'Show a floating donation button in the bottom-right corner of all pages.',
                'paythefly-crypto-gateway'
              )}
            </p>
          </div>

          <div className="paythefly-settings__field paythefly-settings__field--checkbox">
            <label htmlFor="inline_button_auto">
              <input
                type="checkbox"
                id="inline_button_auto"
                checked={settings.inline_button_auto}
                onChange={(e) => handleChange('inline_button_auto', e.target.checked)}
              />
              <span>{__('Auto-add button to post content', 'paythefly-crypto-gateway')}</span>
            </label>
            <p className="description">
              {__(
                'Automatically add a "Support the Author" button at the end of all posts.',
                'paythefly-crypto-gateway'
              )}
            </p>
          </div>
        </div>

        {/* Webhook Section */}
        <div className="paythefly-settings__section">
          <h3>{__('Webhook', 'paythefly-crypto-gateway')}</h3>

          <div className="paythefly-settings__field">
            <label htmlFor="webhook_url">{__('Webhook URL', 'paythefly-crypto-gateway')}</label>
            <div className="paythefly-settings__address">
              <input type="text" id="webhook_url" value={settings.webhook_url} readOnly />
              <button
                type="button"
                className="paythefly-button paythefly-button--small"
                onClick={() => copyToClipboard(settings.webhook_url, 'webhook')}
              >
                {copied === 'webhook' ? __('Copied!', 'paythefly-crypto-gateway') : __('Copy', 'paythefly-crypto-gateway')}
              </button>
            </div>
            <p className="description">
              {__(
                'Copy this URL to your PayTheFly Pro dashboard for webhook notifications.',
                'paythefly-crypto-gateway'
              )}
            </p>
          </div>
        </div>

        {/* Debug Section */}
        <div className="paythefly-settings__section">
          <h3>{__('Advanced', 'paythefly-crypto-gateway')}</h3>

          <div className="paythefly-settings__field paythefly-settings__field--checkbox">
            <label htmlFor="debug_log">
              <input
                type="checkbox"
                id="debug_log"
                checked={settings.debug_log}
                onChange={(e) => handleChange('debug_log', e.target.checked)}
              />
              <span>{__('Enable debug logging', 'paythefly-crypto-gateway')}</span>
            </label>
            <p className="description">
              {__('Log debug information to the WordPress debug log.', 'paythefly-crypto-gateway')}
            </p>
          </div>
        </div>

        <div className="paythefly-settings__actions">
          <button type="submit" className="paythefly-button" disabled={saving}>
            {saving
              ? __('Saving...', 'paythefly-crypto-gateway')
              : __('Save Settings', 'paythefly-crypto-gateway')}
          </button>
        </div>
      </form>
    </div>
  );
}
