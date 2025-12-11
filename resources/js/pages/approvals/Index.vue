<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { approvalApi } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const approvals = ref([]);
const loading = ref(true);
const statusFilter = ref('pending');

const showReviewModal = ref(false);
const selectedApproval = ref(null);
const reviewForm = ref({
    action: '',
    comment: '',
});
const submitting = ref(false);

const canReview = computed(() => {
    return ['admin', 'manager', 'reviewer'].includes(authStore.user?.role);
});

const fetchApprovals = async () => {
    try {
        loading.value = true;
        const params = {};
        if (statusFilter.value) params.status = statusFilter.value;

        const response = await approvalApi.list(params);
        approvals.value = response.data.data || [];
    } catch (err) {
        console.error('Failed to fetch approvals:', err);
    } finally {
        loading.value = false;
    }
};

const openReviewModal = (approval) => {
    selectedApproval.value = approval;
    reviewForm.value = { action: '', comment: '' };
    showReviewModal.value = true;
};

const submitReview = async () => {
    if (!reviewForm.value.action) return;
    if (reviewForm.value.action === 'request_changes' && !reviewForm.value.comment.trim()) {
        alert('Please provide feedback when requesting changes.');
        return;
    }

    try {
        submitting.value = true;

        if (reviewForm.value.action === 'approve') {
            await approvalApi.approve(selectedApproval.value.id, reviewForm.value.comment);
        } else {
            await approvalApi.requestChanges(selectedApproval.value.id, reviewForm.value.comment);
        }

        showReviewModal.value = false;
        await fetchApprovals();
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to submit review');
    } finally {
        submitting.value = false;
    }
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
};

const formatStatus = (status) => {
    if (status === 'rejected') return 'Changes Requested';
    return status?.charAt(0).toUpperCase() + status?.slice(1) || '';
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

const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    return `${diffDays}d ago`;
};

watch(statusFilter, () => {
    fetchApprovals();
});

onMounted(fetchApprovals);
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Pending Approvals</h1>
                    <div class="flex gap-2">
                        <button
                            v-for="status in ['pending', 'approved', 'rejected']"
                            :key="status"
                            @click="statusFilter = status"
                            :class="[
                                'px-4 py-2 text-sm rounded-md',
                                statusFilter === status
                                    ? 'bg-primary-600 text-white dark:bg-primary-500'
                                    : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700'
                            ]"
                        >
                            {{ status === 'rejected' ? 'Changes Requested' : status.charAt(0).toUpperCase() + status.slice(1) }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <!-- Permission Warning -->
                <div v-if="!canReview" class="bg-yellow-50 border border-yellow-200 text-yellow-700 dark:bg-yellow-900/30 dark:border-yellow-800 dark:text-yellow-400 px-4 py-3 rounded mb-6">
                    You don't have permission to review posts. Only admins, managers, and reviewers can approve or request changes.
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="space-y-4">
                    <div v-for="i in 3" :key="i" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 animate-pulse">
                        <div class="flex items-start gap-4">
                            <div class="w-24 h-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            <div class="flex-1">
                                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-48 mb-2"></div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-32 mb-4"></div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-64"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="approvals.length === 0" class="bg-white dark:bg-gray-800 shadow rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                        {{ statusFilter === 'pending' ? 'No posts pending approval' : `No ${formatStatus(statusFilter).toLowerCase()} posts` }}
                    </h3>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">
                        {{ statusFilter === 'pending' ? 'All caught up! Check back later for new submissions.' : 'No posts match this filter.' }}
                    </p>
                </div>

                <!-- Approvals List -->
                <div v-else class="space-y-4">
                    <div
                        v-for="approval in approvals"
                        :key="approval.id"
                        class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden"
                    >
                        <div class="p-6">
                            <div class="flex items-start gap-6">
                                <!-- Thumbnail -->
                                <RouterLink :to="`/posts/${approval.post?.id}`" class="flex-shrink-0">
                                    <div class="w-32 h-32 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                                        <img
                                            v-if="approval.post?.media?.[0]"
                                            :src="approval.post.media[0].thumbnail_url || approval.post.media[0].url"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                </RouterLink>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <RouterLink :to="`/posts/${approval.post?.id}`" class="text-lg font-medium text-gray-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400">
                                                {{ approval.post?.title }}
                                            </RouterLink>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                <RouterLink :to="`/brands/${approval.post?.brand?.id}`" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                                    {{ approval.post?.brand?.name }}
                                                </RouterLink>
                                                <span class="mx-2">·</span>
                                                <span>{{ approval.post?.platforms?.length || 0 }} platforms</span>
                                            </p>
                                        </div>
                                        <span :class="[getStatusColor(approval.status), 'px-3 py-1 text-sm font-medium rounded-full']">
                                            {{ formatStatus(approval.status) }}
                                        </span>
                                    </div>

                                    <!-- Caption preview -->
                                    <p v-if="approval.post?.caption" class="mt-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                        {{ approval.post.caption }}
                                    </p>

                                    <!-- Meta info -->
                                    <div class="mt-4 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            {{ approval.requester?.name || approval.post?.creator?.name }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ formatTimeAgo(approval.created_at) }}
                                        </span>
                                        <span v-if="approval.due_date" class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            Due {{ formatDate(approval.due_date) }}
                                        </span>
                                    </div>

                                    <!-- Previous responses -->
                                    <div v-if="approval.responses?.length" class="mt-4 border-t border-gray-100 dark:border-gray-600 pt-4">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Previous feedback:</p>
                                        <div class="space-y-2">
                                            <div
                                                v-for="response in approval.responses"
                                                :key="response.id"
                                                class="text-sm"
                                            >
                                                <span class="font-medium dark:text-gray-300">{{ response.user?.name }}</span>
                                                <span :class="response.decision === 'approved' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                                    {{ response.decision === 'approved' ? ' approved' : ' requested changes' }}
                                                </span>
                                                <p v-if="response.comment" class="text-gray-600 dark:text-gray-400 mt-1 ml-4 pl-2 border-l-2 border-gray-200 dark:border-gray-600">
                                                    "{{ response.comment }}"
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div v-if="approval.status === 'pending' && canReview" class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end gap-3 border-t border-gray-100 dark:border-gray-600">
                            <RouterLink
                                :to="`/posts/${approval.post?.id}`"
                                class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                            >
                                View Details
                            </RouterLink>
                            <button
                                @click="openReviewModal(approval)"
                                class="px-4 py-2 text-sm bg-primary-600 text-white rounded-md hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600"
                            >
                                Review
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Modal -->
        <div v-if="showReviewModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showReviewModal = false"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Review Post</h3>

                    <div class="mb-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            <strong>{{ selectedApproval?.post?.title }}</strong> by {{ selectedApproval?.post?.creator?.name }}
                        </p>

                        <div class="flex gap-4 mb-4">
                            <button
                                @click="reviewForm.action = 'approve'"
                                :class="[
                                    'flex-1 py-3 rounded-lg border-2 transition-colors',
                                    reviewForm.action === 'approve'
                                        ? 'border-green-500 bg-green-50 text-green-700 dark:border-green-400 dark:bg-green-900/30 dark:text-green-400'
                                        : 'border-gray-200 text-gray-700 hover:border-gray-300 dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-500'
                                ]"
                            >
                                <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Approve
                            </button>
                            <button
                                @click="reviewForm.action = 'request_changes'"
                                :class="[
                                    'flex-1 py-3 rounded-lg border-2 transition-colors',
                                    reviewForm.action === 'request_changes'
                                        ? 'border-red-500 bg-red-50 text-red-700 dark:border-red-400 dark:bg-red-900/30 dark:text-red-400'
                                        : 'border-gray-200 text-gray-700 hover:border-gray-300 dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-500'
                                ]"
                            >
                                <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Request Changes
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1">
                                {{ reviewForm.action === 'request_changes' ? 'Feedback (required)' : 'Comment (optional)' }}
                            </label>
                            <textarea
                                v-model="reviewForm.comment"
                                rows="3"
                                :placeholder="reviewForm.action === 'request_changes' ? 'Explain what changes are needed...' : 'Add a comment...'"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            @click="showReviewModal = false"
                            class="px-4 py-2 text-gray-700 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            @click="submitReview"
                            :disabled="!reviewForm.action || submitting"
                            :class="[
                                'px-4 py-2 rounded-md disabled:opacity-50',
                                reviewForm.action === 'approve'
                                    ? 'bg-green-600 text-white hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600'
                                    : reviewForm.action === 'request_changes'
                                    ? 'bg-red-600 text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600'
                                    : 'bg-gray-300 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                            ]"
                        >
                            {{ submitting ? 'Submitting...' : reviewForm.action === 'approve' ? 'Approve Post' : 'Request Changes' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
