// playwright.config.js
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests',

    timeout: 60 * 1000,

    retries: 1,

    use: {
        baseURL: 'http://localhost/talaklase-dev',
        browserName: 'chromium',
        headless: true,
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        trace: 'retain-on-failure'
    }
});