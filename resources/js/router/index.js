import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

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
        meta: { requiresAuth: true },
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
        meta: { requiresAuth: true },
    },
    {
        path: '/posts/create',
        name: 'posts.create',
        component: () => import('@/pages/posts/Create.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/posts/batch-create',
        name: 'posts.batch-create',
        component: () => import('@/pages/posts/BatchCreate.vue'),
        meta: { requiresAuth: true },
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
        meta: { requiresAuth: true },
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
        meta: { requiresAuth: true },
    },
    {
        path: '/settings',
        name: 'settings',
        component: () => import('@/pages/settings/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/review/:token',
        name: 'public.approval',
        component: () => import('@/pages/public/ApprovalReview.vue'),
        meta: { public: true },
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

    // Redirect home to dashboard if authenticated
    if (to.name === 'home' && isAuthenticated) {
        return next({ name: 'dashboard' });
    }

    next();
});

export default router;
