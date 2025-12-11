import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authApi } from '@/services/api';
import router from '@/router';

export const useAuthStore = defineStore('auth', () => {
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
