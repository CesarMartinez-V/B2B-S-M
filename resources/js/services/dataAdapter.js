import { reactive } from 'vue';
import { apiClient } from './apiClient.js';

const DATA_MODE = 'fallback';

const clone = (value) => JSON.parse(JSON.stringify(value));

const assignData = (target, value) => {
    if (Array.isArray(target) && Array.isArray(value)) {
        target.splice(0, target.length, ...value);
        return target;
    }

    if (!Array.isArray(target) && target && typeof target === 'object' && value && typeof value === 'object' && !Array.isArray(value)) {
        Object.keys(target).forEach((key) => delete target[key]);
        Object.assign(target, value);
        return target;
    }

    return value;
};

const readPayload = (response) => response?.data ?? response;

export const createDataAdapter = ({ mock, endpoint, normalize = (value) => value, extract = readPayload }) => {
    const initial = normalize(typeof mock === 'function' ? mock() : mock);
    const state = reactive({
        mode: DATA_MODE,
        data: Array.isArray(initial) ? reactive([...initial]) : reactive({ ...initial }),
        loading: false,
        error: null,
        loaded: false,
    });

    const adapter = {
        state,
        mode: DATA_MODE,
        list() {
            return state.data;
        },
        async fetch(params = {}) {
            if (DATA_MODE === 'mock' || !endpoint) return state.data;

            state.loading = true;
            state.error = null;

            try {
                const response = await apiClient.get(endpoint, { params });
                const next = normalize(extract(response));
                state.data = assignData(state.data, clone(next));
                state.loaded = true;
            } catch (error) {
                state.error = error;
            } finally {
                state.loading = false;
            }

            return state.data;
        },
        warm(params = {}) {
            if (DATA_MODE !== 'mock') void this.fetch(params);
            return state.data;
        },
    };

    adapter.warm();

    return adapter;
};
