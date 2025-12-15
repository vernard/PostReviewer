<template>
    <div class="w-[280px] mx-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="flex items-center p-3">
            <div v-if="brandLogoUrl" class="w-10 h-10 rounded-full overflow-hidden">
                <img :src="brandLogoUrl" :alt="brandName" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                {{ brandInitial }}
            </div>
            <div class="ml-3">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ brandName }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Just now</p>
            </div>
        </div>

        <!-- Caption -->
        <div class="px-3 pb-2">
            <p class="text-sm whitespace-pre-wrap text-gray-900 dark:text-white">{{ displayCaption }}</p>
            <button
                v-if="isCaptionTruncated && !captionExpanded"
                @click="captionExpanded = true"
                class="text-blue-600 hover:underline text-sm"
            >See more</button>
            <button
                v-if="isCaptionTruncated && captionExpanded"
                @click="captionExpanded = false"
                class="text-blue-600 hover:underline text-sm block"
            >See less</button>
        </div>

        <!-- Image/Video -->
        <div class="aspect-square relative transition-colors duration-300" :style="{ backgroundColor: backgroundColor || '#f3f4f6' }">
            <!-- Processing state -->
            <div
                v-if="mediaStatus === 'processing'"
                class="w-full h-full flex flex-col items-center justify-center"
            >
                <svg class="w-12 h-12 text-gray-400 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/>
                </svg>
                <span class="text-gray-400 text-sm mt-2">Processing video...</span>
            </div>

            <!-- Video playing inline -->
            <video
                v-else-if="isPlaying && mediaType === 'video'"
                :src="mediaUrl"
                class="w-full h-full object-contain"
                autoplay
                controls
                @click.stop
                @ended="isPlaying = false"
            />

            <!-- Image/thumbnail -->
            <img
                v-else-if="displayUrl"
                :src="displayUrl"
                class="w-full h-full object-contain"
            />

            <!-- Placeholder -->
            <div v-else class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500">
                <slot name="placeholder">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="mt-2 text-sm">{{ placeholderText }}</p>
                    </div>
                </slot>
            </div>

            <!-- Video play button -->
            <button
                v-if="mediaType === 'video' && !isPlaying && mediaStatus !== 'processing' && displayUrl"
                @click="isPlaying = true"
                class="absolute inset-0 flex items-center justify-center bg-black/20 hover:bg-black/30 transition-colors"
            >
                <div class="w-16 h-16 rounded-full bg-black/60 flex items-center justify-center">
                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </div>
            </button>
        </div>

        <!-- Engagement stats -->
        <div class="px-3 py-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-1">
                <div class="flex -space-x-1">
                    <span class="w-4 h-4 rounded-full bg-blue-500 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2 20h2c.55 0 1-.45 1-1v-9c0-.55-.45-1-1-1H2v11zm19.83-7.12c.11-.25.17-.52.17-.8V11c0-1.1-.9-2-2-2h-5.5l.92-4.65c.05-.22.02-.46-.08-.66-.23-.45-.52-.86-.88-1.22L14 2 7.59 8.41C7.21 8.79 7 9.3 7 9.83v7.84C7 18.95 8.05 20 9.34 20h8.11c.7 0 1.36-.37 1.72-.97l2.66-6.15z"/>
                        </svg>
                    </span>
                    <span class="w-4 h-4 rounded-full bg-red-500 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </span>
                </div>
                <span>1.2K</span>
            </div>
            <span>48 comments · 12 shares</span>
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
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    brandName: {
        type: String,
        default: 'Brand Name'
    },
    brandLogoUrl: {
        type: String,
        default: null
    },
    mediaUrl: {
        type: String,
        default: null
    },
    thumbnailUrl: {
        type: String,
        default: null
    },
    mediaType: {
        type: String,
        default: 'image',
        validator: (value) => ['image', 'video'].includes(value)
    },
    mediaStatus: {
        type: String,
        default: 'ready',
        validator: (value) => ['ready', 'processing'].includes(value)
    },
    caption: {
        type: String,
        default: ''
    },
    backgroundColor: {
        type: String,
        default: null
    },
    captionLimit: {
        type: Number,
        default: 125
    },
    placeholderText: {
        type: String,
        default: 'No image selected'
    }
});

const isPlaying = ref(false);
const captionExpanded = ref(false);

const brandInitial = computed(() => {
    return props.brandName?.charAt(0)?.toUpperCase() || 'B';
});

const displayUrl = computed(() => {
    if (props.mediaType === 'video' && props.thumbnailUrl) {
        return props.thumbnailUrl;
    }
    return props.mediaUrl;
});

const isCaptionTruncated = computed(() => {
    return props.caption && props.caption.length > props.captionLimit;
});

const displayCaption = computed(() => {
    if (!props.caption) return 'Your caption here...';
    if (captionExpanded.value || !isCaptionTruncated.value) {
        return props.caption;
    }
    return props.caption.substring(0, props.captionLimit) + '...';
});
</script>
