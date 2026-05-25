const API_BASE_URL = '';
const AUTH_KEY = 'portal-auth-session';

const portalSessionToken = () => {
    try {
        const session = JSON.parse(window.sessionStorage.getItem(AUTH_KEY) || 'null');
        return session?.token || '';
    } catch {
        return '';
    }
};

const authHeaders = () => {
    const token = portalSessionToken();
    return token ? { 'X-Portal-Session': token } : {};
};

const handleUnauthorized = (response) => {
    if (response.status !== 401) return;

    try {
        window.sessionStorage.removeItem(AUTH_KEY);
        window.dispatchEvent(new CustomEvent('portal:session-expired'));
    } catch {
        // Ignore storage/event failures; callers still receive the 401 error.
    }
};

const buildUrl = (endpoint, params = {}) => {
    const url = new URL(`${API_BASE_URL}${endpoint}`, window.location.origin);

    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') url.searchParams.set(key, value);
    });

    return url.toString();
};

export const apiClient = {
    async get(endpoint, options = {}) {
        const response = await window.fetch(buildUrl(endpoint, options.params), {
            headers: { Accept: 'application/json', ...authHeaders(), ...(options.headers || {}) },
            ...Object.fromEntries(Object.entries(options).filter(([key]) => key !== 'params')),
        });

        if (!response.ok) {
            handleUnauthorized(response);
            throw new Error(`API request failed: ${response.status}`);
        }

        return response.json();
    },

    async post(endpoint, data = {}, options = {}) {
        const response = await window.fetch(buildUrl(endpoint, options.params), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...authHeaders(),
                ...(options.headers || {}),
            },
            body: JSON.stringify(data),
            ...Object.fromEntries(Object.entries(options).filter(([key]) => !['params', 'headers'].includes(key))),
        });

        if (!response.ok) {
            handleUnauthorized(response);
            throw new Error(`API request failed: ${response.status}`);
        }

        return response.json();
    },
};
