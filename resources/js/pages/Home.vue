<script setup>
import { ref, computed, watch } from 'vue';
import { RouterLink } from 'vue-router';
import Footer from '@/components/Footer.vue';
import { useDarkMode } from '@/composables/useDarkMode';
import { useAuthStore } from '@/stores/auth';
import { extractEdgeColor } from '@/composables/useEdgeColor';
import { validatePost, getCaptionStatus } from '@/utils/platformValidation';
import { homepageApi } from '@/services/api';
import html2canvas from 'html2canvas-pro';
import {
    InstagramFeedPreview,
    FacebookFeedPreview,
    InstagramStoryPreview,
    InstagramReelPreview,
    FacebookStoryPreview,
    FacebookReelPreview
} from '@/components/mockups';

const { isDark, toggle: toggleDarkMode } = useDarkMode();
const authStore = useAuthStore();
const isAuthenticated = computed(() => authStore.isAuthenticated);

// Usage tracking
const showSignupPrompt = ref(false);
const usageCount = ref(0);

const trackUsage = async (action, mediaType = null) => {
    try {
        const response = await homepageApi.trackUsage({
            action,
            platform: selectedPlatform.value,
            media_type: mediaType,
        });
        usageCount.value = response.data.usage_count;
        if (response.data.show_signup_prompt && !isAuthenticated.value) {
            showSignupPrompt.value = true;
        }
    } catch (error) {
        // Silently fail - tracking shouldn't break the user experience
        console.warn('Failed to track usage:', error);
    }
};

const dismissSignupPrompt = () => {
    showSignupPrompt.value = false;
};

const selectedFile = ref(null);
const previewUrl = ref(null);
const caption = ref('');
const selectedPlatform = ref('facebook_feed');
const isVideo = ref(false);

const platforms = [
    { id: 'facebook_feed', name: 'Facebook Feed', icon: 'FB' },
    { id: 'facebook_story', name: 'Facebook Story', icon: 'FB' },
    { id: 'facebook_reel', name: 'Facebook Reel', icon: 'FB' },
    { id: 'instagram_feed', name: 'Instagram Feed', icon: 'IG' },
    { id: 'instagram_story', name: 'Instagram Story', icon: 'IG' },
    { id: 'instagram_reel', name: 'Instagram Reel', icon: 'IG' },
];
const videoDuration = ref(0);
const videoThumbnail = ref(null);
const brandName = ref('');
const edgeColor = ref('rgb(0, 0, 0)');

const formatDuration = (seconds) => {
    if (!seconds) return '';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

// Extract edge color when preview URL changes
watch(previewUrl, async (url) => {
    if (url && !isVideo.value) {
        edgeColor.value = await extractEdgeColor(url);
    } else {
        edgeColor.value = 'rgb(0, 0, 0)';
    }
});

const brandLogo = ref(null);
const brandLogoUrl = ref(null);
const imageDimensions = ref({ width: 0, height: 0 });
const isVertical = computed(() => imageDimensions.value.height > imageDimensions.value.width * 1.2);

// Platform validation
const mediaInfo = computed(() => {
    if (!selectedFile.value) return null;
    return {
        type: isVideo.value ? 'video' : 'image',
        width: imageDimensions.value.width,
        height: imageDimensions.value.height,
        duration: videoDuration.value,
    };
});

const validation = computed(() => {
    return validatePost([selectedPlatform.value], mediaInfo.value, caption.value);
});

const captionStatus = computed(() => {
    return getCaptionStatus([selectedPlatform.value], caption.value.length);
});

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file && file.type.startsWith('image/')) {
        brandLogo.value = file;
        brandLogoUrl.value = URL.createObjectURL(file);
    }
};

const clearLogo = () => {
    brandLogo.value = null;
    brandLogoUrl.value = null;
};

const displayBrandName = computed(() => brandName.value || 'your_brand');
const displayBrandNameFb = computed(() => brandName.value || 'Your Brand');
const brandInitials = computed(() => {
    const name = brandName.value || 'Your Brand';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});


const processFile = (file) => {
    selectedFile.value = file;
    isVideo.value = file.type.startsWith('video/');
    previewUrl.value = URL.createObjectURL(file);
    videoThumbnail.value = null;

    // Track file upload
    const mediaType = file.type.startsWith('video/') ? 'video' : 'image';
    trackUsage('file_upload', mediaType);

    // Detect image dimensions
    if (file.type.startsWith('image/')) {
        const img = new Image();
        img.onload = () => {
            imageDimensions.value = { width: img.width, height: img.height };
        };
        img.src = URL.createObjectURL(file);
    } else if (file.type.startsWith('video/')) {
        const video = document.createElement('video');
        video.crossOrigin = 'anonymous';
        video.onloadedmetadata = () => {
            imageDimensions.value = { width: video.videoWidth, height: video.videoHeight };
            videoDuration.value = video.duration;
            // Seek to first frame to generate thumbnail
            video.currentTime = 0.1;
        };
        video.onseeked = async () => {
            // Generate thumbnail from first frame
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            videoThumbnail.value = canvas.toDataURL('image/jpeg', 0.8);
            // Extract edge color from thumbnail
            edgeColor.value = await extractEdgeColor(videoThumbnail.value);
        };
        video.src = URL.createObjectURL(file);
    }
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        processFile(file);
    }
};

const handleDrop = (event) => {
    event.preventDefault();
    const file = event.dataTransfer.files[0];
    if (file && (file.type.startsWith('image/') || file.type.startsWith('video/'))) {
        processFile(file);
    }
};

const clearPreview = () => {
    selectedFile.value = null;
    previewUrl.value = null;
    caption.value = '';
    imageDimensions.value = { width: 0, height: 0 };
    videoDuration.value = 0;
    videoThumbnail.value = null;
};

const mockupRef = ref(null);
const exporting = ref(false);

const exportAsJpeg = async () => {
    if (exporting.value) return;

    if (!mockupRef.value) {
        alert('No mockup to export. Please upload an image first.');
        return;
    }

    try {
        exporting.value = true;

        // Wait a tick for Vue to finish any pending updates
        await new Promise(resolve => setTimeout(resolve, 100));

        const canvas = await html2canvas(mockupRef.value, {
            backgroundColor: '#ffffff',
            scale: 2,
            useCORS: true,
            allowTaint: true,
            logging: false,
        });

        const link = document.createElement('a');
        link.download = `${selectedPlatform.value}-mockup-${Date.now()}.jpg`;
        link.href = canvas.toDataURL('image/jpeg', 0.95);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Track export
        trackUsage('export', isVideo.value ? 'video' : 'image');
    } catch (err) {
        console.error('Export failed:', err);
        alert('Failed to export mockup: ' + err.message);
    } finally {
        exporting.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <nav class="bg-white dark:bg-gray-800 shadow dark:shadow-gray-700/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center shrink-0">
                        <img src="/images/post-reviewer-logo.svg" alt="Post Reviewer" class="h-9 sm:h-10 shrink-0 dark:hidden" />
                        <img src="/images/post-reviewer-logo-dark.svg" alt="Post Reviewer" class="h-9 sm:h-10 shrink-0 hidden dark:block" />
                    </div>
                    <div class="flex items-center space-x-2 sm:space-x-4">
                        <button
                            @click="toggleDarkMode"
                            class="hidden sm:block p-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700"
                        >
                            <svg v-if="isDark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                            </svg>
                        </button>
                        <template v-if="isAuthenticated">
                            <RouterLink
                                to="/dashboard"
                                class="inline-flex items-center bg-primary-600 text-white px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base rounded-md hover:bg-primary-700 whitespace-nowrap h-auto"
                            >
                                Dashboard
                            </RouterLink>
                        </template>
                        <template v-else>
                            <RouterLink
                                to="/login"
                                class="hidden sm:inline text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                            >
                                Login
                            </RouterLink>
                            <RouterLink
                                to="/register"
                                class="inline-flex items-center bg-primary-600 text-white px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base rounded-md hover:bg-primary-700 whitespace-nowrap h-auto"
                            >
                                Get Started
                            </RouterLink>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Development Notice Banner -->
        <div class="bg-amber-500 text-amber-950">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <div class="flex items-center justify-center gap-2 text-sm font-medium">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span>This app is under development. Your account may be reset during this period.</span>
                </div>
            </div>
        </div>

        <main class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
            <!-- Hero Section -->
            <div class="text-center">
                <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white sm:text-5xl md:text-6xl">
                    Upload. Preview.
                    <span class="block text-primary-600 dark:text-primary-500">Get Approved.</span>
                </h1>
                <p class="mt-3 max-w-md mx-auto text-base text-gray-500 dark:text-gray-400 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                    Show clients exactly how their posts will look on Instagram and Facebook.
                    Get sign-off in one click.
                </p>
                <div class="mt-10 flex flex-col sm:flex-row justify-center gap-3 sm:gap-4">
                    <RouterLink
                        to="/register"
                        class="bg-primary-600 text-white px-6 sm:px-8 py-3 rounded-md text-base sm:text-lg font-medium hover:bg-primary-700 text-center"
                    >
                        Start Free Trial
                    </RouterLink>
                    <a
                        href="#demo"
                        class="bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-500 px-6 sm:px-8 py-3 rounded-md text-base sm:text-lg font-medium border border-primary-600 dark:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-700 text-center"
                    >
                        See It In Action
                    </a>
                </div>
            </div>

            <!-- Demo Section -->
            <div id="demo" class="mt-16 bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-gray-900/50 p-4 sm:p-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Try It Now - No Account Needed</h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Upload any image or video and see exactly how it'll look on Facebook or Instagram. It takes 5 seconds.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Upload Section -->
                    <div class="flex flex-col gap-4">
                        <div class="order-3 lg:order-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Platform</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <button
                                    v-for="platform in platforms"
                                    :key="platform.id"
                                    @click="selectedPlatform = platform.id"
                                    :class="[
                                        'px-3 py-2 text-xs rounded-lg flex items-center justify-center gap-1.5 transition-all',
                                        selectedPlatform === platform.id
                                            ? platform.id.startsWith('facebook')
                                                ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 ring-2 ring-blue-400'
                                                : 'bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300 ring-2 ring-pink-400'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    <span
                                        class="w-5 h-5 shrink-0 rounded-full inline-flex items-center justify-center text-[10px] font-bold text-white"
                                        :style="{ backgroundColor: platform.id.startsWith('facebook') ? '#3b82f6' : '#ec4899' }"
                                    >
                                        {{ platform.icon }}
                                    </span>
                                    {{ platform.name }}
                                </button>
                            </div>
                            <!-- Platform Warnings -->
                            <div v-if="validation.errors.length > 0 || validation.warnings.length > 0" class="mt-3 space-y-2">
                                <div
                                    v-for="(error, idx) in validation.errors"
                                    :key="'error-' + idx"
                                    class="flex items-start gap-2 text-xs bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-3 py-2 rounded-md"
                                >
                                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ error.message }}</span>
                                </div>
                                <div
                                    v-for="(warning, idx) in validation.warnings"
                                    :key="'warning-' + idx"
                                    class="flex items-start gap-2 text-xs bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-3 py-2 rounded-md"
                                >
                                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ warning.message }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Brand Settings -->
                        <div class="p-3 sm:p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg order-1 lg:order-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Brand Settings</h4>
                            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Brand Name</label>
                                    <input
                                        v-model="brandName"
                                        type="text"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                        placeholder="Your Brand"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Logo</label>
                                    <div v-if="!brandLogoUrl" class="relative">
                                        <input
                                            type="file"
                                            accept="image/*"
                                            @change="handleLogoChange"
                                            class="hidden"
                                            id="logo-upload"
                                        />
                                        <label
                                            for="logo-upload"
                                            class="flex items-center justify-center w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 border-dashed rounded-md cursor-pointer hover:border-primary-500 transition-colors bg-white dark:bg-gray-800"
                                        >
                                            <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-gray-500 dark:text-gray-400">Upload</span>
                                        </label>
                                    </div>
                                    <div v-else class="flex items-center gap-2">
                                        <img :src="brandLogoUrl" class="w-10 h-10 rounded-full object-cover" />
                                        <button
                                            @click="clearLogo"
                                            class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!previewUrl"
                            @drop="handleDrop"
                            @dragover.prevent
                            @dragenter.prevent
                            class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-12 text-center hover:border-primary-500 transition-colors cursor-pointer bg-white dark:bg-gray-800 order-2 lg:order-3"
                        >
                            <input
                                type="file"
                                accept="image/*,video/*"
                                @change="handleFileChange"
                                class="hidden"
                                id="demo-file-upload"
                            />
                            <label for="demo-file-upload" class="cursor-pointer">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium text-primary-600 dark:text-primary-500">Click to upload</span> or drag and drop
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF, MP4 up to 10MB</p>
                            </label>
                        </div>

                        <div v-else class="space-y-4 order-2 lg:order-3">
                            <div class="relative bg-gray-100 dark:bg-gray-700 rounded-lg p-4 flex items-center justify-center">
                                <img v-if="!isVideo" :src="previewUrl" class="max-h-[200px] max-w-full object-contain rounded-lg" alt="Preview" />
                                <video v-else :src="previewUrl" class="max-h-[200px] max-w-full object-contain rounded-lg" controls />
                                <button
                                    @click="clearPreview"
                                    class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full hover:bg-red-600"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Caption</label>
                                <textarea
                                    v-model="caption"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                    placeholder="Write a caption..."
                                />
                                <div class="flex justify-end mt-1">
                                    <span
                                        class="text-xs"
                                        :class="{
                                            'text-gray-400 dark:text-gray-500': captionStatus.status === 'ok',
                                            'text-amber-600 dark:text-amber-400': captionStatus.status === 'warning',
                                            'text-red-600 dark:text-red-400': captionStatus.status === 'error',
                                        }"
                                    >
                                        {{ caption.length.toLocaleString() }}<template v-if="captionStatus.limit"> / {{ captionStatus.limit.toLocaleString() }}</template>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Section -->
                    <div class="w-[calc(100%+2rem)] -mx-4 sm:w-full sm:mx-0">
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-none sm:rounded-lg p-2 sm:p-4 flex items-center justify-center min-h-[580px]">
                            <!-- Mockup Container - scales down on very small screens -->
                            <div ref="mockupRef" class="max-[400px]:scale-90 origin-center">
                                <!-- Instagram Feed -->
                                <InstagramFeedPreview
                                    v-if="selectedPlatform === 'instagram_feed'"
                                    :brand-name="displayBrandName"
                                    :brand-logo-url="brandLogoUrl"
                                    :media-url="previewUrl"
                                    :thumbnail-url="videoThumbnail"
                                    :media-type="isVideo ? 'video' : 'image'"
                                    :caption="caption"
                                    :background-color="edgeColor"
                                    placeholder-text="Upload image to preview"
                                />

                                <!-- Facebook Feed -->
                                <FacebookFeedPreview
                                    v-else-if="selectedPlatform === 'facebook_feed'"
                                    :brand-name="displayBrandNameFb"
                                    :brand-logo-url="brandLogoUrl"
                                    :media-url="previewUrl"
                                    :thumbnail-url="videoThumbnail"
                                    :media-type="isVideo ? 'video' : 'image'"
                                    :caption="caption"
                                    :background-color="edgeColor"
                                    placeholder-text="Upload image to preview"
                                />

                                <!-- Instagram Story -->
                                <InstagramStoryPreview
                                    v-else-if="selectedPlatform === 'instagram_story'"
                                    :brand-name="displayBrandName"
                                    :brand-logo-url="brandLogoUrl"
                                    :media-url="previewUrl"
                                    :thumbnail-url="videoThumbnail"
                                    :media-type="isVideo ? 'video' : 'image'"
                                    placeholder-text="Upload image to preview"
                                />

                                <!-- Instagram Reel -->
                                <InstagramReelPreview
                                    v-else-if="selectedPlatform === 'instagram_reel'"
                                    :brand-name="displayBrandName"
                                    :brand-logo-url="brandLogoUrl"
                                    :media-url="previewUrl"
                                    :thumbnail-url="videoThumbnail"
                                    :media-type="isVideo ? 'video' : 'image'"
                                    :caption="caption"
                                    placeholder-text="Upload image to preview"
                                />

                                <!-- Facebook Reel -->
                                <FacebookReelPreview
                                    v-else-if="selectedPlatform === 'facebook_reel'"
                                    :brand-name="displayBrandNameFb"
                                    :brand-logo-url="brandLogoUrl"
                                    :media-url="previewUrl"
                                    :thumbnail-url="videoThumbnail"
                                    :media-type="isVideo ? 'video' : 'image'"
                                    :caption="caption"
                                    placeholder-text="Upload image to preview"
                                />

                                <!-- Facebook Story -->
                                <FacebookStoryPreview
                                    v-else
                                    :brand-name="displayBrandNameFb"
                                    :brand-logo-url="brandLogoUrl"
                                    :media-url="previewUrl"
                                    :thumbnail-url="videoThumbnail"
                                    :media-type="isVideo ? 'video' : 'image'"
                                    placeholder-text="Upload image to preview"
                                />
                            </div>

                        </div>

                        <!-- Export Button -->
                        <div v-if="previewUrl" class="mt-4 text-center">
                            <button
                                @click="exportAsJpeg"
                                :disabled="exporting"
                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="!exporting" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <svg v-else class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ exporting ? 'Exporting...' : 'Export as JPEG' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-8 text-center">
                    <p class="text-gray-600 dark:text-gray-400 mb-4">Like what you see? Create a free account to save mockups, invite clients, and manage approvals.</p>
                    <RouterLink
                        to="/register"
                        class="inline-block bg-primary-600 text-white px-6 py-3 rounded-md font-medium hover:bg-primary-700"
                    >
                        Create Free Account
                    </RouterLink>
                </div>

            </div>

            <!-- Pain Points Section -->
            <div class="mt-24 text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Sound Familiar?</h2>
                <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Pain Point 1 -->
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6 text-left">
                        <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">They can't picture it</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Clients can't visualize posts until you screenshot every platform. Again.
                        </p>
                    </div>

                    <!-- Pain Point 2 -->
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6 text-left">
                        <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Changes lost in threads</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Feedback scattered across email, Slack, and texts. Which version got approved?
                        </p>
                    </div>

                    <!-- Pain Point 3 -->
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6 text-left">
                        <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Waiting kills momentum</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Content calendar says "post today." Client last replied 3 days ago.
                        </p>
                    </div>
                </div>
            </div>

            <!-- How It Works Section -->
            <div class="mt-24">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-12">Three Steps to Faster Approvals</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Step 1 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-primary-600 dark:text-primary-500">1</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Upload your content</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Add your image, video, and caption. Pick your platform.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-primary-600 dark:text-primary-500">2</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Show them the real thing</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Generate realistic mockups they can actually see.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-primary-600 dark:text-primary-500">3</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Get sign-off in one click</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Share a link. They approve or request changes in one click.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="mt-24">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-12">Everything You Need for Smooth Approvals</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Feature 1 -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm dark:shadow-gray-900/30 border border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">Multi-Platform Mockups</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Instagram and Facebook feeds, stories, and reels.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm dark:shadow-gray-900/30 border border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">Catch Mistakes Before Posting</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Warnings for caption length, aspect ratios, and duration.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm dark:shadow-gray-900/30 border border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">Multi-Brand Support</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Separate workflows for each client or brand.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm dark:shadow-gray-900/30 border border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">No Login Required for Clients</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Clients approve via link - no account needed.</p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm dark:shadow-gray-900/30 border border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">Built for Teams</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Creators, reviewers, and managers with role-based access.</p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm dark:shadow-gray-900/30 border border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">Export Beautiful Mockups</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Download JPEGs for presentations and reports.</p>
                    </div>
                </div>
            </div>

            <!-- Who It's For Section -->
            <div id="features" class="mt-24">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-12">Built For People Who Manage Content for Others</h2>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow dark:shadow-gray-900/30">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Marketing Agencies</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Keep every client organized with separate workflows and team access.
                        </p>
                        <span class="inline-block mt-3 px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-medium rounded-full">Multi-brand support</span>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow dark:shadow-gray-900/30">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Freelance Social Media Managers</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Look professional. Close the feedback loop faster.
                        </p>
                        <span class="inline-block mt-3 px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-medium rounded-full">No client login needed</span>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow dark:shadow-gray-900/30">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">In-House Brand Teams</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Get sign-off from stakeholders before anything goes live.
                        </p>
                        <span class="inline-block mt-3 px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-medium rounded-full">Clear approval trail</span>
                    </div>
                </div>
            </div>
        </main>

        <!-- Final CTA Section -->
        <div class="bg-primary-600 dark:bg-primary-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
                <h2 class="text-3xl font-bold text-white mb-4">Ready to Streamline Your Approvals?</h2>
                <p class="text-primary-100 mb-8 max-w-2xl mx-auto">Start your free trial today. No credit card required.</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <RouterLink
                        to="/register"
                        class="bg-white text-primary-600 px-8 py-3 rounded-md text-lg font-medium hover:bg-primary-50 transition-colors"
                    >
                        Get Started Free
                    </RouterLink>
                    <a
                        href="#demo"
                        class="text-white border border-white/30 px-8 py-3 rounded-md text-lg font-medium hover:bg-white/10 transition-colors"
                    >
                        Try the Demo First
                    </a>
                </div>
                <p class="mt-8 text-primary-200 text-sm">
                    We're in beta.
                    <a href="https://discord.gg/9RQWcmZdzR" target="_blank" rel="noopener noreferrer" class="underline hover:text-white">Join our Discord</a>
                    for updates and feedback.
                </p>
            </div>
        </div>

        <Footer />

        <!-- Signup Prompt Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showSignupPrompt" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="dismissSignupPrompt">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 transform transition-all">
                        <div class="text-center">
                            <div class="mx-auto w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                Enjoying the mockup generator?
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">
                                Create a free account to save your work, collaborate with your team, and get client approvals faster.
                            </p>
                            <div class="flex flex-col gap-3">
                                <RouterLink
                                    to="/register"
                                    class="w-full bg-primary-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-primary-700 transition-colors"
                                >
                                    Create Free Account
                                </RouterLink>
                                <button
                                    @click="dismissSignupPrompt"
                                    class="w-full text-gray-600 dark:text-gray-400 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    Maybe later
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
