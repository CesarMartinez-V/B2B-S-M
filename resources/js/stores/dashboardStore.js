import { reactive } from 'vue';
import { dashboardState, fetchDashboard } from '../services/dashboardService.js';

const TTL = 2 * 60 * 1000;
let cache = null;
let inFlight = null;
const state = reactive({ data: null, loading: false, refreshing: false, error: null, lastUpdated: 0 });

const fetchSummary = async (params = {}) => {
    if (cache && Date.now() - cache.timestamp < TTL) return cache.data;
    if (inFlight) return inFlight;

    state.loading = !cache;
    state.refreshing = Boolean(cache);
    state.error = null;

    inFlight = fetchDashboard(params)
        .then((data) => {
            if (dashboardState.error && cache) return cache.data;
            cache = { data, timestamp: Date.now() };
            state.data = data;
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

const clearDashboardCache = () => {
    cache = null;
    inFlight = null;
    state.data = null;
    state.loading = false;
    state.refreshing = false;
    state.error = null;
    state.lastUpdated = 0;
};

export const useDashboardStore = () => ({ state, fetchSummary, clearDashboardCache });
