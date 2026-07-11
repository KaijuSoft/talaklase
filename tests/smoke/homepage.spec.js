const { test, expect } = require('@playwright/test');

test('Homepage loads successfully', async ({ page }) => {
    await page.goto('/talaklase');

    await expect(page).toHaveTitle(/TalaKlase/i);

    await expect(page.locator('body')).toBeVisible();
});