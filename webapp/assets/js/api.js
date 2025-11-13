/**
 * API Client for TransKwanza
 */

const API_BASE = '../api';

// Get auth token from localStorage
function getAuthToken() {
    return localStorage.getItem('auth_token');
}

// Set auth token
function setAuthToken(token) {
    localStorage.setItem('auth_token', token);
}

// Remove auth token
function removeAuthToken() {
    localStorage.removeItem('auth_token');
}

// Make API request
async function apiRequest(endpoint, options = {}) {
    const url = `${API_BASE}/${endpoint}`;

    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    };

    // Add auth token if available
    const token = getAuthToken();
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const config = {
        ...options,
        headers
    };

    try {
        const response = await fetch(url, config);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Request failed');
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// API Methods
const API = {
    // Auth
    register: (data) => apiRequest('auth/register', {
        method: 'POST',
        body: JSON.stringify(data)
    }),

    login: (data) => apiRequest('auth/login', {
        method: 'POST',
        body: JSON.stringify(data)
    }),

    logout: () => apiRequest('auth/logout', {
        method: 'POST'
    }),

    me: () => apiRequest('auth/me'),

    // Countries
    getCountries: () => apiRequest('countries'),

    // Exchange
    getExchangeRate: (from, to) => apiRequest(`exchange-rate?from=${from}&to=${to}`),

    convert: (data) => apiRequest('convert', {
        method: 'POST',
        body: JSON.stringify(data)
    }),

    // Proposals
    getProposals: (params = {}) => {
        const query = new URLSearchParams(params).toString();
        return apiRequest(`proposals${query ? '?' + query : ''}`);
    },

    getProposal: (id) => apiRequest(`proposals/${id}`),

    createProposal: (data) => apiRequest('proposals', {
        method: 'POST',
        body: JSON.stringify(data)
    }),

    acceptProposal: (id, data = {}) => apiRequest(`proposals/${id}/accept`, {
        method: 'POST',
        body: JSON.stringify(data)
    }),

    cancelProposal: (id) => apiRequest(`proposals/${id}`, {
        method: 'DELETE'
    }),

    // Transactions
    getTransactions: () => apiRequest('transactions'),

    getTransaction: (id) => apiRequest(`transactions/${id}`),

    confirmPayment: (id) => apiRequest(`transactions/${id}/confirm-payment`, {
        method: 'POST'
    }),

    // Notifications
    getNotifications: (params = {}) => {
        const query = new URLSearchParams(params).toString();
        return apiRequest(`notifications${query ? '?' + query : ''}`);
    },

    markNotificationRead: (id) => apiRequest(`notifications/${id}/read`, {
        method: 'POST'
    }),

    markAllNotificationsRead: () => apiRequest('notifications/read-all', {
        method: 'POST'
    })
};

// Check if user is authenticated
function isAuthenticated() {
    return !!getAuthToken();
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!isAuthenticated()) {
        window.location.href = 'login.html';
        return false;
    }
    return true;
}

// Redirect to dashboard if authenticated
function requireGuest() {
    if (isAuthenticated()) {
        window.location.href = 'dashboard.html';
        return false;
    }
    return true;
}
