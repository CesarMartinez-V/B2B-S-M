import { reactive } from 'vue';
import { useAccountStore } from './accountStore.js';
import { useCatalogStore } from './catalogStore.js';
import { useDashboardStore } from './dashboardStore.js';
import { useInvoiceStore } from './invoiceStore.js';
import { useOrderStore } from './orderStore.js';
import { useProfileStore } from './profileStore.js';
import { useQuoteStore } from './quoteStore.js';

const state = reactive({ started: false, running: false, completed: false, logs: [] });
const catalogStore = useCatalogStore();
const dashboardStore = useDashboardStore();
const invoiceStore = useInvoiceStore();
const accountStore = useAccountStore();
const profileStore = useProfileStore();
const quoteStore = useQuoteStore();
const orderStore = useOrderStore();

const log = (message, context = {}) => {
    state.logs.unshift({ message, context, time: new Date().toISOString() });
    state.logs.splice(20);
    console.info(`[prefetch] ${message}`, context);
};

const runLimited = async (tasks, limit = 2) => {
    const queue = [...tasks];
    const workers = Array.from({ length: limit }, async () => {
        while (queue.length) {
            const task = queue.shift();
            await task().catch((error) => log('task failed', { message: error.message }));
        }
    });

    await Promise.all(workers);
};

const schedule = (callback) => {
    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(callback, { timeout: 1600 });
        return;
    }

    window.setTimeout(callback, 600);
};

export const usePortalPrefetchStore = () => ({
    state,
    reset() {
        state.started = false;
        state.running = false;
        state.completed = false;
        state.logs = [];
    },
    run({ priorityPath = '' } = {}) {
        if (state.started) return;

        state.started = true;
        schedule(async () => {
            state.running = true;
            log('started', { priorityPath });

            const catalogTask = () => catalogStore.fetchProducts({ page: 1, per_page: 24, include_filters: 0 }).then(() => {
                catalogStore.prefetchNeighbors(1, { page: 1, per_page: 24, include_filters: 0 });
                void catalogStore.fetchFilters();
            });

            const tasks = [
                ...(priorityPath === '/panel' ? [() => dashboardStore.fetchSummary()] : []),
                ...(priorityPath === '/catalogo' ? [catalogTask] : []),
                ...(priorityPath === '/pedidos' ? [() => orderStore.fetchPage({ page: 1, per_page: 10 })] : []),
                ...(priorityPath === '/historial-facturas' ? [() => invoiceStore.fetchPage({ page: 1, per_page: 20, include_filters: 1 }).then(() => invoiceStore.prefetchPage({ page: 2, per_page: 20, include_filters: 0 }))] : []),
                ...(priorityPath === '/estado-de-cuenta' ? [() => accountStore.fetchSummary({ page: 1, per_page: 20, limit: 20, include_summary: 1, include_filters: 1 }).then(() => accountStore.prefetch({ page: 2, per_page: 20, limit: 20, include_summary: 0, include_filters: 0 }))] : []),
                () => profileStore.fetchProfile(),
                ...(priorityPath === '/estado-de-cuenta' ? [] : [() => accountStore.prefetch({ page: 1, per_page: 20, limit: 20, include_summary: 1, include_filters: 1 }).then(() => accountStore.prefetch({ page: 2, per_page: 20, limit: 20, include_summary: 0, include_filters: 0 }))]),
                ...(priorityPath === '/panel' ? [] : [() => dashboardStore.fetchSummary()]),
                ...(priorityPath === '/catalogo' ? [] : [catalogTask]),
                ...(priorityPath === '/historial-facturas' ? [] : [() => invoiceStore.prefetch({ page: 1, per_page: 20, include_filters: 1 }).then(() => invoiceStore.prefetchPage({ page: 2, per_page: 20, include_filters: 0 }))]),
                () => quoteStore.fetchPage({ page: 1, per_page: 20 }),
                ...(priorityPath === '/pedidos' ? [] : [() => orderStore.fetchPage({ page: 1, per_page: 10 })]),
            ];

            await runLimited(tasks, 2);
            state.running = false;
            state.completed = true;
            log('completed');
        });
    },
});
