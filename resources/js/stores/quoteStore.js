import { reactive } from 'vue';
import { fetchQuotes } from '../services/quoteService.js';

const TTL = 4 * 60 * 1000;
const cache = new Map();
const inFlight = new Map();
const state = reactive({ data: null, loading: false, refreshing: false, error: null, lastUpdated: 0 });
const keyFor = (params = {}) => JSON.stringify(Object.entries(params).sort(([a], [b]) => a.localeCompare(b)));
const fresh = (entry) => entry && Date.now() - entry.timestamp < TTL;

const fetchPage = async (params = {}) => {
    const key = keyFor(params);
    const cached = cache.get(key);
    if (fresh(cached)) return cached.data;
    if (inFlight.has(key)) return inFlight.get(key);

    state.loading = !cached;
    state.refreshing = Boolean(cached);
    state.error = null;

    const request = fetchQuotes(params)
        .then((data) => {
            cache.set(key, { data, timestamp: Date.now() });
            state.data = data;
            state.lastUpdated = Date.now();
            return data;
        })
        .catch((error) => {
            state.error = error;
            return cached?.data ?? null;
        })
        .finally(() => {
            state.loading = false;
            state.refreshing = false;
            inFlight.delete(key);
        });

    inFlight.set(key, request);
    return request;
};

const clearQuoteCache = () => {
    cache.clear();
    inFlight.clear();
    state.data = null;
    state.loading = false;
    state.refreshing = false;
    state.error = null;
    state.lastUpdated = 0;
};

export const useQuoteStore = () => ({ state, fetchPage, prefetchPage: fetchPage, clearQuoteCache });
