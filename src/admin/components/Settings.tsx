import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

interface SettingsData {
  project_id: string;
  project_key: string;
  brand: string;
  webhook_url: string;
  fab_enabled: boolean;
  inline_button_auto: boolean;
}

export default function Settings() {
  const [settings, setSettings] = useState<SettingsData>({
    project_id: '',
    project_key: '',
    brand: '',
    webhook_url: '',
    fab_enabled: true,
    inline_button_auto: false,
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
      setSettings({
        project_id: response.project_id || '',
        project_key: response.project_key || '',
        brand: response.brand || '',
        webhook_url: response.webhook_url || '',
        fab_enabled: response.fab_enabled ?? true,
        inline_button_auto: response.inline_button_auto ?? false,
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
        <div className="paythefly-settings__section">
          <h3>{__('API Configuration', 'paythefly')}</h3>

          <div className="paythefly-settings__field">
            <label htmlFor="project_id">{__('Project ID', 'paythefly')}</label>
            <input
              type="text"
              id="project_id"
              value={settings.project_id}
              onChange={(e) => handleChange('project_id', e.target.value)}
              placeholder={__('Enter your PayTheFly Project ID', 'paythefly')}
            />
            <p className="description">
              {__('Your unique project identifier from PayTheFly dashboard.', 'paythefly')}
            </p>
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
            <p className="description">
              {__('Your secret key for API authentication.', 'paythefly')}
            </p>
          </div>

          <div className="paythefly-settings__field">
            <label htmlFor="brand">{__('Brand Name', 'paythefly')}</label>
            <input
              type="text"
              id="brand"
              value={settings.brand}
              onChange={(e) => handleChange('brand', e.target.value)}
              placeholder={__('Enter your brand name (optional)', 'paythefly')}
            />
            <p className="description">
              {__('Display name shown to donors. Defaults to admin display name if empty.', 'paythefly')}
            </p>
          </div>
        </div>

        <div className="paythefly-settings__section">
          <h3>{__('Display Options', 'paythefly')}</h3>

          <div className="paythefly-settings__field paythefly-settings__field--checkbox">
            <label>
              <input
                type="checkbox"
                checked={settings.fab_enabled}
                onChange={(e) => handleChange('fab_enabled', e.target.checked)}
              />
              <span>{__('Enable Floating Action Button (FAB)', 'paythefly')}</span>
            </label>
            <p className="description">
              {__('Show a floating donation button in the bottom-right corner of all pages.', 'paythefly')}
            </p>
          </div>

          <div className="paythefly-settings__field paythefly-settings__field--checkbox">
            <label>
              <input
                type="checkbox"
                checked={settings.inline_button_auto}
                onChange={(e) => handleChange('inline_button_auto', e.target.checked)}
              />
              <span>{__('Auto-add button to post content', 'paythefly')}</span>
            </label>
            <p className="description">
              {__('Automatically add a "Support the Author" button at the end of all posts.', 'paythefly')}
            </p>
          </div>
        </div>

        <div className="paythefly-settings__section">
          <h3>{__('Webhook', 'paythefly')}</h3>

          <div className="paythefly-settings__field">
            <label htmlFor="webhook_url">{__('Webhook URL', 'paythefly')}</label>
            <input
              type="text"
              id="webhook_url"
              value={`${window.location.origin}/?rest_route=/paythefly/v1/webhook`}
              readOnly
            />
            <p className="description">
              {__('Copy this URL to your PayTheFly dashboard for webhook notifications.', 'paythefly')}
            </p>
          </div>
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
