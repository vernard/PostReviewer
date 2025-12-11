<script setup>
import { ref, computed, onMounted } from 'vue';
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
    brand_id: route.query.brand_id || '',
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

const fetchBrands = async () => {
    try {
        const response = await brandApi.list();
        brands.value = response.data.data || response.data;
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
                    <button @click="router.back()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <h1 class="text-2xl font-semibold text-gray-900">Create Post</h1>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div v-if="error" class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded">
                    {{ error }}
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Form -->
                    <div class="space-y-6">
                        <div class="bg-white shadow rounded-lg p-6">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Post Details</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Brand *</label>
                                    <select
                                        v-model="form.brand_id"
                                        @change="fetchBrandMedia(); selectedMedia = []"
                                        required
                                        class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    >
                                        <option value="">Select a brand</option>
                                        <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                            {{ brand.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Title (internal) *</label>
                                    <input
                                        v-model="form.title"
                                        type="text"
                                        required
                                        class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="e.g., December Promo Post"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Caption</label>
                                    <textarea
                                        v-model="form.caption"
                                        rows="4"
                                        class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Write your post caption here..."
                                    />
                                    <p class="mt-1 text-xs text-gray-500">{{ form.caption.length }} characters</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Platforms *</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label
                                            v-for="platform in platforms"
                                            :key="platform.id"
                                            :class="[
                                                'flex items-center p-3 border rounded-lg cursor-pointer transition-colors',
                                                form.platforms.includes(platform.id)
                                                    ? 'border-indigo-500 bg-indigo-50'
                                                    : 'border-gray-200 hover:border-gray-300'
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
                                            <span class="text-sm text-gray-700">{{ platform.name }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Media Section -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-medium text-gray-900">Media</h2>
                                <button
                                    v-if="form.brand_id"
                                    @click="showMediaLibrary = true"
                                    class="text-sm text-indigo-600 hover:text-indigo-800"
                                >
                                    Browse Library
                                </button>
                            </div>

                            <div v-if="!form.brand_id" class="text-center text-gray-500 py-8">
                                Select a brand first to add media
                            </div>

                            <div v-else-if="selectedMedia.length === 0" class="text-center text-gray-500 py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="mt-2">No media selected</p>
                                <button
                                    @click="showMediaLibrary = true"
                                    class="mt-2 text-indigo-600 hover:text-indigo-800"
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
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                @click="saveDraft"
                                :disabled="!canSubmit || loading"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                            >
                                {{ loading ? 'Saving...' : 'Save as Draft' }}
                            </button>
                            <button
                                @click="submitForApproval"
                                :disabled="!canSubmit || loading"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ loading ? 'Submitting...' : 'Submit for Approval' }}
                            </button>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Preview</h2>
                            <div v-if="form.platforms.length > 0" class="flex gap-2">
                                <button
                                    v-for="platform in form.platforms"
                                    :key="platform"
                                    @click="previewPlatform = platform"
                                    :class="[
                                        'px-3 py-1 text-xs rounded-full',
                                        previewPlatform === platform || (!previewPlatform && form.platforms[0] === platform)
                                            ? 'bg-indigo-100 text-indigo-700'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    ]"
                                >
                                    {{ platform.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()) }}
                                </button>
                            </div>
                        </div>

                        <div v-if="form.platforms.length === 0" class="text-center text-gray-500 py-12">
                            Select a platform to see the mockup preview
                        </div>

                        <!-- Instagram Feed Preview -->
                        <div
                            v-else-if="(previewPlatform || form.platforms[0]).includes('instagram_feed')"
                            class="max-w-sm mx-auto bg-white border border-gray-200 rounded-lg overflow-hidden"
                        >
                            <!-- Header -->
                            <div class="flex items-center p-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                    {{ selectedBrand?.name?.charAt(0) || 'B' }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-semibold">{{ selectedBrand?.name || 'Brand Name' }}</p>
                                </div>
                            </div>
                            <!-- Image -->
                            <div class="aspect-square bg-gray-100">
                                <img
                                    v-if="selectedMedia[0]"
                                    :src="selectedMedia[0].url"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                    No image selected
                                </div>
                            </div>
                            <!-- Actions -->
                            <div class="p-3">
                                <div class="flex gap-4 mb-2">
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
                                <p class="text-sm">
                                    <span class="font-semibold">{{ selectedBrand?.name || 'brand' }}</span>
                                    <span class="whitespace-pre-wrap">{{ truncatedCaption || ' Your caption here...' }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Facebook Feed Preview -->
                        <div
                            v-else-if="(previewPlatform || form.platforms[0]).includes('facebook_feed')"
                            class="max-w-sm mx-auto bg-white border border-gray-200 rounded-lg overflow-hidden"
                        >
                            <!-- Header -->
                            <div class="flex items-center p-3">
                                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                    {{ selectedBrand?.name?.charAt(0) || 'B' }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-semibold">{{ selectedBrand?.name || 'Brand Name' }}</p>
                                    <p class="text-xs text-gray-500">Just now</p>
                                </div>
                            </div>
                            <!-- Caption -->
                            <div class="px-3 pb-2">
                                <p class="text-sm whitespace-pre-wrap">{{ form.caption || 'Your caption here...' }}</p>
                            </div>
                            <!-- Image -->
                            <div class="bg-gray-100">
                                <img
                                    v-if="selectedMedia[0]"
                                    :src="selectedMedia[0].url"
                                    class="w-full"
                                />
                                <div v-else class="aspect-video flex items-center justify-center text-gray-400">
                                    No image selected
                                </div>
                            </div>
                            <!-- Actions -->
                            <div class="p-3 border-t border-gray-200 flex justify-around">
                                <button class="flex items-center text-gray-500 text-sm">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                    </svg>
                                    Like
                                </button>
                                <button class="flex items-center text-gray-500 text-sm">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    Comment
                                </button>
                                <button class="flex items-center text-gray-500 text-sm">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                                    </svg>
                                    Share
                                </button>
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
                            <!-- Story Header -->
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

        <!-- Media Library Modal -->
        <div v-if="showMediaLibrary" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showMediaLibrary = false"></div>

                <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[80vh] overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Media Library</h3>
                        <button @click="showMediaLibrary = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6">
                        <div v-if="loadingMedia" class="text-center py-8 text-gray-500">
                            Loading media...
                        </div>
                        <div v-else-if="brandMedia.length === 0" class="text-center py-8 text-gray-500">
                            No media uploaded for this brand yet.
                            <RouterLink to="/media" class="block mt-2 text-indigo-600 hover:text-indigo-800">
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
                                    isMediaSelected(media) ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-transparent hover:border-gray-300'
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
                                    class="absolute inset-0 bg-indigo-500 bg-opacity-20 flex items-center justify-center"
                                >
                                    <svg class="w-8 h-8 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                        <span class="text-sm text-gray-500">{{ selectedMedia.length }} selected</span>
                        <button
                            @click="showMediaLibrary = false"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
