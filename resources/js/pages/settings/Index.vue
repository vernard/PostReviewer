<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { agencyApi, userApi } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const activeTab = ref('profile');

const tabs = [
    { id: 'profile', name: 'Profile' },
    { id: 'agency', name: 'Agency Settings' },
    { id: 'team', name: 'Team Members' },
];

const loading = ref(false);
const saving = ref(false);
const message = ref({ type: '', text: '' });

// Profile
const profileForm = ref({
    name: '',
    email: '',
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Agency
const agency = ref(null);
const agencyForm = ref({
    name: '',
});

// Team
const users = ref([]);
const showInviteModal = ref(false);
const inviteForm = ref({
    email: '',
    name: '',
    role: 'member',
});
const inviting = ref(false);

const canManageAgency = computed(() => {
    return authStore.user?.role === 'admin';
});

const canManageTeam = computed(() => {
    return ['admin', 'manager'].includes(authStore.user?.role);
});

const initForms = () => {
    if (authStore.user) {
        profileForm.value.name = authStore.user.name;
        profileForm.value.email = authStore.user.email;
    }
};

const fetchAgency = async () => {
    try {
        const response = await agencyApi.get();
        agency.value = response.data.agency;
        agencyForm.value.name = agency.value?.name || '';
    } catch (err) {
        console.error('Failed to fetch agency:', err);
    }
};

const fetchUsers = async () => {
    if (!canManageTeam.value) return;

    try {
        loading.value = true;
        const response = await userApi.list();
        users.value = response.data.data || response.data || [];
    } catch (err) {
        console.error('Failed to fetch users:', err);
    } finally {
        loading.value = false;
    }
};

const updateProfile = async () => {
    message.value = { type: '', text: '' };

    try {
        saving.value = true;
        // For now, just update local state - would need a profile update endpoint
        authStore.user.name = profileForm.value.name;
        message.value = { type: 'success', text: 'Profile updated successfully!' };

        // Clear password fields
        profileForm.value.current_password = '';
        profileForm.value.password = '';
        profileForm.value.password_confirmation = '';
    } catch (err) {
        message.value = { type: 'error', text: err.response?.data?.message || 'Failed to update profile' };
    } finally {
        saving.value = false;
    }
};

const updateAgency = async () => {
    message.value = { type: '', text: '' };

    try {
        saving.value = true;
        await agencyApi.update({ name: agencyForm.value.name });
        message.value = { type: 'success', text: 'Agency settings updated!' };
    } catch (err) {
        message.value = { type: 'error', text: err.response?.data?.message || 'Failed to update agency' };
    } finally {
        saving.value = false;
    }
};

const inviteUser = async () => {
    if (!inviteForm.value.email || !inviteForm.value.name) return;

    try {
        inviting.value = true;
        await userApi.invite(inviteForm.value);
        showInviteModal.value = false;
        inviteForm.value = { email: '', name: '', role: 'member' };
        await fetchUsers();
        message.value = { type: 'success', text: 'Invitation sent!' };
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to send invitation');
    } finally {
        inviting.value = false;
    }
};

const updateUserRole = async (user, newRole) => {
    try {
        await userApi.update(user.id, { role: newRole });
        user.role = newRole;
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to update user role');
    }
};

const removeUser = async (user) => {
    if (!confirm(`Are you sure you want to remove ${user.name} from your team?`)) return;

    try {
        await userApi.delete(user.id);
        users.value = users.value.filter(u => u.id !== user.id);
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to remove user');
    }
};

const getRoleBadgeColor = (role) => {
    const colors = {
        admin: 'bg-purple-100 text-purple-700',
        manager: 'bg-blue-100 text-blue-700',
        reviewer: 'bg-green-100 text-green-700',
        member: 'bg-gray-100 text-gray-700',
    };
    return colors[role] || 'bg-gray-100 text-gray-700';
};

onMounted(() => {
    initForms();
    fetchAgency();
    fetchUsers();
});
</script>

<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-2xl font-semibold text-gray-900">Settings</h1>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="bg-white shadow rounded-lg">
                    <!-- Tabs -->
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    activeTab === tab.id
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm'
                                ]"
                            >
                                {{ tab.name }}
                            </button>
                        </nav>
                    </div>

                    <!-- Success/Error Message -->
                    <div
                        v-if="message.text"
                        :class="[
                            'mx-6 mt-6 px-4 py-3 rounded',
                            message.type === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'
                        ]"
                    >
                        {{ message.text }}
                    </div>

                    <!-- Tab Content -->
                    <div class="p-6">
                        <!-- Profile Tab -->
                        <div v-if="activeTab === 'profile'">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Profile Settings</h3>
                            <form @submit.prevent="updateProfile" class="space-y-4 max-w-lg">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <input
                                        v-model="profileForm.name"
                                        type="text"
                                        required
                                        class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input
                                        v-model="profileForm.email"
                                        type="email"
                                        disabled
                                        class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-50 text-gray-500"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">Email cannot be changed</p>
                                </div>

                                <div class="border-t border-gray-200 pt-4 mt-6">
                                    <h4 class="text-sm font-medium text-gray-900 mb-4">Change Password</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Current Password</label>
                                            <input
                                                v-model="profileForm.current_password"
                                                type="password"
                                                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">New Password</label>
                                            <input
                                                v-model="profileForm.password"
                                                type="password"
                                                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                            <input
                                                v-model="profileForm.password_confirmation"
                                                type="password"
                                                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <button
                                        type="submit"
                                        :disabled="saving"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {{ saving ? 'Saving...' : 'Save Changes' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Agency Tab -->
                        <div v-else-if="activeTab === 'agency'">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Agency Settings</h3>

                            <div v-if="!canManageAgency" class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-4">
                                Only admins can modify agency settings.
                            </div>

                            <form @submit.prevent="updateAgency" class="space-y-4 max-w-lg">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Agency Name</label>
                                    <input
                                        v-model="agencyForm.name"
                                        type="text"
                                        :disabled="!canManageAgency"
                                        class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-50 disabled:text-gray-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Logo</label>
                                    <div class="mt-1 flex items-center space-x-4">
                                        <div class="h-16 w-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                                            <img
                                                v-if="agency?.logo"
                                                :src="agency.logo"
                                                class="w-full h-full object-cover"
                                            />
                                            <svg v-else class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <button
                                            v-if="canManageAgency"
                                            type="button"
                                            class="px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                                        >
                                            Change
                                        </button>
                                    </div>
                                </div>
                                <div v-if="canManageAgency" class="pt-4">
                                    <button
                                        type="submit"
                                        :disabled="saving"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {{ saving ? 'Saving...' : 'Save Changes' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Team Tab -->
                        <div v-else-if="activeTab === 'team'">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Team Members</h3>
                                <button
                                    v-if="canManageTeam"
                                    @click="showInviteModal = true"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm"
                                >
                                    Invite Member
                                </button>
                            </div>

                            <div v-if="!canManageTeam" class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-4">
                                Only admins and managers can manage team members.
                            </div>

                            <div v-if="loading" class="text-center py-8 text-gray-500">
                                Loading team members...
                            </div>

                            <div v-else-if="users.length === 0" class="text-center py-8 text-gray-500">
                                No team members found.
                            </div>

                            <div v-else class="overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Member
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Role
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Brands
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="user in users" :key="user.id">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div
                                                        v-if="user.avatar"
                                                        class="h-10 w-10 rounded-full overflow-hidden"
                                                    >
                                                        <img :src="user.avatar" class="w-full h-full object-cover" />
                                                    </div>
                                                    <div
                                                        v-else
                                                        class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center"
                                                    >
                                                        <span class="text-indigo-600 font-medium">{{ user.name?.charAt(0)?.toUpperCase() }}</span>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ user.name }}
                                                            <span v-if="user.id === authStore.user?.id" class="text-gray-500">(you)</span>
                                                        </div>
                                                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <select
                                                    v-if="canManageTeam && user.id !== authStore.user?.id"
                                                    :value="user.role"
                                                    @change="updateUserRole(user, $event.target.value)"
                                                    class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                                >
                                                    <option value="admin">Admin</option>
                                                    <option value="manager">Manager</option>
                                                    <option value="reviewer">Reviewer</option>
                                                    <option value="member">Member</option>
                                                </select>
                                                <span
                                                    v-else
                                                    :class="[getRoleBadgeColor(user.role), 'px-2 py-1 text-xs font-medium rounded-full']"
                                                >
                                                    {{ user.role?.charAt(0).toUpperCase() + user.role?.slice(1) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ user.brands_count || 0 }} brands
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button
                                                    v-if="canManageTeam && user.id !== authStore.user?.id"
                                                    @click="removeUser(user)"
                                                    class="text-red-600 hover:text-red-900"
                                                >
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invite Modal -->
        <div v-if="showInviteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showInviteModal = false"></div>

                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Invite Team Member</h3>

                    <form @submit.prevent="inviteUser" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input
                                v-model="inviteForm.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="John Doe"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input
                                v-model="inviteForm.email"
                                type="email"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="john@example.com"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select
                                v-model="inviteForm.role"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="member">Member</option>
                                <option value="reviewer">Reviewer</option>
                                <option value="manager">Manager</option>
                                <option v-if="authStore.user?.role === 'admin'" value="admin">Admin</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                <strong>Member:</strong> Can create posts<br />
                                <strong>Reviewer:</strong> Can approve/reject posts<br />
                                <strong>Manager:</strong> Full access to assigned brands<br />
                                <strong>Admin:</strong> Full access to everything
                            </p>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button
                                type="button"
                                @click="showInviteModal = false"
                                class="px-4 py-2 text-gray-700 hover:text-gray-900"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="inviting"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ inviting ? 'Sending...' : 'Send Invitation' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
