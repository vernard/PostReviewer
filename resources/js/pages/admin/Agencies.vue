<script setup>
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { adminApi } from '@/services/api';

const agencies = ref([]);
const pagination = ref({});
const loading = ref(true);
const error = ref(null);

const sortBy = ref('storage_bytes');
const sortDirection = ref('desc');
const search = ref('');
const currentPage = ref(1);

const sortOptions = [
    { value: 'storage_bytes', label: 'Storage Used' },
    { value: 'posts_count', label: 'Posts (Most Active)' },
    { value: 'media_count', label: 'Media Files' },
    { value: 'users_count', label: 'Users' },
    { value: 'brands_count', label: 'Brands' },
    { value: 'created_at', label: 'Created Date' },
    { value: 'name', label: 'Name' },
];

const formatBytes = (bytes) => {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const fetchAgencies = async () => {
    try {
        loading.value = true;
        error.value = null;
        const response = await adminApi.agencies({
            sort: sortBy.value,
            direction: sortDirection.value,
            search: search.value,
            page: currentPage.value,
        });
        agencies.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
        };
    } catch (err) {
        console.error('Failed to fetch agencies:', err);
        error.value = err.response?.data?.message || 'Failed to load agencies';
    } finally {
        loading.value = false;
    }
};

const goToPage = (page) => {
    currentPage.value = page;
    fetchAgencies();
};

const handleSearch = () => {
    currentPage.value = 1;
    fetchAgencies();
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(handleSearch, 300);
});

onMounted(fetchAgencies);
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">All Agencies</h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            View statistics and activity for all agencies.
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
                                    placeholder="Search by agency name..."
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                        </div>
                        <div class="sm:w-48">
                            <label for="sort" class="sr-only">Sort by</label>
                            <select
                                id="sort"
                                v-model="sortBy"
                                @change="fetchAgencies"
                                class="block w-full pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                            >
                                <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <button
                            @click="sortDirection = sortDirection === 'desc' ? 'asc' : 'desc'; fetchAgencies()"
                            class="inline-flex items-center gap-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 text-sm"
                            :title="sortDirection === 'desc' ? 'Highest first' : 'Lowest first'"
                        >
                            <svg v-if="sortDirection === 'desc'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                            <span>{{ sortDirection === 'desc' ? 'High→Low' : 'Low→High' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Error State -->
                <div v-if="error" class="rounded-md bg-red-50 dark:bg-red-900/20 p-4 mb-6">
                    <p class="text-sm text-red-700 dark:text-red-400">{{ error }}</p>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="i in 4" :key="i" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 animate-pulse">
                        <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-1/3 mb-4"></div>
                        <div class="grid grid-cols-4 gap-4">
                            <div v-for="j in 4" :key="j">
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full mb-2"></div>
                                <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Agencies Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
                        v-for="agency in agencies"
                        :key="agency.id"
                        class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden"
                    >
                        <div class="p-6">
                            <!-- Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                                        <span class="text-primary-600 dark:text-primary-400 text-lg font-semibold">
                                            {{ agency.name?.charAt(0)?.toUpperCase() }}
                                        </span>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ agency.name }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Created {{ formatDate(agency.created_at) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats Grid -->
                            <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ agency.users_count }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Users</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ agency.brands_count }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Brands</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ agency.posts_count }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Posts</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                                    <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ agency.approved_posts }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Approved</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ agency.media_count || 0 }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Files</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center" :class="{ 'bg-orange-50 dark:bg-orange-900/20': agency.storage_bytes > 500 * 1024 * 1024 }">
                                    <p class="text-xl font-bold" :class="agency.storage_bytes > 500 * 1024 * 1024 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-900 dark:text-white'">
                                        {{ formatBytes(agency.storage_bytes) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Storage</p>
                                </div>
                            </div>

                            <!-- Post Status Breakdown -->
                            <div v-if="agency.posts_count > 0" class="mt-4">
                                <div class="flex items-center gap-2 text-xs">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                        <div class="h-full flex">
                                            <div
                                                v-if="agency.approved_posts"
                                                class="bg-green-500"
                                                :style="{ width: `${(agency.approved_posts / agency.posts_count) * 100}%` }"
                                            ></div>
                                            <div
                                                v-if="agency.pending_posts"
                                                class="bg-yellow-500"
                                                :style="{ width: `${(agency.pending_posts / agency.posts_count) * 100}%` }"
                                            ></div>
                                            <div
                                                v-if="agency.draft_posts"
                                                class="bg-gray-400"
                                                :style="{ width: `${(agency.draft_posts / agency.posts_count) * 100}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                        Approved: {{ agency.approved_posts }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                                        Pending: {{ agency.pending_posts }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                        Draft: {{ agency.draft_posts }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                No posts yet
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!loading && agencies.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No agencies found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ search ? 'Try a different search term.' : 'No agencies have been created yet.' }}
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="!loading && pagination.last_page > 1" class="mt-6 flex items-center justify-between">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Showing
                        <span class="font-medium">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span>
                        to
                        <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
                        of
                        <span class="font-medium">{{ pagination.total }}</span>
                        agencies
                    </p>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <button
                            @click="goToPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
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
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
