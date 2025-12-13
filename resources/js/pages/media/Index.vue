<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { mediaApi } from '@/services/api';
import { useBrandStore } from '@/stores/brand';

const brandStore = useBrandStore();

const media = ref([]);
const loading = ref(true);
const uploading = ref(false);
const uploadProgress = ref(0);

// Use brands from the global store
const brands = computed(() => brandStore.brands);

// Use active brand as default filter
const filters = ref({
    brand_id: brandStore.activeBrandId || '',
    type: '',
    search: '',
});

const showUploadModal = ref(false);
const uploadForm = ref({
    brand_id: brandStore.activeBrandId || '',
    files: [],
});
const videoPreview = ref(null);
const imagePreview = ref(null);

const fetchMedia = async () => {
    try {
        loading.value = true;
        const params = {};
        if (filters.value.brand_id) params.brand_id = filters.value.brand_id;
        if (filters.value.type) params.type = filters.value.type;

        const response = await mediaApi.list(params);
        let items = response.data.data || response.data || [];

        // Client-side search filter
        if (filters.value.search) {
            const searchLower = filters.value.search.toLowerCase();
            items = items.filter(m =>
                m.filename?.toLowerCase().includes(searchLower) ||
                m.original_filename?.toLowerCase().includes(searchLower)
            );
        }

        media.value = items;
    } catch (err) {
        console.error('Failed to fetch media:', err);
    } finally {
        loading.value = false;
    }
};

const handleFileSelect = (event) => {
    uploadForm.value.files = Array.from(event.target.files);
};

const handleDrop = (event) => {
    event.preventDefault();
    uploadForm.value.files = Array.from(event.dataTransfer.files);
};

const uploadMedia = async () => {
    if (!uploadForm.value.brand_id || uploadForm.value.files.length === 0) return;

    uploading.value = true;
    uploadProgress.value = 0;

    try {
        for (let i = 0; i < uploadForm.value.files.length; i++) {
            const file = uploadForm.value.files[i];
            await mediaApi.upload(uploadForm.value.brand_id, file, (progress) => {
                uploadProgress.value = Math.round(((i + progress / 100) / uploadForm.value.files.length) * 100);
            });
        }

        showUploadModal.value = false;
        uploadForm.value = { brand_id: '', files: [] };
        await fetchMedia();
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to upload media');
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
    }
};

const deleteMedia = async (item) => {
    if (!confirm('Are you sure you want to delete this media file?')) return;

    try {
        await mediaApi.delete(item.id);
        media.value = media.value.filter(m => m.id !== item.id);
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to delete media');
    }
};

const formatFileSize = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const formatDuration = (seconds) => {
    if (!seconds) return '';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const playVideo = (item, event) => {
    event.stopPropagation();
    videoPreview.value = item;
};

const closeVideoPreview = () => {
    videoPreview.value = null;
};

const viewImage = (item, event) => {
    event.stopPropagation();
    imagePreview.value = item;
};

const closeImagePreview = () => {
    imagePreview.value = null;
};

watch(filters, () => {
    fetchMedia();
}, { deep: true });

// Sync filter when active brand changes in the store
watch(() => brandStore.activeBrandId, (newBrandId) => {
    if (newBrandId && filters.value.brand_id !== newBrandId) {
        filters.value.brand_id = newBrandId;
        uploadForm.value.brand_id = newBrandId;
    }
});

onMounted(() => {
    fetchMedia();
});
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Media Library</h1>
                    <button
                        @click="showUploadModal = true"
                        class="bg-primary-600 dark:bg-primary-500 text-white px-4 py-2 rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        Upload Media
                    </button>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                    <div class="p-4 flex gap-4 flex-wrap items-center">
                        <select
                            v-model="filters.brand_id"
                            class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        >
                            <option value="">All Brands</option>
                            <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                {{ brand.name }}
                            </option>
                        </select>
                        <select
                            v-model="filters.type"
                            class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        >
                            <option value="">All Types</option>
                            <option value="image">Images</option>
                            <option value="video">Videos</option>
                        </select>
                        <input
                            v-model="filters.search"
                            type="search"
                            placeholder="Search media..."
                            class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder:text-gray-500 dark:placeholder:text-gray-400"
                        />
                        <button
                            v-if="filters.brand_id || filters.type || filters.search"
                            @click="filters = { brand_id: '', type: '', search: '' }"
                            class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300"
                        >
                            Clear filters
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div v-for="i in 12" :key="i" class="aspect-square bg-gray-200 dark:bg-gray-700 rounded-lg animate-pulse"></div>
                </div>

                <!-- Empty State -->
                <div v-else-if="media.length === 0" class="bg-white dark:bg-gray-800 shadow rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No media found</h3>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">
                        {{ filters.brand_id || filters.type || filters.search
                            ? 'Try adjusting your filters.'
                            : 'Upload your first image or video to get started.' }}
                    </p>
                    <button
                        @click="showUploadModal = true"
                        class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 dark:bg-primary-500 hover:bg-primary-700 dark:hover:bg-primary-600"
                    >
                        Upload Media
                    </button>
                </div>

                <!-- Media Grid -->
                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div
                        v-for="item in media"
                        :key="item.id"
                        class="group relative aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden"
                    >
                        <img
                            v-if="item.type === 'image'"
                            :src="item.thumbnail_url || item.url"
                            :alt="item.original_filename"
                            class="w-full h-full object-cover"
                        />
                        <img
                            v-else-if="item.thumbnail_url"
                            :src="item.thumbnail_url"
                            :alt="item.original_filename"
                            class="w-full h-full object-cover"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center bg-gray-200 dark:bg-gray-600"
                        >
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <!-- Video play button overlay (centered only) -->
                        <div
                            v-if="item.type === 'video'"
                            class="absolute inset-0 flex items-center justify-center pointer-events-none"
                        >
                            <button
                                @click="playVideo(item, $event)"
                                class="w-12 h-12 z-10 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors group-hover:scale-110 pointer-events-auto"
                            >
                                <svg class="w-6 h-6 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Video duration badge -->
                        <div
                            v-if="item.type === 'video' && item.duration"
                            class="absolute bottom-2 left-2 z-20 bg-black/70 text-white text-xs px-1.5 py-0.5 rounded pointer-events-none"
                        >
                            {{ formatDuration(item.duration) }}
                        </div>

                        <!-- View button for images (centered) -->
                        <div
                            v-if="item.type === 'image'"
                            class="absolute inset-0 flex items-center justify-center pointer-events-none"
                        >
                            <button
                                @click="viewImage(item, $event)"
                                class="w-12 h-12 z-10 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors group-hover:scale-110 pointer-events-auto opacity-0 group-hover:opacity-100"
                            >
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Delete button (top right corner for all) -->
                        <button
                            @click.stop="deleteMedia(item)"
                            class="absolute top-2 right-2 z-20 p-1.5 bg-white dark:bg-gray-700 rounded-full hover:bg-red-100 dark:hover:bg-red-900 opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>

                        <!-- Info tooltip on hover -->
                        <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                            <p class="text-white text-xs truncate">{{ item.original_filename }}</p>
                            <p class="text-white/70 text-xs">{{ formatFileSize(item.size) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Modal -->
        <div v-if="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" @click="showUploadModal = false"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Upload Media</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brand *</label>
                            <select
                                v-model="uploadForm.brand_id"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            >
                                <option value="">Select a brand</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                    {{ brand.name }}
                                </option>
                            </select>
                        </div>

                        <div
                            @drop="handleDrop"
                            @dragover.prevent
                            @dragenter.prevent
                            class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-primary-400 dark:hover:border-primary-400 transition-colors"
                        >
                            <input
                                type="file"
                                multiple
                                accept="image/*,video/*"
                                @change="handleFileSelect"
                                class="hidden"
                                id="file-upload"
                            />
                            <label for="file-upload" class="cursor-pointer">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="text-primary-600 dark:text-primary-400 font-medium">Click to upload</span> or drag and drop
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PNG, JPG, MP4 up to 100MB</p>
                            </label>
                        </div>

                        <!-- Selected files list -->
                        <div v-if="uploadForm.files.length > 0" class="space-y-2">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Selected files ({{ uploadForm.files.length }})</p>
                            <div class="max-h-32 overflow-y-auto space-y-1">
                                <div
                                    v-for="(file, index) in uploadForm.files"
                                    :key="index"
                                    class="flex items-center justify-between text-sm bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded"
                                >
                                    <span class="truncate text-gray-900 dark:text-white">{{ file.name }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 ml-2">{{ formatFileSize(file.size) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress bar -->
                        <div v-if="uploading" class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                            <div
                                class="bg-primary-600 dark:bg-primary-500 h-2.5 rounded-full transition-all"
                                :style="{ width: uploadProgress + '%' }"
                            ></div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="showUploadModal = false"
                            :disabled="uploading"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="uploadMedia"
                            :disabled="!uploadForm.brand_id || uploadForm.files.length === 0 || uploading"
                            class="bg-primary-600 dark:bg-primary-500 text-white px-4 py-2 rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                        >
                            {{ uploading ? `Uploading... ${uploadProgress}%` : 'Upload' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Preview Modal -->
        <div v-if="videoPreview" class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-90" @click="closeVideoPreview"></div>

                <div class="relative max-w-4xl w-full">
                    <!-- Close button -->
                    <button
                        @click="closeVideoPreview"
                        class="absolute -top-12 right-0 text-white hover:text-gray-300 transition-colors"
                    >
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Video player -->
                    <video
                        :src="videoPreview.url"
                        controls
                        autoplay
                        class="w-full rounded-lg shadow-2xl"
                    >
                        Your browser does not support the video tag.
                    </video>

                    <!-- Video info -->
                    <div class="mt-4 flex items-center justify-between">
                        <div class="text-white">
                            <p class="font-medium">{{ videoPreview.original_filename }}</p>
                            <p class="text-sm text-gray-400">
                                {{ formatDuration(videoPreview.duration) }}
                                <span v-if="videoPreview.width && videoPreview.height">
                                    &bull; {{ videoPreview.width }}x{{ videoPreview.height }}
                                </span>
                                <span v-if="videoPreview.size">
                                    &bull; {{ formatFileSize(videoPreview.size) }}
                                </span>
                            </p>
                        </div>
                        <button
                            @click="deleteMedia(videoPreview); closeVideoPreview()"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md font-medium transition-colors"
                        >
                            Delete Video
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Preview Modal -->
        <div v-if="imagePreview" class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-90" @click="closeImagePreview"></div>

                <div class="relative max-w-4xl w-full">
                    <!-- Close button -->
                    <button
                        @click="closeImagePreview"
                        class="absolute -top-12 right-0 text-white hover:text-gray-300 transition-colors"
                    >
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Image -->
                    <img
                        :src="imagePreview.url"
                        :alt="imagePreview.original_filename"
                        class="w-full rounded-lg shadow-2xl"
                    />

                    <!-- Image info -->
                    <div class="mt-4 flex items-center justify-between">
                        <div class="text-white">
                            <p class="font-medium">{{ imagePreview.original_filename }}</p>
                            <p class="text-sm text-gray-400">
                                <span v-if="imagePreview.width && imagePreview.height">
                                    {{ imagePreview.width }}x{{ imagePreview.height }}
                                </span>
                                <span v-if="imagePreview.size">
                                    <span v-if="imagePreview.width && imagePreview.height">&bull;</span>
                                    {{ formatFileSize(imagePreview.size) }}
                                </span>
                            </p>
                        </div>
                        <button
                            @click="deleteMedia(imagePreview); closeImagePreview()"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md font-medium transition-colors"
                        >
                            Delete Image
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
