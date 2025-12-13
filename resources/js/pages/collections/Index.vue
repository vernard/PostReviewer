<script setup>
import { ref, computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { collectionApi, brandApi } from '@/services/api';

const collections = ref([]);
const brands = ref([]);
const loading = ref(true);
const error = ref('');

// Filters
const selectedBrandId = ref('');

const filteredCollections = computed(() => {
    let result = collections.value;
    if (selectedBrandId.value) {
        result = result.filter(c => c.brand_id === parseInt(selectedBrandId.value));
    }
    return result;
});

const fetchCollections = async () => {
    try {
        loading.value = true;
        const params = {};
        if (selectedBrandId.value) {
            params.brand_id = selectedBrandId.value;
        }
        const response = await collectionApi.list(params);
        collections.value = response.data.data || response.data || [];
    } catch (err) {
        error.value = 'Failed to load collections';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const fetchBrands = async () => {
    try {
        const response = await brandApi.list();
        brands.value = response.data.brands || response.data.data || response.data;
    } catch (err) {
        console.error('Failed to fetch brands:', err);
    }
};

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        pending_approval: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        changes_requested: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    };
    return colors[status] || colors.draft;
};

const copyApprovalLink = (collection) => {
    if (collection.approval_url) {
        navigator.clipboard.writeText(collection.approval_url);
    }
};

onMounted(() => {
    fetchCollections();
    fetchBrands();
});
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Collections</h1>
                </div>

                <!-- Filters -->
                <div class="mt-4 flex gap-4">
                    <select
                        v-model="selectedBrandId"
                        @change="fetchCollections"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    >
                        <option value="">All Brands</option>
                        <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                            {{ brand.name }}
                        </option>
                    </select>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="mt-8 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
                </div>

                <!-- Error -->
                <div v-else-if="error" class="mt-8 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded">
                    {{ error }}
                </div>

                <!-- Empty State -->
                <div v-else-if="filteredCollections.length === 0" class="mt-8 text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No collections</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Create a batch of posts to see them here.
                    </p>
                </div>

                <!-- Collections Grid -->
                <div v-else class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <RouterLink
                        v-for="collection in filteredCollections"
                        :key="collection.id"
                        :to="`/collections/${collection.id}`"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow overflow-hidden"
                    >
                        <!-- Collection Header -->
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ collection.name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ collection.brand?.name }}
                                    </p>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ collection.posts_count }} posts
                                </span>
                            </div>
                        </div>

                        <!-- Status Summary -->
                        <div class="p-4">
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-if="collection.status_summary?.approved > 0"
                                    :class="[getStatusColor('approved'), 'px-2 py-1 text-xs font-medium rounded-full']"
                                >
                                    {{ collection.status_summary.approved }} Approved
                                </span>
                                <span
                                    v-if="collection.status_summary?.pending_approval > 0"
                                    :class="[getStatusColor('pending_approval'), 'px-2 py-1 text-xs font-medium rounded-full']"
                                >
                                    {{ collection.status_summary.pending_approval }} Pending
                                </span>
                                <span
                                    v-if="collection.status_summary?.changes_requested > 0"
                                    :class="[getStatusColor('changes_requested'), 'px-2 py-1 text-xs font-medium rounded-full']"
                                >
                                    {{ collection.status_summary.changes_requested }} Changes Requested
                                </span>
                                <span
                                    v-if="collection.status_summary?.draft > 0"
                                    :class="[getStatusColor('draft'), 'px-2 py-1 text-xs font-medium rounded-full']"
                                >
                                    {{ collection.status_summary.draft }} Draft
                                </span>
                            </div>

                            <!-- Approval Link -->
                            <div v-if="collection.approval_url" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 truncate flex-1">
                                        {{ collection.approval_url }}
                                    </span>
                                    <button
                                        @click.prevent="copyApprovalLink(collection)"
                                        class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300"
                                        title="Copy link"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Created Date -->
                            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                                Created {{ new Date(collection.created_at).toLocaleDateString() }}
                            </p>
                        </div>
                    </RouterLink>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
