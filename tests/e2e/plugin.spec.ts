import { test, expect } from '@playwright/test';

// Increase timeout for admin tests and run serially to avoid session conflicts
test.describe.configure({ timeout: 60000, mode: 'serial' });

test.describe('PayTheFly Plugin', () => {
  test.beforeEach(async ({ page }) => {
    // Login to WordPress admin
    await page.goto('/wp-login.php');
    await page.waitForSelector('#user_login');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
    await page.waitForURL(/\/wp-admin/, { timeout: 30000 });
  });

  test('plugin is activated', async ({ page }) => {
    await page.goto('/wp-admin/plugins.php');
    // Look for the plugin by its name in the table
    const paythefly = page.locator('tr:has-text("PayTheFly Crypto Gateway")');
    await expect(paythefly).toBeVisible();
    // Check deactivate link is visible (means plugin is active)
    await expect(paythefly.locator('a:has-text("禁用"), a:has-text("Deactivate")')).toBeVisible();
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
    await page.goto('/wp-admin/admin.php?page=paythefly');
    await expect(page.locator('#paythefly-admin-app')).toBeVisible();
  });
});
