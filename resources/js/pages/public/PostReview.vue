<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const token = computed(() => route.params.token);

const data = ref(null);
const loading = ref(true);
const error = ref('');
const submitting = ref(false);
const submitSuccess = ref(false);
const submitResult = ref(null);

// Feedback modal
const showFeedbackModal = ref(false);
const feedbackText = ref('');
const approvalComment = ref('');

const fetchPost = async () => {
    try {
        loading.value = true;
        const response = await axios.get(`/api/public/review/${token.value}`);
        data.value = response.data;
    } catch (err) {
        if (err.response?.status === 404) {
            error.value = 'This review link is invalid or has expired.';
        } else {
            error.value = 'Failed to load post for review.';
        }
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const approve = async () => {
    try {
        submitting.value = true;
        await axios.post(`/api/public/review/${token.value}/approve`, {
            comment: approvalComment.value || null,
        });
        submitSuccess.value = true;
        submitResult.value = 'approved';
    } catch (err) {
        error.value = 'Failed to submit approval. Please try again.';
        console.error(err);
    } finally {
        submitting.value = false;
    }
};

const openFeedbackModal = () => {
    feedbackText.value = '';
    showFeedbackModal.value = true;
};

const submitChangesRequest = async () => {
    if (!feedbackText.value.trim()) return;

    try {
        submitting.value = true;
        showFeedbackModal.value = false;
        await axios.post(`/api/public/review/${token.value}/request-changes`, {
            comment: feedbackText.value,
        });
        submitSuccess.value = true;
        submitResult.value = 'changes_requested';
    } catch (err) {
        error.value = 'Failed to submit feedback. Please try again.';
        console.error(err);
    } finally {
        submitting.value = false;
    }
};

const getMediaUrl = (post) => {
    if (post.media?.length > 0) {
        const media = post.media[0];
        return media.thumbnail_url || media.url || `/storage/${media.file_path}`;
    }
    return null;
};

const isVideo = (post) => {
    if (post.media?.length > 0) {
        return post.media[0].type === 'video';
    }
    return false;
};

const formatPlatform = (platform) => {
    const names = {
        instagram_feed: 'Instagram Feed',
        instagram_story: 'Instagram Story',
        instagram_reel: 'Instagram Reel',
        facebook_feed: 'Facebook Feed',
        facebook_story: 'Facebook Story',
    };
    return names[platform] || platform;
};

onMounted(fetchPost);
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Post Review</h1>
                        <p v-if="data" class="text-sm text-gray-500">
                            Requested by {{ data.requester.name }}
                        </p>
                    </div>
                    <div v-if="data && !submitSuccess" class="text-right">
                        <p class="text-xs text-gray-400">Reviewing as</p>
                        <p class="text-sm font-medium text-gray-700">{{ data.invite.email }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-600"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error && !data" class="max-w-md mx-auto mt-20 text-center px-4">
            <svg class="mx-auto h-16 w-16 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h2 class="mt-4 text-lg font-medium text-gray-900">{{ error }}</h2>
            <p class="mt-2 text-sm text-gray-500">Please contact the person who sent you this link.</p>
        </div>

        <!-- Success -->
        <div v-else-if="submitSuccess" class="max-w-md mx-auto mt-20 text-center px-4">
            <svg
                :class="[
                    'mx-auto h-16 w-16',
                    submitResult === 'approved' ? 'text-green-400' : 'text-yellow-400'
                ]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2 class="mt-4 text-lg font-medium text-gray-900">
                {{ submitResult === 'approved' ? 'Post Approved!' : 'Changes Requested!' }}
            </h2>
            <p class="mt-2 text-sm text-gray-500">
                {{ submitResult === 'approved'
                    ? 'Thank you! The content creator has been notified of your approval.'
                    : 'Thank you for your feedback. The content creator has been notified.'
                }}
            </p>
        </div>

        <!-- Content -->
        <main v-else-if="data" class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Error banner -->
            <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-600">{{ error }}</p>
            </div>

            <!-- Already responded notice -->
            <div v-if="data.invite.has_responded" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-700">
                    You've already submitted a review for this post. You can update your response below.
                </p>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- Brand Info -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <img
                        v-if="data.brand.logo_flat_url"
                        :src="data.brand.logo_flat_url"
                        :alt="data.brand.name"
                        class="h-10 w-10 rounded-full object-cover"
                    />
                    <div v-else class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                        <span class="text-primary-600 font-semibold">{{ data.brand.name.charAt(0) }}</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ data.brand.name }}</p>
                        <p class="text-sm text-gray-500">{{ data.post.title }}</p>
                    </div>
                </div>

                <!-- Post Media -->
                <div class="aspect-square bg-gray-100 relative">
                    <img
                        v-if="getMediaUrl(data.post)"
                        :src="getMediaUrl(data.post)"
                        :alt="data.post.title"
                        class="w-full h-full object-contain"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <!-- Video indicator -->
                    <div v-if="isVideo(data.post)" class="absolute bottom-3 right-3 px-2 py-1 bg-black/70 text-white text-xs rounded">
                        Video
                    </div>
                </div>

                <!-- Post Details -->
                <div class="px-6 py-4">
                    <!-- Caption -->
                    <div v-if="data.post.caption" class="mb-4">
                        <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Caption</h3>
                        <p class="text-gray-700 whitespace-pre-wrap">{{ data.post.caption }}</p>
                    </div>

                    <!-- Platforms -->
                    <div v-if="data.post.platforms?.length" class="mb-4">
                        <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Platforms</h3>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="platform in data.post.platforms"
                                :key="platform"
                                class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full"
                            >
                                {{ formatPlatform(platform) }}
                            </span>
                        </div>
                    </div>

                    <!-- Optional approval comment -->
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                            Comment (optional)
                        </label>
                        <textarea
                            v-model="approvalComment"
                            rows="2"
                            class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="Add a comment with your approval..."
                        ></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex gap-3">
                    <button
                        @click="approve"
                        :disabled="submitting"
                        class="flex-1 px-4 py-3 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ submitting ? 'Submitting...' : 'Approve' }}
                    </button>
                    <button
                        @click="openFeedbackModal"
                        :disabled="submitting"
                        class="flex-1 px-4 py-3 text-sm font-medium text-yellow-700 bg-yellow-100 rounded-lg hover:bg-yellow-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Request Changes
                    </button>
                </div>
            </div>

            <!-- Expiration notice -->
            <p class="mt-4 text-center text-xs text-gray-400">
                This link expires on {{ new Date(data.invite.expires_at).toLocaleDateString() }}
            </p>
        </main>

        <!-- Feedback Modal -->
        <Teleport to="body">
            <div
                v-if="showFeedbackModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                    <h3 class="text-lg font-medium text-gray-900">Request Changes</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Describe what changes you'd like to see on this post.
                    </p>

                    <textarea
                        v-model="feedbackText"
                        rows="4"
                        class="mt-4 w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Please update the caption to mention..., The image should be..., etc."
                        autofocus
                    ></textarea>

                    <div class="mt-4 flex justify-end gap-3">
                        <button
                            @click="showFeedbackModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                        >
                            Cancel
                        </button>
                        <button
                            @click="submitChangesRequest"
                            :disabled="!feedbackText.trim()"
                            :class="[
                                'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                                feedbackText.trim()
                                    ? 'text-white bg-yellow-500 hover:bg-yellow-600'
                                    : 'text-gray-400 bg-gray-100 cursor-not-allowed'
                            ]"
                        >
                            Request Changes
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
