<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { agencyApi } from '@/services/api';

const stats = ref({
    total_posts: 0,
    pending_approval: 0,
    approved: 0,
    brands_count: 0,
});
const recentPosts = ref([]);
const loading = ref(true);

const fetchDashboardData = async () => {
    try {
        loading.value = true;
        const response = await agencyApi.dashboardStats();
        stats.value = response.data.stats;
        recentPosts.value = response.data.recent_posts || [];
    } catch (err) {
        console.error('Failed to fetch dashboard stats:', err);
    } finally {
        loading.value = false;
    }
};

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        pending_approval: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        changes_requested: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        published: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
};

const formatStatus = (status) => {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

onMounted(fetchDashboardData);
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Dashboard</h1>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <!-- Loading State -->
                <div v-if="loading" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="i in 4" :key="i" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg animate-pulse">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-6 w-6 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 mb-2"></div>
                                    <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-12"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Posts</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ stats.total_posts }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <RouterLink to="/posts" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                View all posts
                            </RouterLink>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Approval</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ stats.pending_approval }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <RouterLink to="/approvals" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                Review pending
                            </RouterLink>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ stats.approved }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <RouterLink to="/posts?status=approved" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                View approved
                            </RouterLink>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Brands</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ stats.brands_count }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <RouterLink to="/brands" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                Manage brands
                            </RouterLink>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mt-8">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <RouterLink
                            to="/posts/create"
                            class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-indigo-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
                        >
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                <p class="text-sm font-medium text-gray-900">Create New Post</p>
                                <p class="text-sm text-gray-500 truncate">Draft a new social media post</p>
                            </div>
                        </RouterLink>

                        <RouterLink
                            to="/media"
                            class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-indigo-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
                        >
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                <p class="text-sm font-medium text-gray-900">Upload Media</p>
                                <p class="text-sm text-gray-500 truncate">Add images and videos</p>
                            </div>
                        </RouterLink>

                        <RouterLink
                            to="/brands"
                            class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-indigo-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
                        >
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                <p class="text-sm font-medium text-gray-900">Manage Brands</p>
                                <p class="text-sm text-gray-500 truncate">Add or edit brand profiles</p>
                            </div>
                        </RouterLink>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="mt-8">
                    <h2 class="text-lg font-medium text-gray-900">Recent Activity</h2>
                    <div class="mt-4 bg-white shadow rounded-lg overflow-hidden">
                        <div v-if="loading" class="p-6 text-center text-gray-500">
                            Loading...
                        </div>
                        <div v-else-if="recentPosts.length === 0" class="p-6 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-2">No recent activity. Create your first post to get started!</p>
                            <RouterLink
                                to="/posts/create"
                                class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                            >
                                Create Post
                            </RouterLink>
                        </div>
                        <ul v-else class="divide-y divide-gray-200">
                            <li v-for="post in recentPosts" :key="post.id">
                                <RouterLink :to="`/posts/${post.id}`" class="block hover:bg-gray-50">
                                    <div class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center min-w-0">
                                                <div
                                                    v-if="post.brand?.logo"
                                                    class="h-10 w-10 rounded-full overflow-hidden flex-shrink-0"
                                                >
                                                    <img :src="post.brand.logo" :alt="post.brand.name" class="h-full w-full object-cover" />
                                                </div>
                                                <div
                                                    v-else
                                                    class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0"
                                                >
                                                    <span class="text-indigo-600 font-medium">
                                                        {{ post.brand?.name?.charAt(0)?.toUpperCase() || 'P' }}
                                                    </span>
                                                </div>
                                                <div class="ml-4 truncate">
                                                    <p class="text-sm font-medium text-indigo-600 truncate">{{ post.title }}</p>
                                                    <p class="text-sm text-gray-500">{{ post.brand?.name }}</p>
                                                </div>
                                            </div>
                                            <div class="ml-2 flex-shrink-0 flex items-center gap-3">
                                                <span
                                                    :class="[getStatusColor(post.status), 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full']"
                                                >
                                                    {{ formatStatus(post.status) }}
                                                </span>
                                                <span class="text-sm text-gray-500">{{ formatDate(post.updated_at) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </RouterLink>
                            </li>
                        </ul>
                        <div v-if="recentPosts.length > 0" class="bg-gray-50 px-4 py-3 text-right">
                            <RouterLink to="/posts" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                View all posts &rarr;
                            </RouterLink>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
