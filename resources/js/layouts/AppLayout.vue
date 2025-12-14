<script setup>
import { ref, computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useBrandStore } from '@/stores/brand';
import { useDarkMode } from '@/composables/useDarkMode';

const route = useRoute();
const authStore = useAuthStore();
const brandStore = useBrandStore();
const { isDark, toggle: toggleDarkMode } = useDarkMode();

const sidebarOpen = ref(false);
const userMenuOpen = ref(false);
const brandMenuOpen = ref(false);
const agencyMenuOpen = ref(false);
const switchingAgency = ref(false);

// Main navigation - brand-scoped content
const mainNavigation = [
    { name: 'Dashboard', href: '/dashboard', icon: 'home' },
    { name: 'Posts', href: '/posts', icon: 'document' },
    { name: 'Collections', href: '/collections', icon: 'folder' },
    { name: 'Approvals', href: '/approvals', icon: 'check-circle', requiresReview: true },
    { name: 'Media Library', href: '/media', icon: 'photograph' },
];

// Workspace navigation - agency-level management
const workspaceNavigation = [
    { name: 'Brands', href: '/brands', icon: 'office-building' },
    { name: 'Team', href: '/settings/team', icon: 'users' },
    { name: 'Settings', href: '/settings', icon: 'cog' },
];

// Admin navigation - super admin only
const adminNavigation = [
    { name: 'Admin Dashboard', href: '/admin', icon: 'shield-check' },
    { name: 'All Users', href: '/admin/users', icon: 'user-group' },
    { name: 'All Agencies', href: '/admin/agencies', icon: 'building-office' },
];

const filteredMainNavigation = computed(() => mainNavigation.filter(item => {
    if (item.requiresReview) {
        return authStore.canReview;
    }
    return true;
}));

const isActive = (href) => {
    if (href === '/settings' && route.path.startsWith('/settings/team')) {
        return false; // Don't highlight Settings when on Team
    }
    return route.path.startsWith(href);
};

const getUserInitials = () => {
    const name = authStore.user?.name || 'U';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const toggleUserMenu = () => {
    userMenuOpen.value = !userMenuOpen.value;
    brandMenuOpen.value = false;
};

const toggleBrandMenu = () => {
    brandMenuOpen.value = !brandMenuOpen.value;
    userMenuOpen.value = false;
};

const selectBrand = (brand) => {
    brandStore.setActiveBrand(brand);
    brandMenuOpen.value = false;
};

const toggleAgencyMenu = () => {
    agencyMenuOpen.value = !agencyMenuOpen.value;
    userMenuOpen.value = false;
    brandMenuOpen.value = false;
};

const selectAgency = async (agency) => {
    if (agency.id === authStore.user?.agency_id) {
        agencyMenuOpen.value = false;
        return;
    }
    try {
        switchingAgency.value = true;
        await authStore.switchAgency(agency.id);
        agencyMenuOpen.value = false;
    } catch (err) {
        console.error('Failed to switch workspace:', err);
    } finally {
        switchingAgency.value = false;
    }
};

const logout = async () => {
    await authStore.logout();
};
</script>

<template>
    <div class="antialiased bg-gray-50 dark:bg-gray-900 min-h-screen">
        <!-- Mobile top nav -->
        <nav class="bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 fixed lg:hidden left-0 right-0 top-0 z-50">
            <div class="flex flex-wrap justify-between items-center">
                <div class="flex justify-start items-center">
                    <button
                        @click="sidebarOpen = true"
                        class="p-2 mr-2 text-gray-600 rounded-lg cursor-pointer hover:text-gray-900 hover:bg-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700 focus:ring-2 focus:ring-gray-100 dark:focus:ring-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                    >
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only">Toggle sidebar</span>
                    </button>
                    <!-- Brand switcher (mobile) - more prominent -->
                    <button
                        @click="toggleBrandMenu"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm"
                    >
                        <div
                            v-if="brandStore.activeBrand?.logo_url"
                            class="w-6 h-6 rounded-full overflow-hidden bg-white"
                        >
                            <img :src="brandStore.activeBrand.logo_url" :alt="brandStore.activeBrand.name" class="w-full h-full object-cover" />
                        </div>
                        <div
                            v-else
                            class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center"
                        >
                            <span class="text-primary-600 dark:text-primary-400 text-xs font-semibold">
                                {{ brandStore.activeBrand?.name?.charAt(0)?.toUpperCase() || '?' }}
                            </span>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 max-w-[120px] truncate">
                            {{ brandStore.activeBrand?.name || 'Select Brand' }}
                        </span>
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Dark mode toggle (mobile) -->
                    <button
                        @click="toggleDarkMode"
                        class="p-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                    >
                        <svg v-if="isDark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                        </svg>
                    </button>
                    <!-- User avatar (mobile) -->
                    <button
                        @click="toggleUserMenu"
                        class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                    >
                        <span class="sr-only">Open user menu</span>
                        <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-medium">
                            {{ getUserInitials() }}
                        </div>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Mobile sidebar backdrop -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-gray-900/50 dark:bg-gray-900/80 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed top-0 left-0 z-40 w-64 h-screen pt-14 lg:pt-0 transition-transform bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]"
        >
            <div class="overflow-y-auto py-5 px-3 h-full bg-white dark:bg-gray-800">
                <!-- Logo (desktop) -->
                <RouterLink to="/dashboard" class="items-center pl-2.5 mb-5 hidden lg:flex shrink-0">
                    <img src="/images/post-reviewer-logo.svg" alt="Post Reviewer" class="h-10 shrink-0 dark:hidden" />
                    <img src="/images/post-reviewer-logo-dark.svg" alt="Post Reviewer" class="h-10 shrink-0 hidden dark:block" />
                </RouterLink>

                <!-- Close button (mobile) -->
                <button
                    @click="sidebarOpen = false"
                    class="absolute top-3 right-3 p-1.5 text-gray-500 rounded-lg lg:hidden hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Main Navigation -->
                <ul class="space-y-2">
                    <li v-for="item in filteredMainNavigation" :key="item.name">
                        <RouterLink
                            :to="item.href"
                            :class="[
                                'flex items-center p-2 text-base font-medium rounded-lg transition duration-75 group',
                                isActive(item.href)
                                    ? 'bg-primary-50 text-primary-700 dark:bg-gray-700 dark:text-primary-500'
                                    : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700'
                            ]"
                            @click="sidebarOpen = false"
                        >
                            <!-- Dashboard -->
                            <svg v-if="item.icon === 'home'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                            </svg>
                            <!-- Posts -->
                            <svg v-if="item.icon === 'document'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                            </svg>
                            <!-- Collections -->
                            <svg v-if="item.icon === 'folder'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                            </svg>
                            <!-- Approvals -->
                            <svg v-if="item.icon === 'check-circle'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <!-- Media Library -->
                            <svg v-if="item.icon === 'photograph'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                            </svg>
                            <span class="ml-3">{{ item.name }}</span>
                        </RouterLink>
                    </li>
                </ul>

                <!-- Workspace Section -->
                <div class="pt-5 mt-5 border-t border-gray-200 dark:border-gray-700">
                    <div class="px-2 mb-2">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Workspace</div>
                    </div>
                    <ul class="space-y-2">
                        <li v-for="item in workspaceNavigation" :key="item.name">
                            <RouterLink
                                :to="item.href"
                                :class="[
                                    'flex items-center p-2 text-base font-medium rounded-lg transition duration-75 group',
                                    isActive(item.href)
                                        ? 'bg-primary-50 text-primary-700 dark:bg-gray-700 dark:text-primary-500'
                                        : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700'
                                ]"
                                @click="sidebarOpen = false"
                            >
                                <!-- Brands -->
                                <svg v-if="item.icon === 'office-building'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" />
                                </svg>
                                <!-- Team -->
                                <svg v-if="item.icon === 'users'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                </svg>
                                <!-- Settings -->
                                <svg v-if="item.icon === 'cog'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                </svg>
                                <span class="ml-3">{{ item.name }}</span>
                            </RouterLink>
                        </li>
                    </ul>
                </div>

                <!-- Admin Section (Super Admin Only) -->
                <div v-if="authStore.isSuperAdmin" class="pt-5 mt-5 border-t border-red-200 dark:border-red-700/50">
                    <div class="px-2 mb-2">
                        <div class="text-xs font-medium text-red-500 dark:text-red-400 uppercase tracking-wider">Super Admin</div>
                    </div>
                    <ul class="space-y-2">
                        <li v-for="item in adminNavigation" :key="item.name">
                            <RouterLink
                                :to="item.href"
                                :class="[
                                    'flex items-center p-2 text-base font-medium rounded-lg transition duration-75 group',
                                    isActive(item.href)
                                        ? 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'
                                        : 'text-gray-900 hover:bg-red-50 dark:text-white dark:hover:bg-red-900/20'
                                ]"
                                @click="sidebarOpen = false"
                            >
                                <!-- Shield Check (Admin Dashboard) -->
                                <svg v-if="item.icon === 'shield-check'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-red-700 dark:text-red-400' : 'text-gray-500 group-hover:text-red-600 dark:text-gray-400 dark:group-hover:text-red-400']" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <!-- User Group (All Users) -->
                                <svg v-if="item.icon === 'user-group'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-red-700 dark:text-red-400' : 'text-gray-500 group-hover:text-red-600 dark:text-gray-400 dark:group-hover:text-red-400']" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                </svg>
                                <!-- Building Office (All Agencies) -->
                                <svg v-if="item.icon === 'building-office'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-red-700 dark:text-red-400' : 'text-gray-500 group-hover:text-red-600 dark:text-gray-400 dark:group-hover:text-red-400']" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" />
                                </svg>
                                <span class="ml-3">{{ item.name }}</span>
                            </RouterLink>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Sidebar footer -->
            <div class="absolute bottom-0 left-0 w-full bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <!-- Logo (mobile only) -->
                <div class="lg:hidden p-3 flex justify-center">
                    <RouterLink to="/dashboard" @click="sidebarOpen = false">
                        <img src="/images/post-reviewer-logo.svg" alt="Post Reviewer" class="h-8 shrink-0 dark:hidden" />
                        <img src="/images/post-reviewer-logo-dark.svg" alt="Post Reviewer" class="h-8 shrink-0 hidden dark:block" />
                    </RouterLink>
                </div>
                <!-- Desktop footer icons -->
                <div class="hidden lg:flex justify-center p-4 space-x-4">
                    <!-- Dark mode toggle -->
                    <button
                        @click="toggleDarkMode"
                        class="inline-flex justify-center p-2 text-gray-500 rounded-lg cursor-pointer dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                    >
                        <svg v-if="isDark" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                        </svg>
                    </button>
                    <!-- Settings -->
                    <RouterLink
                        to="/settings"
                        class="inline-flex justify-center p-2 text-gray-500 rounded-lg cursor-pointer dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                    >
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                        </svg>
                    </RouterLink>
                </div>
            </div>
        </aside>

        <!-- Main content area -->
        <div class="lg:ml-64">
            <!-- Top bar (desktop) -->
            <header class="hidden lg:flex fixed top-0 right-0 left-64 z-40 h-16 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex flex-1 justify-end items-center px-4">
                    <div class="flex items-center gap-2">
                        <!-- Workspace Switcher (only if multiple agencies) -->
                        <button
                            v-if="authStore.hasMultipleAgencies"
                            @click="toggleAgencyMenu"
                            :disabled="switchingAgency"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-purple-300 dark:border-purple-600 bg-purple-50 dark:bg-purple-900/30 hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors shadow-sm"
                        >
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-medium text-purple-700 dark:text-purple-300 max-w-32 truncate">
                                {{ authStore.user?.agency?.name || 'Workspace' }}
                            </span>
                            <svg v-if="switchingAgency" class="w-4 h-4 text-purple-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 text-purple-500 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Brand Switcher -->
                        <button
                            @click="toggleBrandMenu"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors shadow-sm"
                        >
                            <div
                                v-if="brandStore.activeBrand?.logo_url"
                                class="w-6 h-6 rounded-full overflow-hidden bg-white"
                            >
                                <img :src="brandStore.activeBrand.logo_url" :alt="brandStore.activeBrand.name" class="w-full h-full object-cover" />
                            </div>
                            <div
                                v-else
                                class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center"
                            >
                                <span class="text-primary-600 dark:text-primary-400 text-xs font-semibold">
                                    {{ brandStore.activeBrand?.name?.charAt(0)?.toUpperCase() || '?' }}
                                </span>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white max-w-32 truncate">
                                {{ brandStore.activeBrand?.name || 'Select Brand' }}
                            </span>
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Divider -->
                        <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>

                        <!-- Notifications -->
                        <button class="p-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600">
                            <span class="sr-only">View notifications</span>
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                            </svg>
                        </button>

                        <!-- User menu -->
                        <div class="relative z-50">
                            <button
                                @click="toggleUserMenu"
                                class="flex items-center space-x-3 text-sm rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 p-2 -m-2"
                            >
                                <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-medium">
                                    {{ getUserInitials() }}
                                </div>
                                <span class="text-gray-900 dark:text-white font-medium">{{ authStore.user?.name }}</span>
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Development Notice Banner -->
            <div class="bg-amber-500 text-amber-950">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
                    <div class="flex items-center justify-center gap-2 text-sm font-medium">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>This app is under development. Your account may be reset during this period.</span>
                    </div>
                </div>
            </div>

            <!-- Page content -->
            <main class="p-4 pt-4 lg:pt-16 min-h-screen dark:bg-gray-900">
                <slot />
            </main>
        </div>

        <!-- Brand menu dropdown -->
        <Teleport to="body">
            <div
                v-if="brandMenuOpen"
                class="fixed inset-0 z-[60]"
                @click="brandMenuOpen = false"
            >
                <div
                    @click.stop
                    class="absolute left-4 right-4 top-14 lg:left-auto lg:right-4 lg:top-16 w-auto lg:w-64 bg-white rounded-lg shadow-lg dark:bg-gray-700 max-h-80 overflow-y-auto"
                >
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-600">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Switch Brand</p>
                    </div>
                    <ul class="py-1">
                        <li v-for="brand in brandStore.brands" :key="brand.id">
                            <button
                                @click="selectBrand(brand)"
                                :class="[
                                    'flex items-center gap-3 w-full px-4 py-2.5 text-left hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors',
                                    brand.id === brandStore.activeBrandId ? 'bg-primary-50 dark:bg-primary-900/20' : ''
                                ]"
                            >
                                <div
                                    v-if="brand.logo_url"
                                    class="w-8 h-8 rounded-full overflow-hidden bg-white flex-shrink-0"
                                >
                                    <img :src="brand.logo_url" :alt="brand.name" class="w-full h-full object-cover" />
                                </div>
                                <div
                                    v-else
                                    class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0"
                                >
                                    <span class="text-primary-600 dark:text-primary-400 text-sm font-semibold">
                                        {{ brand.name?.charAt(0)?.toUpperCase() }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ brand.name }}</p>
                                </div>
                                <svg
                                    v-if="brand.id === brandStore.activeBrandId"
                                    class="w-5 h-5 text-primary-600 dark:text-primary-400 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </li>
                    </ul>
                    <div class="border-t border-gray-100 dark:border-gray-600 py-1">
                        <RouterLink
                            to="/brands"
                            @click="brandMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                            </svg>
                            Manage Brands
                        </RouterLink>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Agency/Workspace menu dropdown -->
        <Teleport to="body">
            <div
                v-if="agencyMenuOpen"
                class="fixed inset-0 z-[60]"
                @click="agencyMenuOpen = false"
            >
                <div
                    @click.stop
                    class="absolute left-4 right-4 top-14 lg:left-auto lg:right-4 lg:top-16 w-auto lg:w-72 bg-white rounded-lg shadow-lg dark:bg-gray-700 max-h-80 overflow-y-auto"
                >
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-600">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Switch Workspace</p>
                    </div>
                    <ul class="py-1">
                        <li v-for="agency in authStore.agencies" :key="agency.id">
                            <button
                                @click="selectAgency(agency)"
                                :disabled="switchingAgency"
                                :class="[
                                    'flex items-center gap-3 w-full px-4 py-2.5 text-left hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors disabled:opacity-50',
                                    agency.id === authStore.user?.agency_id ? 'bg-purple-50 dark:bg-purple-900/20' : ''
                                ]"
                            >
                                <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ agency.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ agency.pivot?.role || 'member' }}</p>
                                </div>
                                <svg
                                    v-if="agency.id === authStore.user?.agency_id"
                                    class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </Teleport>

        <!-- User menu dropdown (unified) -->
        <Teleport to="body">
            <div
                v-if="userMenuOpen"
                class="fixed inset-0 z-[60]"
                @click="userMenuOpen = false"
            >
                <div
                    @click.stop
                    class="absolute right-4 top-14 lg:top-16 w-56 bg-white rounded-lg shadow-lg dark:bg-gray-700 divide-y divide-gray-100 dark:divide-gray-600"
                >
                    <div class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ authStore.user?.name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ authStore.user?.email }}</p>
                    </div>
                    <ul class="py-1">
                        <li>
                            <RouterLink
                                to="/settings"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white"
                                @click="userMenuOpen = false"
                            >
                                Settings
                            </RouterLink>
                        </li>
                    </ul>
                    <div class="py-1">
                        <button
                            @click="logout"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white"
                        >
                            Sign out
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
