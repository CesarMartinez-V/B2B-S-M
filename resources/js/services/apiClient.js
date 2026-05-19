const API_BASE_URL = '';

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
            headers: { Accept: 'application/json', ...(options.headers || {}) },
            ...Object.fromEntries(Object.entries(options).filter(([key]) => key !== 'params')),
        });

        if (!response.ok) throw new Error(`API request failed: ${response.status}`);

        return response.json();
    },

    async post(endpoint, data = {}, options = {}) {
        const response = await window.fetch(buildUrl(endpoint, options.params), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(options.headers || {}),
            },
            body: JSON.stringify(data),
            ...Object.fromEntries(Object.entries(options).filter(([key]) => !['params', 'headers'].includes(key))),
        });

        if (!response.ok) throw new Error(`API request failed: ${response.status}`);

        return response.json();
    },
};
