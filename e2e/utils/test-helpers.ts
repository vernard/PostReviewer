import { Page, expect } from '@playwright/test';

// Generate unique test data
export function uniqueEmail(): string {
    return `test-${Date.now()}-${Math.random().toString(36).slice(2)}@example.com`;
}

export function uniqueName(): string {
    return `Test User ${Date.now()}`;
}

export function uniqueBrandName(): string {
    return `Test Brand ${Date.now()}`;
}

export function uniqueAgencyName(): string {
    return `Test Agency ${Date.now()}`;
}

// Authentication helpers
export async function registerUser(page: Page, options?: {
    name?: string;
    email?: string;
    password?: string;
    agencyName?: string;
}): Promise<{ email: string; password: string; name: string; agencyName: string }> {
    const name = options?.name || uniqueName();
    const email = options?.email || uniqueEmail();
    const password = options?.password || 'TestPassword123!';
    const agencyName = options?.agencyName || uniqueAgencyName();

    await page.goto('/register');

    await page.getByLabel('Your Name').fill(name);
    await page.getByLabel(/agency.*company.*name/i).fill(agencyName);
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByLabel('Confirm Password').fill(password);

    await page.getByRole('button', { name: /create account|sign up|register/i }).click();

    // Wait for redirect to dashboard
    await expect(page).toHaveURL(/\/(dashboard|brands)/, { timeout: 10000 });

    return { email, password, name, agencyName };
}

export async function loginAs(page: Page, email: string, password: string): Promise<void> {
    await page.goto('/login');

    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password').fill(password);

    await page.getByRole('button', { name: /sign in|log in|login/i }).click();

    // Wait for redirect to dashboard
    await expect(page).toHaveURL(/\/(dashboard|brands)/, { timeout: 10000 });
}

export async function logout(page: Page): Promise<void> {
    // Click on user menu or logout button
    const userMenu = page.getByRole('button', { name: /profile|account|menu/i });
    if (await userMenu.isVisible()) {
        await userMenu.click();
    }

    await page.getByRole('button', { name: /logout|sign out/i }).click();

    // Wait for redirect to login or home
    await expect(page).toHaveURL(/\/(login|$)/, { timeout: 10000 });
}

// Brand helpers
export async function createBrand(page: Page, name?: string): Promise<string> {
    const brandName = name || uniqueBrandName();

    await page.goto('/brands');

    // Click create brand button
    await page.getByRole('button', { name: /create|new/i }).click();

    // Fill in brand name
    await page.getByLabel('Brand Name').fill(brandName);

    // Submit
    await page.getByRole('button', { name: /create|save/i }).click();

    // Wait for brand to be created
    await expect(page.getByText(brandName)).toBeVisible({ timeout: 10000 });

    return brandName;
}

// Post helpers
export async function navigateToPosts(page: Page): Promise<void> {
    await page.goto('/posts');
    await expect(page).toHaveURL(/\/posts/);
}

export async function navigateToBrands(page: Page): Promise<void> {
    await page.goto('/brands');
    await expect(page).toHaveURL(/\/brands/);
}

// Wait helpers
export async function waitForToast(page: Page, text: string | RegExp): Promise<void> {
    await expect(page.getByText(text)).toBeVisible({ timeout: 5000 });
}

export async function waitForPageLoad(page: Page): Promise<void> {
    await page.waitForLoadState('networkidle');
}
