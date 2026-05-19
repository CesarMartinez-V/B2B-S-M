import { reactive } from 'vue';
import { useAccountStore } from './accountStore.js';
import { useCatalogStore } from './catalogStore.js';
import { useInvoiceStore } from './invoiceStore.js';
import { useOrderStore } from './orderStore.js';
import { useProfileStore } from './profileStore.js';
import { useQuoteStore } from './quoteStore.js';

const state = reactive({ started: false, running: false, completed: false, logs: [] });
const catalogStore = useCatalogStore();
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
                ...(priorityPath === '/catalogo' ? [catalogTask] : []),
                () => profileStore.fetchProfile(),
                () => accountStore.fetchSummary(),
                ...(priorityPath === '/catalogo' ? [] : [catalogTask]),
                () => invoiceStore.fetchPage({ page: 1, per_page: 20 }).then(() => invoiceStore.prefetchPage({ page: 2, per_page: 20 })),
                () => quoteStore.fetchPage({ page: 1, per_page: 20 }),
                () => orderStore.fetchPage({ page: 1, per_page: 10 }),
            ];

            await runLimited(tasks, 2);
            state.running = false;
            state.completed = true;
            log('completed');
        });
    },
});
