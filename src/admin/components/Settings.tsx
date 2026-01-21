import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

interface SettingsData {
  project_id: string;
  project_key: string;
  brand: string;
  webhook_url: string;
}

export default function Settings() {
  const [settings, setSettings] = useState<SettingsData>({
    project_id: '',
    project_key: '',
    brand: '',
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
          <label htmlFor="project_id">{__('Project ID', 'paythefly')}</label>
          <input
            type="text"
            id="project_id"
            value={settings.project_id}
            onChange={(e) => handleChange('project_id', e.target.value)}
            placeholder={__('Enter your PayTheFly Project ID', 'paythefly')}
          />
        </div>

        <div className="paythefly-settings__field">
          <label htmlFor="project_key">{__('Project Key', 'paythefly')}</label>
          <input
            type="password"
            id="project_key"
            value={settings.project_key}
            onChange={(e) => handleChange('project_key', e.target.value)}
            placeholder={__('Enter your PayTheFly Project Key', 'paythefly')}
          />
        </div>

        <div className="paythefly-settings__field">
          <label htmlFor="brand">{__('Brand', 'paythefly')}</label>
          <input
            type="text"
            id="brand"
            value={settings.brand}
            onChange={(e) => handleChange('brand', e.target.value)}
            placeholder={__('Enter your brand name', 'paythefly')}
          />
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
