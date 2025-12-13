import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useBrandStore } from '@/stores/brand';

const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import('@/pages/Home.vue'),
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/auth/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/pages/auth/Register.vue'),
        meta: { guest: true },
    },
    {
        path: '/invitation/:token',
        name: 'invitation',
        component: () => import('@/pages/auth/AcceptInvitation.vue'),
        meta: { guest: true },
    },
    {
        path: '/privacy',
        name: 'privacy',
        component: () => import('@/pages/PrivacyPolicy.vue'),
    },
    {
        path: '/terms',
        name: 'terms',
        component: () => import('@/pages/TermsConditions.vue'),
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('@/pages/Dashboard.vue'),
        meta: { requiresAuth: true, requiresBrand: true },
    },
    {
        path: '/brands',
        name: 'brands',
        component: () => import('@/pages/brands/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/brands/:id',
        name: 'brand.show',
        component: () => import('@/pages/brands/Show.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/posts',
        name: 'posts',
        component: () => import('@/pages/posts/Index.vue'),
        meta: { requiresAuth: true, requiresBrand: true },
    },
    {
        path: '/posts/create',
        name: 'posts.create',
        component: () => import('@/pages/posts/Create.vue'),
        meta: { requiresAuth: true, requiresBrand: true },
    },
    {
        path: '/posts/batch-create',
        name: 'posts.batch-create',
        component: () => import('@/pages/posts/BatchCreate.vue'),
        meta: { requiresAuth: true, requiresBrand: true },
    },
    {
        path: '/posts/:id',
        name: 'posts.show',
        component: () => import('@/pages/posts/Show.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/posts/:id/edit',
        name: 'posts.edit',
        component: () => import('@/pages/posts/Edit.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/collections',
        name: 'collections',
        component: () => import('@/pages/collections/Index.vue'),
        meta: { requiresAuth: true, requiresBrand: true },
    },
    {
        path: '/collections/:id',
        name: 'collections.show',
        component: () => import('@/pages/collections/Show.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/approvals',
        name: 'approvals',
        component: () => import('@/pages/approvals/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/media',
        name: 'media',
        component: () => import('@/pages/media/Index.vue'),
        meta: { requiresAuth: true, requiresBrand: true },
    },
    {
        path: '/settings',
        name: 'settings',
        component: () => import('@/pages/settings/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/settings/team',
        name: 'settings.team',
        component: () => import('@/pages/settings/Index.vue'),
        meta: { requiresAuth: true, defaultTab: 'team' },
    },
    {
        path: '/review/:token',
        name: 'public.approval',
        component: () => import('@/pages/public/ApprovalReview.vue'),
        meta: { public: true },
    },
    {
        path: '/post-review/:token',
        name: 'public.post-review',
        component: () => import('@/pages/public/PostReview.vue'),
        meta: { public: true },
    },
    // Admin routes (super admin only)
    {
        path: '/admin',
        name: 'admin.dashboard',
        component: () => import('@/pages/admin/Dashboard.vue'),
        meta: { requiresAuth: true, requiresSuperAdmin: true },
    },
    {
        path: '/admin/users',
        name: 'admin.users',
        component: () => import('@/pages/admin/Users.vue'),
        meta: { requiresAuth: true, requiresSuperAdmin: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Track if we've initialized auth
let authInitialized = false;

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Public routes don't need auth checks
    if (to.meta.public) {
        return next();
    }

    // Initialize auth on first navigation
    if (!authInitialized && authStore.token) {
        authInitialized = true;
        try {
            await authStore.fetchUser();
        } catch (error) {
            // Token is invalid, continue to route handling
        }
    }

    const isAuthenticated = authStore.isAuthenticated;

    // Route requires auth but user is not authenticated
    if (to.meta.requiresAuth && !isAuthenticated) {
        return next({ name: 'login', query: { redirect: to.fullPath } });
    }

    // Route is for guests only but user is authenticated
    if (to.meta.guest && isAuthenticated) {
        return next({ name: 'dashboard' });
    }

    // Route requires a brand but no brand is selected
    if (to.meta.requiresBrand && isAuthenticated) {
        const brandStore = useBrandStore();
        // Wait for brands to be loaded if not initialized
        if (!brandStore.initialized && brandStore.brands.length === 0) {
            // Brands should already be loaded from auth store init
            // But if not, the component will handle redirect
        }
        // If no active brand, redirect to brands page
        if (!brandStore.activeBrand && brandStore.hasBrands === false) {
            return next({ name: 'brands' });
        }
    }

    next();
});

export default router;
