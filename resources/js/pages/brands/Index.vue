<script setup>
import { ref, onMounted, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { brandApi, userApi } from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { useBrandStore } from '@/stores/brand';

const router = useRouter();
const authStore = useAuthStore();
const brandStore = useBrandStore();

const brands = ref([]);
const users = ref([]);
const loading = ref(true);
const loadingUsers = ref(false);
const showCreateModal = ref(false);
const creating = ref(false);
const error = ref('');

const form = ref({
    name: '',
    description: '',
    logo: null,
    user_ids: [],
});

const logoPreview = ref(null);

const fetchBrands = async () => {
    try {
        loading.value = true;
        const response = await brandApi.list();
        brands.value = response.data.brands || response.data.data || response.data;
    } catch (err) {
        console.error('Failed to fetch brands:', err);
    } finally {
        loading.value = false;
    }
};

const fetchUsers = async () => {
    try {
        loadingUsers.value = true;
        const response = await userApi.list();
        users.value = response.data.users || response.data.data || response.data || [];
    } catch (err) {
        console.error('Failed to fetch users:', err);
        users.value = [];
    } finally {
        loadingUsers.value = false;
    }
};

const handleLogoSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.logo = file;
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeLogo = () => {
    form.value.logo = null;
    logoPreview.value = null;
};

const toggleUserSelection = (userId) => {
    const index = form.value.user_ids.indexOf(userId);
    if (index === -1) {
        form.value.user_ids.push(userId);
    } else {
        form.value.user_ids.splice(index, 1);
    }
};

const isUserSelected = (userId) => {
    return form.value.user_ids.includes(userId) || userId === authStore.user?.id;
};

const getRoleBadgeClass = (role) => {
    const classes = {
        admin: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        manager: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        reviewer: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        creator: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[role] || classes.creator;
};

const openCreateModal = () => {
    showCreateModal.value = true;
    fetchUsers();
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    form.value = { name: '', description: '', logo: null, user_ids: [] };
    logoPreview.value = null;
    error.value = '';
};

const createBrand = async () => {
    if (!form.value.name.trim()) {
        error.value = 'Brand name is required';
        return;
    }

    try {
        creating.value = true;
        error.value = '';

        // Build FormData
        const formData = new FormData();
        formData.append('name', form.value.name);
        if (form.value.description) {
            formData.append('description', form.value.description);
        }
        if (form.value.logo) {
            formData.append('logo', form.value.logo);
        }
        // Add user IDs
        form.value.user_ids.forEach((userId, index) => {
            formData.append(`user_ids[${index}]`, userId);
        });

        await brandApi.createWithFormData(formData);
        closeCreateModal();
        await fetchBrands();
        // Refresh brand store
        await brandStore.fetchBrands();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create brand';
    } finally {
        creating.value = false;
    }
};

const createPost = (brand) => {
    brandStore.setActiveBrand(brand);
    router.push('/posts/create');
};

const createBatch = (brand) => {
    brandStore.setActiveBrand(brand);
    router.push('/posts/batch-create');
};

onMounted(fetchBrands);
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Brands</h1>
                    <button
                        @click="openCreateModal"
                        class="bg-primary-600 dark:bg-primary-500 text-white px-4 py-2 rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Brand
                    </button>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <!-- Loading State -->
                <div v-if="loading" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center text-gray-500 dark:text-gray-400">
                    Loading brands...
                </div>

                <!-- Empty State -->
                <div v-else-if="brands.length === 0" class="bg-white dark:bg-gray-800 shadow rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No brands yet</h3>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">Get started by creating your first brand.</p>
                    <button
                        @click="openCreateModal"
                        class="mt-4 bg-primary-600 dark:bg-primary-500 text-white px-4 py-2 rounded-md hover:bg-primary-700 dark:hover:bg-primary-600"
                    >
                        Create Brand
                    </button>
                </div>

                <!-- Brands Grid -->
                <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="brand in brands"
                        :key="brand.id"
                        class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow"
                    >
                        <RouterLink :to="`/brands/${brand.id}`" class="block p-6">
                            <div class="flex items-center">
                                <div
                                    v-if="brand.logo_url"
                                    class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0"
                                >
                                    <img :src="brand.logo_url" :alt="brand.name" class="w-full h-full object-cover" />
                                </div>
                                <div
                                    v-else
                                    class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0"
                                >
                                    <span class="text-primary-600 dark:text-primary-400 font-semibold text-lg">
                                        {{ brand.name?.charAt(0)?.toUpperCase() || '?' }}
                                    </span>
                                </div>
                                <div class="ml-4 flex-1 min-w-0">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white truncate">{{ brand.name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ brand.description || 'No description' }}</p>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <span>{{ brand.posts_count || 0 }} posts</span>
                                <span class="mx-2">·</span>
                                <span>{{ brand.users_count || 0 }} members</span>
                            </div>
                        </RouterLink>
                        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 flex justify-end gap-2">
                            <button
                                @click.prevent="createPost(brand)"
                                class="px-3 py-1.5 bg-primary-600 dark:bg-primary-500 text-white text-sm font-medium rounded-md hover:bg-primary-700 dark:hover:bg-primary-600"
                            >
                                Create Post
                            </button>
                            <button
                                @click.prevent="createBatch(brand)"
                                class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-100 dark:hover:bg-gray-600"
                            >
                                Batch
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Brand Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" @click="closeCreateModal"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Create New Brand</h3>

                    <div v-if="error" class="mb-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded">
                        {{ error }}
                    </div>

                    <form @submit.prevent="createBrand">
                        <!-- Logo Upload -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Logo (optional)</label>
                            <div class="flex items-start gap-4">
                                <div
                                    v-if="logoPreview"
                                    class="relative w-20 h-20 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700"
                                >
                                    <img :src="logoPreview" class="w-full h-full object-cover" />
                                    <button
                                        type="button"
                                        @click="removeLogo"
                                        class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div
                                    v-else
                                    class="w-20 h-20 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center text-gray-400 dark:text-gray-500"
                                >
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        @change="handleLogoSelect"
                                        class="hidden"
                                        id="logo-upload"
                                    />
                                    <label
                                        for="logo-upload"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer"
                                    >
                                        Choose file
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PNG, JPG up to 2MB</p>
                                </div>
                            </div>
                        </div>

                        <!-- Brand Name -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1">Brand Name *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                placeholder="Enter brand name"
                            />
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1">Description (optional)</label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                placeholder="Brief description of the brand"
                            ></textarea>
                        </div>

                        <!-- Team Access -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Team Access</label>
                            <div v-if="loadingUsers" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                                Loading team members...
                            </div>
                            <div v-else-if="users.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                                No team members found.
                            </div>
                            <div v-else class="border border-gray-200 dark:border-gray-600 rounded-md max-h-48 overflow-y-auto">
                                <div
                                    v-for="user in users"
                                    :key="user.id"
                                    class="flex items-center px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                                >
                                    <input
                                        type="checkbox"
                                        :id="`user-${user.id}`"
                                        :checked="isUserSelected(user.id)"
                                        :disabled="user.id === authStore.user?.id"
                                        @change="toggleUserSelection(user.id)"
                                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded disabled:opacity-50"
                                    />
                                    <label :for="`user-${user.id}`" class="ml-3 flex-1 flex items-center gap-2 cursor-pointer">
                                        <span class="text-sm text-gray-900 dark:text-white">{{ user.name }}</span>
                                        <span :class="[getRoleBadgeClass(user.role), 'text-xs px-1.5 py-0.5 rounded capitalize']">
                                            {{ user.role }}
                                        </span>
                                        <span v-if="user.id === authStore.user?.id" class="text-xs text-gray-500 dark:text-gray-400">(You)</span>
                                    </label>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Admins and managers can access all brands automatically.
                            </p>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="closeCreateModal"
                                class="px-4 py-2 text-gray-700 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="creating"
                                class="bg-primary-600 dark:bg-primary-500 text-white px-4 py-2 rounded-md hover:bg-primary-700 dark:hover:bg-primary-600 disabled:opacity-50"
                            >
                                {{ creating ? 'Creating...' : 'Create Brand' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
