import { reactive } from 'vue';
import { apiClient } from './apiClient.js';

const DATA_MODE = 'fallback';

const clone = (value) => JSON.parse(JSON.stringify(value));

const mergeReactive = (target, value) => {
    if (Array.isArray(target) && Array.isArray(value)) {
        target.splice(0, target.length, ...value);
        return target;
    }

    if (!Array.isArray(target) && target && typeof target === 'object' && value && typeof value === 'object' && !Array.isArray(value)) {
        Object.keys(target).forEach((key) => {
            if (!(key in value)) delete target[key];
        });

        Object.entries(value).forEach(([key, nextValue]) => {
            const currentValue = target[key];

            if (Array.isArray(currentValue) && Array.isArray(nextValue)) {
                currentValue.splice(0, currentValue.length, ...nextValue);
                return;
            }

            if (!Array.isArray(currentValue) && currentValue && typeof currentValue === 'object' && nextValue && typeof nextValue === 'object' && !Array.isArray(nextValue)) {
                mergeReactive(currentValue, nextValue);
                return;
            }

            target[key] = nextValue;
        });

        return target;
    }

    return value;
};

const assignData = (target, value) => {
    return mergeReactive(target, value);
};

const readPayload = (response) => response?.data ?? response;

export const createDataAdapter = ({ mock, endpoint, normalize = (value) => value, extract = readPayload, warmParams = {} }) => {
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
                return next;
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

    adapter.warm(warmParams);

    return adapter;
};
