import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

interface SettingsData {
  api_key: string;
  api_secret: string;
  sandbox_mode: boolean;
  webhook_url: string;
}

export default function Settings() {
  const [settings, setSettings] = useState<SettingsData>({
    api_key: '',
    api_secret: '',
    sandbox_mode: true,
    webhook_url: '',
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
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
      setSettings(response);
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
      setMessage({ type: 'success', text: __('Settings saved successfully.', 'paythefly') });
    } catch (error) {
      setMessage({ type: 'error', text: __('Failed to save settings.', 'paythefly') });
    } finally {
      setSaving(false);
    }
  };

  const handleChange = (field: keyof SettingsData, value: string | boolean) => {
    setSettings((prev) => ({ ...prev, [field]: value }));
  };

  if (loading) {
    return (
      <div className="paythefly-loading">
        <div className="paythefly-loading__spinner" />
      </div>
    );
  }

  return (
    <div className="paythefly-settings">
      <h2>{__('Settings', 'paythefly')}</h2>

      {message && (
        <div className={`paythefly-notice paythefly-notice--${message.type}`}>{message.text}</div>
      )}

      <form className="paythefly-settings__form" onSubmit={handleSubmit}>
        <div className="paythefly-settings__field">
          <label htmlFor="api_key">{__('API Key', 'paythefly')}</label>
          <input
            type="text"
            id="api_key"
            value={settings.api_key}
            onChange={(e) => handleChange('api_key', e.target.value)}
            placeholder={__('Enter your PayTheFly API key', 'paythefly')}
          />
        </div>

        <div className="paythefly-settings__field">
          <label htmlFor="api_secret">{__('API Secret', 'paythefly')}</label>
          <input
            type="password"
            id="api_secret"
            value={settings.api_secret}
            onChange={(e) => handleChange('api_secret', e.target.value)}
            placeholder={__('Enter your PayTheFly API secret', 'paythefly')}
          />
        </div>

        <div className="paythefly-settings__field">
          <label>
            <input
              type="checkbox"
              checked={settings.sandbox_mode}
              onChange={(e) => handleChange('sandbox_mode', e.target.checked)}
            />
            {' '}
            {__('Enable Sandbox Mode', 'paythefly')}
          </label>
          <p className="description">
            {__('Use sandbox environment for testing. Disable for production.', 'paythefly')}
          </p>
        </div>

        <div className="paythefly-settings__field">
          <label htmlFor="webhook_url">{__('Webhook URL', 'paythefly')}</label>
          <input
            type="text"
            id="webhook_url"
            value={`${window.location.origin}/wp-json/paythefly/v1/webhook`}
            readOnly
          />
          <p className="description">
            {__('Copy this URL to your PayTheFly dashboard for webhook notifications.', 'paythefly')}
          </p>
        </div>

        <div className="paythefly-settings__actions">
          <button type="submit" className="paythefly-button" disabled={saving}>
            {saving ? __('Saving...', 'paythefly') : __('Save Settings', 'paythefly')}
          </button>
        </div>
      </form>
    </div>
  );
}
