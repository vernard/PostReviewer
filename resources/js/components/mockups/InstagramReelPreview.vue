<template>
    <div
        class="w-[280px] mx-auto rounded-2xl overflow-hidden aspect-[9/16] relative transition-colors duration-300"
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-2 text-sm">{{ placeholderText }}</p>
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
                <svg class="w-7 h-7 text-white drop-shadow-md" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                </svg>
            </div>
            <div class="flex flex-col items-center">
                <svg class="w-7 h-7 text-white drop-shadow-md" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="1.5" />
                    <circle cx="6" cy="12" r="1.5" />
                    <circle cx="18" cy="12" r="1.5" />
                </svg>
            </div>
            <div class="w-7 h-7 rounded-full border-2 border-white shadow-md">
                <img v-if="brandLogoUrl" :src="brandLogoUrl" class="w-full h-full object-cover rounded-full" />
                <div v-else class="w-full h-full rounded-full" style="background: linear-gradient(to bottom right, #a855f7, #ec4899)"></div>
            </div>
        </div>

        <!-- Bottom content -->
        <div class="absolute bottom-4 left-3 right-14">
            <div class="flex items-center mb-2">
                <img v-if="brandLogoUrl" :src="brandLogoUrl" :alt="brandName" class="w-8 h-8 object-cover rounded-full" />
                <div v-else class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold" style="background: linear-gradient(to bottom right, #a855f7, #ec4899)">
                    {{ brandInitial }}
                </div>
                <span class="ml-2 text-white text-sm font-medium">{{ brandName }}</span>
                <button class="ml-2 px-2 py-0.5 border border-white rounded text-white text-xs">Follow</button>
            </div>
            <p v-if="caption" class="text-white text-sm line-clamp-2">{{ caption }}</p>
            <div class="flex items-center mt-2">
                <svg class="w-4 h-4 text-white/80 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                </svg>
                <span class="text-white/80 text-xs">Original audio</span>
            </div>
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
        default: 'video',
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
    placeholderText: {
        type: String,
        default: 'No video selected'
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
