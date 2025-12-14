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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-2 text-sm">No video selected</p>
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
        <div class="absolute bottom-0 left-0 right-0 h-48 bg-gradient-to-t from-black/80 to-transparent pointer-events-none"></div>

        <!-- Right sidebar actions -->
        <div class="absolute right-3 bottom-32 flex flex-col items-center gap-5 drop-shadow-lg">
            <!-- Like -->
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2 20h2c.55 0 1-.45 1-1v-9c0-.55-.45-1-1-1H2v11zm19.83-7.12c.11-.25.17-.52.17-.8V11c0-1.1-.9-2-2-2h-5.5l.92-4.65c.05-.22.02-.46-.08-.66-.23-.45-.52-.86-.88-1.22L14 2 7.59 8.41C7.21 8.79 7 9.3 7 9.83v7.84C7 18.95 8.05 20 9.34 20h8.11c.7 0 1.36-.37 1.72-.97l2.66-6.15z"/>
                    </svg>
                </div>
                <span class="text-white text-xs mt-1 drop-shadow-md">1.2K</span>
            </div>
            <!-- Comment -->
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18z"/>
                    </svg>
                </div>
                <span class="text-white text-xs mt-1 drop-shadow-md">48</span>
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
                <img v-if="brandLogoUrl" :src="brandLogoUrl" :alt="brandName" class="w-9 h-9 object-cover rounded-full border-2 border-white" />
                <div v-else class="w-9 h-9 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-bold border-2 border-white">
                    {{ brandInitial }}
                </div>
                <span class="ml-2 text-white text-sm font-semibold drop-shadow-md">{{ brandName }}</span>
                <span class="mx-1 text-white/60">·</span>
                <button class="text-white text-sm font-semibold drop-shadow-md">Follow</button>
            </div>
            <!-- Caption -->
            <p v-if="caption" class="text-white text-sm drop-shadow-md line-clamp-2 mb-2">{{ caption }}</p>
            <!-- Audio bar -->
            <div class="flex items-center">
                <svg class="w-4 h-4 text-white mr-2" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                </svg>
                <span class="text-white text-xs drop-shadow-md">Original audio · {{ brandName }}</span>
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
