<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { postApi, brandApi, mediaApi } from '@/services/api';

const router = useRouter();
const route = useRoute();

const brands = ref([]);
const brandMedia = ref([]);
const loading = ref(false);
const loadingMedia = ref(false);
const error = ref('');

const form = ref({
    brand_id: route.query.brand_id ? parseInt(route.query.brand_id) : '',
    title: '',
    caption: '',
    platforms: [],
});

const selectedMedia = ref([]);
const showMediaLibrary = ref(false);
const previewPlatform = ref('');

const platforms = [
    { id: 'facebook_feed', name: 'Facebook Feed', icon: 'FB' },
    { id: 'facebook_story', name: 'Facebook Story', icon: 'FB' },
    { id: 'instagram_feed', name: 'Instagram Feed', icon: 'IG' },
    { id: 'instagram_story', name: 'Instagram Story', icon: 'IG' },
    { id: 'instagram_reel', name: 'Instagram Reel', icon: 'IG' },
];

const selectedBrand = computed(() => {
    return brands.value.find(b => b.id === parseInt(form.value.brand_id));
});

const canSubmit = computed(() => {
    return form.value.brand_id &&
           form.value.title.trim() &&
           form.value.platforms.length > 0;
});

const truncatedCaption = computed(() => {
    if (!form.value.caption) return '';
    if (form.value.caption.length <= 125) return form.value.caption;
    return form.value.caption.substring(0, 125) + '...';
});

// Watch platforms and reset preview when needed
watch(() => form.value.platforms, (newPlatforms) => {
    // Always ensure previewPlatform is valid
    if (!newPlatforms.length) {
        previewPlatform.value = '';
    } else if (!previewPlatform.value || !newPlatforms.includes(previewPlatform.value)) {
        // Set to first available platform if current is invalid or empty
        previewPlatform.value = newPlatforms[0];
    }
}, { deep: true });

// Remember last selected brand
watch(() => form.value.brand_id, (newBrandId) => {
    if (newBrandId) {
        localStorage.setItem('last_selected_brand_id', newBrandId.toString());
    }
});

const fetchBrands = async () => {
    try {
        const response = await brandApi.list();
        brands.value = response.data.brands || response.data.data || response.data;

        // Auto-select brand (priority: query param > last used > only one available)
        if (!form.value.brand_id) {
            if (route.query.brand_id) {
                form.value.brand_id = parseInt(route.query.brand_id);
            } else if (brands.value.length === 1) {
                form.value.brand_id = brands.value[0].id;
            } else {
                const lastBrandId = localStorage.getItem('last_selected_brand_id');
                if (lastBrandId && brands.value.some(b => b.id === parseInt(lastBrandId))) {
                    form.value.brand_id = parseInt(lastBrandId);
                }
            }
        }

        if (form.value.brand_id) {
            fetchBrandMedia();
        }
    } catch (err) {
        console.error('Failed to fetch brands:', err);
    }
};

const fetchBrandMedia = async () => {
    if (!form.value.brand_id) {
        brandMedia.value = [];
        return;
    }

    try {
        loadingMedia.value = true;
        const response = await mediaApi.list({ brand_id: form.value.brand_id });
        brandMedia.value = response.data.data || response.data || [];
    } catch (err) {
        console.error('Failed to fetch media:', err);
    } finally {
        loadingMedia.value = false;
    }
};

const toggleMedia = (media) => {
    const index = selectedMedia.value.findIndex(m => m.id === media.id);
    if (index === -1) {
        selectedMedia.value.push(media);
    } else {
        selectedMedia.value.splice(index, 1);
    }
};

const isMediaSelected = (media) => {
    return selectedMedia.value.some(m => m.id === media.id);
};

const removeMedia = (index) => {
    selectedMedia.value.splice(index, 1);
};

const saveDraft = async () => {
    if (!canSubmit.value) return;

    try {
        loading.value = true;
        error.value = '';

        const response = await postApi.create({
            ...form.value,
            media_ids: selectedMedia.value.map(m => m.id),
        });

        router.push(`/posts/${response.data.post.id}`);
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create post';
    } finally {
        loading.value = false;
    }
};

const submitForApproval = async () => {
    if (!canSubmit.value) return;

    try {
        loading.value = true;
        error.value = '';

        // First create the post
        const createResponse = await postApi.create({
            ...form.value,
            media_ids: selectedMedia.value.map(m => m.id),
        });

        // Then submit for approval
        await postApi.submitForApproval(createResponse.data.post.id);

        router.push(`/posts/${createResponse.data.post.id}`);
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create post';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchBrands();
    if (form.value.brand_id) {
        fetchBrandMedia();
    }
});
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    <button @click="router.back()" class="text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Create Post</h1>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div v-if="error" class="mb-6 bg-red-50 border border-red-200 text-red-600 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400 px-4 py-3 rounded">
                    {{ error }}
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Form -->
                    <div class="space-y-6">
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Post Details</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Brand *</label>
                                    <select
                                        v-model="form.brand_id"
                                        @change="fetchBrandMedia(); selectedMedia = []"
                                        required
                                        class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                                    >
                                        <option value="">Select a brand</option>
                                        <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                            {{ brand.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Title (internal) *</label>
                                    <input
                                        v-model="form.title"
                                        type="text"
                                        required
                                        class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                                        placeholder="e.g., December Promo Post"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Caption</label>
                                    <textarea
                                        v-model="form.caption"
                                        rows="4"
                                        class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                                        placeholder="Write your post caption here..."
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ form.caption.length }} characters</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Platforms *</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label
                                            v-for="platform in platforms"
                                            :key="platform.id"
                                            :class="[
                                                'flex items-center p-3 border rounded-lg cursor-pointer transition-colors',
                                                form.platforms.includes(platform.id)
                                                    ? 'border-primary-500 dark:border-primary-400 bg-primary-50 dark:bg-primary-900/30'
                                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                            ]"
                                        >
                                            <input
                                                type="checkbox"
                                                :value="platform.id"
                                                v-model="form.platforms"
                                                class="sr-only"
                                            />
                                            <span
                                                :class="[
                                                    'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold mr-2',
                                                    platform.id.startsWith('facebook') ? 'bg-blue-100 text-blue-600' : 'bg-gradient-to-br from-purple-500 to-pink-500 text-white'
                                                ]"
                                            >
                                                {{ platform.icon }}
                                            </span>
                                            <span class="text-sm text-gray-700 dark:text-gray-400">{{ platform.name }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Media Section -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Media</h2>
                                <button
                                    v-if="form.brand_id"
                                    @click="showMediaLibrary = true"
                                    class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300"
                                >
                                    Browse Library
                                </button>
                            </div>

                            <div v-if="!form.brand_id" class="text-center text-gray-500 dark:text-gray-400 py-8">
                                Select a brand first to add media
                            </div>

                            <div v-else-if="selectedMedia.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="mt-2">No media selected</p>
                                <button
                                    @click="showMediaLibrary = true"
                                    class="mt-2 text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300"
                                >
                                    Browse media library
                                </button>
                            </div>

                            <div v-else class="grid grid-cols-3 gap-2">
                                <div
                                    v-for="(media, index) in selectedMedia"
                                    :key="media.id"
                                    class="relative aspect-square rounded-lg overflow-hidden group"
                                >
                                    <img
                                        v-if="media.type === 'image'"
                                        :src="media.thumbnail_url || media.url"
                                        class="w-full h-full object-cover"
                                    />
                                    <video
                                        v-else
                                        :src="media.url"
                                        class="w-full h-full object-cover"
                                    />
                                    <button
                                        @click="removeMedia(index)"
                                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <span class="absolute bottom-1 left-1 bg-black bg-opacity-60 text-white text-xs px-2 py-0.5 rounded">
                                        {{ index + 1 }}
                                    </span>
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
                                @click="saveDraft"
                                :disabled="!canSubmit || loading"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                            >
                                {{ loading ? 'Saving...' : 'Save as Draft' }}
                            </button>
                            <button
                                @click="submitForApproval"
                                :disabled="!canSubmit || loading"
                                class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                            >
                                {{ loading ? 'Submitting...' : 'Submit for Approval' }}
                            </button>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Preview</h2>
                            <div v-if="form.platforms.length > 0" class="flex gap-2">
                                <button
                                    v-for="platform in form.platforms"
                                    :key="platform"
                                    @click="previewPlatform = platform"
                                    :class="[
                                        'px-3 py-1 text-xs rounded-full',
                                        previewPlatform === platform || (!previewPlatform && form.platforms[0] === platform)
                                            ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    {{ platform.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()) }}
                                </button>
                            </div>
                        </div>

                        <div v-if="form.platforms.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-12">
                            Select a platform to see the mockup preview
                        </div>

                        <!-- Instagram Feed Preview -->
                        <div
                            v-else-if="(previewPlatform || form.platforms[0]).includes('instagram_feed')"
                            class="max-w-sm mx-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                        >
                            <!-- Header -->
                            <div class="flex items-center p-3">
                                <div v-if="selectedBrand?.logo_url" class="w-8 h-8 rounded-full overflow-hidden">
                                    <img :src="selectedBrand.logo_url" :alt="selectedBrand.name" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                    {{ selectedBrand?.name?.charAt(0) || 'B' }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedBrand?.name || 'Brand Name' }}</p>
                                </div>
                            </div>
                            <!-- Image -->
                            <div class="aspect-square bg-gray-100 dark:bg-gray-700">
                                <img
                                    v-if="selectedMedia[0]"
                                    :src="selectedMedia[0].url"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                    No image selected
                                </div>
                            </div>
                            <!-- Actions -->
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
                                    <span class="font-semibold">{{ selectedBrand?.name || 'brand' }}</span>
                                    <span class="whitespace-pre-wrap">{{ truncatedCaption || ' Your caption here...' }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Facebook Feed Preview -->
                        <div
                            v-else-if="(previewPlatform || form.platforms[0]).includes('facebook_feed')"
                            class="max-w-sm mx-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                        >
                            <!-- Header -->
                            <div class="flex items-center p-3">
                                <div v-if="selectedBrand?.logo_url" class="w-10 h-10 rounded-full overflow-hidden">
                                    <img :src="selectedBrand.logo_url" :alt="selectedBrand.name" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                    {{ selectedBrand?.name?.charAt(0) || 'B' }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedBrand?.name || 'Brand Name' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Just now</p>
                                </div>
                            </div>
                            <!-- Caption -->
                            <div class="px-3 pb-2">
                                <p class="text-sm whitespace-pre-wrap text-gray-900 dark:text-white">{{ form.caption || 'Your caption here...' }}</p>
                            </div>
                            <!-- Image -->
                            <div class="bg-gray-100 dark:bg-gray-700">
                                <img
                                    v-if="selectedMedia[0]"
                                    :src="selectedMedia[0].url"
                                    class="w-full"
                                />
                                <div v-else class="aspect-video flex items-center justify-center text-gray-400 dark:text-gray-500">
                                    No image selected
                                </div>
                            </div>
                            <!-- Actions -->
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

                        <!-- Instagram Story Preview -->
                        <div
                            v-else-if="previewPlatform === 'instagram_story'"
                            class="max-w-[280px] mx-auto bg-black rounded-2xl overflow-hidden aspect-[9/16] relative"
                        >
                            <img
                                v-if="selectedMedia[0]"
                                :src="selectedMedia[0].url"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
                                No image selected
                            </div>
                            <!-- Top gradient overlay -->
                            <div class="absolute top-0 left-0 right-0 h-24 bg-gradient-to-b from-black/60 to-transparent pointer-events-none"></div>
                            <!-- Progress bar -->
                            <div class="absolute top-2 left-2 right-2 h-0.5 bg-white/30 rounded-full overflow-hidden">
                                <div class="h-full w-1/3 bg-white rounded-full"></div>
                            </div>
                            <!-- Header -->
                            <div class="absolute top-4 left-0 right-0 px-3 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="p-0.5 rounded-full bg-gradient-to-br from-yellow-400 via-pink-500 to-purple-600">
                                        <div v-if="selectedBrand?.logo_url" class="w-9 h-9 rounded-full overflow-hidden border-2 border-black bg-white">
                                            <img :src="selectedBrand.logo_url" :alt="selectedBrand.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold border-2 border-black">
                                            {{ selectedBrand?.name?.charAt(0) || 'B' }}
                                        </div>
                                    </div>
                                    <span class="ml-2 text-white text-sm font-medium">{{ selectedBrand?.name || 'Brand' }}</span>
                                    <span class="ml-1 text-white/60 text-xs">2h</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            </div>
                            <!-- Bottom gradient overlay -->
                            <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-black/60 to-transparent pointer-events-none"></div>
                            <!-- Bottom message input -->
                            <div class="absolute bottom-4 left-3 right-3 flex items-center gap-2">
                                <div class="flex-1 border border-white/40 rounded-full px-4 py-2 bg-black/20">
                                    <span class="text-white/80 text-sm">Send message</span>
                                </div>
                                <svg class="w-6 h-6 text-white drop-shadow-md" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                <svg class="w-6 h-6 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </div>
                        </div>

                        <!-- Instagram Reel Preview -->
                        <div
                            v-else-if="previewPlatform === 'instagram_reel'"
                            class="max-w-[280px] mx-auto bg-black rounded-2xl overflow-hidden aspect-[9/16] relative"
                        >
                            <img
                                v-if="selectedMedia[0]"
                                :src="selectedMedia[0].url"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
                                No image selected
                            </div>
                            <!-- Bottom gradient overlay -->
                            <div class="absolute bottom-0 left-0 right-0 h-48 bg-gradient-to-t from-black/70 to-transparent pointer-events-none"></div>
                            <!-- Right sidebar actions -->
                            <div class="absolute right-3 bottom-24 flex flex-col items-center gap-4 drop-shadow-lg">
                                <div class="flex flex-col items-center">
                                    <svg class="w-7 h-7 text-white drop-shadow-md" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <span class="text-white text-xs mt-1 drop-shadow-md">1.2K</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <svg class="w-7 h-7 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <span class="text-white text-xs mt-1 drop-shadow-md">48</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <svg class="w-7 h-7 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </div>
                                <div class="flex flex-col items-center">
                                    <svg class="w-7 h-7 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                                    </svg>
                                </div>
                                <div class="w-7 h-7 rounded border-2 border-white overflow-hidden shadow-md">
                                    <div v-if="selectedBrand?.logo_url">
                                        <img :src="selectedBrand.logo_url" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-full h-full bg-gradient-to-br from-purple-500 to-pink-500"></div>
                                </div>
                            </div>
                            <!-- Bottom content -->
                            <div class="absolute bottom-4 left-3 right-14">
                                <div class="flex items-center mb-2">
                                    <div v-if="selectedBrand?.logo_url" class="w-8 h-8 rounded-full overflow-hidden">
                                        <img :src="selectedBrand.logo_url" :alt="selectedBrand.name" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ selectedBrand?.name?.charAt(0) || 'B' }}
                                    </div>
                                    <span class="ml-2 text-white text-sm font-semibold">{{ selectedBrand?.name || 'Brand' }}</span>
                                    <button class="ml-2 px-3 py-1 border border-white rounded text-white text-xs font-semibold">Follow</button>
                                </div>
                                <p class="text-white text-sm line-clamp-2">{{ truncatedCaption || 'Your caption here...' }}</p>
                                <!-- Audio bar -->
                                <div class="flex items-center mt-2">
                                    <svg class="w-3 h-3 text-white mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 18V5l12-2v13" /><circle cx="6" cy="18" r="3" /><circle cx="18" cy="16" r="3" />
                                    </svg>
                                    <span class="text-white text-xs">Original Audio</span>
                                </div>
                            </div>
                        </div>

                        <!-- Facebook Story Preview -->
                        <div
                            v-else-if="previewPlatform === 'facebook_story'"
                            class="max-w-[280px] mx-auto bg-gray-900 rounded-2xl overflow-hidden aspect-[9/16] relative"
                        >
                            <img
                                v-if="selectedMedia[0]"
                                :src="selectedMedia[0].url"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
                                No image selected
                            </div>
                            <!-- Top gradient overlay -->
                            <div class="absolute top-0 left-0 right-0 h-24 bg-gradient-to-b from-black/60 to-transparent pointer-events-none"></div>
                            <!-- Progress bar -->
                            <div class="absolute top-2 left-2 right-2 h-0.5 bg-white/30 rounded-full overflow-hidden">
                                <div class="h-full w-1/2 bg-white rounded-full"></div>
                            </div>
                            <!-- Header -->
                            <div class="absolute top-4 left-0 right-0 px-3 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="p-0.5 rounded-full bg-blue-500">
                                        <div v-if="selectedBrand?.logo_url" class="w-10 h-10 rounded-full overflow-hidden border-2 border-black bg-black">
                                            <img :src="selectedBrand.logo_url" :alt="selectedBrand.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold border-2 border-black">
                                            {{ selectedBrand?.name?.charAt(0) || 'B' }}
                                        </div>
                                    </div>
                                    <div class="ml-2">
                                        <span class="text-white text-sm font-semibold block">{{ selectedBrand?.name || 'Brand' }}</span>
                                        <span class="text-white/60 text-xs">3h ago</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                                    </svg>
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            </div>
                            <!-- Bottom black bar -->
                            <div class="absolute bottom-0 left-0 right-0 bg-black px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <!-- Create story circle button - dashed circle with plus -->
                                    <button class="w-9 h-9 bg-transparent flex items-center justify-center shrink-0">
                                        <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <!-- Dashed circle segments -->
                                            <path d="M12 2a10 10 0 0 1 7.07 2.93" />
                                            <path d="M21.07 9a10 10 0 0 1 0 6" />
                                            <path d="M19.07 19.07A10 10 0 0 1 12 22" />
                                            <path d="M5 19.07A10 10 0 0 1 2.93 12" />
                                            <path d="M2.93 9A10 10 0 0 1 5 4.93" />
                                            <!-- Plus sign -->
                                            <path d="M12 8v8M8 12h8" />
                                        </svg>
                                    </button>
                                    <!-- Send message input -->
                                    <div class="flex-1 bg-zinc-800 rounded-full px-3 py-1.5">
                                        <span class="text-zinc-400 text-xs">Send message...</span>
                                    </div>
                                    <!-- Visible reactions - Facebook style -->
                                    <div class="flex items-center shrink-0 -space-x-1">
                                        <!-- Like - Blue circle with thumbs up -->
                                        <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M2 20h2c.55 0 1-.45 1-1v-9c0-.55-.45-1-1-1H2v11zm19.83-7.12c.11-.25.17-.52.17-.8V11c0-1.1-.9-2-2-2h-5.5l.92-4.65c.05-.22.02-.46-.08-.66-.23-.45-.52-.86-.88-1.22L14 2 7.59 8.41C7.21 8.79 7 9.3 7 9.83v7.84C7 18.95 8.05 20 9.34 20h8.11c.7 0 1.36-.37 1.72-.97l2.66-6.15z"/>
                                            </svg>
                                        </div>
                                        <!-- Love - Red circle with heart -->
                                        <div class="w-6 h-6 rounded-full bg-red-500 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                            </svg>
                                        </div>
                                        <!-- Haha - Yellow circle with laughing face -->
                                        <div class="w-6 h-6 rounded-full bg-yellow-400 flex items-center justify-center text-xs">
                                            😆
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Media Library Modal -->
        <div v-if="showMediaLibrary" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" @click="showMediaLibrary = false"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[80vh] overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Media Library</h3>
                        <button @click="showMediaLibrary = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6">
                        <div v-if="loadingMedia" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Loading media...
                        </div>
                        <div v-else-if="brandMedia.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No media uploaded for this brand yet.
                            <RouterLink to="/media" class="block mt-2 text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300">
                                Go to Media Library to upload
                            </RouterLink>
                        </div>
                        <div v-else class="grid grid-cols-4 gap-4">
                            <div
                                v-for="media in brandMedia"
                                :key="media.id"
                                @click="toggleMedia(media)"
                                :class="[
                                    'relative aspect-square rounded-lg overflow-hidden cursor-pointer border-2 transition-all',
                                    isMediaSelected(media) ? 'border-primary-500 dark:border-primary-400 ring-2 ring-primary-200 dark:ring-primary-900/30' : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600'
                                ]"
                            >
                                <img
                                    v-if="media.type === 'image'"
                                    :src="media.thumbnail_url || media.url"
                                    class="w-full h-full object-cover"
                                />
                                <video
                                    v-else
                                    :src="media.url"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-if="isMediaSelected(media)"
                                    class="absolute inset-0 bg-primary-500 bg-opacity-20 dark:bg-primary-900/30 flex items-center justify-center"
                                >
                                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-600 flex justify-between items-center">
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ selectedMedia.length }} selected</span>
                        <button
                            @click="showMediaLibrary = false"
                            class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
