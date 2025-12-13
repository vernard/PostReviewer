<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { postApi, brandApi, mediaApi } from '@/services/api';

const router = useRouter();
const route = useRoute();
const postId = route.params.id;

const post = ref(null);
const brands = ref([]);
const brandMedia = ref([]);
const loading = ref(true);
const saving = ref(false);
const loadingMedia = ref(false);
const error = ref('');

const form = ref({
    brand_id: '',
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

const fetchPost = async () => {
    try {
        loading.value = true;
        const response = await postApi.get(postId);
        post.value = response.data.post || response.data;

        form.value = {
            brand_id: post.value.brand_id,
            title: post.value.title,
            caption: post.value.caption || '',
            platforms: post.value.platforms || [],
        };

        selectedMedia.value = post.value.media || [];

        if (form.value.platforms.length > 0) {
            previewPlatform.value = form.value.platforms[0];
        }
    } catch (err) {
        error.value = 'Failed to load post';
        console.error('Failed to fetch post:', err);
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

const savePost = async () => {
    if (!canSubmit.value) return;

    try {
        saving.value = true;
        error.value = '';

        await postApi.update(postId, {
            ...form.value,
            media_ids: selectedMedia.value.map(m => m.id),
        });

        router.push(`/posts/${postId}`);
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to save post';
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await fetchBrands();
    await fetchPost();
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
                    <RouterLink :to="`/posts/${postId}`" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </RouterLink>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Edit Post</h1>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <!-- Loading -->
                <div v-if="loading" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center text-gray-500 dark:text-gray-400">
                    Loading post...
                </div>

                <!-- Error -->
                <div v-else-if="error && !post" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded">
                    {{ error }}
                    <RouterLink to="/posts" class="ml-4 underline">Back to posts</RouterLink>
                </div>

                <div v-else>
                    <div v-if="error" class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded">
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
                                            class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
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
                                            class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                            placeholder="e.g., December Promo Post"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Caption</label>
                                        <textarea
                                            v-model="form.caption"
                                            rows="4"
                                            class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
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
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                            :src="media.thumbnail_url || media.url"
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
                                <RouterLink
                                    :to="`/posts/${postId}`"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    Cancel
                                </RouterLink>
                                <button
                                    @click="savePost"
                                    :disabled="!canSubmit || saving"
                                    class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                                >
                                    {{ saving ? 'Saving...' : 'Save Changes' }}
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
                                class="max-w-sm mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"
                            >
                                <div class="flex items-center p-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ selectedBrand?.name?.charAt(0) || 'B' }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-semibold dark:text-white">{{ selectedBrand?.name || 'Brand Name' }}</p>
                                    </div>
                                </div>
                                <div class="aspect-square bg-gray-100 dark:bg-gray-700">
                                    <img
                                        v-if="selectedMedia[0]"
                                        :src="selectedMedia[0].url"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                        No image selected
                                    </div>
                                </div>
                                <div class="p-3">
                                    <p class="text-sm dark:text-gray-300">
                                        <span class="font-semibold dark:text-white">{{ selectedBrand?.name || 'brand' }}</span>
                                        <span class="whitespace-pre-wrap">{{ truncatedCaption || ' Your caption here...' }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Facebook Feed Preview -->
                            <div
                                v-else-if="(previewPlatform || form.platforms[0]).includes('facebook_feed')"
                                class="max-w-sm mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"
                            >
                                <div class="flex items-center p-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                        {{ selectedBrand?.name?.charAt(0) || 'B' }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-semibold dark:text-white">{{ selectedBrand?.name || 'Brand Name' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Just now</p>
                                    </div>
                                </div>
                                <div class="px-3 pb-2">
                                    <p class="text-sm whitespace-pre-wrap dark:text-gray-300">{{ form.caption || 'Your caption here...' }}</p>
                                </div>
                                <div class="bg-gray-100 dark:bg-gray-700">
                                    <img
                                        v-if="selectedMedia[0]"
                                        :src="selectedMedia[0].url"
                                        class="w-full"
                                    />
                                    <div v-else class="aspect-video flex items-center justify-center text-gray-400">
                                        No image selected
                                    </div>
                                </div>
                            </div>

                            <!-- Story Preview -->
                            <div
                                v-else
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
                                <div class="absolute top-0 left-0 right-0 p-4 bg-gradient-to-b from-black/50 to-transparent">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                            {{ selectedBrand?.name?.charAt(0) || 'B' }}
                                        </div>
                                        <span class="ml-2 text-white text-sm font-medium">{{ selectedBrand?.name || 'Brand' }}</span>
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
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showMediaLibrary = false"></div>

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
                                    isMediaSelected(media) ? 'border-primary-500 dark:border-primary-400 ring-2 ring-primary-200 dark:ring-primary-900/50' : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600'
                                ]"
                            >
                                <img
                                    :src="media.thumbnail_url || media.url"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-if="isMediaSelected(media)"
                                    class="absolute inset-0 bg-primary-500 dark:bg-primary-600 bg-opacity-20 flex items-center justify-center"
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
