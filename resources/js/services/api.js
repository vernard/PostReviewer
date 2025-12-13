import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

// Add token to requests if available
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Handle 401 responses
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default api;

// Auth API
export const authApi = {
    register: (data) => {
        const isFormData = data instanceof FormData;
        return api.post('/register', data, isFormData ? {
            headers: { 'Content-Type': 'multipart/form-data' }
        } : {});
    },
    login: (data) => api.post('/login', data),
    logout: () => api.post('/logout'),
    user: () => api.get('/user'),
    acceptInvitation: (token, data) => api.post(`/invitation/${token}/accept`, data),
};

// Agency API
export const agencyApi = {
    get: () => api.get('/agency'),
    update: (data) => api.put('/agency', data),
    dashboardStats: () => api.get('/dashboard/stats'),
};

// User API
export const userApi = {
    list: () => api.get('/users'),
    invite: (data) => api.post('/users/invite', data),
    update: (id, data) => api.put(`/users/${id}`, data),
    delete: (id) => api.delete(`/users/${id}`),
};

// Brand API
export const brandApi = {
    list: () => api.get('/brands'),
    get: (id) => api.get(`/brands/${id}`),
    create: (data) => api.post('/brands', data),
    createWithFormData: (formData) => api.post('/brands', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    }),
    update: (id, data) => api.put(`/brands/${id}`, data),
    updateWithLogo: (id, formData) => api.post(`/brands/${id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        params: { _method: 'PUT' },
    }),
    delete: (id) => api.delete(`/brands/${id}`),
    addUser: (brandId, userId) => api.post(`/brands/${brandId}/users`, { user_id: userId }),
    removeUser: (brandId, userId) => api.delete(`/brands/${brandId}/users/${userId}`),
};

// Media API
export const mediaApi = {
    list: (params) => api.get('/media', { params }),
    get: (id) => api.get(`/media/${id}`),
    upload: (brandId, file, onProgress) => {
        const formData = new FormData();
        formData.append('brand_id', brandId);
        formData.append('file', file);

        return api.post('/media', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                if (onProgress) {
                    const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    onProgress(percent);
                }
            },
        });
    },
    delete: (id) => api.delete(`/media/${id}`),
};

// Post API
export const postApi = {
    list: (params) => api.get('/posts', { params }),
    get: (id) => api.get(`/posts/${id}`),
    create: (data) => api.post('/posts', data),
    update: (id, data) => api.put(`/posts/${id}`, data),
    delete: (id) => api.delete(`/posts/${id}`),
    submitForApproval: (id, dueDate) => api.post(`/posts/${id}/submit`, { due_date: dueDate }),
    duplicate: (id) => api.post(`/posts/${id}/duplicate`),
    attachMedia: (postId, mediaId, position) => api.post(`/posts/${postId}/media`, { media_id: mediaId, position }),
    detachMedia: (postId, mediaId) => api.delete(`/posts/${postId}/media/${mediaId}`),
    reorderMedia: (postId, mediaIds) => api.put(`/posts/${postId}/media/reorder`, { media_ids: mediaIds }),
};

// Approval API
export const approvalApi = {
    list: (params) => api.get('/approvals', { params }),
    approve: (id, comment) => api.post(`/approvals/${id}/approve`, { comment }),
    requestChanges: (id, comment) => api.post(`/approvals/${id}/request-changes`, { comment }),
};

// Comment API
export const commentApi = {
    list: (postId) => api.get(`/posts/${postId}/comments`),
    create: (postId, data) => api.post(`/posts/${postId}/comments`, data),
    update: (id, body) => api.put(`/comments/${id}`, { body }),
    delete: (id) => api.delete(`/comments/${id}`),
    resolve: (id) => api.post(`/comments/${id}/resolve`),
};

// Collection API
export const collectionApi = {
    list: (params) => api.get('/collections', { params }),
    get: (id) => api.get(`/collections/${id}`),
    create: (data) => api.post('/collections', data),
    update: (id, data) => api.put(`/collections/${id}`, data),
    delete: (id) => api.delete(`/collections/${id}`),
    generateApprovalLink: (id, expiresInDays) => api.post(`/collections/${id}/generate-link`, { expires_in_days: expiresInDays }),
    submitForApproval: (id) => api.post(`/collections/${id}/submit`),
    addPosts: (id, postIds) => api.post(`/collections/${id}/posts`, { post_ids: postIds }),
    removePosts: (id, postIds) => api.delete(`/collections/${id}/posts`, { data: { post_ids: postIds } }),
};
