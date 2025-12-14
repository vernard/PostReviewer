<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { postApi, commentApi, brandApi } from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import html2canvas from 'html2canvas-pro';
import axios from 'axios';
import { validatePost } from '@/utils/platformValidation';
import {
    InstagramFeedPreview,
    FacebookFeedPreview,
    InstagramStoryPreview,
    InstagramReelPreview,
    FacebookStoryPreview,
    FacebookReelPreview
} from '@/components/mockups';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const postId = route.params.id;

const post = ref(null);
const loading = ref(true);
const error = ref('');
const activePreview = ref('');
const showFullCaption = ref(false);

const newComment = ref('');
const submittingComment = ref(false);

// Invite reviewers state
const showInviteModal = ref(false);
const reviewerEmails = ref('');
const saveAsDefault = ref(false);
const sendingInvites = ref(false);
const inviteError = ref('');
const inviteSuccess = ref('');

const canManage = computed(() => {
    return authStore.user?.role === 'admin' || authStore.user?.role === 'manager';
});

const canEdit = computed(() => {
    if (!post.value) return false;
    return post.value.created_by === authStore.user?.id || canManage.value;
});

const canSubmitForApproval = computed(() => {
    if (!post.value) return false;
    return ['draft', 'changes_requested'].includes(post.value.status) && canEdit.value;
});

const canInviteReviewers = computed(() => {
    if (!post.value) return false;
    return post.value.status === 'pending_approval';
});

const truncatedCaption = computed(() => {
    if (!post.value?.caption) return '';
    if (post.value.caption.length <= 125 || showFullCaption.value) return post.value.caption;
    return post.value.caption.substring(0, 125) + '...';
});

// Platform validation
const validation = computed(() => {
    if (!post.value) return { errors: [], warnings: [] };
    const media = post.value.media?.length > 0 ? post.value.media[0] : null;
    return validatePost(post.value.platforms || [], media, post.value.caption);
});

const fetchPost = async () => {
    try {
        loading.value = true;
        const response = await postApi.get(postId);
        post.value = response.data.post || response.data;
        if (post.value.platforms?.length > 0) {
            activePreview.value = post.value.platforms[0];
        }
    } catch (err) {
        error.value = 'Failed to load post';
        console.error('Failed to fetch post:', err);
    } finally {
        loading.value = false;
    }
};

const submitForApproval = async () => {
    try {
        await postApi.submitForApproval(postId);
        await fetchPost();
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to submit for approval');
    }
};

const deletePost = async () => {
    if (!confirm('Are you sure you want to delete this post?')) return;

    try {
        await postApi.delete(postId);
        router.push('/posts');
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to delete post');
    }
};

const addComment = async () => {
    if (!newComment.value.trim()) return;

    try {
        submittingComment.value = true;
        await commentApi.create(postId, { body: newComment.value });
        newComment.value = '';
        await fetchPost();
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to add comment');
    } finally {
        submittingComment.value = false;
    }
};

const openInviteModal = async () => {
    inviteError.value = '';
    inviteSuccess.value = '';
    reviewerEmails.value = '';
    saveAsDefault.value = false;

    // Fetch default reviewers for this brand
    if (post.value?.brand?.id) {
        try {
            const response = await axios.get(`/api/brands/${post.value.brand.id}/default-reviewers`);
            const defaults = response.data.default_reviewers || [];
            if (defaults.length > 0) {
                reviewerEmails.value = defaults.join(', ');
            }
        } catch (err) {
            // Silently fail - defaults are optional
        }
    }

    showInviteModal.value = true;
};

const parseEmails = (input) => {
    return input
        .split(/[,\n]+/)
        .map(e => e.trim().toLowerCase())
        .filter(e => e && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e));
};

const sendInvites = async () => {
    const emails = parseEmails(reviewerEmails.value);

    if (emails.length === 0) {
        inviteError.value = 'Please enter at least one valid email address.';
        return;
    }

    if (emails.length > 10) {
        inviteError.value = 'Maximum 10 reviewer emails allowed.';
        return;
    }

    try {
        sendingInvites.value = true;
        inviteError.value = '';

        await axios.post(`/api/posts/${postId}/invite-reviewers`, {
            reviewer_emails: emails,
            save_as_default: saveAsDefault.value,
        });

        inviteSuccess.value = `Sent ${emails.length} invitation(s) successfully!`;
        await fetchPost();

        // Close modal after delay
        setTimeout(() => {
            showInviteModal.value = false;
        }, 1500);
    } catch (err) {
        inviteError.value = err.response?.data?.message || 'Failed to send invitations.';
    } finally {
        sendingInvites.value = false;
    }
};

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        pending_approval: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        changes_requested: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        published: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
};

const formatStatus = (status) => {
    return status?.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) || '';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const mockupRef = ref(null);
const exporting = ref(false);

// Convert an image URL to a data URL to avoid CORS issues with html2canvas
const imageToDataUrl = async (url) => {
    if (!url) return null;
    try {
        // Check if this is a media URL that we can proxy through the API
        const mediaMatch = url.match(/\/storage\/brands\/\d+\/media\/([^/]+)/);
        if (mediaMatch && post.value?.media) {
            // Find the media by matching the filename
            const filename = mediaMatch[1];
            const media = post.value.media.find(m => m.url.includes(filename));
            if (media) {
                // Use the stream API endpoint to fetch via the authenticated API
                const response = await axios.get(`/api/media/${media.id}/stream`, {
                    responseType: 'blob'
                });
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.readAsDataURL(response.data);
                });
            }
        }
        // Fallback: try direct fetch
        const response = await fetch(url);
        const blob = await response.blob();
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.readAsDataURL(blob);
        });
    } catch (err) {
        console.warn('Failed to convert image to data URL:', url, err);
        return null;
    }
};

// Create a safe filename from text
const slugify = (text) => {
    return text
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim()
        .substring(0, 50);
};

const exportAsJpeg = async () => {
    if (exporting.value) return;

    if (!mockupRef.value) {
        alert('No mockup to export.');
        return;
    }

    try {
        exporting.value = true;

        // Wait a tick for Vue to finish any pending updates
        await new Promise(resolve => setTimeout(resolve, 100));

        // Convert all images in the mockup to data URLs to avoid CORS issues
        const images = mockupRef.value.querySelectorAll('img');
        const originalSrcs = new Map();

        await Promise.all(Array.from(images).map(async (img) => {
            const originalSrc = img.src;
            if (originalSrc && !originalSrc.startsWith('data:')) {
                originalSrcs.set(img, originalSrc);
                const dataUrl = await imageToDataUrl(originalSrc);
                if (dataUrl) {
                    img.src = dataUrl;
                }
            }
        }));

        // Wait for images to update in the DOM
        await new Promise(resolve => setTimeout(resolve, 50));

        const canvas = await html2canvas(mockupRef.value, {
            backgroundColor: '#ffffff',
            scale: 2,
            useCORS: true,
            allowTaint: true,
            logging: false,
        });

        // Restore original image sources
        originalSrcs.forEach((src, img) => {
            img.src = src;
        });

        const link = document.createElement('a');
        const platformName = activePreview.value.replace(/_/g, '-');
        const postTitle = post.value.title ? slugify(post.value.title) : 'post';
        const brandName = post.value.brand?.name ? slugify(post.value.brand.name) : 'brand';
        link.download = `${brandName}-${postTitle}-${platformName}.jpg`;
        link.href = canvas.toDataURL('image/jpeg', 0.95);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (err) {
        console.error('Export failed:', err);
        alert('Failed to export mockup: ' + err.message);
    } finally {
        exporting.value = false;
    }
};

onMounted(fetchPost);
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Loading -->
                <div v-if="loading" class="animate-pulse">
                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-48 mb-4"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-96"></div>
                </div>

                <!-- Error -->
                <div v-else-if="error" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded">
                    {{ error }}
                    <RouterLink to="/posts" class="ml-4 underline">Back to posts</RouterLink>
                </div>

                <!-- Content -->
                <div v-else>
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <RouterLink to="/posts" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </RouterLink>
                            <div>
                                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ post.title }}</h1>
                                <div class="flex items-center gap-2 mt-1">
                                    <RouterLink :to="`/brands/${post.brand?.id}`" class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300">
                                        {{ post.brand?.name }}
                                    </RouterLink>
                                    <span class="text-gray-300 dark:text-gray-600">|</span>
                                    <span :class="[getStatusColor(post.status), 'px-2 py-0.5 text-xs font-medium rounded-full']">
                                        {{ formatStatus(post.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="canSubmitForApproval"
                                @click="submitForApproval"
                                class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600"
                            >
                                Submit for Approval
                            </button>
                            <button
                                v-if="canInviteReviewers"
                                @click="openInviteModal"
                                class="px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Invite Reviewers
                            </button>
                            <RouterLink
                                v-if="canEdit && post.status !== 'approved'"
                                :to="`/posts/${postId}/edit`"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                Edit
                            </RouterLink>
                            <button
                                v-if="canEdit"
                                @click="deletePost"
                                class="px-4 py-2 border border-red-300 dark:border-red-600 rounded-md text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Preview Column -->
                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Preview</h2>
                                    <div v-if="post.platforms?.length > 0" class="flex gap-2">
                                        <button
                                            v-for="platform in post.platforms"
                                            :key="platform"
                                            @click="activePreview = platform"
                                            :class="[
                                                'px-3 py-1 text-xs rounded-full',
                                                activePreview === platform
                                                    ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                            ]"
                                        >
                                            {{ platform.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()) }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Instagram Feed Preview -->
                                <div v-if="activePreview.includes('instagram_feed')" ref="mockupRef">
                                    <InstagramFeedPreview
                                        :brand-name="post.brand?.instagram_handle || post.brand?.name || 'Brand Name'"
                                        :brand-logo-url="post.brand?.logo_flat_url"
                                        :media-url="post.media?.[0]?.url"
                                        :thumbnail-url="post.media?.[0]?.thumbnail_url"
                                        :media-type="post.media?.[0]?.type || 'image'"
                                        :caption="post.caption"
                                    />
                                </div>

                                <!-- Facebook Feed Preview -->
                                <div v-else-if="activePreview.includes('facebook_feed')" ref="mockupRef">
                                    <FacebookFeedPreview
                                        :brand-name="post.brand?.facebook_page_name || post.brand?.name || 'Brand Name'"
                                        :brand-logo-url="post.brand?.logo_flat_url"
                                        :media-url="post.media?.[0]?.url"
                                        :thumbnail-url="post.media?.[0]?.thumbnail_url"
                                        :media-type="post.media?.[0]?.type || 'image'"
                                        :caption="post.caption"
                                    />
                                </div>

                                <!-- Instagram Story Preview -->
                                <div v-else-if="activePreview.includes('instagram_story')" ref="mockupRef">
                                    <InstagramStoryPreview
                                        :brand-name="post.brand?.instagram_handle || post.brand?.name || 'Brand Name'"
                                        :brand-logo-url="post.brand?.logo_flat_url"
                                        :media-url="post.media?.[0]?.url"
                                        :thumbnail-url="post.media?.[0]?.thumbnail_url"
                                        :media-type="post.media?.[0]?.type || 'image'"
                                    />
                                </div>

                                <!-- Instagram Reel Preview -->
                                <div v-else-if="activePreview.includes('instagram_reel')" ref="mockupRef">
                                    <InstagramReelPreview
                                        :brand-name="post.brand?.instagram_handle || post.brand?.name || 'Brand Name'"
                                        :brand-logo-url="post.brand?.logo_flat_url"
                                        :media-url="post.media?.[0]?.url"
                                        :thumbnail-url="post.media?.[0]?.thumbnail_url"
                                        :media-type="post.media?.[0]?.type || 'video'"
                                        :caption="post.caption"
                                    />
                                </div>

                                <!-- Facebook Story Preview -->
                                <div v-else-if="activePreview.includes('facebook_story')" ref="mockupRef">
                                    <FacebookStoryPreview
                                        :brand-name="post.brand?.facebook_page_name || post.brand?.name || 'Brand Name'"
                                        :brand-logo-url="post.brand?.logo_flat_url"
                                        :media-url="post.media?.[0]?.url"
                                        :thumbnail-url="post.media?.[0]?.thumbnail_url"
                                        :media-type="post.media?.[0]?.type || 'image'"
                                    />
                                </div>

                                <!-- Facebook Reel Preview -->
                                <div v-else ref="mockupRef">
                                    <FacebookReelPreview
                                        :brand-name="post.brand?.facebook_page_name || post.brand?.name || 'Brand Name'"
                                        :brand-logo-url="post.brand?.logo_flat_url"
                                        :media-url="post.media?.[0]?.url"
                                        :thumbnail-url="post.media?.[0]?.thumbnail_url"
                                        :media-type="post.media?.[0]?.type || 'video'"
                                        :caption="post.caption"
                                    />
                                </div>

                                <!-- Export Button -->
                                <div class="mt-4 text-center">
                                    <button
                                        @click="exportAsJpeg"
                                        :disabled="exporting"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 dark:bg-green-500 text-white rounded-md hover:bg-green-700 dark:hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
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

                                <!-- Platform Warnings -->
                                <div v-if="validation.errors.length > 0 || validation.warnings.length > 0" class="mt-4 space-y-2">
                                    <!-- Errors -->
                                    <div
                                        v-for="(error, index) in validation.errors"
                                        :key="'error-' + index"
                                        class="flex items-start gap-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"
                                    >
                                        <svg class="w-5 h-5 text-red-500 dark:text-red-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ error.platformName }}</p>
                                            <p class="text-sm text-red-600 dark:text-red-400">{{ error.message }}</p>
                                        </div>
                                    </div>

                                    <!-- Warnings -->
                                    <div
                                        v-for="(warning, index) in validation.warnings"
                                        :key="'warning-' + index"
                                        class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg"
                                    >
                                        <svg class="w-5 h-5 text-amber-500 dark:text-amber-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">{{ warning.platformName }}</p>
                                            <p class="text-sm text-amber-600 dark:text-amber-400">{{ warning.message }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Media Gallery -->
                            <div v-if="post.media?.length > 1" class="mt-6 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">All Media ({{ post.media.length }})</h3>
                                <div class="grid grid-cols-4 gap-4">
                                    <div
                                        v-for="media in post.media"
                                        :key="media.id"
                                        class="aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700"
                                    >
                                        <img
                                            :src="media.thumbnail_url || media.url"
                                            class="w-full h-full object-cover"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-6">
                            <!-- Post Details -->
                            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Details</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created by</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ post.creator?.name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ formatDate(post.created_at) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last updated</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ formatDate(post.updated_at) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Platforms</dt>
                                        <dd class="flex flex-wrap gap-1 mt-1">
                                            <span
                                                v-for="platform in post.platforms"
                                                :key="platform"
                                                class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs rounded"
                                            >
                                                {{ platform.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()) }}
                                            </span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Comments -->
                            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Comments</h3>

                                <div v-if="!post.comments?.length" class="text-gray-500 dark:text-gray-400 text-sm">
                                    No comments yet.
                                </div>

                                <div v-else class="space-y-4 mb-4">
                                    <div
                                        v-for="comment in post.comments"
                                        :key="comment.id"
                                        class="border-l-2 border-gray-200 dark:border-gray-600 pl-3"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ comment.user?.name }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(comment.created_at) }}</span>
                                        </div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ comment.body }}</p>
                                    </div>
                                </div>

                                <form @submit.prevent="addComment" class="mt-4">
                                    <textarea
                                        v-model="newComment"
                                        rows="2"
                                        placeholder="Add a comment..."
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                                    ></textarea>
                                    <button
                                        type="submit"
                                        :disabled="!newComment.trim() || submittingComment"
                                        class="mt-2 px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white text-sm rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                                    >
                                        {{ submittingComment ? 'Posting...' : 'Post Comment' }}
                                    </button>
                                </form>
                            </div>

                            <!-- Approval History -->
                            <div v-if="post.latest_approval_request" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Approval Status</h3>
                                <div class="border-l-2 border-primary-500 dark:border-primary-400 pl-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatStatus(post.latest_approval_request.status) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Requested {{ formatDate(post.latest_approval_request.created_at) }}
                                    </p>
                                    <div v-if="post.latest_approval_request.responses?.length" class="mt-3 space-y-2">
                                        <div
                                            v-for="response in post.latest_approval_request.responses"
                                            :key="response.id"
                                            class="text-sm"
                                        >
                                            <span class="font-medium dark:text-white">{{ response.user?.name }}</span>
                                            <span :class="response.approved ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                                {{ response.approved ? ' approved' : ' requested changes' }}
                                            </span>
                                            <p v-if="response.comment" class="text-gray-600 dark:text-gray-400 mt-1">{{ response.comment }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invite Reviewers Modal -->
        <Teleport to="body">
            <div
                v-if="showInviteModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Invite External Reviewers</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Send review links to clients or stakeholders via email.
                        </p>
                    </div>

                    <div class="px-6 py-4">
                        <!-- Success message -->
                        <div v-if="inviteSuccess" class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <p class="text-sm text-green-600 dark:text-green-400">{{ inviteSuccess }}</p>
                        </div>

                        <!-- Error message -->
                        <div v-if="inviteError" class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-sm text-red-600 dark:text-red-400">{{ inviteError }}</p>
                        </div>

                        <div v-if="!inviteSuccess">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Reviewer Email Addresses
                            </label>
                            <textarea
                                v-model="reviewerEmails"
                                rows="3"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                placeholder="client@example.com, manager@example.com"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Separate multiple emails with commas. Max 10.
                            </p>

                            <label class="flex items-center gap-2 mt-4 cursor-pointer">
                                <input
                                    v-model="saveAsDefault"
                                    type="checkbox"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    Save as default reviewers for {{ post?.brand?.name }}
                                </span>
                            </label>
                        </div>

                        <!-- Existing invites info -->
                        <div v-if="post?.latest_approval_request?.invites?.length" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Already Invited</p>
                            <div class="space-y-1">
                                <div
                                    v-for="invite in post.latest_approval_request.invites"
                                    :key="invite.id"
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span class="text-gray-700 dark:text-gray-300">{{ invite.email }}</span>
                                    <span
                                        :class="invite.responded_at ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500'"
                                        class="text-xs"
                                    >
                                        {{ invite.responded_at ? 'Responded' : 'Pending' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3 rounded-b-lg">
                        <button
                            @click="showInviteModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                        >
                            {{ inviteSuccess ? 'Close' : 'Cancel' }}
                        </button>
                        <button
                            v-if="!inviteSuccess"
                            @click="sendInvites"
                            :disabled="sendingInvites || !reviewerEmails.trim()"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="sendingInvites" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ sendingInvites ? 'Sending...' : 'Send Invitations' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
