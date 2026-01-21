import { test, expect } from '@playwright/test';

test.describe('PayTheFly Plugin', () => {
  test.beforeEach(async ({ page }) => {
    // Login to WordPress admin
    await page.goto('/wp-admin');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
    await page.waitForURL(/\/wp-admin\//);
  });

  test('plugin is activated', async ({ page }) => {
    await page.goto('/wp-admin/plugins.php');
    const paythefly = page.locator('tr[data-slug="paythefly"]');
    await expect(paythefly).toBeVisible();
    await expect(paythefly.locator('.deactivate')).toBeVisible();
  });

  test('admin menu is visible', async ({ page }) => {
    await page.goto('/wp-admin/');
    const menuItem = page.locator('#adminmenu a:has-text("PayTheFly")');
    await expect(menuItem).toBeVisible();
  });

  test('admin page loads', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=paythefly');
    const appContainer = page.locator('#paythefly-admin-app');
    await expect(appContainer).toBeVisible();
  });

  test('settings page loads', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=paythefly-settings');
    await expect(page.locator('#paythefly-settings-app')).toBeVisible();
  });
});
