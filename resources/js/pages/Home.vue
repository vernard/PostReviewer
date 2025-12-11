<script setup>
import { ref, computed } from 'vue';
import { RouterLink } from 'vue-router';
import Footer from '@/components/Footer.vue';
import { useDarkMode } from '@/composables/useDarkMode';

const { isDark, toggle: toggleDarkMode } = useDarkMode();

const selectedFile = ref(null);
const previewUrl = ref(null);
const caption = ref('');
const selectedPlatform = ref('instagram');
const isVideo = ref(false);
const brandName = ref('');
const brandLogo = ref(null);
const brandLogoUrl = ref(null);
const imageDimensions = ref({ width: 0, height: 0 });
const isVertical = computed(() => imageDimensions.value.height > imageDimensions.value.width * 1.2);

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

    // Detect image dimensions
    if (file.type.startsWith('image/')) {
        const img = new Image();
        img.onload = () => {
            imageDimensions.value = { width: img.width, height: img.height };
        };
        img.src = URL.createObjectURL(file);
    } else if (file.type.startsWith('video/')) {
        const video = document.createElement('video');
        video.onloadedmetadata = () => {
            imageDimensions.value = { width: video.videoWidth, height: video.videoHeight };
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
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <nav class="bg-white dark:bg-gray-800 shadow dark:shadow-gray-700/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <span class="text-xl font-bold text-gray-900 dark:text-white">Post Reviewer</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button
                            @click="toggleDarkMode"
                            class="p-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700"
                        >
                            <svg v-if="isDark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                            </svg>
                        </button>
                        <RouterLink
                            to="/login"
                            class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                        >
                            Login
                        </RouterLink>
                        <RouterLink
                            to="/register"
                            class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700"
                        >
                            Get Started
                        </RouterLink>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white sm:text-5xl md:text-6xl">
                    Preview Your Social Media Posts
                    <span class="block text-primary-600 dark:text-primary-500">Before You Publish</span>
                </h1>
                <p class="mt-3 max-w-md mx-auto text-base text-gray-500 dark:text-gray-400 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                    Create pixel-perfect mockups of your Facebook and Instagram posts.
                    Get team approval before going live. Perfect for agencies and brands.
                </p>
                <div class="mt-10 flex justify-center gap-4">
                    <RouterLink
                        to="/register"
                        class="bg-primary-600 text-white px-8 py-3 rounded-md text-lg font-medium hover:bg-primary-700"
                    >
                        Start Free Trial
                    </RouterLink>
                    <a
                        href="#demo"
                        class="bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-500 px-8 py-3 rounded-md text-lg font-medium border border-primary-600 dark:border-primary-500 hover:bg-primary-50 dark:hover:bg-gray-700"
                    >
                        Try It Now
                    </a>
                </div>
            </div>

            <!-- Demo Section -->
            <div id="demo" class="mt-24 bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-gray-900/50 p-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Try It Now</h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Upload an image or video to see how it looks on social media</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Upload Section -->
                    <div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Platform</label>
                            <div class="flex gap-2">
                                <button
                                    @click="selectedPlatform = 'instagram'"
                                    :class="[
                                        'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                        selectedPlatform === 'instagram'
                                            ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    Instagram
                                </button>
                                <button
                                    @click="selectedPlatform = 'facebook'"
                                    :class="[
                                        'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                        selectedPlatform === 'facebook'
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    Facebook
                                </button>
                            </div>
                        </div>

                        <!-- Brand Settings -->
                        <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Brand Settings</h4>
                            <div class="grid grid-cols-2 gap-4">
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
                            class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-12 text-center hover:border-primary-500 transition-colors cursor-pointer bg-white dark:bg-gray-800"
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

                        <div v-else class="space-y-4">
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
                            </div>
                        </div>
                    </div>

                    <!-- Preview Section -->
                    <div>
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 min-h-[400px] flex items-center justify-center">
                            <div v-if="!previewUrl" class="text-center text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <p class="mt-2">Upload media to see preview</p>
                            </div>

                            <!-- Instagram Mockup -->
                            <div v-else-if="selectedPlatform === 'instagram'" class="bg-white rounded-lg shadow-lg w-full max-w-[375px]">
                                <!-- Header -->
                                <div class="flex items-center p-3 border-b">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-500 p-0.5">
                                        <div class="w-full h-full bg-white rounded-full flex items-center justify-center overflow-hidden">
                                            <img v-if="brandLogoUrl" :src="brandLogoUrl" class="w-full h-full object-cover" />
                                            <span v-else class="text-xs font-bold text-gray-800">{{ brandInitials }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
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
                                <div class="aspect-square bg-gray-200 overflow-hidden">
                                    <img v-if="!isVideo" :src="previewUrl" class="w-full h-full object-cover" />
                                    <video v-else :src="previewUrl" class="w-full h-full object-cover" autoplay muted loop />
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
                                        <span class="font-semibold">{{ displayBrandName }}</span>
                                        <span class="whitespace-pre-wrap">{{ truncatedCaption }}</span>
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

                            <!-- Facebook Mockup -->
                            <div v-else class="bg-white rounded-lg shadow-lg w-full max-w-[500px]">
                                <!-- Header -->
                                <div class="flex items-center p-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center overflow-hidden">
                                        <img v-if="brandLogoUrl" :src="brandLogoUrl" class="w-full h-full object-cover" />
                                        <span v-else class="text-white font-bold">{{ brandInitials }}</span>
                                    </div>
                                    <div class="ml-3">
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
                                    class="flex items-center justify-center"
                                    :class="isVertical ? 'bg-black' : 'bg-gray-200'"
                                >
                                    <img
                                        v-if="!isVideo"
                                        :src="previewUrl"
                                        :class="isVertical ? 'max-h-[500px] w-auto' : 'w-full'"
                                    />
                                    <video
                                        v-else
                                        :src="previewUrl"
                                        :class="isVertical ? 'max-h-[500px] w-auto' : 'w-full'"
                                        controls
                                    />
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
                        </div>
                    </div>
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
            </div>

            <div id="features" class="mt-24">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow dark:shadow-gray-900/30">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Realistic Mockups</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            See exactly how your posts will look on Facebook and Instagram before publishing.
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow dark:shadow-gray-900/30">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Team Approvals</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Streamlined approval workflow for your team. Comment, request changes, and approve.
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow dark:shadow-gray-900/30">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Multi-Brand Support</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Manage multiple brands and clients from a single dashboard. Perfect for agencies.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
