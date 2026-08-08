const { test, expect } = require('@playwright/test');
const { appPath, login } = require('../auth/login.helpers');

test('Student records open full student profile', async ({ page }) => {
  await login(page);
  await page.goto(appPath('index.php?page=students'));
  const studentLink = page.locator('a[href*="page=student_profile"]').first();
  await expect(studentLink).toBeVisible();
  await studentLink.click();
  await expect(page).toHaveURL(/page=student_profile/);
  await expect(page.getByRole('heading', { level: 2 })).toBeVisible();
  await expect(page.getByRole('tab', { name: 'Grades' })).toBeVisible();
  await expect(page.getByRole('tab', { name: 'Attendance' })).toBeVisible();
  await page.getByRole('tab', { name: 'Grades' }).click();
  await expect(page.locator('#profileGrades')).toBeVisible();
  await expect(page.locator('#profileGrades')).toContainText(/Participation|No grade records/);
  await page.getByRole('tab', { name: 'Attendance' }).click();
  await expect(page.locator('#profileAttendance')).toBeVisible();
  await expect(page.locator('#profileAttendance')).toContainText(/Hours Rendered|No attendance records/);
});
