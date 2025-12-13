<script setup>
import { computed } from 'vue';

const props = defineProps({
    used: {
        type: Number,
        required: true,
    },
    quota: {
        type: Number,
        required: true,
    },
    usedFormatted: {
        type: String,
        default: '',
    },
    quotaFormatted: {
        type: String,
        default: '',
    },
    showLabel: {
        type: Boolean,
        default: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const percentage = computed(() => {
    if (props.quota <= 0) return 0;
    return Math.min(100, Math.round((props.used / props.quota) * 100));
});

const barColor = computed(() => {
    if (percentage.value >= 90) return 'bg-red-500';
    if (percentage.value >= 70) return 'bg-yellow-500';
    return 'bg-primary-500';
});

const textColor = computed(() => {
    if (percentage.value >= 90) return 'text-red-600 dark:text-red-400';
    if (percentage.value >= 70) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-gray-600 dark:text-gray-400';
});

const displayUsed = computed(() => props.usedFormatted || formatBytes(props.used));
const displayQuota = computed(() => props.quotaFormatted || formatBytes(props.quota));

function formatBytes(bytes) {
    if (bytes >= 1073741824) {
        return (bytes / 1073741824).toFixed(1) + ' GB';
    } else if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(1) + ' MB';
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(1) + ' KB';
    }
    return bytes + ' B';
}
</script>

<template>
    <div :class="compact ? 'space-y-1' : 'space-y-2'">
        <div v-if="showLabel" class="flex items-center justify-between" :class="compact ? 'text-xs' : 'text-sm'">
            <span class="text-gray-500 dark:text-gray-400">Storage</span>
            <span :class="textColor">{{ displayUsed }} / {{ displayQuota }}</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden" :class="compact ? 'h-1.5' : 'h-2'">
            <div
                :class="barColor"
                class="h-full rounded-full transition-all duration-300"
                :style="{ width: percentage + '%' }"
            ></div>
        </div>
        <div v-if="!showLabel && !compact" class="text-xs text-center" :class="textColor">
            {{ percentage }}% used
        </div>
    </div>
</template>
