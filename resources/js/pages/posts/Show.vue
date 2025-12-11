<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { postApi, commentApi } from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import html2canvas from 'html2canvas-pro';

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

const truncatedCaption = computed(() => {
    if (!post.value?.caption) return '';
    if (post.value.caption.length <= 125 || showFullCaption.value) return post.value.caption;
    return post.value.caption.substring(0, 125) + '...';
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

        const canvas = await html2canvas(mockupRef.value, {
            backgroundColor: '#ffffff',
            scale: 2,
            useCORS: true,
            allowTaint: true,
            logging: false,
        });

        const link = document.createElement('a');
        const platformName = activePreview.value.replace(/_/g, '-');
        link.download = `${post.value.brand?.name || 'post'}-${platformName}-${Date.now()}.jpg`;
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
                                <div
                                    v-if="activePreview.includes('instagram_feed')"
                                    ref="mockupRef"
                                    class="max-w-sm mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"
                                >
                                    <div class="flex items-center p-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                            {{ post.brand?.name?.charAt(0) || 'B' }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-semibold dark:text-white">{{ post.brand?.name }}</p>
                                        </div>
                                    </div>
                                    <div class="aspect-square bg-gray-100 dark:bg-gray-700">
                                        <img
                                            v-if="post.media?.[0]"
                                            :src="post.media[0].url"
                                            class="w-full h-full object-cover"
                                        />
                                    </div>
                                    <div class="p-3">
                                        <div class="flex gap-4 mb-2">
                                            <svg class="w-6 h-6 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                            <svg class="w-6 h-6 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm dark:text-gray-300">
                                            <span class="font-semibold dark:text-white">{{ post.brand?.name }}</span>
                                            <span class="whitespace-pre-wrap"> {{ truncatedCaption }}</span>
                                            <button
                                                v-if="post.caption?.length > 125"
                                                @click="showFullCaption = !showFullCaption"
                                                class="text-gray-500 dark:text-gray-400 ml-1"
                                            >
                                                {{ showFullCaption ? 'less' : 'more' }}
                                            </button>
                                        </p>
                                    </div>
                                </div>

                                <!-- Facebook Feed Preview -->
                                <div
                                    v-else-if="activePreview.includes('facebook_feed')"
                                    ref="mockupRef"
                                    class="max-w-sm mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"
                                >
                                    <div class="flex items-center p-3">
                                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                            {{ post.brand?.name?.charAt(0) || 'B' }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-semibold dark:text-white">{{ post.brand?.name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Just now</p>
                                        </div>
                                    </div>
                                    <div class="px-3 pb-2">
                                        <p class="text-sm whitespace-pre-wrap dark:text-gray-300">{{ post.caption }}</p>
                                    </div>
                                    <div class="bg-gray-100 dark:bg-gray-700">
                                        <img
                                            v-if="post.media?.[0]"
                                            :src="post.media[0].url"
                                            class="w-full"
                                        />
                                    </div>
                                    <div class="p-3 border-t border-gray-200 dark:border-gray-600 flex justify-around">
                                        <span class="text-gray-500 dark:text-gray-400 text-sm">Like</span>
                                        <span class="text-gray-500 dark:text-gray-400 text-sm">Comment</span>
                                        <span class="text-gray-500 dark:text-gray-400 text-sm">Share</span>
                                    </div>
                                </div>

                                <!-- Story Preview -->
                                <div
                                    v-else
                                    ref="mockupRef"
                                    class="max-w-[280px] mx-auto bg-black rounded-2xl overflow-hidden aspect-[9/16] relative"
                                >
                                    <img
                                        v-if="post.media?.[0]"
                                        :src="post.media[0].url"
                                        class="w-full h-full object-cover"
                                    />
                                    <div class="absolute top-0 left-0 right-0 p-4 bg-gradient-to-b from-black/50 to-transparent">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xs font-bold">
                                                {{ post.brand?.name?.charAt(0) || 'B' }}
                                            </div>
                                            <span class="ml-2 text-white text-sm font-medium">{{ post.brand?.name }}</span>
                                        </div>
                                    </div>
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
                                            v-if="media.type === 'image'"
                                            :src="media.thumbnail_url || media.url"
                                            class="w-full h-full object-cover"
                                        />
                                        <video
                                            v-else
                                            :src="media.url"
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
    </AppLayout>
</template>
