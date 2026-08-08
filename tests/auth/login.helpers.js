const { expect } = require('@playwright/test');

const credentialCandidates = [
  process.env.TALAKLASE_TEST_USERNAME && process.env.TALAKLASE_TEST_PASSWORD
    ? {
        username: process.env.TALAKLASE_TEST_USERNAME,
        password: process.env.TALAKLASE_TEST_PASSWORD,
        source: 'environment'
      }
    : null,
  { username: 'Admin', password: 'Admin@2026', source: 'default Admin@2026' },
  { username: 'admin', password: 'Admin@2025', source: 'legacy admin@2025' },
  { username: 'Admin', password: 'Admin@2025', source: 'legacy Admin@2025' },
  { username: 'admin', password: 'Admin@2026', source: 'default admin@2026' }
].filter(Boolean);

function appPath(path) {
  return `/talaklase-dev/${path.replace(/^\/+/, '')}`;
}

async function expectLoginPage(page) {
  await expect(page).toHaveTitle(/TalaKlase.*Sign in/i);
  await expect(page.getByRole('heading', { name: /sign in to your account/i })).toBeVisible();
  await expect(page.locator('#username')).toBeVisible();
  await expect(page.locator('#password')).toBeVisible();
  await expect(page.getByRole('button', { name: /^sign in$/i })).toBeVisible();
}

async function clearSession(page) {
  await page.context().clearCookies();
  await page.goto(appPath('logout.php'));
  await page.waitForLoadState('domcontentloaded');
}

async function submitLogin(page, username, password) {
  await page.goto(appPath('login.php'));
  await expectLoginPage(page);
  await page.locator('#username').fill(username);
  await page.locator('#password').fill(password);
  await page.getByRole('button', { name: /^sign in$/i }).click();
  await page.waitForLoadState('domcontentloaded');
}

async function login(page) {
  let lastUrl = '';

  for (const credentials of credentialCandidates) {
    await clearSession(page);
    await submitLogin(page, credentials.username, credentials.password);
    lastUrl = page.url();

    if (!/login\.php/i.test(lastUrl)) {
      await expect(page.locator('.sidebar-user')).toBeVisible();
      return credentials;
    }
  }

  throw new Error(
    'Unable to log in with configured or known default credentials. ' +
    'Set TALAKLASE_TEST_USERNAME and TALAKLASE_TEST_PASSWORD for this environment. ' +
    `Last URL: ${lastUrl}`
  );
}

async function logout(page) {
  await page.getByRole('link', { name: /logout/i }).click();
  await page.waitForLoadState('domcontentloaded');
}

module.exports = {
  appPath,
  clearSession,
  credentialCandidates,
  expectLoginPage,
  login,
  logout,
  submitLogin
};
