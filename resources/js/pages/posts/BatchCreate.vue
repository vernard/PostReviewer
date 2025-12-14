<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import html2canvas from 'html2canvas-pro';
import AppLayout from '@/layouts/AppLayout.vue';
import { collectionApi, mediaApi } from '@/services/api';
import { useBrandStore } from '@/stores/brand';
import {
    InstagramFeedPreview,
    FacebookFeedPreview,
    InstagramStoryPreview,
    InstagramReelPreview,
    FacebookStoryPreview,
    FacebookReelPreview
} from '@/components/mockups';

const router = useRouter();
const route = useRoute();
const brandStore = useBrandStore();

const brandMedia = ref([]);
const loading = ref(false);
const loadingMedia = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const error = ref('');

// Mockup export
const mockupRef = ref(null);
const exporting = ref(false);

// Drag and drop state
const isDragging = ref(false);

// Import from spreadsheet
const showImportModal = ref(false);
const importStep = ref(1); // 1 = paste, 2 = preview
const importText = ref('');
const parsedImportData = ref([]);

// Bulk paste for titles/captions
const showBulkPasteModal = ref(false);
const bulkPasteMode = ref('captions'); // 'titles' or 'captions'
const bulkPasteText = ref('');

// Use the active brand from the global store
const brand = computed(() => brandStore.activeBrand);

// Collection name
const collectionName = ref('');

// Posts being created in this batch
const posts = ref([]);
const selectedPostIndex = ref(0);

// View mode: 'table' or 'card'
const viewMode = ref('table');

// Platform editor dropdown (for table view)
const editingPlatformsIndex = ref(null);

// Media library modal
const showMediaLibrary = ref(false);

// Default platforms for new posts (pre-select FB + IG Feed)
const defaultPlatforms = ref(['facebook_feed', 'instagram_feed']);

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

const previewPlatform = ref('');

// Update preview platform when selected post or its platforms change
watch(
    () => selectedPost.value?.platforms,
    (platforms) => {
        if (platforms && platforms.length > 0) {
            // Keep current platform if valid for this post, otherwise use first
            if (!previewPlatform.value || !platforms.includes(previewPlatform.value)) {
                previewPlatform.value = platforms[0];
            }
        } else {
            previewPlatform.value = '';
        }
    },
    { immediate: true, deep: true }
);

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

// Direct upload handlers
const handleDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (e) => {
    e.preventDefault();
    isDragging.value = false;
};

const handleDrop = async (e) => {
    e.preventDefault();
    isDragging.value = false;
    const files = Array.from(e.dataTransfer.files);
    await uploadFiles(files);
};

const handleFileSelect = async (e) => {
    const files = Array.from(e.target.files);
    await uploadFiles(files);
    e.target.value = ''; // Reset input
};

const uploadFiles = async (files) => {
    if (!brandStore.activeBrandId || files.length === 0) return;

    const imageFiles = files.filter(f => f.type.startsWith('image/') || f.type.startsWith('video/'));
    if (imageFiles.length === 0) {
        error.value = 'Please select image or video files';
        return;
    }

    try {
        uploading.value = true;
        uploadProgress.value = 0;
        error.value = '';

        for (let i = 0; i < imageFiles.length; i++) {
            const file = imageFiles[i];
            const response = await mediaApi.upload(brandStore.activeBrandId, file);
            const newMedia = response.data.media || response.data;

            // Add to brand media list
            brandMedia.value.unshift(newMedia);

            // Auto-add as post
            addPostFromMedia(newMedia);

            uploadProgress.value = Math.round(((i + 1) / imageFiles.length) * 100);
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to upload files';
        console.error('Upload error:', err);
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
    }
};

// Import from spreadsheet functions
const parseSpreadsheetData = () => {
    if (!importText.value.trim()) return;

    const lines = importText.value.trim().split('\n');
    parsedImportData.value = lines.map(line => {
        const parts = line.split('\t');
        const filename = parts[0]?.trim() || '';
        const title = parts[1]?.trim() || '';
        const caption = parts[2]?.trim() || '';
        const platformsStr = parts[3]?.trim() || '';

        // Parse platforms (comma-separated) or use defaults
        let platforms = [];
        if (platformsStr) {
            platforms = platformsStr.split(',').map(p => p.trim()).filter(Boolean);
        }

        // Find matching post by filename
        const matchedPost = findMatchingPost(filename);

        return {
            filename,
            title,
            caption,
            platforms,
            matchedPost,
            matchedFilename: matchedPost?.media?.original_filename || matchedPost?.media?.filename || null
        };
    });

    importStep.value = 2;
};

const findMatchingPost = (filename) => {
    if (!filename) return null;

    // Normalize: lowercase, remove extension
    const normalized = filename.toLowerCase().replace(/\.[^.]+$/, '');

    return posts.value.find(post => {
        const mediaName = (post.media.original_filename || post.media.filename || '')
            .toLowerCase()
            .replace(/\.[^.]+$/, '');
        return mediaName === normalized ||
            mediaName.includes(normalized) ||
            normalized.includes(mediaName);
    });
};

const applyImportData = () => {
    parsedImportData.value.forEach(row => {
        if (row.matchedPost) {
            row.matchedPost.title = row.title;
            row.matchedPost.caption = row.caption;
            // Use row platforms if specified, otherwise keep existing
            if (row.platforms.length > 0) {
                row.matchedPost.platforms = row.platforms;
            }
        }
    });

    // Reset modal
    showImportModal.value = false;
    importStep.value = 1;
    importText.value = '';
    parsedImportData.value = [];
};

const resetImportModal = () => {
    showImportModal.value = false;
    importStep.value = 1;
    importText.value = '';
    parsedImportData.value = [];
};

// Bulk paste functions
const openBulkPaste = (mode) => {
    bulkPasteMode.value = mode;
    bulkPasteText.value = '';
    showBulkPasteModal.value = true;
};

const applyBulkPaste = () => {
    if (!bulkPasteText.value.trim()) return;

    // Titles use newlines, captions use --- delimiter
    const values = bulkPasteMode.value === 'titles'
        ? bulkPasteText.value.split('\n').map(v => v.trim()).filter(v => v)
        : bulkPasteText.value.split('---').map(v => v.trim()).filter(v => v);

    const field = bulkPasteMode.value === 'titles' ? 'title' : 'caption';

    // Apply to posts in order
    values.forEach((value, index) => {
        if (posts.value[index]) {
            posts.value[index][field] = value;
        }
    });

    showBulkPasteModal.value = false;
    bulkPasteText.value = '';
};

const bulkPasteCount = computed(() => {
    if (!bulkPasteText.value.trim()) return 0;
    if (bulkPasteMode.value === 'titles') {
        return bulkPasteText.value.split('\n').filter(v => v.trim()).length;
    }
    return bulkPasteText.value.split('---').filter(v => v.trim()).length;
});

// Auto-apply default platforms when changed
watch(defaultPlatforms, (newPlatforms) => {
    posts.value.forEach(post => {
        post.platforms = [...newPlatforms];
    });
}, { deep: true });

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

// Convert an image URL to a data URL to avoid CORS issues with html2canvas
const imageToDataUrl = async (url) => {
    if (!url) return null;
    try {
        // Check if this is a media URL that we can proxy through the API
        const mediaMatch = url.match(/\/storage\/brands\/\d+\/media\/([^/]+)/);
        if (mediaMatch && selectedPost.value?.media) {
            // Find the media by matching the filename
            const filename = mediaMatch[1];
            if (selectedPost.value.media.url.includes(filename)) {
                // Use the stream API endpoint to fetch via the authenticated API
                const response = await axios.get(`/api/media/${selectedPost.value.media.id}/stream`, {
                    responseType: 'blob'
                });
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.readAsDataURL(response.data);
                });
            }
        }
        // Fallback: try direct fetch
        const response = await fetch(url);
        const blob = await response.blob();
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.readAsDataURL(blob);
        });
    } catch (err) {
        console.warn('Failed to convert image to data URL:', url, err);
        return null;
    }
};

// Create a safe filename from text
const slugify = (text) => {
    return text
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim()
        .substring(0, 50);
};

const exportAsJpeg = async () => {
    if (!mockupRef.value || exporting.value) return;

    try {
        exporting.value = true;

        // Wait for Vue to update DOM and images to load
        await nextTick();
        await new Promise(resolve => setTimeout(resolve, 100));

        // Convert all images in the mockup to data URLs to avoid CORS issues
        const images = mockupRef.value.querySelectorAll('img');
        const originalSrcs = new Map();

        await Promise.all(Array.from(images).map(async (img) => {
            const originalSrc = img.src;
            if (originalSrc && !originalSrc.startsWith('data:')) {
                originalSrcs.set(img, originalSrc);
                const dataUrl = await imageToDataUrl(originalSrc);
                if (dataUrl) {
                    img.src = dataUrl;
                }
            }
        }));

        // Wait for images to update in the DOM
        await new Promise(resolve => setTimeout(resolve, 50));

        const canvas = await html2canvas(mockupRef.value, {
            useCORS: true,
            allowTaint: true,
            backgroundColor: null,
            scale: 2,
            logging: false,
        });

        // Restore original image sources
        originalSrcs.forEach((src, img) => {
            img.src = src;
        });

        const link = document.createElement('a');
        const platformName = previewPlatform.value.replace(/_/g, '-');
        const postTitle = selectedPost.value?.title ? slugify(selectedPost.value.title) : 'draft';
        const brandName = brand.value?.name ? slugify(brand.value.name) : 'brand';
        link.download = `${brandName}-${postTitle}-${platformName}.jpg`;
        link.href = canvas.toDataURL('image/jpeg', 0.95);
        link.click();
    } catch (err) {
        console.error('Export failed:', err);
        error.value = 'Failed to export mockup';
    } finally {
        exporting.value = false;
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
                                    class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold mr-2"
                                    :style="{
                                        backgroundColor: platform.id.startsWith('facebook') ? '#dbeafe' : '#ec4899',
                                        color: platform.id.startsWith('facebook') ? '#2563eb' : 'white'
                                    }"
                                >
                                    {{ platform.icon }}
                                </span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ platform.name }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Step 2: Select Media -->
                    <div
                        class="bg-white dark:bg-gray-800 shadow rounded-lg p-6"
                        @dragover="handleDragOver"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Select Media</h2>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    Click to add/remove from batch
                                </span>
                                <label class="cursor-pointer px-3 py-1.5 text-sm bg-primary-600 text-white rounded-md hover:bg-primary-700">
                                    <input
                                        type="file"
                                        multiple
                                        accept="image/*,video/*"
                                        class="sr-only"
                                        @change="handleFileSelect"
                                    />
                                    Upload Files
                                </label>
                            </div>
                        </div>

                        <!-- Drag overlay -->
                        <div
                            v-if="isDragging"
                            class="border-2 border-dashed border-primary-400 bg-primary-50 dark:bg-primary-900/20 rounded-lg p-12 text-center mb-4"
                        >
                            <svg class="mx-auto h-12 w-12 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" />
                            </svg>
                            <p class="mt-2 text-sm text-primary-600 dark:text-primary-400">Drop files here to upload</p>
                        </div>

                        <!-- Upload progress -->
                        <div v-if="uploading" class="mb-4">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div
                                        class="bg-primary-600 h-2 rounded-full transition-all"
                                        :style="{ width: `${uploadProgress}%` }"
                                    ></div>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ uploadProgress }}%</span>
                            </div>
                        </div>

                        <div v-if="loadingMedia" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Loading media...
                        </div>
                        <div v-else-if="brandMedia.length === 0 && !isDragging" class="text-center py-8">
                            <label class="cursor-pointer block border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 hover:border-primary-400 dark:hover:border-primary-500 transition-colors">
                                <input
                                    type="file"
                                    multiple
                                    accept="image/*,video/*"
                                    class="sr-only"
                                    @change="handleFileSelect"
                                />
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Drag & drop images or videos here, or click to browse
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                    PNG, JPG, GIF, MP4, MOV up to 50MB
                                </p>
                            </label>
                        </div>
                        <div v-else-if="!isDragging" class="grid grid-cols-6 gap-3">
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
                        <div :class="['bg-white dark:bg-gray-800 shadow rounded-lg p-6', editingPlatformsIndex !== null ? 'overflow-visible' : '']">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Edit Posts</h2>
                                    <button
                                        @click="openBulkPaste('titles')"
                                        class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300"
                                        title="Paste multiple titles at once"
                                    >
                                        Bulk Paste Titles
                                    </button>
                                    <span class="text-gray-300 dark:text-gray-600">|</span>
                                    <button
                                        @click="openBulkPaste('captions')"
                                        class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300"
                                        title="Paste multiple captions at once"
                                    >
                                        Bulk Paste Captions
                                    </button>
                                </div>
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
                            <div v-else :class="editingPlatformsIndex !== null ? 'overflow-visible' : 'overflow-x-auto'">
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
                                            <td class="py-2 px-2 relative" @click.stop>
                                                <!-- Display mode -->
                                                <div
                                                    v-if="editingPlatformsIndex !== index"
                                                    @click="editingPlatformsIndex = index"
                                                    class="flex flex-wrap gap-1 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1 -m-1"
                                                >
                                                    <span
                                                        v-for="platformId in post.platforms"
                                                        :key="platformId"
                                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium"
                                                        :style="{
                                                            backgroundColor: platformId.startsWith('facebook') ? '#dbeafe' : '#fce7f3',
                                                            color: platformId.startsWith('facebook') ? '#2563eb' : '#db2777'
                                                        }"
                                                    >
                                                        {{ platformId.includes('feed') ? 'Feed' : platformId.includes('story') ? 'Story' : 'Reel' }}
                                                    </span>
                                                    <span v-if="post.platforms.length === 0" class="text-xs text-red-500">None</span>
                                                </div>
                                                <!-- Edit mode dropdown -->
                                                <div
                                                    v-else
                                                    class="absolute z-10 top-0 left-0 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg p-2 min-w-[180px]"
                                                >
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Platforms</span>
                                                        <button
                                                            @click="editingPlatformsIndex = null"
                                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <label
                                                        v-for="platform in platforms"
                                                        :key="platform.id"
                                                        class="flex items-center gap-2 py-1 px-1 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer"
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            :checked="post.platforms.includes(platform.id)"
                                                            @change="togglePostPlatform(index, platform.id)"
                                                            class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                                        />
                                                        <span
                                                            class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold"
                                                            :style="{
                                                                backgroundColor: platform.id.startsWith('facebook') ? '#dbeafe' : '#ec4899',
                                                                color: platform.id.startsWith('facebook') ? '#2563eb' : 'white'
                                                            }"
                                                        >
                                                            {{ platform.icon }}
                                                        </span>
                                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ platform.name }}</span>
                                                    </label>
                                                </div>
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
                                                    class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold mr-1"
                                                    :style="{
                                                        backgroundColor: platform.id.startsWith('facebook') ? '#dbeafe' : '#ec4899',
                                                        color: platform.id.startsWith('facebook') ? '#2563eb' : 'white'
                                                    }"
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
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Preview</h2>
                                <button
                                    v-if="selectedPost && selectedPost.platforms.length > 0"
                                    @click="exportAsJpeg"
                                    :disabled="exporting"
                                    class="flex items-center gap-1 px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    {{ exporting ? 'Exporting...' : 'Export JPEG' }}
                                </button>
                            </div>

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
                                        @click="previewPlatform = platformId"
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

                                <!-- Mockup container for export -->
                                <div ref="mockupRef" class="inline-block">
                                    <!-- Instagram Feed Preview -->
                                    <InstagramFeedPreview
                                        v-if="previewPlatform === 'instagram_feed'"
                                        :brand-name="brand?.instagram_handle || brand?.name || 'Brand Name'"
                                        :brand-logo-url="brand?.logo_flat_url"
                                        :media-url="selectedPost.media.url"
                                        :caption="selectedPost.caption"
                                    />

                                    <!-- Facebook Feed Preview -->
                                    <FacebookFeedPreview
                                        v-else-if="previewPlatform === 'facebook_feed'"
                                        :brand-name="brand?.facebook_page_name || brand?.name || 'Brand Name'"
                                        :brand-logo-url="brand?.logo_flat_url"
                                        :media-url="selectedPost.media.url"
                                        :caption="selectedPost.caption"
                                    />

                                    <!-- Instagram Story Preview -->
                                    <InstagramStoryPreview
                                        v-else-if="previewPlatform === 'instagram_story'"
                                        :brand-name="brand?.instagram_handle || brand?.name || 'Brand Name'"
                                        :brand-logo-url="brand?.logo_flat_url"
                                        :media-url="selectedPost.media.url"
                                    />

                                    <!-- Instagram Reel Preview -->
                                    <InstagramReelPreview
                                        v-else-if="previewPlatform === 'instagram_reel'"
                                        :brand-name="brand?.instagram_handle || brand?.name || 'Brand Name'"
                                        :brand-logo-url="brand?.logo_flat_url"
                                        :media-url="selectedPost.media.url"
                                        :caption="selectedPost.caption"
                                    />

                                    <!-- Facebook Story Preview -->
                                    <FacebookStoryPreview
                                        v-else-if="previewPlatform === 'facebook_story'"
                                        :brand-name="brand?.facebook_page_name || brand?.name || 'Brand Name'"
                                        :brand-logo-url="brand?.logo_flat_url"
                                        :media-url="selectedPost.media.url"
                                    />

                                    <!-- Facebook Reel Preview -->
                                    <FacebookReelPreview
                                        v-else-if="previewPlatform === 'facebook_reel'"
                                        :brand-name="brand?.facebook_page_name || brand?.name || 'Brand Name'"
                                        :brand-logo-url="brand?.logo_flat_url"
                                        :media-url="selectedPost.media.url"
                                        :caption="selectedPost.caption"
                                    />
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

        <!-- Import from Spreadsheet Modal -->
        <div v-if="showImportModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" @click="resetImportModal"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Import from Spreadsheet
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                                Step {{ importStep }} of 2
                            </span>
                        </h3>
                        <button @click="resetImportModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Step 1: Paste Data -->
                    <div v-if="importStep === 1">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Copy rows from your spreadsheet and paste below. Expected columns (tab-separated):
                        </p>
                        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <code class="text-xs text-gray-700 dark:text-gray-300">
                                filename | title | caption | platforms (optional)
                            </code>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Platforms: comma-separated like "facebook_feed,instagram_feed"
                            </p>
                        </div>
                        <textarea
                            v-model="importText"
                            rows="10"
                            placeholder="IMG_001.jpg&#9;December Promo&#9;Check out our holiday sale!&#9;facebook_feed,instagram_feed&#10;IMG_002.jpg&#9;New Product&#9;Introducing our latest...&#10;..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                        ></textarea>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ posts.length }} post(s) waiting to be matched.
                        </p>
                        <div class="mt-4 flex justify-end gap-3">
                            <button
                                @click="resetImportModal"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                @click="parseSpreadsheetData"
                                :disabled="!importText.trim()"
                                class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                            >
                                Preview Matches
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Preview Matches -->
                    <div v-else-if="importStep === 2">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Review matched data before applying. Only matched rows will be updated.
                        </p>
                        <div class="max-h-80 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                                    <tr>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 dark:text-gray-400">Filename</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 dark:text-gray-400">Title</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 dark:text-gray-400">Caption</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(row, index) in parsedImportData"
                                        :key="index"
                                        :class="row.matchedPost ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'"
                                    >
                                        <td class="py-2 px-3">
                                            <span v-if="row.matchedPost" class="text-green-600 dark:text-green-400">✓</span>
                                            <span v-else class="text-red-500 dark:text-red-400">✗</span>
                                        </td>
                                        <td class="py-2 px-3">
                                            <div class="text-gray-900 dark:text-white">{{ row.filename }}</div>
                                            <div v-if="row.matchedFilename" class="text-xs text-gray-500 dark:text-gray-400">
                                                → {{ row.matchedFilename }}
                                            </div>
                                        </td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300 max-w-[150px] truncate">
                                            {{ row.title || '-' }}
                                        </td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300 max-w-[200px] truncate">
                                            {{ row.caption || '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ parsedImportData.filter(r => r.matchedPost).length }} of {{ parsedImportData.length }} rows matched.
                        </p>
                        <div class="mt-4 flex justify-end gap-3">
                            <button
                                @click="importStep = 1"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                Back
                            </button>
                            <button
                                @click="applyImportData"
                                :disabled="!parsedImportData.some(r => r.matchedPost)"
                                class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                            >
                                Apply {{ parsedImportData.filter(r => r.matchedPost).length }} Matches
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Paste Modal -->
        <div v-if="showBulkPasteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" @click="showBulkPasteModal = false"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Bulk Paste {{ bulkPasteMode === 'titles' ? 'Titles' : 'Captions' }}
                        </h3>
                        <button @click="showBulkPasteModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        <template v-if="bulkPasteMode === 'titles'">
                            Paste one title per line. They will be applied to posts in order.
                        </template>
                        <template v-else>
                            Paste captions separated by <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">---</code> on its own line.
                            They will be applied to posts in order.
                        </template>
                    </p>

                    <textarea
                        v-model="bulkPasteText"
                        rows="10"
                        :placeholder="bulkPasteMode === 'titles'
                            ? 'December Promo\nNew Product Launch\nHoliday Sale\nBehind the Scenes'
                            : 'Check out our holiday sale! 🎄\n\nShop now at example.com\n---\nIntroducing our latest product...\n\n#NewProduct\n---\nDon\'t miss out!'"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                    ></textarea>

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ bulkPasteCount }} {{ bulkPasteMode === 'titles' ? 'title(s)' : 'caption(s)' }} → {{ posts.length }} post(s)
                    </p>

                    <div class="mt-4 flex justify-end gap-3">
                        <button
                            @click="showBulkPasteModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Cancel
                        </button>
                        <button
                            @click="applyBulkPaste"
                            :disabled="!bulkPasteText.trim()"
                            class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                        >
                            Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
