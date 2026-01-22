import { test, expect } from '@playwright/test';

test.describe('Donation Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Go to a page with the donation FAB
    await page.goto('/');
    // Wait for page to load
    await page.waitForLoadState('networkidle');
  });

  test('FAB button is visible', async ({ page }) => {
    // Look for the FAB button
    const fab = page.locator('.paythefly-fab');
    await expect(fab).toBeVisible();
  });

  test('FAB opens modal on click', async ({ page }) => {
    const fab = page.locator('.paythefly-fab');
    await fab.click();

    // Modal should appear
    const modal = page.locator('.ptf-overlay');
    await expect(modal).toBeVisible();

    // Modal should contain key elements
    await expect(page.locator('.ptf-card')).toBeVisible();
    await expect(page.locator('.ptf-input')).toBeVisible();
    await expect(page.locator('.ptf-pay-btn')).toBeVisible();
  });

  test('can enter amount in modal', async ({ page }) => {
    const fab = page.locator('.paythefly-fab');
    await fab.click();

    const input = page.locator('.ptf-input');
    await expect(input).toBeVisible();

    await input.fill('50.00');
    await expect(input).toHaveValue('50.00');
  });

  test('can switch networks', async ({ page }) => {
    const fab = page.locator('.paythefly-fab');
    await fab.click();

    // Find network buttons
    const tronButton = page.locator('.ptf-chain-btn:has-text("TRON")');
    const bscButton = page.locator('.ptf-chain-btn:has-text("BNB")');

    // TRC20 should be selected by default
    await expect(tronButton).toHaveClass(/active/);

    // Click BSC button
    await bscButton.click();
    await expect(bscButton).toHaveClass(/active/);
    await expect(tronButton).not.toHaveClass(/active/);

    // Switch back to TRON
    await tronButton.click();
    await expect(tronButton).toHaveClass(/active/);
  });

  test('shows validation error for invalid amount', async ({ page }) => {
    const fab = page.locator('.paythefly-fab');
    await fab.click();

    // Try to pay without entering amount
    const payButton = page.locator('.ptf-pay-btn');
    await payButton.click();

    // Error message should appear
    const error = page.locator('.ptf-error');
    await expect(error).toBeVisible();
    await expect(error).toContainText(/valid amount/i);
  });

  test('modal closes on ESC key', async ({ page }) => {
    const fab = page.locator('.paythefly-fab');
    await fab.click();

    const modal = page.locator('.ptf-overlay');
    await expect(modal).toBeVisible();

    // Press ESC
    await page.keyboard.press('Escape');

    // Modal should be closed
    await expect(modal).not.toBeVisible();
  });

  test('modal closes on close button click', async ({ page }) => {
    const fab = page.locator('.paythefly-fab');
    await fab.click();

    const modal = page.locator('.ptf-overlay');
    await expect(modal).toBeVisible();

    // Click close button
    const closeButton = page.locator('.ptf-close');
    await closeButton.click();

    // Modal should be closed
    await expect(modal).not.toBeVisible();
  });

  test('modal closes on overlay click', async ({ page }) => {
    const fab = page.locator('.paythefly-fab');
    await fab.click();

    const modal = page.locator('.ptf-overlay');
    await expect(modal).toBeVisible();

    // Click on overlay (outside the card)
    await modal.click({ position: { x: 10, y: 10 } });

    // Modal should be closed
    await expect(modal).not.toBeVisible();
  });

  test('displays recipient name when configured', async ({ page }) => {
    const fab = page.locator('.paythefly-fab');
    await fab.click();

    // Look for recipient name element
    const nameElement = page.locator('.ptf-name');
    await expect(nameElement).toBeVisible();
  });

  test('input focuses when modal opens', async ({ page }) => {
    const fab = page.locator('.paythefly-fab');
    await fab.click();

    // Wait for modal animation
    await page.waitForTimeout(200);

    const input = page.locator('.ptf-input');
    await expect(input).toBeFocused();
  });
});
