import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authApi } from '@/services/api';
import router from '@/router';
import { useBrandStore } from './brand';

export const useAuthStore = defineStore('auth', () => {
    const getBrandStore = () => useBrandStore();
    const user = ref(null);
    const token = ref(localStorage.getItem('auth_token'));
    const loading = ref(false);
    const error = ref(null);

    const isAuthenticated = computed(() => !!user.value);
    const isAdmin = computed(() => user.value?.role === 'admin');
    const isManager = computed(() => ['admin', 'manager'].includes(user.value?.role));
    const canReview = computed(() => ['admin', 'manager', 'reviewer'].includes(user.value?.role));

    async function fetchUser() {
        if (!token.value) return;

        try {
            loading.value = true;
            const response = await authApi.user();
            user.value = response.data.user;

            // Initialize brand store after user is loaded
            const brandStore = getBrandStore();
            await brandStore.fetchBrands();

            // Subscribe to real-time brand updates
            brandStore.subscribeToChannel(user.value?.agency_id);
        } catch (err) {
            console.error('Failed to fetch user:', err);
            logout();
        } finally {
            loading.value = false;
        }
    }

    async function login(credentials) {
        try {
            loading.value = true;
            error.value = null;

            const response = await authApi.login(credentials);
            token.value = response.data.token;
            user.value = response.data.user;
            localStorage.setItem('auth_token', token.value);

            // Initialize brand store after login
            const brandStore = getBrandStore();
            await brandStore.fetchBrands();
            brandStore.subscribeToChannel(user.value?.agency_id);

            router.push('/dashboard');
        } catch (err) {
            error.value = err.response?.data?.message || 'Login failed';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function register(data) {
        try {
            loading.value = true;
            error.value = null;

            const response = await authApi.register(data);
            token.value = response.data.token;
            user.value = response.data.user;
            localStorage.setItem('auth_token', token.value);

            // Initialize brand store after registration
            const brandStore = getBrandStore();
            await brandStore.fetchBrands();
            brandStore.subscribeToChannel(user.value?.agency_id);

            router.push('/dashboard');
        } catch (err) {
            error.value = err.response?.data?.message || 'Registration failed';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            if (token.value) {
                await authApi.logout();
            }
        } catch (err) {
            console.error('Logout error:', err);
        } finally {
            user.value = null;
            token.value = null;
            localStorage.removeItem('auth_token');

            // Reset brand store on logout
            const brandStore = getBrandStore();
            brandStore.reset();

            router.push('/login');
        }
    }

    async function acceptInvitation(inviteToken, data) {
        try {
            loading.value = true;
            error.value = null;

            const response = await authApi.acceptInvitation(inviteToken, data);
            token.value = response.data.token;
            user.value = response.data.user;
            localStorage.setItem('auth_token', token.value);

            // Initialize brand store after accepting invitation
            const brandStore = getBrandStore();
            await brandStore.fetchBrands();
            brandStore.subscribeToChannel(user.value?.agency_id);

            router.push('/dashboard');
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to accept invitation';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        user,
        token,
        loading,
        error,
        isAuthenticated,
        isAdmin,
        isManager,
        canReview,
        fetchUser,
        login,
        register,
        logout,
        acceptInvitation,
    };
});
