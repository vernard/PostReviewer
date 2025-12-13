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
const isPlayingVideo = ref(false);
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

// Reset video playback when platform changes
watch(selectedPlatform, () => {
    isPlayingVideo.value = false;
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

const captionExpanded = ref(false);
const captionMaxLength = 125;

const displayCaption = computed(() => caption.value || 'Your caption here...');
const isCaptionLong = computed(() => caption.value.length > captionMaxLength);
const truncatedCaption = computed(() => {
    if (!caption.value) return 'Your caption here...';
    if (captionExpanded.value || caption.value.length <= captionMaxLength) {
        return caption.value;
    }
    return caption.value.substring(0, captionMaxLength).trim() + '...';
});

const toggleCaption = () => {
    captionExpanded.value = !captionExpanded.value;
};

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
    isPlayingVideo.value = false;
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
            <div class="text-center">
                <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white sm:text-5xl md:text-6xl">
                    Client Approvals,
                    <span class="block text-primary-600 dark:text-primary-500">Made Simple</span>
                </h1>
                <p class="mt-3 max-w-md mx-auto text-base text-gray-500 dark:text-gray-400 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                    Make client approvals effortless. Post Reviewer helps agencies and freelancers
                    get faster sign-offs with realistic mockups and streamlined feedback.
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
                        Try It Now
                    </a>
                </div>
            </div>

            <!-- Demo Section -->
            <div id="demo" class="mt-16 sm:mt-24 bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-gray-900/50 p-4 sm:p-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Try It Now</h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Upload an image or video to see how it looks on social media</p>
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
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-none sm:rounded-lg p-2 sm:p-4 flex items-center justify-center">
                            <!-- Mockup Container - scales down on very small screens -->
                            <div ref="mockupRef" class="max-[400px]:scale-90">
                            <!-- Instagram Feed Mockup -->
                            <div v-if="selectedPlatform === 'instagram_feed'" class="bg-white rounded-lg shadow-lg w-full max-w-[350px] sm:max-w-[375px]">
                                <!-- Header -->
                                <div class="flex items-center gap-3 p-3 border-b">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-500 p-0.5">
                                        <div class="w-full h-full bg-white rounded-full flex items-center justify-center overflow-hidden">
                                            <img v-if="brandLogoUrl" :src="brandLogoUrl" class="w-full h-full object-cover" />
                                            <span v-else class="text-xs font-bold text-gray-800">{{ brandInitials }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ displayBrandName }}</p>
                                        <p class="text-xs text-gray-500">Sponsored</p>
                                    </div>
                                    <svg class="ml-auto w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="5" r="1.5" />
                                        <circle cx="12" cy="12" r="1.5" />
                                        <circle cx="12" cy="19" r="1.5" />
                                    </svg>
                                </div>
                                <!-- Image -->
                                <div class="w-full aspect-square overflow-hidden flex items-center justify-center relative transition-colors duration-300" :style="{ backgroundColor: edgeColor }">
                                    <img v-if="previewUrl && !isVideo" :src="previewUrl" class="max-w-full max-h-full object-contain" />
                                    <!-- Video playing inline -->
                                    <video
                                        v-else-if="previewUrl && isVideo && isPlayingVideo"
                                        :src="previewUrl"
                                        class="w-full h-full object-contain"
                                        autoplay
                                        controls
                                        @ended="isPlayingVideo = false"
                                    />
                                    <!-- Video thumbnail with play button -->
                                    <template v-else-if="previewUrl && isVideo">
                                        <img v-if="videoThumbnail" :src="videoThumbnail" class="max-w-full max-h-full object-contain" />
                                        <div v-else class="w-full h-full bg-gray-900 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                        <button
                                            @click="isPlayingVideo = true"
                                            class="absolute inset-0 flex items-center justify-center bg-black/20 hover:bg-black/30 transition-colors z-10"
                                        >
                                            <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center">
                                                <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z" />
                                                </svg>
                                            </div>
                                        </button>
                                        <!-- Duration badge -->
                                        <div v-if="videoDuration" class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-1.5 py-0.5 rounded z-20">
                                            {{ formatDuration(videoDuration) }}
                                        </div>
                                    </template>
                                    <!-- Sample placeholder -->
                                    <div v-else class="w-full h-full bg-gradient-to-br from-pink-400 via-purple-400 to-indigo-400 flex items-center justify-center">
                                        <div class="text-center text-white">
                                            <svg class="mx-auto h-16 w-16 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p class="mt-2 text-sm font-medium opacity-90">Your image here</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Actions -->
                                <div class="p-3">
                                    <div class="flex items-center gap-4">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                        </svg>
                                        <svg class="w-6 h-6 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold mt-2">1,234 likes</p>
                                    <p class="text-sm mt-1">
                                        <span class="font-semibold">{{ displayBrandName }}</span>{{ ' ' }}<span class="whitespace-pre-wrap">{{ truncatedCaption }}</span>
                                        <button
                                            v-if="isCaptionLong && !captionExpanded"
                                            @click="toggleCaption"
                                            class="text-gray-500 hover:text-gray-700"
                                        >more</button>
                                        <button
                                            v-if="isCaptionLong && captionExpanded"
                                            @click="toggleCaption"
                                            class="text-gray-500 hover:text-gray-700 block"
                                        >less</button>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">2 HOURS AGO</p>
                                </div>
                            </div>

                            <!-- Facebook Feed Mockup -->
                            <div v-else-if="selectedPlatform === 'facebook_feed'" class="bg-white rounded-lg shadow-lg w-full max-w-[350px] sm:max-w-[500px]">
                                <!-- Header -->
                                <div class="flex items-center gap-3 p-3">
                                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center overflow-hidden relative">
                                        <img v-if="brandLogoUrl" :src="brandLogoUrl" class="w-full h-full object-cover" />
                                        <div v-if="brandLogoUrl" class="absolute inset-0 rounded-full" style="box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.1)"></div>
                                        <span v-else class="text-white font-bold bg-blue-600 w-full h-full flex items-center justify-center">{{ brandInitials }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ displayBrandNameFb }}</p>
                                        <div class="flex items-center text-xs text-gray-500">
                                            <span>2h</span>
                                            <span class="mx-1">·</span>
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <svg class="ml-auto w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="5" r="1.5" />
                                        <circle cx="12" cy="12" r="1.5" />
                                        <circle cx="12" cy="19" r="1.5" />
                                    </svg>
                                </div>
                                <!-- Caption -->
                                <p class="px-3 pb-2 text-sm text-gray-900">
                                    <span class="whitespace-pre-wrap">{{ truncatedCaption }}</span>
                                    <button
                                        v-if="isCaptionLong && !captionExpanded"
                                        @click="toggleCaption"
                                        class="text-blue-600 hover:underline ml-1"
                                    >See more</button>
                                    <button
                                        v-if="isCaptionLong && captionExpanded"
                                        @click="toggleCaption"
                                        class="text-blue-600 hover:underline block"
                                    >See less</button>
                                </p>
                                <!-- Image -->
                                <div
                                    class="w-full aspect-square flex items-center justify-center transition-colors duration-300 relative"
                                    :style="{ backgroundColor: edgeColor }"
                                >
                                    <img
                                        v-if="previewUrl && !isVideo"
                                        :src="previewUrl"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                    <!-- Video playing inline -->
                                    <video
                                        v-else-if="previewUrl && isVideo && isPlayingVideo"
                                        :src="previewUrl"
                                        class="max-h-full max-w-full object-contain"
                                        autoplay
                                        controls
                                        @ended="isPlayingVideo = false"
                                    />
                                    <!-- Video thumbnail with play button -->
                                    <template v-else-if="previewUrl && isVideo">
                                        <img v-if="videoThumbnail" :src="videoThumbnail" class="max-h-full max-w-full object-contain" />
                                        <div v-else class="w-full h-full bg-gray-900 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                        <button
                                            @click="isPlayingVideo = true"
                                            class="absolute inset-0 flex items-center justify-center bg-black/20 hover:bg-black/30 transition-colors z-10"
                                        >
                                            <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center">
                                                <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z" />
                                                </svg>
                                            </div>
                                        </button>
                                        <!-- Duration badge -->
                                        <div v-if="videoDuration" class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-1.5 py-0.5 rounded z-20">
                                            {{ formatDuration(videoDuration) }}
                                        </div>
                                    </template>
                                    <!-- Sample placeholder -->
                                    <div v-else class="w-full h-full bg-gradient-to-br from-blue-400 via-blue-500 to-indigo-500 flex items-center justify-center">
                                        <div class="text-center text-white">
                                            <svg class="mx-auto h-16 w-16 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p class="mt-2 text-sm font-medium opacity-90">Your image here</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Stats -->
                                <div class="px-3 py-2 flex items-center justify-between text-sm text-gray-500 border-b">
                                    <div class="flex items-center">
                                        <div class="flex -space-x-1">
                                            <div class="w-5 h-5 rounded-full bg-blue-600 flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                                </svg>
                                            </div>
                                            <div class="w-5 h-5 rounded-full bg-red-500 flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <span class="ml-2">1.2K</span>
                                    </div>
                                    <span>42 comments · 18 shares</span>
                                </div>
                                <!-- Actions -->
                                <div class="px-3 py-1 flex justify-around">
                                    <button class="flex items-center gap-2 py-2 px-4 rounded hover:bg-gray-100 text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                        </svg>
                                        Like
                                    </button>
                                    <button class="flex items-center gap-2 py-2 px-4 rounded hover:bg-gray-100 text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        Comment
                                    </button>
                                    <button class="flex items-center gap-2 py-2 px-4 rounded hover:bg-gray-100 text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                        </svg>
                                        Share
                                    </button>
                                </div>
                            </div>

                            <!-- Instagram Story Mockup -->
                            <div v-else-if="selectedPlatform === 'instagram_story'" class="bg-black rounded-2xl shadow-lg overflow-hidden aspect-[9/16] w-[280px] relative">
                                <!-- Background image/video -->
                                <img v-if="previewUrl && !isVideo" :src="previewUrl" class="absolute inset-0 w-full h-full object-cover" />
                                <!-- Video playing inline -->
                                <video
                                    v-else-if="previewUrl && isVideo && isPlayingVideo"
                                    :src="previewUrl"
                                    class="absolute inset-0 w-full h-full object-cover"
                                    autoplay
                                    @ended="isPlayingVideo = false"
                                />
                                <!-- Video thumbnail with play button -->
                                <template v-else-if="previewUrl && isVideo">
                                    <img v-if="videoThumbnail" :src="videoThumbnail" class="absolute inset-0 w-full h-full object-cover" />
                                    <div v-else class="absolute inset-0 bg-gray-900 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <button
                                        @click="isPlayingVideo = true"
                                        class="absolute inset-0 flex items-center justify-center z-10"
                                    >
                                        <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors">
                                            <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </div>
                                    </button>
                                    <!-- Duration badge -->
                                    <div v-if="videoDuration" class="absolute top-14 right-3 bg-black/70 text-white text-xs px-1.5 py-0.5 rounded z-20">
                                        {{ formatDuration(videoDuration) }}
                                    </div>
                                </template>
                                <div v-else class="absolute inset-0 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center">
                                    <div class="text-center text-white">
                                        <svg class="mx-auto h-16 w-16 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="mt-2 text-sm font-medium opacity-90">Your story here</p>
                                    </div>
                                </div>
                                <!-- Story Header -->
                                <div class="absolute top-0 left-0 right-0 p-3 bg-gradient-to-b from-black/50 to-transparent">
                                    <!-- Progress bar -->
                                    <div class="w-full h-0.5 bg-white/30 rounded-full mb-3">
                                        <div class="h-full w-1/3 bg-white rounded-full"></div>
                                    </div>
                                    <!-- Profile -->
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-500 p-0.5">
                                            <div class="w-full h-full bg-white rounded-full flex items-center justify-center overflow-hidden">
                                                <img v-if="brandLogoUrl" :src="brandLogoUrl" class="w-full h-full object-cover" />
                                                <span v-else class="text-[10px] font-bold text-gray-800">{{ brandInitials }}</span>
                                            </div>
                                        </div>
                                        <span class="text-white text-sm font-medium">{{ displayBrandName }}</span>
                                        <span class="text-white/70 text-xs">2h</span>
                                        <svg class="ml-auto w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="5" r="1.5" />
                                            <circle cx="12" cy="12" r="1.5" />
                                            <circle cx="12" cy="19" r="1.5" />
                                        </svg>
                                    </div>
                                </div>
                                <!-- Story Footer -->
                                <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/50 to-transparent">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 px-4 py-2 rounded-full border border-white/50 text-white/70 text-sm">
                                            Send message
                                        </div>
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Instagram Reel Mockup -->
                            <div v-else-if="selectedPlatform === 'instagram_reel'" class="bg-black rounded-2xl shadow-lg overflow-hidden aspect-[9/16] w-[280px] relative">
                                <!-- Background image/video -->
                                <img v-if="previewUrl && !isVideo" :src="previewUrl" class="absolute inset-0 w-full h-full object-cover" />
                                <!-- Video playing inline -->
                                <video
                                    v-else-if="previewUrl && isVideo && isPlayingVideo"
                                    :src="previewUrl"
                                    class="absolute inset-0 w-full h-full object-cover"
                                    autoplay
                                    @ended="isPlayingVideo = false"
                                />
                                <!-- Video thumbnail with play button -->
                                <template v-else-if="previewUrl && isVideo">
                                    <img v-if="videoThumbnail" :src="videoThumbnail" class="absolute inset-0 w-full h-full object-cover" />
                                    <div v-else class="absolute inset-0 bg-gray-900 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <button
                                        @click="isPlayingVideo = true"
                                        class="absolute inset-0 flex items-center justify-center z-10"
                                    >
                                        <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors">
                                            <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </div>
                                    </button>
                                    <!-- Duration badge -->
                                    <div v-if="videoDuration" class="absolute top-14 right-3 bg-black/70 text-white text-xs px-1.5 py-0.5 rounded z-20">
                                        {{ formatDuration(videoDuration) }}
                                    </div>
                                </template>
                                <div v-else class="absolute inset-0 bg-gradient-to-br from-purple-600 via-pink-500 to-orange-400 flex items-center justify-center">
                                    <div class="text-center text-white">
                                        <svg class="mx-auto h-16 w-16 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <p class="mt-2 text-sm font-medium opacity-90">Your reel here</p>
                                    </div>
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
                                        <img v-if="brandLogoUrl" :src="brandLogoUrl" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full bg-gradient-to-br from-purple-500 to-pink-500"></div>
                                    </div>
                                </div>
                                <!-- Bottom content -->
                                <div class="absolute bottom-4 left-3 right-14">
                                    <div class="flex items-center mb-2">
                                        <div v-if="brandLogoUrl" class="w-8 h-8 rounded-full overflow-hidden">
                                            <img :src="brandLogoUrl" :alt="displayBrandName" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                            {{ brandInitials }}
                                        </div>
                                        <span class="ml-2 text-white text-sm font-semibold">{{ displayBrandName }}</span>
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

                            <!-- Facebook Reel Mockup -->
                            <div v-else-if="selectedPlatform === 'facebook_reel'" class="bg-black rounded-2xl shadow-lg overflow-hidden aspect-[9/16] w-[280px] relative">
                                <!-- Background image/video -->
                                <img v-if="previewUrl && !isVideo" :src="previewUrl" class="absolute inset-0 w-full h-full object-cover" />
                                <!-- Video playing inline -->
                                <video
                                    v-else-if="previewUrl && isVideo && isPlayingVideo"
                                    :src="previewUrl"
                                    class="absolute inset-0 w-full h-full object-cover"
                                    autoplay
                                    @ended="isPlayingVideo = false"
                                />
                                <!-- Video thumbnail with play button -->
                                <template v-else-if="previewUrl && isVideo">
                                    <img v-if="videoThumbnail" :src="videoThumbnail" class="absolute inset-0 w-full h-full object-cover" />
                                    <div v-else class="absolute inset-0 bg-gray-900 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <button
                                        @click="isPlayingVideo = true"
                                        class="absolute inset-0 flex items-center justify-center z-10"
                                    >
                                        <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors">
                                            <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </div>
                                    </button>
                                    <!-- Duration badge -->
                                    <div v-if="videoDuration" class="absolute top-14 right-3 bg-black/70 text-white text-xs px-1.5 py-0.5 rounded z-20">
                                        {{ formatDuration(videoDuration) }}
                                    </div>
                                </template>
                                <div v-else class="absolute inset-0 bg-gradient-to-br from-blue-600 via-blue-500 to-indigo-500 flex items-center justify-center">
                                    <div class="text-center text-white">
                                        <svg class="mx-auto h-16 w-16 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <p class="mt-2 text-sm font-medium opacity-90">Your reel here</p>
                                    </div>
                                </div>
                                <!-- Bottom gradient overlay (reduced height) -->
                                <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-black/50 to-transparent pointer-events-none"></div>
                                <!-- Right sidebar actions -->
                                <div class="absolute right-2 bottom-16 flex flex-col items-center gap-4 drop-shadow-lg">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-6 h-6 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                        </svg>
                                        <span class="text-white text-xs mt-1 drop-shadow-md">1.2K</span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <svg class="w-6 h-6 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                        </svg>
                                        <span class="text-white text-xs mt-1 drop-shadow-md">48</span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <svg class="w-6 h-6 text-white drop-shadow-md" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <svg class="w-6 h-6 text-white drop-shadow-md" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z" />
                                        </svg>
                                    </div>
                                </div>
                                <!-- Bottom content with black bar -->
                                <div class="absolute bottom-3 left-2 right-12">
                                    <div class="bg-black/60 px-3 py-2 rounded-lg">
                                        <div class="flex items-center mb-1.5">
                                            <div v-if="brandLogoUrl" class="w-7 h-7 rounded-full overflow-hidden">
                                                <img :src="brandLogoUrl" :alt="displayBrandNameFb" class="w-full h-full object-cover" />
                                            </div>
                                            <div v-else class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold">
                                                {{ brandInitials }}
                                            </div>
                                            <span class="ml-2 text-white text-sm font-semibold">{{ displayBrandNameFb }}</span>
                                            <button class="ml-2 px-2 py-0.5 bg-blue-500 rounded text-white text-xs font-semibold">Follow</button>
                                        </div>
                                        <p class="text-white text-sm line-clamp-2">{{ truncatedCaption || 'Your caption here...' }}</p>
                                        <!-- Audio bar -->
                                        <div class="flex items-center mt-1.5">
                                            <svg class="w-3 h-3 text-white mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 18V5l12-2v13" /><circle cx="6" cy="18" r="3" /><circle cx="18" cy="16" r="3" />
                                            </svg>
                                            <span class="text-white text-xs">Original Audio</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Facebook Story Mockup -->
                            <div v-else class="bg-gray-900 rounded-2xl shadow-lg overflow-hidden aspect-[9/16] w-[280px] relative">
                                <!-- Background image/video -->
                                <img v-if="previewUrl && !isVideo" :src="previewUrl" class="absolute inset-0 w-full h-full object-cover" />
                                <!-- Video playing inline -->
                                <video
                                    v-else-if="previewUrl && isVideo && isPlayingVideo"
                                    :src="previewUrl"
                                    class="absolute inset-0 w-full h-full object-cover"
                                    autoplay
                                    @ended="isPlayingVideo = false"
                                />
                                <!-- Video thumbnail with play button -->
                                <template v-else-if="previewUrl && isVideo">
                                    <img v-if="videoThumbnail" :src="videoThumbnail" class="absolute inset-0 w-full h-full object-cover" />
                                    <div v-else class="absolute inset-0 bg-gray-900 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <button
                                        @click="isPlayingVideo = true"
                                        class="absolute inset-0 flex items-center justify-center z-10"
                                    >
                                        <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center hover:bg-black/80 transition-colors">
                                            <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </div>
                                    </button>
                                    <!-- Duration badge -->
                                    <div v-if="videoDuration" class="absolute top-14 right-3 bg-black/70 text-white text-xs px-1.5 py-0.5 rounded z-20">
                                        {{ formatDuration(videoDuration) }}
                                    </div>
                                </template>
                                <div v-else class="absolute inset-0 bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 flex items-center justify-center">
                                    <div class="text-center text-white">
                                        <svg class="mx-auto h-16 w-16 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="mt-2 text-sm font-medium opacity-90">Your story here</p>
                                    </div>
                                </div>
                                <!-- Story Header -->
                                <div class="absolute top-0 left-0 right-0 p-3 bg-gradient-to-b from-black/50 to-transparent">
                                    <!-- Progress bar -->
                                    <div class="w-full h-0.5 bg-white/30 rounded-full mb-3">
                                        <div class="h-full w-1/3 bg-white rounded-full"></div>
                                    </div>
                                    <!-- Profile -->
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center overflow-hidden border-2 border-blue-500">
                                            <img v-if="brandLogoUrl" :src="brandLogoUrl" class="w-full h-full object-cover" />
                                            <span v-else class="text-xs font-bold text-blue-600">{{ brandInitials }}</span>
                                        </div>
                                        <div>
                                            <span class="text-white text-sm font-medium block">{{ displayBrandNameFb }}</span>
                                            <span class="text-white/70 text-xs">2h ago</span>
                                        </div>
                                        <svg class="ml-auto w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="5" r="1.5" />
                                            <circle cx="12" cy="12" r="1.5" />
                                            <circle cx="12" cy="19" r="1.5" />
                                        </svg>
                                    </div>
                                </div>
                                <!-- Story Footer -->
                                <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/50 to-transparent">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 px-4 py-2 rounded-full border border-white/50 text-white/70 text-sm">
                                            Reply...
                                        </div>
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                        </svg>
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Export Button -->
                <div v-if="previewUrl" class="mt-6 text-center">
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

                <div class="mt-8 text-center">
                    <p class="text-gray-600 dark:text-gray-400 mb-4">Want to save your mockups and get team approvals?</p>
                    <RouterLink
                        to="/register"
                        class="inline-block bg-primary-600 text-white px-6 py-3 rounded-md font-medium hover:bg-primary-700"
                    >
                        Create Free Account
                    </RouterLink>
                </div>

                <!-- Discord Section -->
                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Join Our Community</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            We're still in beta. Join our Discord to get updates and share feedback.
                        </p>
                        <a
                            href="https://discord.gg/9RQWcmZdzR"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-4 inline-flex items-center gap-2 px-6 py-2 bg-[#5865F2] text-white rounded-md hover:bg-[#4752C4] transition-colors"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                            </svg>
                            Join Discord
                        </a>
                    </div>
                </div>
            </div>

            <div id="features" class="mt-24">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-12">Who It's For</h2>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow dark:shadow-gray-900/30">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Marketing Agencies</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Teams managing multiple client accounts who need streamlined approval workflows.
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow dark:shadow-gray-900/30">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Freelance Creators</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Social media managers and content creators seeking professional client interactions.
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow dark:shadow-gray-900/30">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">In-House Teams</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Brand teams coordinating with external stakeholders on content approval.
                        </p>
                    </div>
                </div>
            </div>
        </main>

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
