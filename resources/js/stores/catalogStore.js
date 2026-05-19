import { reactive } from 'vue';
import { catalogProducts } from '../data/mockCatalog.js';
import { formatCurrency, parseCurrency } from '../utils/currency.js';
import { apiClient } from '../services/apiClient.js';

const CACHE_TTL = 7 * 60 * 1000;
const products = reactive(catalogProducts.map((product) => normalizeProduct(product)));
const filters = reactive({ categories: [], brands: [], min_price: 0, max_price: 10000, availability: ['all', 'available', 'unavailable'] });
const meta = reactive({ current_page: 1, page: 1, per_page: 8, total: products.length, last_page: 1, source: 'mock-fallback' });
const state = reactive({ data: products, filters, meta, loading: false, filtersLoading: false, filtersWarming: false, error: null, filtersError: null, loaded: false, filtersLoaded: false, lastUpdated: 0, logs: [] });
const pageCache = new Map();
const inFlight = new Map();
let filtersCache = null;
let filtersInFlight = null;

const addLog = (level, message, context = {}) => {
    const entry = { level, message, context, time: new Date().toISOString() };
    state.logs.unshift(entry);
    state.logs.splice(25);

    const method = level === 'error' ? 'error' : level === 'warn' ? 'warn' : 'info';
    console[method](`[catalog] ${message}`, context);
};

function normalizeProduct(product) {
    const availableQty = product.availableQty ?? product.available_qty ?? product.stock ?? null;
    const hasQty = availableQty !== null && availableQty !== undefined && availableQty !== '';
    const qty = hasQty ? Number(availableQty) : null;
    const isAvailable = product.isAvailable ?? product.is_available ?? (qty !== null && qty > 0);

    return {
        ...product,
        brand: product.brand,
        category: product.category || 'General',
        priceValue: product.priceValue ?? product.price_value ?? parseCurrency(product.price),
        price: formatCurrency(product.priceValue ?? product.price_value ?? parseCurrency(product.price)),
        stock: qty,
        availableQty: qty,
        isAvailable,
        stockLabel: product.stockLabel ?? product.stock_label ?? (qty !== null && qty > 0 ? `Disponible: ${qty} unidades` : 'Consultar disponibilidad'),
        lastStockUpdate: product.lastStockUpdate ?? product.last_stock_update ?? '',
    };
}

const cacheKey = (params = {}) => JSON.stringify(Object.entries(params)
    .filter(([, value]) => value !== undefined && value !== null && value !== '')
    .sort(([left], [right]) => left.localeCompare(right)));

const isFresh = (entry) => entry && Date.now() - entry.timestamp < CACHE_TTL;

const assignProducts = (items) => {
    products.splice(0, products.length, ...items.map(normalizeProduct));
};

const assignFilters = (nextFilters = {}) => {
    if (!nextFilters || Object.keys(nextFilters).length === 0) return;

    filters.categories = nextFilters.categories ?? filters.categories;
    filters.brands = nextFilters.brands ?? filters.brands;
    filters.min_price = nextFilters.min_price ?? filters.min_price;
    filters.max_price = nextFilters.max_price ?? filters.max_price;
    filters.availability = nextFilters.availability ?? filters.availability;
    state.filtersWarming = Boolean(nextFilters.warming);
    state.filtersLoaded = true;
};

const assignMeta = (nextMeta = {}) => {
    const perPage = Number(nextMeta.per_page ?? meta.per_page ?? 8) || 8;
    const total = Number(nextMeta.total ?? products.length) || 0;
    const currentPage = Number(nextMeta.current_page ?? nextMeta.page ?? meta.current_page ?? 1) || 1;

    meta.current_page = currentPage;
    meta.page = currentPage;
    meta.per_page = perPage;
    meta.total = total;
    meta.last_page = Number(nextMeta.last_page ?? Math.max(1, Math.ceil(total / perPage))) || 1;
    meta.source = nextMeta.source ?? meta.source;
};

const applyCatalogResponse = (response) => {
    assignProducts(response?.data?.products ?? []);
    assignFilters(response?.data?.filters ?? {});
    assignMeta(response?.meta ?? {});
    state.loaded = true;
    state.lastUpdated = Date.now();
};

const requestCatalog = async (params, key) => {
    if (inFlight.has(key)) return inFlight.get(key);

    addLog('info', 'request products', params);

    const request = apiClient.get('/api/portal/catalog', { params })
        .then((response) => {
            pageCache.set(key, { response, timestamp: Date.now() });
            addLog('info', 'products response', { source: response?.meta?.source, total: response?.meta?.total, products: response?.data?.products?.length ?? 0 });
            return response;
        })
        .catch((error) => {
            addLog('error', 'products request failed', { message: error.message });
            throw error;
        })
        .finally(() => inFlight.delete(key));

    inFlight.set(key, request);
    return request;
};

const fetchProducts = async (params = {}) => {
    const key = cacheKey(params);
    const cached = pageCache.get(key);

    if (cached) {
        addLog('info', isFresh(cached) ? 'page cache hit' : 'stale page cache shown', params);
        applyCatalogResponse(cached.response);
        if (isFresh(cached)) return products;
    }

    state.loading = !cached && !state.loaded;
    state.error = null;

    try {
        const response = await requestCatalog(params, key);
        applyCatalogResponse(response);
    } catch (error) {
        state.error = error;
    } finally {
        state.loading = false;
    }

    return products;
};

const fetchFilters = async ({ force = false, cycle = 0 } = {}) => {
    if (!force && isFresh(filtersCache)) {
        addLog('info', 'filters cache hit');
        assignFilters(filtersCache.filters);
        return filters;
    }

    if (filtersInFlight) {
        addLog('info', 'filters request reused');
        return filtersInFlight;
    }

    state.filtersLoading = !state.filtersLoaded;
    state.filtersError = null;
    addLog('info', 'request filters');

    filtersInFlight = apiClient.get('/api/portal/catalog', { params: { page: 1, per_page: 1, include_filters: 1 } })
        .then((response) => {
            const nextFilters = response?.data?.filters ?? {};
            assignFilters(nextFilters);
            filtersCache = { filters: nextFilters, timestamp: Date.now() };
            addLog('info', 'filters response', { categories: nextFilters.categories?.length ?? 0, brands: nextFilters.brands?.length ?? 0, warming: Boolean(nextFilters.warming) });

            if (nextFilters.warming && cycle < 3) {
                window.setTimeout(() => { void fetchFilters({ force: true, cycle: cycle + 1 }); }, 1200);
            }

            return filters;
        })
        .catch((error) => {
            state.filtersError = error;
            addLog('error', 'filters request failed', { message: error.message });
            return filters;
        })
        .finally(() => {
            state.filtersLoading = false;
            filtersInFlight = null;
        });

    return filtersInFlight;
};

export const useCatalogStore = () => ({
    state,
    listProducts: () => products,
    fetchProducts,
    fetchFilters,
    addLog,
    filterProducts: ({ search = '', categories = [], brands = [], maxPrice = 10000, availability = 'all' } = {}) => {
        const term = search.trim().toLowerCase();

        return products.filter((product) => {
            const matchesSearch = !term || [product.name, product.brand, product.category, product.id].join(' ').toLowerCase().includes(term);
            const matchesCategory = categories.length === 0 || categories.includes(product.category);
            const matchesBrand = brands.length === 0 || brands.includes(product.brand);
            const matchesPrice = product.priceValue <= Number(maxPrice || 10000);
            const matchesAvailability = availability !== 'available' || product.isAvailable;

            return matchesSearch && matchesCategory && matchesBrand && matchesPrice && matchesAvailability;
        });
    },
});
