<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { collectionApi } from '@/services/api';

const route = useRoute();
const router = useRouter();
const collectionId = route.params.id;

const collection = ref(null);
const loading = ref(true);
const error = ref('');
const generatingLink = ref(false);
const submitting = ref(false);

const selectedPostIndex = ref(0);
const selectedPost = computed(() => collection.value?.posts?.[selectedPostIndex.value] || null);

const previewPlatform = computed(() => {
    if (!selectedPost.value) return '';
    return selectedPost.value.platforms?.[0] || '';
});

const truncatedCaption = computed(() => {
    if (!selectedPost.value?.caption) return '';
    if (selectedPost.value.caption.length <= 125) return selectedPost.value.caption;
    return selectedPost.value.caption.substring(0, 125) + '...';
});

const hasPostsToSubmit = computed(() => {
    if (!collection.value?.posts) return false;
    return collection.value.posts.some(p => ['draft', 'changes_requested'].includes(p.status));
});

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        pending_approval: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        changes_requested: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    };
    return colors[status] || colors.draft;
};

const formatStatus = (status) => {
    return status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
};

const fetchCollection = async () => {
    try {
        loading.value = true;
        const response = await collectionApi.get(collectionId);
        collection.value = response.data.collection;
    } catch (err) {
        error.value = 'Failed to load collection';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const generateApprovalLink = async () => {
    try {
        generatingLink.value = true;
        const response = await collectionApi.generateApprovalLink(collectionId);
        collection.value.approval_url = response.data.approval_url;
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to generate link';
    } finally {
        generatingLink.value = false;
    }
};

const submitForApproval = async () => {
    try {
        submitting.value = true;
        const response = await collectionApi.submitForApproval(collectionId);
        collection.value.approval_url = response.data.approval_url;
        // Refresh to get updated post statuses
        await fetchCollection();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to submit for approval';
    } finally {
        submitting.value = false;
    }
};

const copyApprovalLink = () => {
    if (collection.value?.approval_url) {
        navigator.clipboard.writeText(collection.value.approval_url);
    }
};

const selectPost = (index) => {
    selectedPostIndex.value = index;
};

onMounted(() => {
    fetchCollection();
});
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Loading -->
                <div v-if="loading" class="text-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
                </div>

                <!-- Error -->
                <div v-else-if="error" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded">
                    {{ error }}
                    <RouterLink to="/collections" class="ml-4 underline">Back to collections</RouterLink>
                </div>

                <div v-else-if="collection">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <RouterLink to="/collections" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </RouterLink>
                            <div>
                                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ collection.name }}</h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ collection.brand?.name }} &middot; {{ collection.posts?.length || 0 }} posts
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                v-if="hasPostsToSubmit"
                                @click="submitForApproval"
                                :disabled="submitting"
                                class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                            >
                                {{ submitting ? 'Submitting...' : 'Submit for Approval' }}
                            </button>
                            <button
                                v-if="!collection.approval_url"
                                @click="generateApprovalLink"
                                :disabled="generatingLink"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                            >
                                {{ generatingLink ? 'Generating...' : 'Generate Link' }}
                            </button>
                        </div>
                    </div>

                    <!-- Approval Link Banner -->
                    <div v-if="collection.approval_url" class="mt-4 bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-primary-800 dark:text-primary-300">
                                    Client Approval Link
                                </p>
                                <p class="text-sm text-primary-600 dark:text-primary-400 mt-1">
                                    {{ collection.approval_url }}
                                </p>
                            </div>
                            <button
                                @click="copyApprovalLink"
                                class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600"
                            >
                                Copy Link
                            </button>
                        </div>
                    </div>

                    <!-- Status Summary -->
                    <div class="mt-6 flex flex-wrap gap-3">
                        <span
                            v-if="collection.status_summary?.approved > 0"
                            :class="[getStatusColor('approved'), 'px-3 py-1.5 text-sm font-medium rounded-full']"
                        >
                            {{ collection.status_summary.approved }} Approved
                        </span>
                        <span
                            v-if="collection.status_summary?.pending_approval > 0"
                            :class="[getStatusColor('pending_approval'), 'px-3 py-1.5 text-sm font-medium rounded-full']"
                        >
                            {{ collection.status_summary.pending_approval }} Pending
                        </span>
                        <span
                            v-if="collection.status_summary?.changes_requested > 0"
                            :class="[getStatusColor('changes_requested'), 'px-3 py-1.5 text-sm font-medium rounded-full']"
                        >
                            {{ collection.status_summary.changes_requested }} Changes Requested
                        </span>
                        <span
                            v-if="collection.status_summary?.draft > 0"
                            :class="[getStatusColor('draft'), 'px-3 py-1.5 text-sm font-medium rounded-full']"
                        >
                            {{ collection.status_summary.draft }} Draft
                        </span>
                    </div>

                    <!-- Posts Grid + Preview -->
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Posts List -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Posts</h2>
                            <div class="space-y-3">
                                <div
                                    v-for="(post, index) in collection.posts"
                                    :key="post.id"
                                    @click="selectPost(index)"
                                    :class="[
                                        'flex items-center gap-4 p-3 rounded-lg cursor-pointer transition-colors',
                                        selectedPostIndex === index
                                            ? 'bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800'
                                            : 'hover:bg-gray-50 dark:hover:bg-gray-700 border border-transparent'
                                    ]"
                                >
                                    <!-- Thumbnail -->
                                    <div class="w-16 h-16 rounded overflow-hidden flex-shrink-0">
                                        <img
                                            v-if="post.media?.[0]"
                                            :src="post.media[0].thumbnail_url || post.media[0].url"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full bg-gray-200 dark:bg-gray-700"></div>
                                    </div>
                                    <!-- Info -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ post.title }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ post.caption || 'No caption' }}
                                        </p>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span :class="[getStatusColor(post.status), 'px-2 py-0.5 text-xs rounded-full']">
                                                {{ formatStatus(post.status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Edit Link -->
                                    <RouterLink
                                        :to="`/posts/${post.id}/edit`"
                                        @click.stop
                                        class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </RouterLink>
                                </div>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Preview</h2>

                            <div v-if="!selectedPost" class="text-center text-gray-500 dark:text-gray-400 py-12">
                                Select a post to preview
                            </div>

                            <div v-else-if="selectedPost.platforms?.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-12">
                                No platforms selected
                            </div>

                            <div v-else>
                                <!-- Platform tabs -->
                                <div v-if="selectedPost.platforms?.length > 1" class="flex gap-2 mb-4">
                                    <button
                                        v-for="platformId in selectedPost.platforms"
                                        :key="platformId"
                                        :class="[
                                            'px-3 py-1 text-xs rounded-full',
                                            previewPlatform === platformId
                                                ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                        ]"
                                    >
                                        {{ platformId.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()) }}
                                    </button>
                                </div>

                                <!-- Instagram Feed Preview -->
                                <div
                                    v-if="previewPlatform === 'instagram_feed'"
                                    class="max-w-sm mx-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                                >
                                    <div class="flex items-center p-3">
                                        <div v-if="collection.brand?.logo_flat_url" class="w-8 h-8 rounded-full overflow-hidden">
                                            <img :src="collection.brand.logo_flat_url" :alt="collection.brand.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                            {{ collection.brand?.name?.charAt(0) || 'B' }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ collection.brand?.name }}</p>
                                        </div>
                                    </div>
                                    <div class="aspect-square bg-gray-100 dark:bg-gray-700">
                                        <img v-if="selectedPost.media?.[0]" :src="selectedPost.media[0].url" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="p-3">
                                        <div class="flex gap-4 mb-2 text-gray-900 dark:text-white">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            <span class="font-semibold">{{ collection.brand?.name }}</span>
                                            {{ truncatedCaption || ' Your caption...' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Facebook Feed Preview -->
                                <div
                                    v-else-if="previewPlatform === 'facebook_feed'"
                                    class="max-w-sm mx-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                                >
                                    <div class="flex items-center p-3">
                                        <div v-if="collection.brand?.logo_flat_url" class="w-10 h-10 rounded-full overflow-hidden">
                                            <img :src="collection.brand.logo_flat_url" :alt="collection.brand.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                            {{ collection.brand?.name?.charAt(0) || 'B' }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ collection.brand?.name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Just now</p>
                                        </div>
                                    </div>
                                    <div class="px-3 pb-2">
                                        <p class="text-sm text-gray-900 dark:text-white">{{ selectedPost.caption || 'Your caption...' }}</p>
                                    </div>
                                    <div class="bg-gray-100 dark:bg-gray-700">
                                        <img v-if="selectedPost.media?.[0]" :src="selectedPost.media[0].url" class="w-full" />
                                    </div>
                                </div>

                                <!-- Story/Reel Preview -->
                                <div
                                    v-else
                                    class="max-w-[280px] mx-auto bg-black rounded-2xl overflow-hidden aspect-[9/16] relative"
                                >
                                    <img v-if="selectedPost.media?.[0]" :src="selectedPost.media[0].url" class="w-full h-full object-cover" />
                                    <div class="absolute top-0 left-0 right-0 h-24 bg-gradient-to-b from-black/60 to-transparent"></div>
                                    <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-black/60 to-transparent"></div>
                                    <div class="absolute top-4 left-3 flex items-center">
                                        <div v-if="collection.brand?.logo_flat_url" class="w-8 h-8 rounded-full overflow-hidden border-2 border-white">
                                            <img :src="collection.brand.logo_flat_url" class="w-full h-full object-cover" />
                                        </div>
                                        <span class="ml-2 text-white text-sm font-medium">{{ collection.brand?.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
