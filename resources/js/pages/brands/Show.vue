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
    logo: null,
});
const logoPreview = ref(null);
const logoInput = ref(null);

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
        brand.value = response.data.brand || response.data.data || response.data;
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

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        editForm.value.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const updateBrand = async () => {
    if (!editForm.value.name.trim()) {
        return;
    }

    try {
        saving.value = true;

        const formData = new FormData();
        formData.append('name', editForm.value.name);
        formData.append('description', editForm.value.description || '');
        if (editForm.value.logo) {
            formData.append('logo', editForm.value.logo);
        }

        const response = await brandApi.updateWithLogo(brandId, formData);
        const updatedBrand = response.data.brand || response.data;
        brand.value.name = updatedBrand.name;
        brand.value.description = updatedBrand.description;
        brand.value.logo_url = updatedBrand.logo_url;
        showEditModal.value = false;
        editForm.value.logo = null;
        logoPreview.value = null;
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
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        pending_approval: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        changes_requested: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        published: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
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
                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-48 mb-4"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-96"></div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded">
                    {{ error }}
                    <RouterLink to="/brands" class="ml-4 underline">Back to brands</RouterLink>
                </div>

                <!-- Brand Header -->
                <div v-else>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <RouterLink to="/brands" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </RouterLink>
                            <div
                                v-if="brand?.logo_url"
                                class="w-16 h-16 rounded-full overflow-hidden flex-shrink-0"
                            >
                                <img :src="brand.logo_url" :alt="brand.name" class="w-full h-full object-cover" />
                            </div>
                            <div
                                v-else
                                class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0"
                            >
                                <span class="text-primary-600 dark:text-primary-400 font-semibold text-2xl">
                                    {{ brand?.name?.charAt(0)?.toUpperCase() }}
                                </span>
                            </div>
                            <div class="ml-4">
                                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ brand?.name }}</h1>
                                <p class="text-gray-500 dark:text-gray-400">{{ brand?.description || 'No description' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Post Creation Actions -->
                            <RouterLink
                                :to="`/posts/create?brand_id=${brandId}`"
                                class="px-4 py-2 bg-primary-600 dark:bg-primary-500 text-white rounded-md hover:bg-primary-700 dark:hover:bg-primary-600"
                            >
                                Create Post
                            </RouterLink>
                            <RouterLink
                                :to="`/posts/batch-create?brand_id=${brandId}`"
                                class="px-4 py-2 border border-primary-600 dark:border-primary-500 text-primary-600 dark:text-primary-400 rounded-md hover:bg-primary-50 dark:hover:bg-primary-900/30"
                            >
                                Batch Create
                            </RouterLink>
                            <!-- Admin Actions -->
                            <template v-if="canManage">
                                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                                <button
                                    @click="showEditModal = true"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="deleteBrand"
                                    class="px-4 py-2 border border-red-300 dark:border-red-800 rounded-md text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30"
                                >
                                    Delete
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Posts</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ brand?.posts_count || 0 }}</dd>
                        </div>
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Team Members</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ brand?.users_count || users.length }}</dd>
                        </div>
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Media Files</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ brand?.media_count || 0 }}</dd>
                        </div>
                    </div>

                    <!-- Content Grid -->
                    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <!-- Recent Posts -->
                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                                <div class="px-4 py-5 sm:px-6 flex justify-between items-center border-b border-gray-200 dark:border-gray-600">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Posts</h3>
                                    <RouterLink
                                        :to="`/posts/create?brand_id=${brandId}`"
                                        class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500"
                                    >
                                        Create Post
                                    </RouterLink>
                                </div>
                                <div v-if="posts.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                    No posts yet. Create your first post!
                                </div>
                                <ul v-else class="divide-y divide-gray-200 dark:divide-gray-600">
                                    <li v-for="post in posts" :key="post.id">
                                        <RouterLink :to="`/posts/${post.id}`" class="block hover:bg-gray-50 dark:hover:bg-gray-700 px-4 py-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ post.title }}</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
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
                                <div v-if="posts.length > 0" class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-right border-t border-gray-200 dark:border-gray-600">
                                    <RouterLink
                                        :to="`/posts?brand_id=${brandId}`"
                                        class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500"
                                    >
                                        View all posts &rarr;
                                    </RouterLink>
                                </div>
                            </div>
                        </div>

                        <!-- Team Members -->
                        <div>
                            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                                <div class="px-4 py-5 sm:px-6 flex justify-between items-center border-b border-gray-200 dark:border-gray-600">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Team Members</h3>
                                    <button
                                        v-if="canManage"
                                        @click="showAddUserModal = true"
                                        class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500"
                                    >
                                        Add Member
                                    </button>
                                </div>
                                <div v-if="users.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                    No team members assigned.
                                </div>
                                <ul v-else class="divide-y divide-gray-200 dark:divide-gray-600">
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
                                                class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center"
                                            >
                                                <span class="text-gray-600 dark:text-gray-400 text-sm font-medium">
                                                    {{ user.name?.charAt(0)?.toUpperCase() }}
                                                </span>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ user.role }}</p>
                                            </div>
                                        </div>
                                        <button
                                            v-if="canManage && user.id !== authStore.user?.id"
                                            @click="removeUser(user.id)"
                                            class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
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

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Edit Brand</h3>

                    <form @submit.prevent="updateBrand">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brand Logo</label>
                            <div class="flex items-center gap-4">
                                <div
                                    v-if="logoPreview || brand?.logo_url"
                                    class="w-16 h-16 rounded-full overflow-hidden flex-shrink-0"
                                >
                                    <img :src="logoPreview || brand?.logo_url" alt="Logo preview" class="w-full h-full object-cover" />
                                </div>
                                <div
                                    v-else
                                    class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0"
                                >
                                    <span class="text-gray-400 text-2xl">{{ editForm.name?.charAt(0)?.toUpperCase() || '?' }}</span>
                                </div>
                                <div>
                                    <input
                                        ref="logoInput"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleLogoChange"
                                    />
                                    <button
                                        type="button"
                                        @click="logoInput.click()"
                                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Change Logo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brand Name</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea
                                v-model="editForm.description"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="showEditModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="saving"
                                class="bg-primary-600 dark:bg-primary-500 text-white px-4 py-2 rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
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

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Add Team Member</h3>

                    <div v-if="availableUsers.length === 0" class="text-gray-500 dark:text-gray-400 text-center py-4">
                        No available users to add. All team members are already assigned.
                    </div>
                    <form v-else @submit.prevent="addUser">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select User</label>
                            <select
                                v-model="selectedUserId"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500"
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
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="saving || !selectedUserId"
                                class="bg-primary-600 dark:bg-primary-500 text-white px-4 py-2 rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
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
