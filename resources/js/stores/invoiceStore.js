import { reactive } from 'vue';
import { fetchInvoices, invoiceState } from '../services/invoiceService.js';

const TTL = 4 * 60 * 1000;
const cache = new Map();
const inFlight = new Map();
const state = reactive({ loading: false, refreshing: false, error: null, lastUpdated: 0 });
const keyFor = (params = {}) => JSON.stringify(Object.entries(params).sort(([a], [b]) => a.localeCompare(b)));
const fresh = (entry) => entry && Date.now() - entry.timestamp < TTL;
let requestSeq = 0;
let latestSeq = 0;

const getCached = (params = {}) => cache.get(keyFor(params))?.data ?? null;
const isFresh = (params = {}) => fresh(cache.get(keyFor(params)));

const refreshInBackground = async (params = {}) => {
    const key = keyFor(params);
    const cached = cache.get(key);
    if (inFlight.has(key)) return inFlight.get(key);

    state.refreshing = Boolean(cached);
    state.error = null;
    const seq = ++requestSeq;
    latestSeq = seq;

    const request = fetchInvoices(params)
        .then((data) => {
            if (invoiceState.error && cached) return cached.data;
            cache.set(key, { data, timestamp: Date.now() });
            if (seq === latestSeq) state.lastUpdated = Date.now();
            return data;
        })
        .catch((error) => {
            if (seq === latestSeq) state.error = error;
            return cached?.data ?? null;
        })
        .finally(() => {
            if (seq === latestSeq) state.refreshing = false;
            inFlight.delete(key);
        });

    inFlight.set(key, request);
    return request;
};

const fetchPage = async (params = {}) => {
    const key = keyFor(params);
    const cached = cache.get(key);
    if (fresh(cached)) return cached.data;
    if (cached) {
        void refreshInBackground(params);
        return cached.data;
    }
    if (inFlight.has(key)) return inFlight.get(key);

    state.loading = !cached;
    state.refreshing = Boolean(cached);
    state.error = null;
    const seq = ++requestSeq;
    latestSeq = seq;

    const request = fetchInvoices(params)
        .then((data) => {
            if (invoiceState.error && cached) return cached.data;
            cache.set(key, { data, timestamp: Date.now() });
            if (seq === latestSeq) state.lastUpdated = Date.now();
            return data;
        })
        .catch((error) => {
            if (seq === latestSeq) state.error = error;
            return cached?.data ?? null;
        })
        .finally(() => {
            if (seq === latestSeq) {
                state.loading = false;
                state.refreshing = false;
            }
            inFlight.delete(key);
        });

    inFlight.set(key, request);
    return request;
};

const prefetch = (params = {}) => {
    if (isFresh(params)) return Promise.resolve(getCached(params));
    return refreshInBackground(params);
};

const clearInvoiceCache = () => {
    cache.clear();
    inFlight.clear();
    state.loading = false;
    state.refreshing = false;
    state.error = null;
    state.lastUpdated = 0;
};

export const useInvoiceStore = () => ({ state, fetchPage, prefetchPage: prefetch, prefetch, refreshInBackground, getCached, isFresh, clearInvoiceCache });
