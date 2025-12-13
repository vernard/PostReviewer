<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { adminApi } from '@/services/api';

const stats = ref(null);
const loading = ref(true);
const error = ref(null);

const fetchDashboard = async () => {
    try {
        loading.value = true;
        error.value = null;
        const response = await adminApi.dashboard();
        stats.value = response.data;
    } catch (err) {
        console.error('Failed to fetch admin dashboard:', err);
        error.value = err.response?.data?.message || 'Failed to load dashboard';
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getActivityIcon = (type) => {
    if (type === 'post') {
        return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />`;
    }
    return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />`;
};

onMounted(fetchDashboard);
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Admin Dashboard</h1>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                        Super Admin
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">System-wide overview and management</p>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <!-- Error State -->
                <div v-if="error" class="rounded-md bg-red-50 dark:bg-red-900/20 p-4 mb-6">
                    <p class="text-sm text-red-700 dark:text-red-400">{{ error }}</p>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="i in 4" :key="i" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg animate-pulse">
                        <div class="p-5">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 mb-2"></div>
                            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div v-else-if="stats" class="space-y-6">
                    <!-- Main Stats -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Users -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</p>
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.users.total }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span v-for="(count, role) in stats.users.by_role" :key="role" class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        {{ role }}: {{ count }}
                                    </span>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                                <RouterLink to="/admin/users" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                    View all users
                                </RouterLink>
                            </div>
                        </div>

                        <!-- Agencies -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Agencies</p>
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.agencies.total }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Brands -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Brands</p>
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.brands.total }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Posts -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Posts</p>
                                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.posts.total }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span v-for="(count, status) in stats.posts.by_status" :key="status" class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        {{ status.replace(/_/g, ' ') }}: {{ count }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Stats -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Approval Statistics</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Approval Requests</p>
                                    <p class="text-3xl font-semibold text-gray-900 dark:text-white">{{ stats.approvals.total }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Approval Rate</p>
                                    <p class="text-3xl font-semibold text-green-600 dark:text-green-400">{{ stats.approvals.approval_rate }}%</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">By Status</p>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        <span v-for="(count, status) in stats.approvals.by_status" :key="status" class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                            {{ status }}: {{ count }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Recent Activity</h3>
                            <div v-if="stats.recent_activity.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                                No recent activity
                            </div>
                            <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                                <li v-for="activity in stats.recent_activity" :key="`${activity.type}-${activity.id}`" class="py-3">
                                    <div class="flex items-center gap-4">
                                        <div class="flex-shrink-0">
                                            <div :class="[
                                                'h-8 w-8 rounded-full flex items-center justify-center',
                                                activity.type === 'post' ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-blue-100 dark:bg-blue-900/30'
                                            ]">
                                                <svg :class="[
                                                    'h-4 w-4',
                                                    activity.type === 'post' ? 'text-yellow-600 dark:text-yellow-400' : 'text-blue-600 dark:text-blue-400'
                                                ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path v-if="activity.type === 'post'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                <template v-if="activity.type === 'post'">
                                                    {{ activity.title }}
                                                </template>
                                                <template v-else>
                                                    {{ activity.name }}
                                                </template>
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <template v-if="activity.type === 'post'">
                                                    Post by {{ activity.user }} in {{ activity.brand }}
                                                    <span :class="[
                                                        'ml-2 px-2 py-0.5 text-xs rounded-full',
                                                        activity.status === 'approved' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                                        activity.status === 'pending_approval' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                    ]">
                                                        {{ activity.status.replace(/_/g, ' ') }}
                                                    </span>
                                                </template>
                                                <template v-else>
                                                    New user joined {{ activity.agency }}
                                                </template>
                                            </p>
                                        </div>
                                        <div class="flex-shrink-0 text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(activity.created_at) }}
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
