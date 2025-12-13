<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const token = computed(() => route.params.token);

const collection = ref(null);
const loading = ref(true);
const error = ref('');
const submitting = ref(false);
const submitSuccess = ref(false);

// Track changes per post
const postFeedback = ref({});
const postStatuses = ref({});
const captionEdits = ref({});

// Preview modal
const previewPost = ref(null);
const showPreviewModal = ref(false);

// Feedback modal
const feedbackPost = ref(null);
const showFeedbackModal = ref(false);
const feedbackText = ref('');

const fetchCollection = async () => {
    try {
        loading.value = true;
        const response = await axios.get(`/api/public/approval/${token.value}`);
        collection.value = response.data;

        // Initialize status tracking
        collection.value.posts.forEach(post => {
            postStatuses.value[post.id] = post.status === 'approved' ? 'approved' : null;
            captionEdits.value[post.id] = post.caption;
            postFeedback.value[post.id] = '';
        });
    } catch (err) {
        if (err.response?.status === 404) {
            error.value = 'This approval link is invalid or has expired.';
        } else {
            error.value = 'Failed to load collection for review.';
        }
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const approvePost = (post) => {
    postStatuses.value[post.id] = 'approved';
    postFeedback.value[post.id] = '';
};

const openFeedbackModal = (post) => {
    feedbackPost.value = post;
    feedbackText.value = postFeedback.value[post.id] || '';
    showFeedbackModal.value = true;
};

const submitFeedback = () => {
    if (feedbackPost.value) {
        postStatuses.value[feedbackPost.value.id] = 'changes_requested';
        postFeedback.value[feedbackPost.value.id] = feedbackText.value;
    }
    showFeedbackModal.value = false;
    feedbackPost.value = null;
    feedbackText.value = '';
};

const openPreview = (post) => {
    previewPost.value = post;
    showPreviewModal.value = true;
};

const closePreview = () => {
    showPreviewModal.value = false;
    previewPost.value = null;
};

const approveAll = () => {
    collection.value.posts.forEach(post => {
        if (!postStatuses.value[post.id]) {
            postStatuses.value[post.id] = 'approved';
        }
    });
};

const pendingCount = computed(() => {
    return collection.value?.posts.filter(p => !postStatuses.value[p.id]).length || 0;
});

const approvedCount = computed(() => {
    return collection.value?.posts.filter(p => postStatuses.value[p.id] === 'approved').length || 0;
});

const changesRequestedCount = computed(() => {
    return collection.value?.posts.filter(p => postStatuses.value[p.id] === 'changes_requested').length || 0;
});

const canSubmit = computed(() => {
    if (!collection.value) return false;
    return collection.value.posts.every(p => postStatuses.value[p.id]);
});

const submitReview = async () => {
    if (!canSubmit.value) return;

    try {
        submitting.value = true;

        const reviews = collection.value.posts.map(post => ({
            post_id: post.id,
            status: postStatuses.value[post.id],
            feedback: postFeedback.value[post.id] || null,
            caption_suggestion: captionEdits.value[post.id] !== post.caption ? captionEdits.value[post.id] : null,
        }));

        await axios.post(`/api/public/approval/${token.value}/submit`, { reviews });
        submitSuccess.value = true;
    } catch (err) {
        error.value = 'Failed to submit review. Please try again.';
        console.error(err);
    } finally {
        submitting.value = false;
    }
};

const getStatusBadgeClass = (status) => {
    const classes = {
        approved: 'bg-green-100 text-green-700',
        changes_requested: 'bg-yellow-100 text-yellow-700',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getMediaUrl = (post) => {
    if (post.media?.length > 0) {
        return post.media[0].url || `/storage/${post.media[0].file_path}`;
    }
    return null;
};

onMounted(fetchCollection);
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Content Review</h1>
                        <p v-if="collection" class="text-sm text-gray-500">{{ collection.name }}</p>
                    </div>
                    <div v-if="collection && !submitSuccess" class="flex items-center gap-4">
                        <div class="text-sm text-gray-500">
                            <span class="text-green-600 font-medium">{{ approvedCount }}</span> approved,
                            <span class="text-yellow-600 font-medium">{{ changesRequestedCount }}</span> changes,
                            <span class="text-gray-600 font-medium">{{ pendingCount }}</span> pending
                        </div>
                        <button
                            v-if="pendingCount > 0"
                            @click="approveAll"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors"
                        >
                            Approve All Remaining
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-600"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="max-w-md mx-auto mt-20 text-center">
            <svg class="mx-auto h-16 w-16 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h2 class="mt-4 text-lg font-medium text-gray-900">{{ error }}</h2>
            <p class="mt-2 text-sm text-gray-500">Please contact the person who sent you this link.</p>
        </div>

        <!-- Success -->
        <div v-else-if="submitSuccess" class="max-w-md mx-auto mt-20 text-center">
            <svg class="mx-auto h-16 w-16 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2 class="mt-4 text-lg font-medium text-gray-900">Review Submitted!</h2>
            <p class="mt-2 text-sm text-gray-500">
                Thank you for your feedback. The content creator has been notified of your review.
            </p>
        </div>

        <!-- Content -->
        <main v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Brand Info -->
            <div v-if="collection.brand" class="mb-6 flex items-center gap-3">
                <img
                    v-if="collection.brand.logo_flat_url"
                    :src="collection.brand.logo_flat_url"
                    :alt="collection.brand.name"
                    class="h-10 w-10 rounded-full object-cover"
                />
                <div>
                    <p class="text-sm text-gray-500">Brand</p>
                    <p class="font-medium text-gray-900">{{ collection.brand.name }}</p>
                </div>
            </div>

            <!-- Posts Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="post in collection.posts"
                    :key="post.id"
                    class="bg-white rounded-lg shadow overflow-hidden"
                >
                    <!-- Post Image -->
                    <div class="relative aspect-square bg-gray-100">
                        <img
                            v-if="getMediaUrl(post)"
                            :src="getMediaUrl(post)"
                            :alt="post.title"
                            class="w-full h-full object-cover cursor-pointer"
                            @click="openPreview(post)"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <!-- Status Badge -->
                        <div
                            v-if="postStatuses[post.id]"
                            class="absolute top-2 right-2"
                        >
                            <span
                                :class="[getStatusBadgeClass(postStatuses[post.id]), 'px-2 py-1 text-xs font-medium rounded-full']"
                            >
                                {{ postStatuses[post.id] === 'approved' ? 'Approved' : 'Changes Requested' }}
                            </span>
                        </div>
                    </div>

                    <!-- Post Content -->
                    <div class="p-4">
                        <h3 class="font-medium text-gray-900 truncate">{{ post.title }}</h3>

                        <!-- Caption (editable) -->
                        <div class="mt-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Caption</label>
                            <textarea
                                v-model="captionEdits[post.id]"
                                rows="3"
                                class="w-full text-sm text-gray-600 border border-gray-200 rounded-md p-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                placeholder="Edit caption suggestion..."
                            ></textarea>
                            <p v-if="captionEdits[post.id] !== post.caption" class="text-xs text-primary-600 mt-1">
                                Caption modified
                            </p>
                        </div>

                        <!-- Platforms -->
                        <div class="mt-2 flex flex-wrap gap-1">
                            <span
                                v-for="platform in post.platforms"
                                :key="platform"
                                class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded"
                            >
                                {{ platform }}
                            </span>
                        </div>

                        <!-- Feedback Display -->
                        <div v-if="postFeedback[post.id]" class="mt-3 p-2 bg-yellow-50 rounded-md">
                            <p class="text-xs font-medium text-yellow-700">Your feedback:</p>
                            <p class="text-sm text-yellow-800 mt-1">{{ postFeedback[post.id] }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 flex gap-2">
                            <button
                                @click="approvePost(post)"
                                :class="[
                                    'flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                                    postStatuses[post.id] === 'approved'
                                        ? 'bg-green-600 text-white'
                                        : 'bg-green-50 text-green-700 hover:bg-green-100'
                                ]"
                            >
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Approve
                            </button>
                            <button
                                @click="openFeedbackModal(post)"
                                :class="[
                                    'flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                                    postStatuses[post.id] === 'changes_requested'
                                        ? 'bg-yellow-500 text-white'
                                        : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100'
                                ]"
                            >
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-8 sticky bottom-0 bg-gray-50 py-4 border-t border-gray-200">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        <template v-if="pendingCount > 0">
                            {{ pendingCount }} post(s) still need your review
                        </template>
                        <template v-else>
                            All posts reviewed. Ready to submit!
                        </template>
                    </p>
                    <button
                        @click="submitReview"
                        :disabled="!canSubmit || submitting"
                        :class="[
                            'px-6 py-3 text-sm font-medium rounded-lg transition-colors',
                            canSubmit && !submitting
                                ? 'bg-primary-600 text-white hover:bg-primary-700'
                                : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                        ]"
                    >
                        <span v-if="submitting">Submitting...</span>
                        <span v-else>Submit Review</span>
                    </button>
                </div>
            </div>
        </main>

        <!-- Preview Modal -->
        <Teleport to="body">
            <div
                v-if="showPreviewModal && previewPost"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
                @click="closePreview"
            >
                <div class="max-w-4xl max-h-[90vh] overflow-auto" @click.stop>
                    <img
                        :src="getMediaUrl(previewPost)"
                        :alt="previewPost.title"
                        class="max-w-full max-h-[90vh] object-contain"
                    />
                </div>
                <button
                    @click="closePreview"
                    class="absolute top-4 right-4 p-2 text-white hover:text-gray-300"
                >
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Teleport>

        <!-- Feedback Modal -->
        <Teleport to="body">
            <div
                v-if="showFeedbackModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            >
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
                    <h3 class="text-lg font-medium text-gray-900">Request Changes</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Provide feedback for "{{ feedbackPost?.title }}"
                    </p>

                    <textarea
                        v-model="feedbackText"
                        rows="4"
                        class="mt-4 w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Describe what changes you'd like to see..."
                    ></textarea>

                    <div class="mt-4 flex justify-end gap-3">
                        <button
                            @click="showFeedbackModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                        >
                            Cancel
                        </button>
                        <button
                            @click="submitFeedback"
                            class="px-4 py-2 text-sm font-medium text-white bg-yellow-500 rounded-lg hover:bg-yellow-600"
                        >
                            Request Changes
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
