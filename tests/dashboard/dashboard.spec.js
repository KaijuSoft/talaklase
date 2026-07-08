const { test, expect } = require('@playwright/test');
const { login } = require('../auth/login.helpers');

function collectPageHealth(page) {
  const consoleErrors = [];
  const failedRequests = [];
  const failedResponses = [];
  const externalHosts = ['cdnjs.cloudflare.com', 'fonts.googleapis.com', 'fonts.gstatic.com'];

  function isExternalResource(url) {
    return externalHosts.some(host => url.includes(host));
  }

  page.on('console', message => {
    if (message.type() === 'error') {
      if (/Failed to load resource: net::ERR_NETWORK_ACCESS_DENIED/i.test(message.text())) {
        return;
      }
      consoleErrors.push(message.text());
    }
  });

  page.on('requestfailed', request => {
    if (isExternalResource(request.url())) {
      return;
    }
    failedRequests.push(`${request.method()} ${request.url()} - ${request.failure()?.errorText || 'request failed'}`);
  });

  page.on('response', response => {
    const status = response.status();
    if (isExternalResource(response.url())) {
      return;
    }
    if (status >= 400) {
      failedResponses.push(`${status} ${response.url()}`);
    }
  });

  return {
    expectHealthy() {
      expect(consoleErrors, 'JavaScript console errors').toEqual([]);
      expect(failedRequests, 'Failed network requests').toEqual([]);
      expect(failedResponses, 'HTTP responses with status >= 400').toEqual([]);
    }
  };
}

async function expectDashboardShell(page) {
  await expect(page).toHaveTitle(/TalaKlase/i);
  await expect(page.locator('#wrapper')).toBeVisible();
  await expect(page.locator('#sidebar')).toBeVisible();
  await expect(page.locator('.sidebar-logo')).toContainText('TalaKlase');
  await expect(page.locator('.top-bar')).toBeVisible();
  await expect(page.locator('.page-title')).toBeVisible();
  await expect(page.locator('.content-area')).toBeVisible();
}

async function expectDashboardCards(page) {
  const cards = page.locator('.stat-card');
  await expect(cards.first()).toBeVisible();
  expect(await cards.count()).toBeGreaterThan(0);
  await expect(page.locator('.stat-label').filter({ hasText: /total students/i })).toBeVisible();
}

async function discoverSidebarLinks(page) {
  return page.locator('.sidebar-nav a.nav-link').evaluateAll(links => {
    const seen = new Set();
    const currentOrigin = window.location.origin;

    return links
      .map(link => ({
        href: link.href,
        label: link.textContent.trim().replace(/\s+/g, ' ')
      }))
      .filter(link => {
        if (!link.href || !link.label) {
          return false;
        }

        const url = new URL(link.href);
        const isInternal = url.origin === currentOrigin;
        const isLogout = /logout\.php/i.test(url.pathname);

        if (!isInternal || isLogout || seen.has(url.href)) {
          return false;
        }

        seen.add(url.href);
        return true;
      });
  });
}

async function returnToDashboard(page) {
  await page.bringToFront();
  await expectDashboardShell(page);
  await expectDashboardCards(page);
}

test.describe('Dashboard module', () => {
  test('Dashboard loads after login and displays cards', async ({ page }) => {
    const health = collectPageHealth(page);

    await login(page);

    await expectDashboardShell(page);
    await expectDashboardCards(page);
    health.expectHealthy();
  });

  test('Navigation links are present and functional', async ({ page }) => {
    test.setTimeout(120000);

    const health = collectPageHealth(page);

    await login(page);

    const sidebarLinks = await discoverSidebarLinks(page);
    expect(sidebarLinks.length, 'Discovered internal sidebar links').toBeGreaterThan(0);

    for (const link of sidebarLinks) {
      const navPage = await page.context().newPage();
      const navHealth = collectPageHealth(navPage);
      const response = await navPage.goto(link.href, {
        waitUntil: 'commit',
        timeout: 15000
      });

      expect(response, `Response for sidebar link ${link.href}`).not.toBeNull();
      expect(response.ok(), `${link.href} returned HTTP ${response.status()}`).toBeTruthy();

      await expectDashboardShell(navPage);
      await expect(navPage.locator('body')).not.toContainText(/not found|fatal error|parse error/i);
      navHealth.expectHealthy();

      await navPage.close();

      await returnToDashboard(page);
    }

    health.expectHealthy();
  });

  test('No JavaScript console errors or failed network requests on dashboard load', async ({ page }) => {
    const health = collectPageHealth(page);

    await login(page);
    await page.waitForLoadState('domcontentloaded');

    health.expectHealthy();
  });

  test('User name and role are displayed correctly', async ({ page }) => {
    await login(page);

    const userMenu = page.locator('.sidebar-user');
    const expectedName = process.env.TALAKLASE_TEST_DISPLAY_NAME;
    const expectedRole = process.env.TALAKLASE_TEST_ROLE;

    await expect(userMenu).toBeVisible();
    await expect(userMenu.locator('.sidebar-user-label')).toHaveText(/logged in as/i);

    if (expectedName) {
      await expect(userMenu.locator('.sidebar-user-name')).toHaveText(expectedName);
    } else {
      await expect(userMenu.locator('.sidebar-user-name')).not.toBeEmpty();
    }

    if (expectedRole) {
      await expect(userMenu.locator('.sidebar-user-role')).toHaveText(new RegExp(`^${expectedRole}$`, 'i'));
    } else {
      await expect(userMenu.locator('.sidebar-user-role')).toHaveText(/admin|instructor|viewer/i);
    }
  });
});
