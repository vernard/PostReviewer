import { test, expect } from '@playwright/test';
import { registerUser, uniqueBrandName } from './utils/test-helpers';

test.describe('Brand Management', () => {
    test.beforeEach(async ({ page }) => {
        // Register a fresh user for each test
        await registerUser(page);
    });

    test('user can create a new brand', async ({ page }) => {
        const brandName = uniqueBrandName();

        await page.goto('/brands');

        // Click add brand button
        await page.getByRole('button', { name: /add brand/i }).click();

        // Wait for modal to appear
        await expect(page.getByRole('heading', { name: /create new brand/i })).toBeVisible();

        // Fill in brand name
        await page.locator('input[type="text"]').first().fill(brandName);

        // Submit the form
        await page.getByRole('button', { name: /create brand/i }).click();

        // Should see the brand in the list
        await expect(page.getByText(brandName)).toBeVisible({ timeout: 10000 });
    });

    test('user can view brand details', async ({ page }) => {
        await page.goto('/brands');

        // Click on the first brand card (default brand created during registration)
        await page.locator('a[href^="/brands/"]').first().click();

        // Should see brand detail page
        await expect(page).toHaveURL(/\/brands\/\d+/);
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    });

    test('user can edit brand name', async ({ page }) => {
        const newName = uniqueBrandName();

        await page.goto('/brands');

        // Click on first brand to view details
        await page.locator('a[href^="/brands/"]').first().click();
        await expect(page).toHaveURL(/\/brands\/\d+/);

        // Click edit button
        await page.getByRole('button', { name: /edit/i }).click();

        // Update brand name
        await page.locator('input[type="text"]').first().fill(newName);

        // Save changes
        await page.getByRole('button', { name: /save/i }).click();

        // Should see updated name
        await expect(page.getByRole('heading', { name: newName })).toBeVisible({ timeout: 10000 });
    });

    test('brands page shows brands for new users', async ({ page }) => {
        await page.goto('/brands');

        // A default brand is created during registration, so there should be at least one
        const brandCards = page.locator('a[href^="/brands/"]');

        // Should have at least one brand
        await expect(brandCards.first()).toBeVisible({ timeout: 5000 });
    });

    test('user can navigate between brands list and details', async ({ page }) => {
        await page.goto('/brands');

        // Navigate to first brand detail
        await page.locator('a[href^="/brands/"]').first().click();
        await expect(page).toHaveURL(/\/brands\/\d+/);

        // Navigate back to list
        await page.goto('/brands');
        await expect(page).toHaveURL(/\/brands$/);
    });
});
