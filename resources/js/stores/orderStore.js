import { reactive } from 'vue';
import { fetchOrders } from '../services/orderService.js';

const TTL = 4 * 60 * 1000;
let cache = null;
let inFlight = null;
const state = reactive({ loading: false, refreshing: false, error: null, lastUpdated: 0 });

const fetchPage = async (params = {}) => {
    if (cache && Date.now() - cache.timestamp < TTL) return cache.data;
    if (inFlight) return inFlight;

    state.loading = !cache;
    state.refreshing = Boolean(cache);
    state.error = null;

    inFlight = fetchOrders(params)
        .then((data) => {
            cache = { data, timestamp: Date.now() };
            state.lastUpdated = Date.now();
            return data;
        })
        .catch((error) => {
            state.error = error;
            return cache?.data ?? null;
        })
        .finally(() => {
            state.loading = false;
            state.refreshing = false;
            inFlight = null;
        });

    return inFlight;
};

export const useOrderStore = () => ({ state, fetchPage, prefetchPage: fetchPage });
