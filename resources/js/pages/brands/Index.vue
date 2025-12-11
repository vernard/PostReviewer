<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import { brandApi } from '@/services/api';

const brands = ref([]);
const loading = ref(true);
const showCreateModal = ref(false);
const creating = ref(false);
const error = ref('');

const form = ref({
    name: '',
    description: '',
});

const fetchBrands = async () => {
    try {
        loading.value = true;
        const response = await brandApi.list();
        brands.value = response.data.data || response.data;
    } catch (err) {
        console.error('Failed to fetch brands:', err);
    } finally {
        loading.value = false;
    }
};

const createBrand = async () => {
    if (!form.value.name.trim()) {
        error.value = 'Brand name is required';
        return;
    }

    try {
        creating.value = true;
        error.value = '';
        await brandApi.create(form.value);
        showCreateModal.value = false;
        form.value = { name: '', description: '' };
        await fetchBrands();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create brand';
    } finally {
        creating.value = false;
    }
};

const deleteBrand = async (brand) => {
    if (!confirm(`Are you sure you want to delete "${brand.name}"?`)) return;

    try {
        await brandApi.delete(brand.id);
        await fetchBrands();
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to delete brand');
    }
};

onMounted(fetchBrands);
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900">Brands</h1>
                    <button
                        @click="showCreateModal = true"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center gap-2"
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
                <div v-if="loading" class="bg-white shadow rounded-lg p-6 text-center text-gray-500">
                    Loading brands...
                </div>

                <!-- Empty State -->
                <div v-else-if="brands.length === 0" class="bg-white shadow rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No brands yet</h3>
                    <p class="mt-2 text-gray-500">Get started by creating your first brand.</p>
                    <button
                        @click="showCreateModal = true"
                        class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                    >
                        Create Brand
                    </button>
                </div>

                <!-- Brands Grid -->
                <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="brand in brands"
                        :key="brand.id"
                        class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow"
                    >
                        <RouterLink :to="`/brands/${brand.id}`" class="block p-6">
                            <div class="flex items-center">
                                <div
                                    v-if="brand.logo"
                                    class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0"
                                >
                                    <img :src="brand.logo" :alt="brand.name" class="w-full h-full object-cover" />
                                </div>
                                <div
                                    v-else
                                    class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0"
                                >
                                    <span class="text-indigo-600 font-semibold text-lg">
                                        {{ brand.name?.charAt(0)?.toUpperCase() || '?' }}
                                    </span>
                                </div>
                                <div class="ml-4 flex-1 min-w-0">
                                    <h3 class="text-lg font-medium text-gray-900 truncate">{{ brand.name }}</h3>
                                    <p class="text-sm text-gray-500 truncate">{{ brand.description || 'No description' }}</p>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center text-sm text-gray-500">
                                <span>{{ brand.posts_count || 0 }} posts</span>
                                <span class="mx-2">·</span>
                                <span>{{ brand.users_count || 0 }} members</span>
                            </div>
                        </RouterLink>
                        <div class="px-6 py-3 bg-gray-50 flex justify-end gap-2">
                            <RouterLink
                                :to="`/brands/${brand.id}`"
                                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                            >
                                View
                            </RouterLink>
                            <button
                                @click.prevent="deleteBrand(brand)"
                                class="text-red-600 hover:text-red-800 text-sm font-medium"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Brand Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showCreateModal = false"></div>

                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Brand</h3>

                    <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded">
                        {{ error }}
                    </div>

                    <form @submit.prevent="createBrand">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Enter brand name"
                            />
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Brief description of the brand"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="showCreateModal = false"
                                class="px-4 py-2 text-gray-700 hover:text-gray-900"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="creating"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
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
