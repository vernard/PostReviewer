<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const form = ref({
    name: '',
    email: '',
    agency_name: '',
    password: '',
    password_confirmation: '',
});

const logo = ref(null);
const logoPreview = ref(null);
const errors = ref({});

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file && file.type.startsWith('image/')) {
        logo.value = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const clearLogo = () => {
    logo.value = null;
    logoPreview.value = null;
};

const submit = async () => {
    errors.value = {};
    try {
        const formData = new FormData();
        formData.append('name', form.value.name);
        formData.append('email', form.value.email);
        formData.append('agency_name', form.value.agency_name);
        formData.append('password', form.value.password);
        formData.append('password_confirmation', form.value.password_confirmation);
        if (logo.value) {
            formData.append('logo', logo.value);
        }
        await authStore.register(formData);
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }
    }
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <RouterLink to="/" class="block text-center text-xl font-bold text-primary-600 dark:text-primary-500 hover:text-primary-700 dark:hover:text-primary-400">
                    Post Reviewer
                </RouterLink>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                    Create your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                    Already have an account?
                    <RouterLink to="/login" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                        Sign in
                    </RouterLink>
                </p>
            </div>

            <div v-if="authStore.error" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded">
                {{ authStore.error }}
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Your Name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-400 dark:focus:border-primary-400 sm:text-sm"
                        />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.name[0] }}</p>
                    </div>
                    <div>
                        <label for="agency_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Agency / Company Name</label>
                        <input
                            id="agency_name"
                            v-model="form.agency_name"
                            type="text"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-400 dark:focus:border-primary-400 sm:text-sm"
                        />
                        <p v-if="errors.agency_name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.agency_name[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Brand Logo <span class="text-gray-400 dark:text-gray-500 font-normal">(optional)</span>
                        </label>
                        <div v-if="!logoPreview" class="mt-1">
                            <input
                                type="file"
                                accept="image/*"
                                @change="handleLogoChange"
                                class="hidden"
                                id="logo-upload"
                            />
                            <label
                                for="logo-upload"
                                class="flex items-center justify-center w-full px-3 py-4 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md cursor-pointer hover:border-primary-500 dark:hover:border-primary-400 transition-colors"
                            >
                                <svg class="w-6 h-6 mr-2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Click to upload logo</span>
                            </label>
                        </div>
                        <div v-else class="mt-1 flex items-center gap-3">
                            <img :src="logoPreview" class="w-12 h-12 rounded-full object-cover border border-gray-200 dark:border-gray-600" />
                            <button
                                type="button"
                                @click="clearLogo"
                                class="text-sm text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            >
                                Remove
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This will be your first brand's logo</p>
                        <p v-if="errors.logo" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.logo[0] }}</p>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email address</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-400 dark:focus:border-primary-400 sm:text-sm"
                        />
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.email[0] }}</p>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-400 dark:focus:border-primary-400 sm:text-sm"
                        />
                        <p v-if="errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.password[0] }}</p>
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-400 dark:focus:border-primary-400 sm:text-sm"
                        />
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="authStore.loading"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-900 disabled:opacity-50"
                    >
                        {{ authStore.loading ? 'Creating Account...' : 'Create Account' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
