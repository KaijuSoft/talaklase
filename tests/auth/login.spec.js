const { test, expect } = require('@playwright/test');
const {
  appPath,
  clearSession,
  expectLoginPage,
  login,
  logout,
  submitLogin
} = require('./login.helpers');

test.describe('Login module', () => {
  test('Login page loads', async ({ page }) => {
    await clearSession(page);
    await page.goto(appPath('login.php'));

    await expectLoginPage(page);
    await expect(page.locator('.wordmark')).toContainText('TalaKlase');
  });

  test('Valid login', async ({ page }) => {
    await login(page);

    await expect(page).toHaveTitle(/TalaKlase/i);
    await expect(page).not.toHaveURL(/login\.php/i);
    await expect(page.locator('.sidebar-logo')).toContainText('TalaKlase');
  });

  test('Invalid login', async ({ page }) => {
    await clearSession(page);
    await submitLogin(page, `invalid-user-${Date.now()}`, 'wrong-password');

    await expect(page).toHaveURL(/login\.php/i);
    await expect(page.getByRole('alert')).toBeVisible();
    await expect(page.getByRole('alert')).toContainText(/no account found|incorrect password|invalid session/i);
  });

  test('Logout', async ({ page }) => {
    await login(page);
    await logout(page);

    await expect(page).toHaveURL(/login\.php/i);
    await expectLoginPage(page);
  });

  test('Protected page redirects unauthenticated users', async ({ page }) => {
    await clearSession(page);
    await page.goto(appPath('index.php'));

    await expect(page).toHaveURL(/login\.php/i);
    await expectLoginPage(page);
  });

  test('Session persists after refresh', async ({ page }) => {
    await login(page);
    await page.reload();
    await page.waitForLoadState('domcontentloaded');

    await expect(page).not.toHaveURL(/login\.php/i);
    await expect(page.locator('.sidebar-user')).toBeVisible();
  });

  test('User menu appears after login', async ({ page }) => {
    await login(page);

    const userMenu = page.locator('.sidebar-user');
    await expect(userMenu).toBeVisible();
    await expect(userMenu).toContainText(/logged in as/i);
    await expect(userMenu.locator('.sidebar-user-name')).not.toBeEmpty();
    await expect(userMenu.locator('.sidebar-user-role')).not.toBeEmpty();
  });
});
