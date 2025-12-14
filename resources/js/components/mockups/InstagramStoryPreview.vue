<template>
    <div
        class="max-w-[280px] mx-auto rounded-2xl overflow-hidden aspect-[9/16] relative transition-colors duration-300"
        :style="{ backgroundColor: backgroundColor || '#000' }"
    >
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
            class="w-full h-full object-cover"
            autoplay
            controls
            @click.stop
            @ended="isPlaying = false"
        />

        <!-- Image/thumbnail -->
        <img
            v-else-if="displayUrl"
            :src="displayUrl"
            class="w-full h-full object-cover"
        />

        <!-- Placeholder -->
        <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
            <slot name="placeholder">
                <div class="text-center text-white">
                    <svg class="mx-auto h-16 w-16 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-2 text-sm">No image selected</p>
                </div>
            </slot>
        </div>

        <!-- Video play button -->
        <button
            v-if="mediaType === 'video' && !isPlaying && mediaStatus !== 'processing' && displayUrl"
            @click="isPlaying = true"
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
                    <div v-if="brandLogoUrl" class="w-9 h-9 rounded-full overflow-hidden border-2 border-black bg-white">
                        <img :src="brandLogoUrl" :alt="brandName" class="w-full h-full object-cover" />
                    </div>
                    <div v-else class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold border-2 border-black">
                        {{ brandInitial }}
                    </div>
                </div>
                <span class="ml-2 text-white text-sm font-medium">{{ brandName }}</span>
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
    backgroundColor: {
        type: String,
        default: null
    }
});

const isPlaying = ref(false);

const brandInitial = computed(() => {
    return props.brandName?.charAt(0)?.toUpperCase() || 'B';
});

const displayUrl = computed(() => {
    if (props.mediaType === 'video' && props.thumbnailUrl) {
        return props.thumbnailUrl;
    }
    return props.mediaUrl;
});
</script>
