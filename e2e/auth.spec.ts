import { test, expect } from '@playwright/test';
import { registerUser, loginAs, uniqueEmail, uniqueName, uniqueAgencyName } from './utils/test-helpers';

test.describe('Authentication', () => {
    test.describe('Registration', () => {
        test('user can register with valid data', async ({ page }) => {
            const email = uniqueEmail();
            const name = uniqueName();
            const agencyName = uniqueAgencyName();
            const password = 'TestPassword123!';

            await page.goto('/register');

            await page.getByLabel('Your Name').fill(name);
            await page.getByLabel(/agency.*company.*name/i).fill(agencyName);
            await page.getByLabel('Email address').fill(email);
            await page.getByLabel('Password', { exact: true }).fill(password);
            await page.getByLabel('Confirm Password').fill(password);

            await page.getByRole('button', { name: /create account|sign up|register/i }).click();

            // Should redirect to dashboard or brands page
            await expect(page).toHaveURL(/\/(dashboard|brands)/, { timeout: 10000 });

            // User name should be visible somewhere (nav, header, etc.)
            await expect(page.getByText(name)).toBeVisible({ timeout: 5000 });
        });

        test('registration fails with missing fields', async ({ page }) => {
            await page.goto('/register');

            // Try to submit empty form
            await page.getByRole('button', { name: 'Create Account' }).click();

            // Should show validation errors (stay on page)
            await expect(page).toHaveURL(/\/register/);
        });

        test('registration fails with invalid email', async ({ page }) => {
            await page.goto('/register');

            await page.getByLabel('Your Name').fill('Test User');
            await page.getByLabel(/agency.*company.*name/i).fill('Test Agency');
            await page.getByLabel('Email address').fill('invalid-email');
            await page.getByLabel('Password', { exact: true }).fill('TestPassword123!');
            await page.getByLabel('Confirm Password').fill('TestPassword123!');

            await page.getByRole('button', { name: /create account|sign up|register/i }).click();

            // Should show error or stay on register page
            await expect(page).toHaveURL(/\/register/);
        });

        test('registration fails with mismatched passwords', async ({ page }) => {
            await page.goto('/register');

            await page.getByLabel('Your Name').fill('Test User');
            await page.getByLabel(/agency.*company.*name/i).fill('Test Agency');
            await page.getByLabel('Email address').fill(uniqueEmail());
            await page.getByLabel('Password', { exact: true }).fill('TestPassword123!');
            await page.getByLabel('Confirm Password').fill('DifferentPassword!');

            await page.getByRole('button', { name: /create account|sign up|register/i }).click();

            // Should show error or stay on register page
            await expect(page).toHaveURL(/\/register/);
        });
    });

    test.describe('Login', () => {
        let testUser: { email: string; password: string };

        test.beforeAll(async ({ browser }) => {
            // Register a user to use for login tests
            const page = await browser.newPage();
            testUser = await registerUser(page);
            await page.close();
        });

        test('user can login with valid credentials', async ({ page }) => {
            await page.goto('/login');

            await page.getByLabel('Email address').fill(testUser.email);
            await page.getByLabel('Password').fill(testUser.password);

            await page.getByRole('button', { name: /sign in|log in|login/i }).click();

            // Should redirect to dashboard
            await expect(page).toHaveURL(/\/(dashboard|brands)/, { timeout: 10000 });
        });

        test('login fails with wrong password', async ({ page }) => {
            await page.goto('/login');

            await page.getByLabel('Email address').fill(testUser.email);
            await page.getByLabel('Password').fill('WrongPassword!');

            await page.getByRole('button', { name: /sign in|log in|login/i }).click();

            // Should show error message
            await expect(page.getByText(/invalid|incorrect|wrong/i)).toBeVisible({ timeout: 5000 });

            // Should stay on login page
            await expect(page).toHaveURL(/\/login/);
        });

        test('login fails with non-existent email', async ({ page }) => {
            await page.goto('/login');

            await page.getByLabel('Email address').fill('nonexistent@example.com');
            await page.getByLabel('Password').fill('TestPassword123!');

            await page.getByRole('button', { name: /sign in|log in|login/i }).click();

            // Should show error message
            await expect(page.getByText(/invalid|incorrect|not found/i)).toBeVisible({ timeout: 5000 });
        });
    });

    test.describe('Logout', () => {
        test('user can logout', async ({ page }) => {
            // Register and login
            const { email, password } = await registerUser(page);

            // Find and click logout (may be in a dropdown menu)
            // First try to find a user menu button
            const userMenuButton = page.locator('[data-testid="user-menu"], button:has-text("Account"), button:has-text("Profile")').first();

            if (await userMenuButton.isVisible()) {
                await userMenuButton.click();
            }

            // Click logout
            await page.getByRole('button', { name: /logout|sign out/i }).click();

            // Should redirect to login or home
            await expect(page).toHaveURL(/\/(login)?$/, { timeout: 10000 });
        });
    });
});
