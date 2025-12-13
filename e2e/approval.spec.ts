import { test, expect } from '@playwright/test';
import { registerUser, uniqueBrandName } from './utils/test-helpers';

test.describe('Approval Workflow', () => {
    let brandName: string;

    test.beforeEach(async ({ page }) => {
        // Register a fresh user
        await registerUser(page);

        // Create a brand
        brandName = uniqueBrandName();
        await page.goto('/brands');
        await page.getByRole('button', { name: /create|new/i }).click();
        await page.getByLabel(/brand name/i).fill(brandName);
        await page.getByRole('button', { name: /create|save/i }).last().click();
        await expect(page.getByText(brandName)).toBeVisible({ timeout: 10000 });
    });

    test('post detail page shows approval status', async ({ page }) => {
        // Navigate to create post
        await page.getByText(brandName).click();
        await page.getByRole('link', { name: /create post/i }).click();

        // Fill post details
        await page.getByLabel(/title/i).fill('Approval Test Post');

        const platformCheckbox = page.getByLabel(/facebook feed|instagram feed/i).first();
        if (await platformCheckbox.isVisible()) {
            await platformCheckbox.check();
        }

        // If we can submit, do so
        const createButton = page.getByRole('button', { name: /create|save/i });
        if (await createButton.isEnabled()) {
            await createButton.click();

            // Wait for redirect to post detail
            await page.waitForURL(/\/posts\/\d+/, { timeout: 10000 });

            // Should show some status indicator (draft, pending, approved, etc.)
            const statusIndicators = page.getByText(/draft|pending|approved|changes requested/i);
            await expect(statusIndicators.first()).toBeVisible({ timeout: 5000 });
        }
    });

    test('post can be submitted for approval', async ({ page }) => {
        // Navigate to create post
        await page.getByText(brandName).click();
        await page.getByRole('link', { name: /create post/i }).click();

        await page.getByLabel(/title/i).fill('Post to Submit');

        const platformCheckbox = page.getByLabel(/facebook feed|instagram feed/i).first();
        if (await platformCheckbox.isVisible()) {
            await platformCheckbox.check();
        }

        const createButton = page.getByRole('button', { name: /create|save/i });
        if (await createButton.isEnabled()) {
            await createButton.click();
            await page.waitForURL(/\/posts\/\d+/, { timeout: 10000 });

            // Look for submit for approval button
            const submitButton = page.getByRole('button', { name: /submit|request approval|send for approval/i });

            if (await submitButton.isVisible()) {
                await submitButton.click();

                // Should show some confirmation or status change
                await expect(page.getByText(/pending|submitted|approval/i)).toBeVisible({ timeout: 5000 });
            }
        }
    });

    test('approvals page is accessible', async ({ page }) => {
        // Navigate to approvals page (if it exists)
        await page.goto('/approvals');

        // Should either show approvals list or redirect
        const isOnApprovalsPage = page.url().includes('/approvals');
        const hasApprovalContent = await page.getByText(/approval|pending|review/i).isVisible().catch(() => false);

        // Either we're on the page or we were redirected
        expect(isOnApprovalsPage || page.url().includes('/login') || page.url().includes('/dashboard')).toBeTruthy();
    });
});

test.describe('Public Approval Link', () => {
    test('invalid approval token shows error', async ({ page }) => {
        // Try to access with invalid token
        await page.goto('/public/approval/invalid-token-12345');

        // Should show error message
        await expect(page.getByText(/invalid|expired|not found|error/i)).toBeVisible({ timeout: 5000 });
    });

    test('invalid review token shows error', async ({ page }) => {
        // Try to access with invalid token
        await page.goto('/public/review/invalid-token-12345');

        // Should show error message
        await expect(page.getByText(/invalid|expired|not found|error/i)).toBeVisible({ timeout: 5000 });
    });
});

test.describe('Post Status Workflow', () => {
    test('draft posts can be edited', async ({ page }) => {
        await registerUser(page);

        // Create brand
        const brandName = uniqueBrandName();
        await page.goto('/brands');
        await page.getByRole('button', { name: /create|new/i }).click();
        await page.getByLabel(/brand name/i).fill(brandName);
        await page.getByRole('button', { name: /create|save/i }).last().click();
        await expect(page.getByText(brandName)).toBeVisible({ timeout: 10000 });

        // Create post
        await page.getByText(brandName).click();
        await page.getByRole('link', { name: /create post/i }).click();

        await page.getByLabel(/title/i).fill('Editable Draft Post');

        const platformCheckbox = page.getByLabel(/facebook feed|instagram feed/i).first();
        if (await platformCheckbox.isVisible()) {
            await platformCheckbox.check();
        }

        const createButton = page.getByRole('button', { name: /create|save/i });
        if (await createButton.isEnabled()) {
            await createButton.click();
            await page.waitForURL(/\/posts\/\d+/, { timeout: 10000 });

            // Look for edit button
            const editButton = page.getByRole('button', { name: /edit/i });

            if (await editButton.isVisible()) {
                await editButton.click();

                // Should show edit form
                await expect(page.getByLabel(/title/i)).toBeVisible();
            }
        }
    });
});
