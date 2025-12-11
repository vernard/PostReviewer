<script setup>
import { ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useDarkMode } from '@/composables/useDarkMode';

const route = useRoute();
const authStore = useAuthStore();
const { isDark, toggle: toggleDarkMode } = useDarkMode();

const sidebarOpen = ref(false);
const userMenuOpen = ref(false);

const navigation = [
    { name: 'Dashboard', href: '/dashboard', icon: 'home' },
    { name: 'Posts', href: '/posts', icon: 'document' },
    { name: 'Approvals', href: '/approvals', icon: 'check-circle', requiresReview: true },
    { name: 'Media Library', href: '/media', icon: 'photograph' },
    { name: 'Brands', href: '/brands', icon: 'office-building' },
    { name: 'Settings', href: '/settings', icon: 'cog' },
];

const filteredNavigation = navigation.filter(item => {
    if (item.requiresReview) {
        return authStore.canReview;
    }
    return true;
});

const isActive = (href) => route.path.startsWith(href);

const getUserInitials = () => {
    const name = authStore.user?.name || 'U';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
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
                    <RouterLink to="/dashboard" class="flex items-center">
                        <span class="self-center text-xl font-semibold whitespace-nowrap text-primary-600 dark:text-primary-500">Post Reviewer</span>
                    </RouterLink>
                </div>
                <div class="flex items-center">
                    <!-- Dark mode toggle (mobile) -->
                    <button
                        @click="toggleDarkMode"
                        class="p-2 mr-1 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
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
                        @click="userMenuOpen = !userMenuOpen"
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
                <RouterLink to="/dashboard" class="items-center pl-2.5 mb-5 hidden lg:flex">
                    <span class="self-center text-xl font-semibold whitespace-nowrap text-primary-600 dark:text-primary-500">Post Reviewer</span>
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

                <!-- Navigation -->
                <ul class="space-y-2">
                    <li v-for="item in filteredNavigation" :key="item.name">
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
                            <!-- Approvals -->
                            <svg v-if="item.icon === 'check-circle'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <!-- Media Library -->
                            <svg v-if="item.icon === 'photograph'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                            </svg>
                            <!-- Brands -->
                            <svg v-if="item.icon === 'office-building'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" />
                            </svg>
                            <!-- Settings -->
                            <svg v-if="item.icon === 'cog'" :class="['w-6 h-6 transition duration-75', isActive(item.href) ? 'text-primary-700 dark:text-primary-500' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white']" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                            </svg>
                            <span class="ml-3">{{ item.name }}</span>
                        </RouterLink>
                    </li>
                </ul>

                <!-- Agency info -->
                <div class="pt-5 mt-5 border-t border-gray-200 dark:border-gray-700">
                    <div class="px-2">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Agency</div>
                        <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ authStore.user?.agency?.name }}</div>
                    </div>
                </div>
            </div>

            <!-- Sidebar footer -->
            <div class="hidden absolute bottom-0 left-0 justify-center p-4 space-x-4 w-full lg:flex bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
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
        </aside>

        <!-- Main content area -->
        <div class="lg:ml-64">
            <!-- Top bar (desktop) -->
            <header class="hidden lg:flex sticky top-0 z-30 h-16 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex flex-1 justify-end items-center px-4">
                    <!-- Notifications -->
                    <button class="p-2 mr-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600">
                        <span class="sr-only">View notifications</span>
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                        </svg>
                    </button>

                    <!-- User menu -->
                    <div class="relative">
                        <button
                            @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center space-x-3 text-sm rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                        >
                            <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-medium">
                                {{ getUserInitials() }}
                            </div>
                            <span class="text-gray-900 dark:text-white font-medium">{{ authStore.user?.name }}</span>
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div
                            v-if="userMenuOpen"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg dark:bg-gray-700 divide-y divide-gray-100 dark:divide-gray-600 z-50"
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
                </div>
            </header>

            <!-- Page content -->
            <main class="p-4 pt-20 lg:pt-4 min-h-screen dark:bg-gray-900">
                <slot />
            </main>
        </div>

        <!-- User menu dropdown (mobile) -->
        <div
            v-if="userMenuOpen"
            class="lg:hidden fixed inset-0 z-50"
            @click="userMenuOpen = false"
        >
            <div class="absolute top-14 right-4 w-56 bg-white rounded-lg shadow-lg dark:bg-gray-700 divide-y divide-gray-100 dark:divide-gray-600">
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

        <!-- Click outside backdrop for desktop dropdown -->
        <div
            v-if="userMenuOpen"
            class="hidden lg:block fixed inset-0 z-40"
            @click="userMenuOpen = false"
        />
    </div>
</template>
