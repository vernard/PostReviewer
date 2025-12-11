<script setup>
import { ref, onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { postApi, brandApi } from '@/services/api';

const route = useRoute();

const posts = ref([]);
const brands = ref([]);
const loading = ref(true);
const pagination = ref({});

const filters = ref({
    brand_id: route.query.brand_id || '',
    status: route.query.status || '',
    platform: '',
    mine: false,
});

const fetchBrands = async () => {
    try {
        const response = await brandApi.list();
        brands.value = response.data.data || response.data;
    } catch (err) {
        console.error('Failed to fetch brands:', err);
    }
};

const fetchPosts = async (page = 1) => {
    try {
        loading.value = true;
        const params = { page, per_page: 12 };

        if (filters.value.brand_id) params.brand_id = filters.value.brand_id;
        if (filters.value.status) params.status = filters.value.status;
        if (filters.value.platform) params.platform = filters.value.platform;
        if (filters.value.mine) params.mine = true;

        const response = await postApi.list(params);
        posts.value = response.data.data || [];
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total,
        };
    } catch (err) {
        console.error('Failed to fetch posts:', err);
    } finally {
        loading.value = false;
    }
};

const deletePost = async (post) => {
    if (!confirm(`Are you sure you want to delete "${post.title}"?`)) return;

    try {
        await postApi.delete(post.id);
        posts.value = posts.value.filter(p => p.id !== post.id);
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to delete post');
    }
};

const duplicatePost = async (post) => {
    try {
        const response = await postApi.duplicate(post.id);
        posts.value.unshift(response.data.post);
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to duplicate post');
    }
};

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-700',
        pending_approval: 'bg-yellow-100 text-yellow-700',
        changes_requested: 'bg-red-100 text-red-700',
        approved: 'bg-green-100 text-green-700',
        published: 'bg-blue-100 text-blue-700',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

const formatStatus = (status) => {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

const formatPlatforms = (platforms) => {
    if (!platforms || platforms.length === 0) return 'No platforms';
    return platforms.map(p => p.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())).join(', ');
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

watch(filters, () => {
    fetchPosts();
}, { deep: true });

onMounted(() => {
    fetchBrands();
    fetchPosts();
});
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900">Posts</h1>
                    <RouterLink
                        to="/posts/create"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Post
                    </RouterLink>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <!-- Filters -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="p-4 flex gap-4 flex-wrap items-center">
                        <select
                            v-model="filters.brand_id"
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">All Brands</option>
                            <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                {{ brand.name }}
                            </option>
                        </select>
                        <select
                            v-model="filters.status"
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="pending_approval">Pending Approval</option>
                            <option value="changes_requested">Changes Requested</option>
                            <option value="approved">Approved</option>
                        </select>
                        <select
                            v-model="filters.platform"
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">All Platforms</option>
                            <option value="facebook_feed">Facebook Feed</option>
                            <option value="facebook_story">Facebook Story</option>
                            <option value="instagram_feed">Instagram Feed</option>
                            <option value="instagram_story">Instagram Story</option>
                            <option value="instagram_reel">Instagram Reel</option>
                        </select>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input
                                type="checkbox"
                                v-model="filters.mine"
                                class="h-4 w-4 text-indigo-600 border-gray-300 rounded"
                            />
                            My posts only
                        </label>
                        <button
                            v-if="filters.brand_id || filters.status || filters.platform || filters.mine"
                            @click="filters = { brand_id: '', status: '', platform: '', mine: false }"
                            class="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            Clear filters
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="i in 6" :key="i" class="bg-white shadow rounded-lg overflow-hidden animate-pulse">
                        <div class="h-48 bg-gray-200"></div>
                        <div class="p-4">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="posts.length === 0" class="bg-white shadow rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No posts found</h3>
                    <p class="mt-2 text-gray-500">
                        {{ filters.brand_id || filters.status || filters.platform || filters.mine
                            ? 'Try adjusting your filters.'
                            : 'Get started by creating your first post.' }}
                    </p>
                    <RouterLink
                        to="/posts/create"
                        class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                        Create Post
                    </RouterLink>
                </div>

                <!-- Posts Grid -->
                <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="post in posts"
                        :key="post.id"
                        class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow"
                    >
                        <RouterLink :to="`/posts/${post.id}`" class="block">
                            <!-- Thumbnail -->
                            <div class="h-48 bg-gray-100 relative">
                                <img
                                    v-if="post.media?.[0]?.thumbnail_url || post.media?.[0]?.url"
                                    :src="post.media[0].thumbnail_url || post.media[0].url"
                                    :alt="post.title"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <svg class="h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <!-- Status Badge -->
                                <span
                                    :class="[getStatusColor(post.status), 'absolute top-2 right-2 px-2 py-1 text-xs font-medium rounded-full']"
                                >
                                    {{ formatStatus(post.status) }}
                                </span>
                                <!-- Media count -->
                                <span
                                    v-if="post.media?.length > 1"
                                    class="absolute bottom-2 right-2 bg-black bg-opacity-60 text-white px-2 py-1 text-xs rounded"
                                >
                                    +{{ post.media.length - 1 }} more
                                </span>
                            </div>
                            <div class="p-4">
                                <h3 class="text-sm font-medium text-gray-900 truncate">{{ post.title }}</h3>
                                <p class="text-xs text-gray-500 mt-1 truncate">{{ post.brand?.name }}</p>
                                <div class="mt-2 flex items-center justify-between">
                                    <p class="text-xs text-gray-500">{{ formatPlatforms(post.platforms) }}</p>
                                    <p class="text-xs text-gray-400">{{ formatDate(post.updated_at) }}</p>
                                </div>
                            </div>
                        </RouterLink>
                        <div class="px-4 py-3 bg-gray-50 flex justify-end gap-2 border-t border-gray-100">
                            <RouterLink
                                :to="`/posts/${post.id}`"
                                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                            >
                                View
                            </RouterLink>
                            <button
                                @click.prevent="duplicatePost(post)"
                                class="text-gray-600 hover:text-gray-800 text-sm font-medium"
                            >
                                Duplicate
                            </button>
                            <button
                                @click.prevent="deletePost(post)"
                                class="text-red-600 hover:text-red-800 text-sm font-medium"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="mt-6 flex justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <button
                            v-for="page in pagination.last_page"
                            :key="page"
                            @click="fetchPosts(page)"
                            :class="[
                                page === pagination.current_page
                                    ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                            ]"
                        >
                            {{ page }}
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
