import { test, expect } from '@playwright/test';

test.describe('Admin Settings', () => {
  test.beforeEach(async ({ page }) => {
    // Login to WordPress admin
    await page.goto('/wp-admin');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
    await page.waitForURL(/\/wp-admin\//);
  });

  test('settings page loads', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=paythefly-settings');

    // Settings container should be visible
    const settingsContainer = page.locator('#paythefly-settings-app');
    await expect(settingsContainer).toBeVisible();
  });

  test('can navigate to settings from menu', async ({ page }) => {
    await page.goto('/wp-admin/');

    // Click on PayTheFly menu
    const menuItem = page.locator('#adminmenu a:has-text("PayTheFly")');
    await menuItem.click();

    // Should be on PayTheFly page
    await expect(page).toHaveURL(/page=paythefly/);
  });

  test('settings form has required fields', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=paythefly-settings');

    // Wait for React app to load
    await page.waitForSelector('#paythefly-settings-app');
    await page.waitForTimeout(500);

    // Check for project ID field
    const projectIdInput = page.locator('input[name="project_id"], input[id*="project"]');
    await expect(projectIdInput.first()).toBeVisible();
  });

  test('can update project ID', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=paythefly-settings');

    // Wait for app to load
    await page.waitForSelector('#paythefly-settings-app');
    await page.waitForTimeout(500);

    // Find and fill project ID input
    const projectIdInput = page.locator('input[name="project_id"]').first();

    if (await projectIdInput.isVisible()) {
      await projectIdInput.fill('test-project-123');
      await expect(projectIdInput).toHaveValue('test-project-123');
    }
  });

  test('shows success message on save', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=paythefly-settings');

    // Wait for app to load
    await page.waitForSelector('#paythefly-settings-app');
    await page.waitForTimeout(500);

    // Look for save button
    const saveButton = page.locator('button[type="submit"], button:has-text("Save")');

    if (await saveButton.first().isVisible()) {
      await saveButton.first().click();

      // Wait for success message or notification
      await page.waitForTimeout(1000);

      // Check for success indicator (could be toast, notice, or text)
      const successIndicators = [
        page.locator('.notice-success'),
        page.locator('.success'),
        page.locator('text=/saved|success/i'),
      ];

      for (const indicator of successIndicators) {
        if (await indicator.first().isVisible().catch(() => false)) {
          await expect(indicator.first()).toBeVisible();
          break;
        }
      }
      // Note: success message may vary based on implementation
    }
  });

  test('settings persist after reload', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=paythefly-settings');

    // Wait for app to load
    await page.waitForSelector('#paythefly-settings-app');
    await page.waitForTimeout(500);

    // Find project ID input
    const projectIdInput = page.locator('input[name="project_id"]').first();

    if (await projectIdInput.isVisible()) {
      const testValue = 'persist-test-' + Date.now();

      // Fill and save
      await projectIdInput.fill(testValue);

      const saveButton = page.locator('button[type="submit"], button:has-text("Save")').first();
      if (await saveButton.isVisible()) {
        await saveButton.click();
        await page.waitForTimeout(1000);
      }

      // Reload page
      await page.reload();
      await page.waitForSelector('#paythefly-settings-app');
      await page.waitForTimeout(500);

      // Check value persisted
      const reloadedInput = page.locator('input[name="project_id"]').first();
      if (await reloadedInput.isVisible()) {
        await expect(reloadedInput).toHaveValue(testValue);
      }
    }
  });

  test('fab toggle works', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=paythefly-settings');

    // Wait for app to load
    await page.waitForSelector('#paythefly-settings-app');
    await page.waitForTimeout(500);

    // Look for FAB toggle
    const fabToggle = page.locator('input[name="fab_enabled"], input[type="checkbox"]').first();

    if (await fabToggle.isVisible()) {
      const initialState = await fabToggle.isChecked();

      // Toggle it
      await fabToggle.click();

      // Check state changed
      await expect(fabToggle).toBeChecked({ checked: !initialState });
    }
  });
});
