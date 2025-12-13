<script setup>
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { adminApi } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const users = ref([]);
const pagination = ref({});
const loading = ref(true);
const error = ref(null);
const impersonating = ref(null);

const sortBy = ref('posts_count');
const sortDirection = ref('desc');
const search = ref('');
const currentPage = ref(1);

const sortOptions = [
    { value: 'posts_count', label: 'Posts (Power Users)' },
    { value: 'brands_count', label: 'Brands' },
    { value: 'created_at', label: 'Join Date' },
    { value: 'name', label: 'Name' },
    { value: 'email', label: 'Email' },
];

const fetchUsers = async () => {
    try {
        loading.value = true;
        error.value = null;
        const response = await adminApi.users({
            sort: sortBy.value,
            direction: sortDirection.value,
            search: search.value,
            page: currentPage.value,
        });
        users.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
        };
    } catch (err) {
        console.error('Failed to fetch users:', err);
        error.value = err.response?.data?.message || 'Failed to load users';
    } finally {
        loading.value = false;
    }
};

const handleSort = (field) => {
    if (sortBy.value === field) {
        sortDirection.value = sortDirection.value === 'desc' ? 'asc' : 'desc';
    } else {
        sortBy.value = field;
        sortDirection.value = 'desc';
    }
    currentPage.value = 1;
    fetchUsers();
};

const handleSearch = () => {
    currentPage.value = 1;
    fetchUsers();
};

const goToPage = (page) => {
    currentPage.value = page;
    fetchUsers();
};

const impersonateUser = async (user) => {
    if (!confirm(`Are you sure you want to login as ${user.name}? This will be logged.`)) {
        return;
    }

    try {
        impersonating.value = user.id;
        await authStore.impersonate(user.id);
    } catch (err) {
        console.error('Impersonation failed:', err);
        alert(err.response?.data?.message || 'Failed to impersonate user');
    } finally {
        impersonating.value = null;
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getRoleColor = (role) => {
    const colors = {
        admin: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        manager: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        creator: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        reviewer: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    };
    return colors[role] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
};

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(handleSearch, 300);
});

onMounted(fetchUsers);
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">All Users</h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Manage users across all agencies. Sorted by power users by default.
                        </p>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 mb-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <label for="search" class="sr-only">Search</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    placeholder="Search by name or email..."
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                        </div>
                        <div class="sm:w-48">
                            <label for="sort" class="sr-only">Sort by</label>
                            <select
                                id="sort"
                                v-model="sortBy"
                                @change="fetchUsers"
                                class="block w-full pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                            >
                                <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <button
                            @click="sortDirection = sortDirection === 'desc' ? 'asc' : 'desc'; fetchUsers()"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            <svg v-if="sortDirection === 'desc'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Error State -->
                <div v-if="error" class="rounded-md bg-red-50 dark:bg-red-900/20 p-4 mb-6">
                    <p class="text-sm text-red-700 dark:text-red-400">{{ error }}</p>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="animate-pulse space-y-4">
                        <div v-for="i in 5" :key="i" class="flex items-center gap-4">
                            <div class="h-10 w-10 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div v-else class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Agency
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Role
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Posts
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Brands
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Joined
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                                    <span class="text-primary-600 dark:text-primary-400 font-medium">
                                                        {{ user.name.charAt(0).toUpperCase() }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ user.name }}
                                                    <span v-if="user.is_super_admin" class="ml-1 text-xs text-red-600 dark:text-red-400">(Super Admin)</span>
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ user.agency?.name || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="[getRoleColor(user.role), 'px-2 py-1 text-xs font-medium rounded-full']">
                                            {{ user.role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ user.posts_count }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ user.brands_count }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(user.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            v-if="user.id !== authStore.user?.id"
                                            @click="impersonateUser(user)"
                                            :disabled="impersonating === user.id"
                                            class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 disabled:opacity-50"
                                        >
                                            <svg v-if="impersonating === user.id" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                            </svg>
                                            Login as
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.last_page > 1" class="bg-gray-50 dark:bg-gray-700 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-600 sm:px-6">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Showing
                                    <span class="font-medium">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span>
                                    to
                                    <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
                                    of
                                    <span class="font-medium">{{ pagination.total }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <button
                                        @click="goToPage(pagination.current_page - 1)"
                                        :disabled="pagination.current_page === 1"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <button
                                        v-for="page in pagination.last_page"
                                        :key="page"
                                        @click="goToPage(page)"
                                        :class="[
                                            page === pagination.current_page
                                                ? 'z-10 bg-primary-50 dark:bg-primary-900/30 border-primary-500 text-primary-600 dark:text-primary-400'
                                                : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                        ]"
                                    >
                                        {{ page }}
                                    </button>
                                    <button
                                        @click="goToPage(pagination.current_page + 1)"
                                        :disabled="pagination.current_page === pagination.last_page"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span class="sr-only">Next</span>
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
