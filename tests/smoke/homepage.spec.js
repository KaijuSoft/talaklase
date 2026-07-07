const { test, expect } = require('@playwright/test');

test('Homepage loads successfully', async ({ page }) => {
    await page.goto('/talaklase_with_instructor');

    await expect(page).toHaveTitle(/TalaKlase/i);

    await expect(page.locator('body')).toBeVisible();
});