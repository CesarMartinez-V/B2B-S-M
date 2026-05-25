import { reactive } from 'vue';
import { accountState, fetchAccount } from '../services/accountService.js';

const TTL = 2 * 60 * 1000;
const CACHE_VERSION = 'account-v2';
let cache = null;
const pageCache = new Map();
const inFlight = new Map();
const state = reactive({ loading: false, refreshing: false, error: null, lastUpdated: 0 });
const keyFor = (params = {}) => `${CACHE_VERSION}:${JSON.stringify(Object.entries(params).sort(([a], [b]) => a.localeCompare(b)))}`;
let requestSeq = 0;
let latestSeq = 0;

const getCached = (params = {}) => pageCache.get(keyFor(params))?.data ?? null;
const isFresh = (params = {}) => {
    const entry = pageCache.get(keyFor(params));
    return Boolean(entry && Date.now() - entry.timestamp < TTL);
};

const refreshInBackground = async (params = {}) => {
    const key = keyFor(params);
    const cached = pageCache.get(key);
    if (inFlight.has(key)) return inFlight.get(key);

    state.refreshing = Boolean(cached);
    state.error = null;
    const seq = ++requestSeq;
    latestSeq = seq;

    const request = fetchAccount(params)
        .then((data) => {
            if (accountState.error && cached) return cached.data;
            cache = { data, timestamp: Date.now() };
            pageCache.set(key, cache);
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

const fetchSummary = async (params = {}) => {
    const key = keyFor(params);
    const cached = pageCache.get(key);
    if (cached && Date.now() - cached.timestamp < TTL) return cached.data;
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

    const request = fetchAccount(params)
        .then((data) => {
            if (accountState.error && cached) return cached.data;
            cache = { data, timestamp: Date.now() };
            pageCache.set(key, cache);
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

const clearAccountCache = () => {
    cache = null;
    pageCache.clear();
    inFlight.clear();
};

export const useAccountStore = () => ({ state, fetchSummary, prefetch, prefetchSummary: prefetch, refreshInBackground, getCached, isFresh, clearAccountCache });
