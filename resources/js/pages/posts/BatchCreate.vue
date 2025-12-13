<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { collectionApi, mediaApi } from '@/services/api';
import { useBrandStore } from '@/stores/brand';

const router = useRouter();
const route = useRoute();
const brandStore = useBrandStore();

const brandMedia = ref([]);
const loading = ref(false);
const loadingMedia = ref(false);
const error = ref('');

// Use the active brand from the global store
const brand = computed(() => brandStore.activeBrand);

// Collection name
const collectionName = ref('');

// Posts being created in this batch
const posts = ref([]);
const selectedPostIndex = ref(0);

// View mode: 'table' or 'card'
const viewMode = ref('card');

// Media library modal
const showMediaLibrary = ref(false);

// Default platforms for new posts
const defaultPlatforms = ref([]);

// Created collection (after save)
const createdCollection = ref(null);
const showSuccessModal = ref(false);

const platforms = [
    { id: 'facebook_feed', name: 'Facebook Feed', icon: 'FB' },
    { id: 'facebook_story', name: 'Facebook Story', icon: 'FB' },
    { id: 'facebook_reel', name: 'Facebook Reel', icon: 'FB' },
    { id: 'instagram_feed', name: 'Instagram Feed', icon: 'IG' },
    { id: 'instagram_story', name: 'Instagram Story', icon: 'IG' },
    { id: 'instagram_reel', name: 'Instagram Reel', icon: 'IG' },
];

const selectedPost = computed(() => posts.value[selectedPostIndex.value] || null);

const canSubmit = computed(() => {
    return posts.value.length > 0 && posts.value.every(post =>
        post.title.trim() && post.platforms.length > 0
    );
});

const previewPlatform = computed(() => {
    if (!selectedPost.value) return '';
    return selectedPost.value.platforms[0] || '';
});

const truncatedCaption = computed(() => {
    if (!selectedPost.value?.caption) return '';
    if (selectedPost.value.caption.length <= 125) return selectedPost.value.caption;
    return selectedPost.value.caption.substring(0, 125) + '...';
});

// Check brand and fetch media on mount
const initPage = () => {
    if (!brandStore.activeBrand) {
        // No active brand - redirect to brands page
        router.push('/brands');
        return;
    }

    // Fetch media for this brand
    fetchBrandMedia();
};

const fetchBrandMedia = async () => {
    if (!brandStore.activeBrandId) {
        brandMedia.value = [];
        return;
    }

    try {
        loadingMedia.value = true;
        const response = await mediaApi.list({ brand_id: brandStore.activeBrandId });
        brandMedia.value = response.data.data || response.data || [];
    } catch (err) {
        console.error('Failed to fetch media:', err);
    } finally {
        loadingMedia.value = false;
    }
};

const addPostFromMedia = (media) => {
    posts.value.push({
        media: media,
        title: '',
        caption: '',
        platforms: [...defaultPlatforms.value],
    });
    selectedPostIndex.value = posts.value.length - 1;
};

const toggleMediaSelection = (media) => {
    const existingIndex = posts.value.findIndex(p => p.media.id === media.id);
    if (existingIndex !== -1) {
        // Remove the post
        posts.value.splice(existingIndex, 1);
        if (selectedPostIndex.value >= posts.value.length) {
            selectedPostIndex.value = Math.max(0, posts.value.length - 1);
        }
    } else {
        // Add new post
        addPostFromMedia(media);
    }
};

const isMediaInBatch = (media) => {
    return posts.value.some(p => p.media.id === media.id);
};

const removePost = (index) => {
    posts.value.splice(index, 1);
    if (selectedPostIndex.value >= posts.value.length) {
        selectedPostIndex.value = Math.max(0, posts.value.length - 1);
    }
};

const selectPost = (index) => {
    selectedPostIndex.value = index;
};

const updatePostField = (index, field, value) => {
    if (posts.value[index]) {
        posts.value[index][field] = value;
    }
};

const togglePostPlatform = (index, platformId) => {
    if (!posts.value[index]) return;
    const platforms = posts.value[index].platforms;
    const idx = platforms.indexOf(platformId);
    if (idx === -1) {
        platforms.push(platformId);
    } else {
        platforms.splice(idx, 1);
    }
};

const applyDefaultPlatformsToAll = () => {
    posts.value.forEach(post => {
        post.platforms = [...defaultPlatforms.value];
    });
};

const getCollectionName = () => {
    if (collectionName.value.trim()) {
        return collectionName.value.trim();
    }
    // Auto-generate name: Brand - Date
    const date = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    return `${brand.value?.name || 'Batch'} - ${date}`;
};

const saveDrafts = async () => {
    if (!canSubmit.value) return;

    try {
        loading.value = true;
        error.value = '';

        // Create collection with all posts
        const response = await collectionApi.create({
            brand_id: brandStore.activeBrandId,
            name: getCollectionName(),
            posts: posts.value.map(post => ({
                title: post.title,
                caption: post.caption,
                platforms: post.platforms,
                media_id: post.media.id,
            })),
        });

        createdCollection.value = response.data.collection;
        showSuccessModal.value = true;
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create collection';
    } finally {
        loading.value = false;
    }
};

const submitForApproval = async () => {
    if (!canSubmit.value) return;

    try {
        loading.value = true;
        error.value = '';

        // Create collection with all posts
        const createResponse = await collectionApi.create({
            brand_id: brandStore.activeBrandId,
            name: getCollectionName(),
            posts: posts.value.map(post => ({
                title: post.title,
                caption: post.caption,
                platforms: post.platforms,
                media_id: post.media.id,
            })),
        });

        // Submit the collection for approval (generates link)
        const submitResponse = await collectionApi.submitForApproval(createResponse.data.collection.id);

        createdCollection.value = {
            ...createResponse.data.collection,
            approval_url: submitResponse.data.approval_url,
        };
        showSuccessModal.value = true;
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create collection';
    } finally {
        loading.value = false;
    }
};

const copyApprovalLink = () => {
    if (createdCollection.value?.approval_url) {
        navigator.clipboard.writeText(createdCollection.value.approval_url);
    }
};

const goToCollection = () => {
    if (createdCollection.value?.id) {
        router.push(`/collections/${createdCollection.value.id}`);
    } else {
        router.push('/posts');
    }
};

// Watch for brand changes in the store
watch(() => brandStore.activeBrandId, (newVal, oldVal) => {
    if (newVal && newVal !== oldVal) {
        // Clear posts when brand changes
        posts.value = [];
        selectedPostIndex.value = 0;
        fetchBrandMedia();
    }
});

onMounted(() => {
    initPage();
});
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <button @click="router.back()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Batch Create</h1>
                        <span v-if="posts.length > 0" class="text-sm text-gray-500 dark:text-gray-400">
                            {{ posts.length }} post{{ posts.length !== 1 ? 's' : '' }}
                        </span>
                    </div>
                    <!-- Brand Context -->
                    <RouterLink
                        v-if="brand"
                        :to="`/brands/${brand.id}`"
                        class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    >
                        <div v-if="brand.logo_flat_url" class="w-6 h-6 rounded-full overflow-hidden">
                            <img :src="brand.logo_flat_url" :alt="brand.name" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                            <span class="text-primary-600 dark:text-primary-400 text-xs font-semibold">
                                {{ brand.name?.charAt(0)?.toUpperCase() }}
                            </span>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ brand.name }}</span>
                    </RouterLink>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div v-if="error" class="mb-6 bg-red-50 border border-red-200 text-red-600 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400 px-4 py-3 rounded">
                    {{ error }}
                </div>

                <div v-if="brand" class="space-y-6">
                    <!-- Collection Name -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Collection Details</h2>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1">Collection Name</label>
                            <input
                                v-model="collectionName"
                                type="text"
                                :placeholder="`${brand.name} - ${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave empty to auto-generate</p>
                        </div>
                    </div>

                    <!-- Step 1: Default Platforms -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Default Platforms</h2>
                            <button
                                v-if="posts.length > 0"
                                @click="applyDefaultPlatformsToAll"
                                class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300"
                            >
                                Apply to all posts
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="platform in platforms"
                                :key="platform.id"
                                :class="[
                                    'flex items-center px-3 py-2 border rounded-lg cursor-pointer transition-colors',
                                    defaultPlatforms.includes(platform.id)
                                        ? 'border-primary-500 dark:border-primary-400 bg-primary-50 dark:bg-primary-900/30'
                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                ]"
                            >
                                <input
                                    type="checkbox"
                                    :value="platform.id"
                                    v-model="defaultPlatforms"
                                    class="sr-only"
                                />
                                <span
                                    :class="[
                                        'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold mr-2',
                                        platform.id.startsWith('facebook') ? 'bg-blue-100 text-blue-600' : 'bg-gradient-to-br from-purple-500 to-pink-500 text-white'
                                    ]"
                                >
                                    {{ platform.icon }}
                                </span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ platform.name }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Step 2: Select Media -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Select Media</h2>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Click to add/remove from batch
                            </span>
                        </div>

                        <div v-if="loadingMedia" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Loading media...
                        </div>
                        <div v-else-if="brandMedia.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No media uploaded for this brand yet.
                            <RouterLink to="/media" class="block mt-2 text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300">
                                Go to Media Library to upload
                            </RouterLink>
                        </div>
                        <div v-else class="grid grid-cols-6 gap-3">
                            <div
                                v-for="media in brandMedia"
                                :key="media.id"
                                @click="toggleMediaSelection(media)"
                                :class="[
                                    'relative aspect-square rounded-lg overflow-hidden cursor-pointer border-2 transition-all',
                                    isMediaInBatch(media) ? 'border-primary-500 dark:border-primary-400 ring-2 ring-primary-200 dark:ring-primary-900/30' : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600'
                                ]"
                            >
                                <img
                                    :src="media.thumbnail_url || media.url"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-if="isMediaInBatch(media)"
                                    class="absolute inset-0 bg-primary-500 bg-opacity-20 dark:bg-primary-900/30 flex items-center justify-center"
                                >
                                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span
                                    v-if="isMediaInBatch(media)"
                                    class="absolute bottom-1 left-1 bg-primary-600 text-white text-xs px-2 py-0.5 rounded font-medium"
                                >
                                    {{ posts.findIndex(p => p.media.id === media.id) + 1 }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Edit Posts (only show if posts exist) -->
                    <div v-if="posts.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left: Post List (Table/Card View) -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Edit Posts</h2>
                                <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                                    <button
                                        @click="viewMode = 'card'"
                                        :class="[
                                            'px-3 py-1 text-sm rounded-md transition-colors',
                                            viewMode === 'card' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400'
                                        ]"
                                    >
                                        Cards
                                    </button>
                                    <button
                                        @click="viewMode = 'table'"
                                        :class="[
                                            'px-3 py-1 text-sm rounded-md transition-colors',
                                            viewMode === 'table' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400'
                                        ]"
                                    >
                                        Table
                                    </button>
                                </div>
                            </div>

                            <!-- Card View -->
                            <div v-if="viewMode === 'card'" class="grid grid-cols-3 gap-3">
                                <div
                                    v-for="(post, index) in posts"
                                    :key="post.media.id"
                                    @click="selectPost(index)"
                                    :class="[
                                        'relative aspect-square rounded-lg overflow-hidden cursor-pointer border-2 transition-all group',
                                        selectedPostIndex === index ? 'border-primary-500 dark:border-primary-400 ring-2 ring-primary-200 dark:ring-primary-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                    ]"
                                >
                                    <img
                                        :src="post.media.thumbnail_url || post.media.url"
                                        class="w-full h-full object-cover"
                                    />
                                    <!-- Remove button -->
                                    <button
                                        @click.stop="removePost(index)"
                                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <!-- Post number -->
                                    <span class="absolute bottom-1 left-1 bg-black bg-opacity-60 text-white text-xs px-2 py-0.5 rounded">
                                        {{ index + 1 }}
                                    </span>
                                    <!-- Status indicators -->
                                    <div class="absolute bottom-1 right-1 flex gap-1">
                                        <span v-if="!post.title.trim()" class="bg-yellow-500 text-white text-xs px-1.5 py-0.5 rounded">
                                            No title
                                        </span>
                                        <span v-if="post.platforms.length === 0" class="bg-red-500 text-white text-xs px-1.5 py-0.5 rounded">
                                            No platform
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Table View -->
                            <div v-else class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-600">
                                            <th class="text-left py-2 px-2 font-medium text-gray-500 dark:text-gray-400 w-16"></th>
                                            <th class="text-left py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Title</th>
                                            <th class="text-left py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Caption</th>
                                            <th class="text-left py-2 px-2 font-medium text-gray-500 dark:text-gray-400 w-20">Platforms</th>
                                            <th class="w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(post, index) in posts"
                                            :key="post.media.id"
                                            @click="selectPost(index)"
                                            :class="[
                                                'border-b border-gray-100 dark:border-gray-700 cursor-pointer transition-colors',
                                                selectedPostIndex === index ? 'bg-primary-50 dark:bg-primary-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                                            ]"
                                        >
                                            <td class="py-2 px-2">
                                                <div class="w-12 h-12 rounded overflow-hidden">
                                                    <img
                                                        :src="post.media.thumbnail_url || post.media.url"
                                                        class="w-full h-full object-cover"
                                                    />
                                                </div>
                                            </td>
                                            <td class="py-2 px-2">
                                                <input
                                                    :value="post.title"
                                                    @input="updatePostField(index, 'title', $event.target.value)"
                                                    @click.stop
                                                    type="text"
                                                    placeholder="Post title..."
                                                    class="w-full px-2 py-1 text-sm border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                                                />
                                            </td>
                                            <td class="py-2 px-2">
                                                <input
                                                    :value="post.caption"
                                                    @input="updatePostField(index, 'caption', $event.target.value)"
                                                    @click.stop
                                                    type="text"
                                                    placeholder="Caption..."
                                                    class="w-full px-2 py-1 text-sm border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                                                />
                                            </td>
                                            <td class="py-2 px-2">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ post.platforms.length || 0 }}
                                                </span>
                                            </td>
                                            <td class="py-2 px-2">
                                                <button
                                                    @click.stop="removePost(index)"
                                                    class="text-red-500 hover:text-red-700 dark:hover:text-red-400"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Selected Post Edit Form (below in card view) -->
                            <div v-if="selectedPost && viewMode === 'card'" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-600">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Editing Post {{ selectedPostIndex + 1 }}
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1">Title *</label>
                                        <input
                                            :value="selectedPost.title"
                                            @input="updatePostField(selectedPostIndex, 'title', $event.target.value)"
                                            type="text"
                                            placeholder="e.g., December Promo Post"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1">Caption</label>
                                        <textarea
                                            :value="selectedPost.caption"
                                            @input="updatePostField(selectedPostIndex, 'caption', $event.target.value)"
                                            rows="3"
                                            placeholder="Write your post caption..."
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Platforms *</label>
                                        <div class="flex flex-wrap gap-2">
                                            <label
                                                v-for="platform in platforms"
                                                :key="platform.id"
                                                :class="[
                                                    'flex items-center px-2 py-1 border rounded cursor-pointer transition-colors text-sm',
                                                    selectedPost.platforms.includes(platform.id)
                                                        ? 'border-primary-500 dark:border-primary-400 bg-primary-50 dark:bg-primary-900/30'
                                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                                ]"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :checked="selectedPost.platforms.includes(platform.id)"
                                                    @change="togglePostPlatform(selectedPostIndex, platform.id)"
                                                    class="sr-only"
                                                />
                                                <span
                                                    :class="[
                                                        'w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold mr-1',
                                                        platform.id.startsWith('facebook') ? 'bg-blue-100 text-blue-600' : 'bg-gradient-to-br from-purple-500 to-pink-500 text-white'
                                                    ]"
                                                >
                                                    {{ platform.icon }}
                                                </span>
                                                <span class="text-gray-700 dark:text-gray-300">{{ platform.name }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Mockup Preview -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Preview</h2>

                            <div v-if="!selectedPost" class="text-center text-gray-500 dark:text-gray-400 py-12">
                                Select a post to preview
                            </div>

                            <div v-else-if="selectedPost.platforms.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-12">
                                Select a platform to see the mockup preview
                            </div>

                            <!-- Platform tabs -->
                            <div v-else>
                                <div v-if="selectedPost.platforms.length > 1" class="flex gap-2 mb-4">
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
                                        <div v-if="brand?.logo_flat_url" class="w-8 h-8 rounded-full overflow-hidden">
                                            <img :src="brand.logo_flat_url" :alt="brand.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                            {{ brand?.name?.charAt(0) || 'B' }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ brand?.name || 'Brand Name' }}</p>
                                        </div>
                                    </div>
                                    <div class="aspect-square bg-gray-100 dark:bg-gray-700">
                                        <img :src="selectedPost.media.url" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="p-3">
                                        <div class="flex gap-4 mb-2 text-gray-900 dark:text-white">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            <span class="font-semibold">{{ brand?.name || 'brand' }}</span>
                                            <span class="whitespace-pre-wrap">{{ truncatedCaption || ' Your caption here...' }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Facebook Feed Preview -->
                                <div
                                    v-else-if="previewPlatform === 'facebook_feed'"
                                    class="max-w-sm mx-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                                >
                                    <div class="flex items-center p-3">
                                        <div v-if="brand?.logo_flat_url" class="w-10 h-10 rounded-full overflow-hidden">
                                            <img :src="brand.logo_flat_url" :alt="brand.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                            {{ brand?.name?.charAt(0) || 'B' }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ brand?.name || 'Brand Name' }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Just now</p>
                                        </div>
                                    </div>
                                    <div class="px-3 pb-2">
                                        <p class="text-sm whitespace-pre-wrap text-gray-900 dark:text-white">{{ selectedPost.caption || 'Your caption here...' }}</p>
                                    </div>
                                    <div class="bg-gray-100 dark:bg-gray-700">
                                        <img :src="selectedPost.media.url" class="w-full" />
                                    </div>
                                    <div class="p-3 border-t border-gray-200 dark:border-gray-700 flex justify-around">
                                        <button class="flex items-center text-gray-500 dark:text-gray-400 text-sm">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                            </svg>
                                            Like
                                        </button>
                                        <button class="flex items-center text-gray-500 dark:text-gray-400 text-sm">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            Comment
                                        </button>
                                        <button class="flex items-center text-gray-500 dark:text-gray-400 text-sm">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                                            </svg>
                                            Share
                                        </button>
                                    </div>
                                </div>

                                <!-- Story/Reel placeholders -->
                                <div
                                    v-else-if="previewPlatform.includes('story') || previewPlatform.includes('reel')"
                                    class="max-w-[280px] mx-auto bg-black rounded-2xl overflow-hidden aspect-[9/16] relative"
                                >
                                    <img :src="selectedPost.media.url" class="w-full h-full object-cover" />
                                    <div class="absolute top-0 left-0 right-0 h-24 bg-gradient-to-b from-black/60 to-transparent pointer-events-none"></div>
                                    <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-black/60 to-transparent pointer-events-none"></div>
                                    <div class="absolute top-4 left-3 flex items-center">
                                        <div v-if="brand?.logo_flat_url" class="w-8 h-8 rounded-full overflow-hidden border-2 border-white">
                                            <img :src="brand.logo_flat_url" :alt="brand.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold border-2 border-white">
                                            {{ brand?.name?.charAt(0) || 'B' }}
                                        </div>
                                        <span class="ml-2 text-white text-sm font-medium">{{ brand?.name }}</span>
                                    </div>
                                    <div v-if="selectedPost.caption" class="absolute bottom-4 left-3 right-3">
                                        <p class="text-white text-sm line-clamp-2">{{ truncatedCaption }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <button
                            @click="router.back()"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveDrafts"
                            :disabled="!canSubmit || loading"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                        >
                            {{ loading ? 'Saving...' : `Save ${posts.length} as Drafts` }}
                        </button>
                        <button
                            @click="submitForApproval"
                            :disabled="!canSubmit || loading"
                            class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                        >
                            {{ loading ? 'Submitting...' : `Submit ${posts.length} for Approval` }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <div v-if="showSuccessModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                            Collection Created!
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ createdCollection?.name }} with {{ posts.length }} posts has been created.
                        </p>

                        <!-- Approval Link (if submitted for approval) -->
                        <div v-if="createdCollection?.approval_url" class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Share this link with your client:
                            </label>
                            <div class="flex gap-2">
                                <input
                                    :value="createdCollection.approval_url"
                                    readonly
                                    class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                />
                                <button
                                    @click="copyApprovalLink"
                                    class="px-3 py-2 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-500"
                                    title="Copy link"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-center gap-3">
                            <button
                                @click="goToCollection"
                                class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600"
                            >
                                View Collection
                            </button>
                            <RouterLink
                                to="/posts"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                Back to Posts
                            </RouterLink>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
