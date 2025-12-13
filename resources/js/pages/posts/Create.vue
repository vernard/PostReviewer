<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { postApi, mediaApi, brandApi } from '@/services/api';
import { useBrandStore } from '@/stores/brand';
import axios from 'axios';
import html2canvas from 'html2canvas-pro';
import { validatePost, getCaptionStatus, PLATFORM_LIMITS } from '@/utils/platformValidation';
import { extractEdgeColor } from '@/composables/useEdgeColor';

const router = useRouter();
const route = useRoute();
const brandStore = useBrandStore();

const brandMedia = ref([]);
const loading = ref(false);
const loadingMedia = ref(false);
const loadingMoreMedia = ref(false);
const mediaCurrentPage = ref(1);
const mediaHasMorePages = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const error = ref('');
const mediaScrollContainer = ref(null);

// Use the active brand from the global store
const brand = computed(() => brandStore.activeBrand);

const form = ref({
    brand_id: brandStore.activeBrandId,
    title: '',
    caption: '',
    platforms: ['facebook_feed', 'instagram_feed'],
});

const selectedMedia = ref([]);
const showMediaLibrary = ref(false);
const showBrandDropdown = ref(false);
const previewPlatform = ref('');
const videoPreview = ref(null); // Media item being previewed
const isPlayingVideo = ref(false); // Whether video is playing inline in mockup
const mediaEdgeColor = ref('rgb(0, 0, 0)'); // Dynamic background for letterboxing
const mockupRef = ref(null); // Reference to mockup container for export
const exporting = ref(false); // Export in progress state

// Submit for approval modal state
const showSubmitModal = ref(false);
const reviewerEmails = ref('');
const saveReviewersAsDefault = ref(false);
const loadingDefaults = ref(false);

const platforms = [
    { id: 'facebook_feed', name: 'Facebook Feed', icon: 'FB' },
    { id: 'facebook_story', name: 'Facebook Story', icon: 'FB' },
    { id: 'facebook_reel', name: 'Facebook Reel', icon: 'FB' },
    { id: 'instagram_feed', name: 'Instagram Feed', icon: 'IG' },
    { id: 'instagram_story', name: 'Instagram Story', icon: 'IG' },
    { id: 'instagram_reel', name: 'Instagram Reel', icon: 'IG' },
];

const selectedBrand = computed(() => brand.value);

// Display names for mockups - use platform-specific names or fall back to brand name
const instagramDisplayName = computed(() =>
    selectedBrand.value?.instagram_handle || selectedBrand.value?.name || 'Brand'
);
const facebookDisplayName = computed(() =>
    selectedBrand.value?.facebook_page_name || selectedBrand.value?.name || 'Brand'
);

// Platform validation
const validation = computed(() => {
    const media = selectedMedia.value.length > 0 ? selectedMedia.value[0] : null;
    return validatePost(form.value.platforms, media, form.value.caption);
});

const captionStatus = computed(() => {
    return getCaptionStatus(form.value.platforms, form.value.caption?.length || 0);
});

const canSubmit = computed(() => {
    return form.value.brand_id &&
           form.value.title.trim() &&
           form.value.platforms.length > 0 &&
           !validation.value.hasErrors;
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

// Reset video playback when switching preview platforms
watch(previewPlatform, () => {
    isPlayingVideo.value = false;
});

// Reset video playback and extract edge color when media changes
watch(selectedMedia, async () => {
    isPlayingVideo.value = false;

    // Extract edge color for letterboxing
    const media = selectedMedia.value[0];
    if (media) {
        // Use thumbnail for videos, URL for images
        const colorUrl = media.thumbnail_url || media.url;
        if (colorUrl) {
            mediaEdgeColor.value = await extractEdgeColor(colorUrl);
        } else {
            mediaEdgeColor.value = 'rgb(0, 0, 0)';
        }
    } else {
        mediaEdgeColor.value = 'rgb(0, 0, 0)';
    }
}, { deep: true });

// Echo channel for real-time updates
let echoChannel = null;

// Handle media processed event from WebSocket
const handleMediaProcessed = (event) => {
    const { action, mediaId, media } = event;

    if (action === 'ready' && media) {
        // Update in brandMedia array
        const brandIndex = brandMedia.value.findIndex(m => m.id === mediaId);
        if (brandIndex !== -1) {
            brandMedia.value[brandIndex] = media;
        }

        // Update in selectedMedia array
        const selectedIndex = selectedMedia.value.findIndex(m => m.id === mediaId);
        if (selectedIndex !== -1) {
            selectedMedia.value[selectedIndex] = media;
        }
    } else if (action === 'failed') {
        // Update status to failed
        const brandIndex = brandMedia.value.findIndex(m => m.id === mediaId);
        if (brandIndex !== -1) {
            brandMedia.value[brandIndex].status = 'failed';
        }

        const selectedIndex = selectedMedia.value.findIndex(m => m.id === mediaId);
        if (selectedIndex !== -1) {
            selectedMedia.value[selectedIndex].status = 'failed';
        }
    }
};

// Subscribe to brand channel for media updates
const subscribeToMediaUpdates = () => {
    if (!window.Echo || !brandStore.activeBrandId) return;

    unsubscribeFromMediaUpdates();

    try {
        echoChannel = window.Echo.private(`brand.${brandStore.activeBrandId}`);
        echoChannel.listen('.media.processed', handleMediaProcessed);
    } catch (e) {
        console.warn('Failed to subscribe to media channel:', e);
    }
};

const unsubscribeFromMediaUpdates = () => {
    if (echoChannel && window.Echo) {
        try {
            window.Echo.leave(`brand.${brandStore.activeBrandId}`);
        } catch (e) {
            // Ignore errors when leaving channel
        }
        echoChannel = null;
    }
};

// Check brand and fetch media on mount
const initPage = () => {
    if (!brandStore.activeBrand) {
        // No active brand - redirect to brands page
        router.push('/brands');
        return;
    }

    // Update form with current brand
    form.value.brand_id = brandStore.activeBrandId;

    // Fetch media for this brand
    fetchBrandMedia();
};

const fetchBrandMedia = async (page = 1) => {
    if (!brandStore.activeBrandId) {
        brandMedia.value = [];
        return;
    }

    try {
        if (page === 1) {
            loadingMedia.value = true;
            brandMedia.value = [];
        } else {
            loadingMoreMedia.value = true;
        }

        const response = await mediaApi.list({
            brand_id: brandStore.activeBrandId,
            page: page,
            per_page: 24
        });

        const data = response.data.data || response.data || [];
        const lastPage = response.data.last_page || 1;

        if (page === 1) {
            brandMedia.value = data;
        } else {
            brandMedia.value = [...brandMedia.value, ...data];
        }

        mediaCurrentPage.value = page;
        mediaHasMorePages.value = page < lastPage;
    } catch (err) {
        console.error('Failed to fetch media:', err);
    } finally {
        loadingMedia.value = false;
        loadingMoreMedia.value = false;
    }
};

const loadMoreMedia = () => {
    if (!loadingMoreMedia.value && mediaHasMorePages.value) {
        fetchBrandMedia(mediaCurrentPage.value + 1);
    }
};

const handleMediaScroll = (event) => {
    const container = event.target;
    const scrollBottom = container.scrollHeight - container.scrollTop - container.clientHeight;

    // Load more when within 200px of bottom
    if (scrollBottom < 200 && !loadingMoreMedia.value && mediaHasMorePages.value) {
        loadMoreMedia();
    }
};

const toggleMedia = (media) => {
    const index = selectedMedia.value.findIndex(m => m.id === media.id);
    if (index === -1) {
        // Single media only - replace instead of append
        selectedMedia.value = [media];
    } else {
        selectedMedia.value.splice(index, 1);
    }
};

const isMediaSelected = (media) => {
    return selectedMedia.value.some(m => m.id === media.id);
};

const isVideo = (media) => {
    return media.type === 'video';
};

const playVideo = (media, event) => {
    event.stopPropagation(); // Prevent selecting the media
    videoPreview.value = media;
};

const closeVideoPreview = () => {
    videoPreview.value = null;
};

const formatDuration = (seconds) => {
    if (!seconds) return '';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const removeMedia = (index) => {
    selectedMedia.value.splice(index, 1);
};

const handleFileDrop = (event) => {
    event.preventDefault();
    const files = Array.from(event.dataTransfer.files).filter(
        file => file.type.startsWith('image/') || file.type.startsWith('video/')
    );
    if (files.length > 0) {
        // Single media only - take first file
        uploadFile(files[0]);
    }
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        uploadFile(file);
    }
    // Reset input so same file can be selected again
    event.target.value = '';
};

// File size limits (in bytes)
const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB
const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100MB
const SUPPORTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const SUPPORTED_VIDEO_TYPES = ['video/mp4', 'video/quicktime', 'video/x-msvideo'];

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const uploadFile = async (file) => {
    if (!brandStore.activeBrandId) {
        error.value = 'Please select a brand first';
        return;
    }

    // Validate file type
    const isImage = SUPPORTED_IMAGE_TYPES.includes(file.type);
    const isVideo = SUPPORTED_VIDEO_TYPES.includes(file.type);

    if (!isImage && !isVideo) {
        error.value = 'Unsupported file type. Please upload JPG, PNG, WebP images or MP4, MOV, AVI videos.';
        return;
    }

    // Validate file size
    const maxSize = isImage ? MAX_IMAGE_SIZE : MAX_VIDEO_SIZE;
    if (file.size > maxSize) {
        const maxSizeMB = isImage ? '10MB' : '100MB';
        error.value = `File too large (${formatFileSize(file.size)}). Maximum size for ${isImage ? 'images' : 'videos'} is ${maxSizeMB}.`;
        return;
    }

    uploading.value = true;
    uploadProgress.value = 0;
    error.value = '';

    try {
        const response = await mediaApi.upload(
            brandStore.activeBrandId,
            file,
            (progress) => {
                uploadProgress.value = progress;
            }
        );
        // Auto-select the uploaded media (replaces any existing)
        const uploadedMedia = response.data.media || response.data;
        selectedMedia.value = [uploadedMedia];
        // Add to brand media list
        brandMedia.value.unshift(uploadedMedia);
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to upload file';
        console.error('Upload failed:', err);
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
    }
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

const openSubmitModal = async () => {
    if (!canSubmit.value) return;

    reviewerEmails.value = '';
    saveReviewersAsDefault.value = false;

    // Fetch default reviewers
    if (brandStore.activeBrandId) {
        try {
            loadingDefaults.value = true;
            const response = await axios.get(`/api/brands/${brandStore.activeBrandId}/default-reviewers`);
            const defaults = response.data.default_reviewers || [];
            if (defaults.length > 0) {
                reviewerEmails.value = defaults.join(', ');
            }
        } catch (err) {
            // Silently fail - defaults are optional
        } finally {
            loadingDefaults.value = false;
        }
    }

    showSubmitModal.value = true;
};

const parseEmails = (input) => {
    return input
        .split(/[,\n]+/)
        .map(e => e.trim().toLowerCase())
        .filter(e => e && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e));
};

const submitForApproval = async () => {
    if (!canSubmit.value) return;

    const emails = parseEmails(reviewerEmails.value);
    if (emails.length > 10) {
        error.value = 'Maximum 10 reviewer emails allowed.';
        return;
    }

    try {
        loading.value = true;
        error.value = '';
        showSubmitModal.value = false;

        // First create the post
        const createResponse = await postApi.create({
            ...form.value,
            media_ids: selectedMedia.value.map(m => m.id),
        });

        // Then submit for approval with reviewer emails
        await axios.post(`/api/posts/${createResponse.data.post.id}/submit`, {
            reviewer_emails: emails.length > 0 ? emails : undefined,
            save_reviewers_as_default: saveReviewersAsDefault.value && emails.length > 0,
        });

        router.push(`/posts/${createResponse.data.post.id}`);
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create post';
    } finally {
        loading.value = false;
    }
};

// Watch for brand changes in the store
watch(() => brandStore.activeBrandId, (newVal, oldVal) => {
    if (newVal && newVal !== oldVal) {
        form.value.brand_id = newVal;
        selectedMedia.value = []; // Clear selected media when brand changes
        fetchBrandMedia();
    }
});

const exportAsJpeg = async () => {
    if (!mockupRef.value || exporting.value) return;

    try {
        exporting.value = true;
        // Wait a moment for any animations to settle
        await new Promise(resolve => setTimeout(resolve, 100));

        const canvas = await html2canvas(mockupRef.value, {
            backgroundColor: '#ffffff',
            scale: 2,
            useCORS: true,
            allowTaint: true,
            logging: false,
        });

        const link = document.createElement('a');
        const platformName = previewPlatform.value.replace(/_/g, '-');
        const brandName = brand.value?.name || 'post';
        link.download = `${brandName}-${platformName}-${Date.now()}.jpg`;
        link.href = canvas.toDataURL('image/jpeg', 0.95);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (err) {
        console.error('Export failed:', err);
        alert('Failed to export mockup: ' + err.message);
    } finally {
        exporting.value = false;
    }
};

onMounted(() => {
    initPage();
    subscribeToMediaUpdates();
});

onUnmounted(() => {
    unsubscribeFromMediaUpdates();
});
</script>

<template>
    <AppLayout>
        <div class="py-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-2 mb-4">
                    <div class="flex items-center gap-2 sm:gap-4">
                        <button @click="router.back()" class="text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <h1 class="text-lg sm:text-2xl font-semibold text-gray-900 dark:text-white">Create Post</h1>
                    </div>
                    <!-- Actions -->
                    <div class="flex gap-2">
                        <button
                            @click="saveDraft"
                            :disabled="!canSubmit || loading"
                            class="px-2 sm:px-3 py-1.5 text-xs sm:text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                        >
                            <span class="hidden sm:inline">{{ loading ? 'Saving...' : 'Save Draft' }}</span>
                            <span class="sm:hidden">{{ loading ? '...' : 'Draft' }}</span>
                        </button>
                        <button
                            @click="openSubmitModal"
                            :disabled="!canSubmit || loading"
                            class="px-2 sm:px-3 py-1.5 text-xs sm:text-sm bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                        >
                            <span class="hidden sm:inline">{{ loading ? 'Submitting...' : 'Submit for Approval' }}</span>
                            <span class="sm:hidden">{{ loading ? '...' : 'Submit' }}</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div v-if="error" class="mb-6 bg-red-50 border border-red-200 text-red-600 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400 px-4 py-3 rounded">
                    {{ error }}
                </div>

                <div v-if="brand" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Form -->
                    <div class="space-y-6">
                        <!-- Media Section -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Media</h2>
                                <button
                                    @click="showMediaLibrary = true"
                                    class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300"
                                >
                                    Pick from Library
                                </button>
                            </div>

                            <!-- Upload/Drop Zone (shown when no media selected) -->
                            <div
                                v-if="selectedMedia.length === 0"
                                @drop="handleFileDrop"
                                @dragover.prevent
                                @dragenter.prevent
                                class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-primary-400 dark:hover:border-primary-500 transition-colors"
                            >
                                <div v-if="uploading" class="space-y-3">
                                    <svg class="mx-auto h-12 w-12 text-primary-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="text-gray-600 dark:text-gray-400">Uploading... {{ uploadProgress }}%</p>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-primary-500 h-2 rounded-full transition-all" :style="{ width: uploadProgress + '%' }"></div>
                                    </div>
                                </div>
                                <div v-else>
                                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="mt-2 text-gray-600 dark:text-gray-400">Drag & drop files here</p>
                                    <div class="mt-3 flex items-center justify-center gap-3">
                                        <label class="cursor-pointer px-3 py-1.5 bg-primary-600 dark:bg-primary-500 text-white text-sm rounded-md hover:bg-primary-700 dark:hover:bg-primary-600">
                                            Upload
                                            <input type="file" @change="handleFileUpload" class="hidden" accept="image/*,video/*" />
                                        </label>
                                        <span class="text-gray-400 dark:text-gray-500">or</span>
                                        <button
                                            @click="showMediaLibrary = true"
                                            class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                                        >
                                            Pick from Library
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="grid grid-cols-3 gap-2">
                                <div
                                    v-for="(media, index) in selectedMedia"
                                    :key="media.id"
                                    class="relative aspect-square rounded-lg overflow-hidden group"
                                >
                                    <!-- Processing state -->
                                    <div
                                        v-if="media.status === 'processing'"
                                        class="w-full h-full bg-gray-900 flex flex-col items-center justify-center"
                                    >
                                        <svg class="w-8 h-8 text-gray-400 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/>
                                        </svg>
                                        <span class="text-gray-400 text-xs mt-2">Processing...</span>
                                    </div>
                                    <!-- Ready state -->
                                    <img
                                        v-else
                                        :src="media.thumbnail_url || media.url"
                                        class="w-full h-full object-cover"
                                    />
                                    <!-- Video play button for selected media -->
                                    <div
                                        v-if="isVideo(media) && media.status !== 'processing'"
                                        class="absolute inset-0 flex items-center justify-center"
                                    >
                                        <button
                                            @click.stop="videoPreview = media"
                                            class="w-10 h-10 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors"
                                        >
                                            <svg class="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </button>
                                    </div>
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
                                <!-- Add more button -->
                                <label
                                    class="aspect-square rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex flex-col items-center justify-center cursor-pointer hover:border-primary-400 dark:hover:border-primary-500 transition-colors"
                                >
                                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">Replace</span>
                                    <input type="file" @change="handleFileUpload" class="hidden" accept="image/*,video/*" />
                                </label>
                            </div>
                        </div>

                        <!-- Post Details -->
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Post Details</h2>
                            <div class="space-y-4">
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
                                        :class="[
                                            'mt-1 block w-full border rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500',
                                            captionStatus.status === 'error' ? 'border-red-300 dark:border-red-600' : 'border-gray-300 dark:border-gray-600'
                                        ]"
                                        placeholder="Write your post caption here..."
                                    />
                                    <p
                                        :class="[
                                            'mt-1 text-xs',
                                            captionStatus.status === 'error' ? 'text-red-500 dark:text-red-400 font-medium' :
                                            captionStatus.status === 'warning' ? 'text-amber-500 dark:text-amber-400' :
                                            'text-gray-500 dark:text-gray-400'
                                        ]"
                                    >
                                        {{ form.caption.length.toLocaleString() }}
                                        <template v-if="captionStatus.limit"> / {{ captionStatus.limit.toLocaleString() }}</template>
                                        characters
                                        <template v-if="captionStatus.status === 'error'"> (over limit)</template>
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Platforms *</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label
                                            v-for="platform in platforms"
                                            :key="platform.id"
                                            :class="[
                                                'flex items-center p-2 sm:p-3 border rounded-lg cursor-pointer transition-colors',
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
                                                class="w-7 h-7 sm:w-8 sm:h-8 shrink-0 rounded-full flex items-center justify-center text-xs font-bold mr-2"
                                                :style="{
                                                    backgroundColor: platform.id.startsWith('facebook') ? '#dbeafe' : '#ec4899',
                                                    color: platform.id.startsWith('facebook') ? '#2563eb' : 'white'
                                                }"
                                            >
                                                {{ platform.icon }}
                                            </span>
                                            <span class="text-xs sm:text-sm text-gray-700 dark:text-gray-400">{{ platform.name }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Preview</h2>
                            <button
                                v-if="form.platforms.length > 0 && selectedMedia.length > 0"
                                @click="exportAsJpeg"
                                :disabled="exporting"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-sm bg-green-600 dark:bg-green-500 text-white rounded-md hover:bg-green-700 dark:hover:bg-green-600 disabled:opacity-50"
                            >
                                <svg v-if="exporting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                {{ exporting ? 'Exporting...' : 'Export JPEG' }}
                            </button>
                            <div v-if="form.platforms.length > 0" class="flex gap-2 flex-wrap mt-3">
                                <button
                                    v-for="platform in form.platforms"
                                    :key="platform"
                                    @click="previewPlatform = platform"
                                    :class="[
                                        'px-3 py-1.5 text-xs rounded-full flex items-center gap-1.5 transition-all',
                                        previewPlatform === platform || (!previewPlatform && form.platforms[0] === platform)
                                            ? platform.startsWith('facebook')
                                                ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 ring-2 ring-blue-400'
                                                : 'bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300 ring-2 ring-pink-400'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    <span
                                        :style="{
                                            width: '20px',
                                            height: '20px',
                                            minWidth: '20px',
                                            minHeight: '20px',
                                            borderRadius: '50%',
                                            display: 'inline-flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontSize: '10px',
                                            fontWeight: 'bold',
                                            flexShrink: 0,
                                            backgroundColor: platform.startsWith('facebook') ? '#3b82f6' : '#ec4899',
                                            color: 'white'
                                        }"
                                    >
                                        {{ platform.startsWith('facebook') ? 'FB' : 'IG' }}
                                    </span>
                                    {{ platform.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()) }}
                                </button>
                            </div>
                        </div>

                        <div ref="mockupRef">
                            <div v-if="form.platforms.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-12">
                            Select a platform to see the mockup preview
                        </div>

                        <!-- Instagram Feed Preview -->
                        <div
                            v-else-if="(previewPlatform || form.platforms[0]).includes('instagram_feed')"
                            class="w-full max-w-[280px] mx-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                        >
                            <!-- Header -->
                            <div class="flex items-center p-3">
                                <div v-if="selectedBrand?.logo_flat_url" class="w-8 h-8 rounded-full overflow-hidden">
                                    <img :src="selectedBrand.logo_flat_url" :alt="selectedBrand.name" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                    {{ instagramDisplayName.charAt(0) }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ instagramDisplayName }}</p>
                                </div>
                            </div>
                            <!-- Image/Video -->
                            <div class="aspect-square relative transition-colors duration-300" :style="{ backgroundColor: mediaEdgeColor }">
                                <!-- Processing state -->
                                <div
                                    v-if="selectedMedia[0]?.status === 'processing'"
                                    class="w-full h-full flex flex-col items-center justify-center"
                                >
                                    <svg class="w-12 h-12 text-gray-400 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/>
                                    </svg>
                                    <span class="text-gray-400 text-sm mt-2">Processing video...</span>
                                </div>
                                <!-- Video playing inline -->
                                <video
                                    v-else-if="isPlayingVideo && selectedMedia[0]?.type === 'video'"
                                    :src="selectedMedia[0].url"
                                    class="w-full h-full object-contain"
                                    autoplay
                                    controls
                                    @click.stop
                                    @ended="isPlayingVideo = false"
                                />
                                <!-- Image/thumbnail -->
                                <img
                                    v-else-if="selectedMedia[0]"
                                    :src="selectedMedia[0].thumbnail_url || selectedMedia[0].url"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500">
                                    No image selected
                                </div>
                                <!-- Video play button -->
                                <button
                                    v-if="selectedMedia[0]?.type === 'video' && !isPlayingVideo && selectedMedia[0]?.status !== 'processing'"
                                    @click="isPlayingVideo = true"
                                    class="absolute inset-0 flex items-center justify-center bg-black/20 hover:bg-black/30 transition-colors"
                                >
                                    <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z" />
                                        </svg>
                                    </div>
                                </button>
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
                                    <span class="font-semibold">{{ instagramDisplayName }}</span>
                                    <span class="whitespace-pre-wrap">{{ truncatedCaption || ' Your caption here...' }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Facebook Feed Preview -->
                        <div
                            v-else-if="(previewPlatform || form.platforms[0]).includes('facebook_feed')"
                            class="w-full max-w-[280px] mx-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                        >
                            <!-- Header -->
                            <div class="flex items-center p-3">
                                <div v-if="selectedBrand?.logo_flat_url" class="w-10 h-10 rounded-full overflow-hidden">
                                    <img :src="selectedBrand.logo_flat_url" :alt="selectedBrand.name" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                    {{ facebookDisplayName.charAt(0) }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ facebookDisplayName }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Just now</p>
                                </div>
                            </div>
                            <!-- Caption -->
                            <div class="px-3 pb-2">
                                <p class="text-sm whitespace-pre-wrap text-gray-900 dark:text-white">{{ form.caption || 'Your caption here...' }}</p>
                            </div>
                            <!-- Image/Video -->
                            <div class="relative transition-colors duration-300" :style="{ backgroundColor: mediaEdgeColor }">
                                <!-- Processing state -->
                                <div
                                    v-if="selectedMedia[0]?.status === 'processing'"
                                    class="aspect-video flex flex-col items-center justify-center"
                                >
                                    <svg class="w-12 h-12 text-gray-400 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/>
                                    </svg>
                                    <span class="text-gray-400 text-sm mt-2">Processing video...</span>
                                </div>
                                <!-- Video playing inline -->
                                <video
                                    v-else-if="isPlayingVideo && selectedMedia[0]?.type === 'video'"
                                    :src="selectedMedia[0].url"
                                    class="w-full object-contain"
                                    autoplay
                                    controls
                                    @click.stop
                                    @ended="isPlayingVideo = false"
                                />
                                <!-- Image/thumbnail -->
                                <img
                                    v-else-if="selectedMedia[0]"
                                    :src="selectedMedia[0].thumbnail_url || selectedMedia[0].url"
                                    class="w-full"
                                />
                                <div v-else class="aspect-video flex items-center justify-center bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500">
                                    No image selected
                                </div>
                                <!-- Video play button -->
                                <button
                                    v-if="selectedMedia[0]?.type === 'video' && !isPlayingVideo && selectedMedia[0]?.status !== 'processing'"
                                    @click="isPlayingVideo = true"
                                    class="absolute inset-0 flex items-center justify-center bg-black/20 hover:bg-black/30 transition-colors"
                                >
                                    <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z" />
                                        </svg>
                                    </div>
                                </button>
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
                            class="max-w-[280px] mx-auto rounded-2xl overflow-hidden aspect-[9/16] relative transition-colors duration-300"
                            :style="{ backgroundColor: mediaEdgeColor }"
                        >
                            <!-- Processing state -->
                            <div
                                v-if="selectedMedia[0]?.status === 'processing'"
                                class="w-full h-full flex flex-col items-center justify-center"
                            >
                                <svg class="w-12 h-12 text-gray-400 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/>
                                </svg>
                                <span class="text-gray-400 text-sm mt-2">Processing video...</span>
                            </div>
                            <!-- Video playing inline -->
                            <video
                                v-else-if="isPlayingVideo && selectedMedia[0]?.type === 'video'"
                                :src="selectedMedia[0].url"
                                class="w-full h-full object-cover"
                                autoplay
                                controls
                                @click.stop
                                @ended="isPlayingVideo = false"
                            />
                            <!-- Image/thumbnail -->
                            <img
                                v-else-if="selectedMedia[0]"
                                :src="selectedMedia[0].thumbnail_url || selectedMedia[0].url"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
                                No image selected
                            </div>
                            <!-- Video play button -->
                            <button
                                v-if="selectedMedia[0]?.type === 'video' && !isPlayingVideo && selectedMedia[0]?.status !== 'processing'"
                                @click="isPlayingVideo = true"
                                class="absolute inset-0 flex items-center justify-center z-10"
                            >
                                <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </div>
                            </button>
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
                                        <div v-if="selectedBrand?.logo_flat_url" class="w-9 h-9 rounded-full overflow-hidden border-2 border-black bg-white">
                                            <img :src="selectedBrand.logo_flat_url" :alt="selectedBrand.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold border-2 border-black">
                                            {{ instagramDisplayName.charAt(0) }}
                                        </div>
                                    </div>
                                    <span class="ml-2 text-white text-sm font-medium">{{ instagramDisplayName }}</span>
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
                            class="max-w-[280px] mx-auto rounded-2xl overflow-hidden aspect-[9/16] relative transition-colors duration-300"
                            :style="{ backgroundColor: mediaEdgeColor }"
                        >
                            <!-- Processing state -->
                            <div
                                v-if="selectedMedia[0]?.status === 'processing'"
                                class="w-full h-full flex flex-col items-center justify-center"
                            >
                                <svg class="w-12 h-12 text-gray-400 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/>
                                </svg>
                                <span class="text-gray-400 text-sm mt-2">Processing video...</span>
                            </div>
                            <!-- Video playing inline -->
                            <video
                                v-else-if="isPlayingVideo && selectedMedia[0]?.type === 'video'"
                                :src="selectedMedia[0].url"
                                class="w-full h-full object-cover"
                                autoplay
                                controls
                                @click.stop
                                @ended="isPlayingVideo = false"
                            />
                            <!-- Image/thumbnail -->
                            <img
                                v-else-if="selectedMedia[0]"
                                :src="selectedMedia[0].thumbnail_url || selectedMedia[0].url"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
                                No image selected
                            </div>
                            <!-- Video play button -->
                            <button
                                v-if="selectedMedia[0]?.type === 'video' && !isPlayingVideo && selectedMedia[0]?.status !== 'processing'"
                                @click="isPlayingVideo = true"
                                class="absolute inset-0 flex items-center justify-center z-10"
                            >
                                <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </div>
                            </button>
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
                                    <div v-if="selectedBrand?.logo_flat_url">
                                        <img :src="selectedBrand.logo_flat_url" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-full h-full bg-gradient-to-br from-purple-500 to-pink-500"></div>
                                </div>
                            </div>
                            <!-- Bottom content -->
                            <div class="absolute bottom-4 left-3 right-14">
                                <div class="flex items-center mb-2">
                                    <div v-if="selectedBrand?.logo_flat_url" class="w-8 h-8 rounded-full overflow-hidden">
                                        <img :src="selectedBrand.logo_flat_url" :alt="selectedBrand.name" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ instagramDisplayName.charAt(0) }}
                                    </div>
                                    <span class="ml-2 text-white text-sm font-semibold">{{ instagramDisplayName }}</span>
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

                        <!-- Facebook Reel Preview -->
                        <div
                            v-else-if="previewPlatform === 'facebook_reel'"
                            class="max-w-[280px] mx-auto rounded-2xl overflow-hidden aspect-[9/16] relative transition-colors duration-300"
                            :style="{ backgroundColor: mediaEdgeColor }"
                        >
                            <!-- Processing state -->
                            <div
                                v-if="selectedMedia[0]?.status === 'processing'"
                                class="w-full h-full flex flex-col items-center justify-center"
                            >
                                <svg class="w-12 h-12 text-gray-400 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/>
                                </svg>
                                <span class="text-gray-400 text-sm mt-2">Processing video...</span>
                            </div>
                            <!-- Video playing inline -->
                            <video
                                v-else-if="isPlayingVideo && selectedMedia[0]?.type === 'video'"
                                :src="selectedMedia[0].url"
                                class="w-full h-full object-cover"
                                autoplay
                                controls
                                @click.stop
                                @ended="isPlayingVideo = false"
                            />
                            <!-- Image/thumbnail -->
                            <img
                                v-else-if="selectedMedia[0]"
                                :src="selectedMedia[0].thumbnail_url || selectedMedia[0].url"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
                                No image selected
                            </div>
                            <!-- Video play button -->
                            <button
                                v-if="selectedMedia[0]?.type === 'video' && !isPlayingVideo && selectedMedia[0]?.status !== 'processing'"
                                @click="isPlayingVideo = true"
                                class="absolute inset-0 flex items-center justify-center z-10"
                            >
                                <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </div>
                            </button>
                            <!-- Bottom gradient overlay -->
                            <div class="absolute bottom-0 left-0 right-0 h-48 bg-gradient-to-t from-black/70 via-black/30 to-transparent pointer-events-none"></div>
                            <!-- Right sidebar actions -->
                            <div class="absolute right-3 bottom-24 flex flex-col items-center gap-5 drop-shadow-lg">
                                <!-- Like -->
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M2 20h2c.55 0 1-.45 1-1v-9c0-.55-.45-1-1-1H2v11zm19.83-7.12c.11-.25.17-.52.17-.8V11c0-1.1-.9-2-2-2h-5.5l.92-4.65c.05-.22.02-.46-.08-.66-.23-.45-.52-.86-.88-1.22L14 2 7.59 8.41C7.21 8.79 7 9.3 7 9.83v7.84C7 18.95 8.05 20 9.34 20h8.11c.7 0 1.36-.37 1.72-.97l2.66-6.15z"/>
                                        </svg>
                                    </div>
                                    <span class="text-white text-xs mt-1 font-medium drop-shadow-md">1.2K</span>
                                </div>
                                <!-- Comment -->
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                                        </svg>
                                    </div>
                                    <span class="text-white text-xs mt-1 font-medium drop-shadow-md">48</span>
                                </div>
                                <!-- Share -->
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M21.707 11.293l-8-8A1 1 0 0012 4v3.545A11.015 11.015 0 002 18.5V20a1 1 0 001.784.62 11.456 11.456 0 018.216-4.073V20a1 1 0 001.707.707l8-8a1 1 0 000-1.414z"/>
                                        </svg>
                                    </div>
                                </div>
                                <!-- Music/Audio -->
                                <div class="flex flex-col items-center">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center border-2 border-white overflow-hidden">
                                        <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <!-- Bottom content -->
                            <div class="absolute bottom-4 left-3 right-14">
                                <!-- Profile + Follow -->
                                <div class="flex items-center mb-2">
                                    <div v-if="selectedBrand?.logo_flat_url" class="w-9 h-9 rounded-full overflow-hidden border-2 border-white">
                                        <img :src="selectedBrand.logo_flat_url" :alt="selectedBrand.name" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-9 h-9 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-bold border-2 border-white">
                                        {{ facebookDisplayName.charAt(0) }}
                                    </div>
                                    <span class="ml-2 text-white text-sm font-semibold drop-shadow-md">{{ facebookDisplayName }}</span>
                                    <span class="mx-1 text-white/60">·</span>
                                    <button class="text-white text-sm font-semibold drop-shadow-md">Follow</button>
                                </div>
                                <!-- Caption -->
                                <p class="text-white text-sm drop-shadow-md line-clamp-2 mb-2">{{ truncatedCaption || 'Your caption here...' }}</p>
                                <!-- Audio bar -->
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-white mr-2" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                                    </svg>
                                    <span class="text-white text-xs drop-shadow-md">Original audio · {{ facebookDisplayName }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Facebook Story Preview -->
                        <div
                            v-else-if="previewPlatform === 'facebook_story'"
                            class="max-w-[280px] mx-auto rounded-2xl overflow-hidden aspect-[9/16] relative transition-colors duration-300"
                            :style="{ backgroundColor: mediaEdgeColor }"
                        >
                            <!-- Processing state -->
                            <div
                                v-if="selectedMedia[0]?.status === 'processing'"
                                class="w-full h-full flex flex-col items-center justify-center"
                            >
                                <svg class="w-12 h-12 text-gray-400 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/>
                                </svg>
                                <span class="text-gray-400 text-sm mt-2">Processing video...</span>
                            </div>
                            <!-- Video playing inline -->
                            <video
                                v-else-if="isPlayingVideo && selectedMedia[0]?.type === 'video'"
                                :src="selectedMedia[0].url"
                                class="w-full h-full object-cover"
                                autoplay
                                controls
                                @click.stop
                                @ended="isPlayingVideo = false"
                            />
                            <!-- Image/thumbnail -->
                            <img
                                v-else-if="selectedMedia[0]"
                                :src="selectedMedia[0].thumbnail_url || selectedMedia[0].url"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
                                No image selected
                            </div>
                            <!-- Video play button -->
                            <button
                                v-if="selectedMedia[0]?.type === 'video' && !isPlayingVideo && selectedMedia[0]?.status !== 'processing'"
                                @click="isPlayingVideo = true"
                                class="absolute inset-0 flex items-center justify-center z-10"
                            >
                                <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </div>
                            </button>
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
                                        <div v-if="selectedBrand?.logo_flat_url" class="w-10 h-10 rounded-full overflow-hidden border-2 border-black bg-white">
                                            <img :src="selectedBrand.logo_flat_url" :alt="selectedBrand.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold border-2 border-black">
                                            {{ facebookDisplayName.charAt(0) }}
                                        </div>
                                    </div>
                                    <div class="ml-2">
                                        <span class="text-white text-sm font-semibold block">{{ facebookDisplayName }}</span>
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

                    <!-- Platform Warnings (below preview for visibility) -->
                    <div v-if="validation.errors.length > 0 || validation.warnings.length > 0" class="mt-4 space-y-2">
                        <!-- Errors (blocking) -->
                        <div
                            v-for="(error, index) in validation.errors"
                            :key="'error-' + index"
                            class="flex items-start gap-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"
                        >
                            <svg class="w-5 h-5 text-red-500 dark:text-red-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ error.platformName }}</p>
                                <p class="text-sm text-red-600 dark:text-red-400">{{ error.message }}</p>
                            </div>
                        </div>

                        <!-- Warnings (non-blocking) -->
                        <div
                            v-for="(warning, index) in validation.warnings"
                            :key="'warning-' + index"
                            class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg"
                        >
                            <svg class="w-5 h-5 text-amber-500 dark:text-amber-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-amber-800 dark:text-amber-300">{{ warning.platformName }}</p>
                                <p class="text-sm text-amber-600 dark:text-amber-400">{{ warning.message }}</p>
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
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white shrink-0">Media Library</h3>
                            <!-- Brand Pill Selector -->
                            <div class="relative">
                                <button
                                    @click="showBrandDropdown = !showBrandDropdown"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <div
                                        v-if="brandStore.activeBrand?.logo_url"
                                        class="w-5 h-5 rounded-full overflow-hidden bg-white shrink-0"
                                    >
                                        <img :src="brandStore.activeBrand.logo_url" :alt="brandStore.activeBrand.name" class="w-full h-full object-cover" />
                                    </div>
                                    <div
                                        v-else
                                        class="w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center shrink-0"
                                    >
                                        <span class="text-primary-600 dark:text-primary-400 text-xs font-semibold">
                                            {{ brandStore.activeBrand?.name?.charAt(0)?.toUpperCase() || '?' }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 max-w-[100px] truncate">
                                        {{ brandStore.activeBrand?.name || 'Select' }}
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <!-- Brand Dropdown -->
                                <div
                                    v-if="showBrandDropdown"
                                    class="absolute top-full left-0 mt-1 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 py-1 z-10"
                                >
                                    <button
                                        v-for="b in brandStore.brands"
                                        :key="b.id"
                                        @click="brandStore.setActiveBrand(b); showBrandDropdown = false"
                                        :class="[
                                            'flex items-center gap-2 w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700',
                                            b.id === brandStore.activeBrandId ? 'bg-primary-50 dark:bg-primary-900/20' : ''
                                        ]"
                                    >
                                        <div
                                            v-if="b.logo_url"
                                            class="w-6 h-6 rounded-full overflow-hidden bg-white shrink-0"
                                        >
                                            <img :src="b.logo_url" :alt="b.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div
                                            v-else
                                            class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center shrink-0"
                                        >
                                            <span class="text-primary-600 dark:text-primary-400 text-xs font-semibold">
                                                {{ b.name?.charAt(0)?.toUpperCase() }}
                                            </span>
                                        </div>
                                        <span class="truncate text-gray-700 dark:text-gray-300">{{ b.name }}</span>
                                        <svg
                                            v-if="b.id === brandStore.activeBrandId"
                                            class="w-4 h-4 text-primary-600 dark:text-primary-400 ml-auto shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <label class="cursor-pointer px-3 py-1.5 bg-primary-600 dark:bg-primary-500 text-white text-sm rounded-md hover:bg-primary-700 dark:hover:bg-primary-600">
                                Upload New
                                <input type="file" @change="handleFileUpload" class="hidden" accept="image/*,video/*" />
                            </label>
                            <button @click="showMediaLibrary = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6" @scroll="handleMediaScroll">
                        <div v-if="loadingMedia" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Loading media...
                        </div>
                        <div v-else-if="brandMedia.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No media uploaded for this brand yet.
                            <RouterLink to="/media" class="block mt-2 text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300">
                                Go to Media Library to upload
                            </RouterLink>
                        </div>
                        <template v-else>
                            <div class="grid grid-cols-4 gap-4">
                                <div
                                    v-for="media in brandMedia"
                                    :key="media.id"
                                    @click="toggleMedia(media)"
                                    :class="[
                                        'relative aspect-square rounded-lg overflow-hidden cursor-pointer border-2 transition-all group',
                                        isMediaSelected(media) ? 'border-primary-500 dark:border-primary-400 ring-2 ring-primary-200 dark:ring-primary-900/30' : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600'
                                    ]"
                                >
                                    <!-- Processing state -->
                                    <div
                                        v-if="media.status === 'processing'"
                                        class="w-full h-full bg-gray-900 flex flex-col items-center justify-center"
                                    >
                                        <svg class="w-8 h-8 text-gray-400 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/>
                                        </svg>
                                        <span class="text-gray-400 text-xs mt-2">Processing...</span>
                                    </div>
                                    <!-- Ready state -->
                                    <img
                                        v-else
                                        :src="media.thumbnail_url || media.url"
                                        class="w-full h-full object-cover"
                                    />
                                    <!-- Video play button overlay -->
                                    <div
                                        v-if="isVideo(media) && media.status !== 'processing'"
                                        class="absolute inset-0 flex items-center justify-center"
                                    >
                                        <button
                                            @click="playVideo(media, $event)"
                                            class="w-12 h-12 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors group-hover:scale-110"
                                        >
                                            <svg class="w-6 h-6 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!-- Selected overlay -->
                                    <div
                                        v-if="isMediaSelected(media)"
                                        class="absolute inset-0 bg-primary-500 bg-opacity-20 dark:bg-primary-900/30 flex items-center justify-center pointer-events-none"
                                    >
                                        <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <!-- Loading more indicator -->
                            <div v-if="loadingMoreMedia" class="text-center py-4 text-gray-500 dark:text-gray-400">
                                <svg class="animate-spin h-5 w-5 mx-auto text-primary-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </template>
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

                    <!-- Video info and select button -->
                    <div class="mt-4 flex items-center justify-between">
                        <div class="text-white">
                            <p class="font-medium">{{ videoPreview.original_filename }}</p>
                            <p class="text-sm text-gray-400">
                                {{ formatDuration(videoPreview.duration) }}
                                <span v-if="videoPreview.width && videoPreview.height">
                                    &bull; {{ videoPreview.width }}x{{ videoPreview.height }}
                                </span>
                            </p>
                        </div>
                        <button
                            @click="toggleMedia(videoPreview); closeVideoPreview()"
                            :class="[
                                'px-4 py-2 rounded-md font-medium transition-colors',
                                isMediaSelected(videoPreview)
                                    ? 'bg-red-600 hover:bg-red-700 text-white'
                                    : 'bg-primary-600 hover:bg-primary-700 text-white'
                            ]"
                        >
                            {{ isMediaSelected(videoPreview) ? 'Deselect' : 'Select Video' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit for Approval Modal -->
        <Teleport to="body">
            <div
                v-if="showSubmitModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Submit for Approval</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Create post and submit for review. Optionally invite external reviewers.
                        </p>
                    </div>

                    <div class="px-6 py-4">
                        <div v-if="loadingDefaults" class="flex items-center justify-center py-4">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
                        </div>

                        <div v-else>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                External Reviewer Emails (optional)
                            </label>
                            <textarea
                                v-model="reviewerEmails"
                                rows="3"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                placeholder="client@example.com, manager@example.com"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Separate multiple emails with commas. They'll receive a link to review and approve.
                            </p>

                            <label v-if="reviewerEmails.trim()" class="flex items-center gap-2 mt-4 cursor-pointer">
                                <input
                                    v-model="saveReviewersAsDefault"
                                    type="checkbox"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    Save as default reviewers for this brand
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3 rounded-b-lg">
                        <button
                            @click="showSubmitModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            @click="submitForApproval"
                            :disabled="loading"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ loading ? 'Submitting...' : 'Submit for Approval' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
