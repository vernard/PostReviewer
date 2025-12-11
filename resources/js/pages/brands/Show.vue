<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { brandApi, postApi, userApi } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const brandId = route.params.id;

const brand = ref(null);
const posts = ref([]);
const users = ref([]);
const allUsers = ref([]);
const loading = ref(true);
const error = ref('');

const showEditModal = ref(false);
const showAddUserModal = ref(false);
const saving = ref(false);
const editForm = ref({
    name: '',
    description: '',
});

const selectedUserId = ref('');

const canManage = computed(() => {
    return authStore.user?.role === 'admin' || authStore.user?.role === 'manager';
});

const availableUsers = computed(() => {
    const assignedIds = users.value.map(u => u.id);
    return allUsers.value.filter(u => !assignedIds.includes(u.id));
});

const fetchBrand = async () => {
    try {
        loading.value = true;
        const response = await brandApi.get(brandId);
        brand.value = response.data.data || response.data;
        editForm.value = {
            name: brand.value.name,
            description: brand.value.description || '',
        };
    } catch (err) {
        error.value = 'Failed to load brand';
        console.error('Failed to fetch brand:', err);
    } finally {
        loading.value = false;
    }
};

const fetchPosts = async () => {
    try {
        const response = await postApi.list({ brand_id: brandId, per_page: 10 });
        posts.value = response.data.data || response.data;
    } catch (err) {
        console.error('Failed to fetch posts:', err);
    }
};

const fetchUsers = async () => {
    try {
        // Get users assigned to this brand (from brand details)
        if (brand.value?.users) {
            users.value = brand.value.users;
        }
        // Get all agency users for add user modal
        if (canManage.value) {
            const response = await userApi.list();
            allUsers.value = response.data.data || response.data || [];
        }
    } catch (err) {
        console.error('Failed to fetch users:', err);
    }
};

const updateBrand = async () => {
    if (!editForm.value.name.trim()) {
        return;
    }

    try {
        saving.value = true;
        await brandApi.update(brandId, editForm.value);
        brand.value.name = editForm.value.name;
        brand.value.description = editForm.value.description;
        showEditModal.value = false;
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to update brand';
    } finally {
        saving.value = false;
    }
};

const deleteBrand = async () => {
    if (!confirm(`Are you sure you want to delete "${brand.value.name}"? This will also delete all associated posts.`)) {
        return;
    }

    try {
        await brandApi.delete(brandId);
        router.push('/brands');
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to delete brand');
    }
};

const addUser = async () => {
    if (!selectedUserId.value) return;

    try {
        saving.value = true;
        await brandApi.addUser(brandId, selectedUserId.value);
        const user = allUsers.value.find(u => u.id === parseInt(selectedUserId.value));
        if (user) {
            users.value.push(user);
        }
        selectedUserId.value = '';
        showAddUserModal.value = false;
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to add user');
    } finally {
        saving.value = false;
    }
};

const removeUser = async (userId) => {
    const user = users.value.find(u => u.id === userId);
    if (!confirm(`Remove ${user?.name} from this brand?`)) return;

    try {
        await brandApi.removeUser(brandId, userId);
        users.value = users.value.filter(u => u.id !== userId);
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to remove user');
    }
};

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-700',
        pending_approval: 'bg-yellow-100 text-yellow-700',
        changes_requested: 'bg-red-100 text-red-700',
        approved: 'bg-green-100 text-green-700',
        published: 'bg-blue-100 text-blue-700',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

const formatStatus = (status) => {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

onMounted(async () => {
    await fetchBrand();
    await Promise.all([fetchPosts(), fetchUsers()]);
});
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Loading State -->
                <div v-if="loading" class="animate-pulse">
                    <div class="h-8 bg-gray-200 rounded w-48 mb-4"></div>
                    <div class="h-4 bg-gray-200 rounded w-96"></div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded">
                    {{ error }}
                    <RouterLink to="/brands" class="ml-4 underline">Back to brands</RouterLink>
                </div>

                <!-- Brand Header -->
                <div v-else>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <RouterLink to="/brands" class="text-gray-400 hover:text-gray-600 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </RouterLink>
                            <div
                                v-if="brand?.logo"
                                class="w-16 h-16 rounded-full overflow-hidden flex-shrink-0"
                            >
                                <img :src="brand.logo" :alt="brand.name" class="w-full h-full object-cover" />
                            </div>
                            <div
                                v-else
                                class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0"
                            >
                                <span class="text-indigo-600 font-semibold text-2xl">
                                    {{ brand?.name?.charAt(0)?.toUpperCase() }}
                                </span>
                            </div>
                            <div class="ml-4">
                                <h1 class="text-2xl font-semibold text-gray-900">{{ brand?.name }}</h1>
                                <p class="text-gray-500">{{ brand?.description || 'No description' }}</p>
                            </div>
                        </div>
                        <div v-if="canManage" class="flex items-center gap-2">
                            <button
                                @click="showEditModal = true"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                            >
                                Edit
                            </button>
                            <button
                                @click="deleteBrand"
                                class="px-4 py-2 border border-red-300 rounded-md text-red-600 hover:bg-red-50"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="bg-white shadow rounded-lg p-4">
                            <dt class="text-sm font-medium text-gray-500">Total Posts</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ brand?.posts_count || 0 }}</dd>
                        </div>
                        <div class="bg-white shadow rounded-lg p-4">
                            <dt class="text-sm font-medium text-gray-500">Team Members</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ brand?.users_count || users.length }}</dd>
                        </div>
                        <div class="bg-white shadow rounded-lg p-4">
                            <dt class="text-sm font-medium text-gray-500">Media Files</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ brand?.media_count || 0 }}</dd>
                        </div>
                    </div>

                    <!-- Content Grid -->
                    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <!-- Recent Posts -->
                        <div class="lg:col-span-2">
                            <div class="bg-white shadow rounded-lg">
                                <div class="px-4 py-5 sm:px-6 flex justify-between items-center border-b border-gray-200">
                                    <h3 class="text-lg font-medium text-gray-900">Recent Posts</h3>
                                    <RouterLink
                                        :to="`/posts/create?brand_id=${brandId}`"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                    >
                                        Create Post
                                    </RouterLink>
                                </div>
                                <div v-if="posts.length === 0" class="p-6 text-center text-gray-500">
                                    No posts yet. Create your first post!
                                </div>
                                <ul v-else class="divide-y divide-gray-200">
                                    <li v-for="post in posts" :key="post.id">
                                        <RouterLink :to="`/posts/${post.id}`" class="block hover:bg-gray-50 px-4 py-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate">{{ post.title }}</p>
                                                    <p class="text-sm text-gray-500 truncate">
                                                        {{ post.platforms?.join(', ') || 'No platforms' }}
                                                    </p>
                                                </div>
                                                <span
                                                    :class="[getStatusColor(post.status), 'ml-2 px-2 py-1 text-xs font-medium rounded-full']"
                                                >
                                                    {{ formatStatus(post.status) }}
                                                </span>
                                            </div>
                                        </RouterLink>
                                    </li>
                                </ul>
                                <div v-if="posts.length > 0" class="px-4 py-3 bg-gray-50 text-right border-t border-gray-200">
                                    <RouterLink
                                        :to="`/posts?brand_id=${brandId}`"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                    >
                                        View all posts &rarr;
                                    </RouterLink>
                                </div>
                            </div>
                        </div>

                        <!-- Team Members -->
                        <div>
                            <div class="bg-white shadow rounded-lg">
                                <div class="px-4 py-5 sm:px-6 flex justify-between items-center border-b border-gray-200">
                                    <h3 class="text-lg font-medium text-gray-900">Team Members</h3>
                                    <button
                                        v-if="canManage"
                                        @click="showAddUserModal = true"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                    >
                                        Add Member
                                    </button>
                                </div>
                                <div v-if="users.length === 0" class="p-6 text-center text-gray-500">
                                    No team members assigned.
                                </div>
                                <ul v-else class="divide-y divide-gray-200">
                                    <li v-for="user in users" :key="user.id" class="px-4 py-3 flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div
                                                v-if="user.avatar"
                                                class="w-8 h-8 rounded-full overflow-hidden"
                                            >
                                                <img :src="user.avatar" :alt="user.name" class="w-full h-full object-cover" />
                                            </div>
                                            <div
                                                v-else
                                                class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center"
                                            >
                                                <span class="text-gray-600 text-sm font-medium">
                                                    {{ user.name?.charAt(0)?.toUpperCase() }}
                                                </span>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                                                <p class="text-xs text-gray-500">{{ user.role }}</p>
                                            </div>
                                        </div>
                                        <button
                                            v-if="canManage && user.id !== authStore.user?.id"
                                            @click="removeUser(user.id)"
                                            class="text-red-600 hover:text-red-800"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Brand Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showEditModal = false"></div>

                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Brand</h3>

                    <form @submit.prevent="updateBrand">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea
                                v-model="editForm.description"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="showEditModal = false"
                                class="px-4 py-2 text-gray-700 hover:text-gray-900"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="saving"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ saving ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div v-if="showAddUserModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showAddUserModal = false"></div>

                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add Team Member</h3>

                    <div v-if="availableUsers.length === 0" class="text-gray-500 text-center py-4">
                        No available users to add. All team members are already assigned.
                    </div>
                    <form v-else @submit.prevent="addUser">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select User</label>
                            <select
                                v-model="selectedUserId"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="">Choose a user...</option>
                                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="showAddUserModal = false"
                                class="px-4 py-2 text-gray-700 hover:text-gray-900"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="saving || !selectedUserId"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ saving ? 'Adding...' : 'Add Member' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
