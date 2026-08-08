const { test, expect } = require('@playwright/test');

test('Homepage loads successfully', async ({ page }) => {
    await page.goto('/index.php?page=analytics');

    await expect(page).toHaveTitle(/TalaKlase/i);

    await expect(page.locator('body')).toBeVisible();
});