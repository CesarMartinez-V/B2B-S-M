import { mockQuotes, mobileQuotes, quoteRows, quoteStats } from '../data/mockQuotes.js';
import { parseCurrency } from '../utils/currency.js';
import { apiClient } from './apiClient.js';
import { createDataAdapter } from './dataAdapter.js';

const STORAGE_KEY = 'sm_quotes_temp';
const CART_KEYS = ['portal-quote-cart', 'sm_quote_cart', 'sm_temp_cart', 'sm_cart_temp', 'tempCart', 'cart', 'carritoTemporal'];

const clone = (value) => JSON.parse(JSON.stringify(value));

const readJson = (key, fallback = null) => {
    try {
        const raw = window.localStorage.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
    } catch {
        return fallback;
    }
};

const writeJson = (key, value) => {
    try {
        window.localStorage.setItem(key, JSON.stringify(value));
    } catch {
        // Keep runtime state available if storage is unavailable.
    }
};

const quoteResource = createDataAdapter({
    mock: () => ({ stats: quoteStats, quotes: mockQuotes, rows: quoteRows, mobileQuotes }),
    endpoint: '/api/portal/quotes',
    extract: (response) => response?.data ?? {},
    normalize: (payload) => ({
        stats: payload.stats ?? quoteStats,
        quotes: (payload.quotes ?? mockQuotes).map((quote) => ({
            ...quote,
            id: quote.id ?? quote.number,
            amount: Number(quote.amount ?? quote.totalValue ?? 0),
            status: quote.status ?? 'Pendiente',
            ref: quote.ref ?? `REF: Cotización ${quote.number ?? quote.id}`,
            vehicle: quote.vehicle ?? 'Cotización comercial',
            brand: quote.brand ?? 'Inversiones S&M',
            archived: Boolean(quote.archived),
            items: quote.items ?? [],
        })),
        rows: payload.rows ?? quoteRows,
        mobileQuotes: payload.mobileQuotes ?? payload.mobile_quotes ?? mobileQuotes,
        meta: payload._meta ?? {},
        source: payload._source ?? 'mock',
    }),
});

const normalizeCartItems = (cart) => {
    const source = Array.isArray(cart) ? cart : cart?.items || cart?.productos || cart?.products || [];

    return source.map((item, index) => {
        const qty = Number(item.qty || item.quantity || item.cantidad || 1);
        const rawPrice = item.priceValue ?? item.price ?? item.precio ?? item.amount ?? 0;
        const price = typeof rawPrice === 'number' ? rawPrice : parseCurrency(rawPrice);

        return {
            id: item.id || item.sku || item.code || `TMP-${index + 1}`,
            name: item.name || item.nombre || item.title || `Item ${index + 1}`,
            sku: item.sku || item.id || item.code || `TMP-${index + 1}`,
            brand: item.brand || item.marca || 'Marca por confirmar',
            qty,
            price,
            availableQty: item.availableQty ?? item.stock ?? null,
        };
    });
};

export const getQuoteStats = () => quoteResource.list().stats;
export const getQuoteRows = () => quoteResource.list().rows;
export const getMobileQuotes = () => quoteResource.list().mobileQuotes;

export const quoteService = {
    loadQuotes() {
        if (typeof window === 'undefined') return clone(mockQuotes);

        return clone(quoteResource.list().quotes ?? readJson(STORAGE_KEY, clone(mockQuotes)));
    },

    loadLocalQuotes() {
        if (typeof window === 'undefined') return [];

        return clone(readJson(STORAGE_KEY, [])).filter((quote) => quote?.local === true || String(quote?.id || '').includes('QT-TMP'));
    },

    saveQuotes(quotes) {
        if (typeof window === 'undefined') return;

        writeJson(STORAGE_KEY, quotes.filter((quote) => quote?.local === true || String(quote?.id || '').includes('QT-TMP')));
    },

    addLocalQuote(quote) {
        if (typeof window === 'undefined' || !quote) return quote;

        const localQuote = { ...quote, local: true };
        const current = this.loadLocalQuotes().filter((item) => item.id !== localQuote.id);
        writeJson(STORAGE_KEY, [localQuote, ...current]);
        return localQuote;
    },

    getTemporaryCart(runtimeItems = []) {
        const runtimeCartItems = normalizeCartItems(runtimeItems);

        if (runtimeCartItems.length) return { key: 'quoteCartStore', items: runtimeCartItems };
        if (typeof window === 'undefined') return null;

        for (const key of CART_KEYS) {
            const cart = readJson(key);
            const items = normalizeCartItems(cart);

            if (items.length) return { key, items };
        }

        return null;
    },

    createQuoteFromCart(cart) {
        const total = cart.items.reduce((sum, item) => sum + item.qty * item.price, 0);
        const number = Math.floor(Date.now() / 1000).toString().slice(-6);

        return {
            id: `#QT-TMP-${number}`,
            client: 'Cliente temporal',
            ref: 'REF: Carrito temporal',
            vehicle: 'Vehículo por definir',
            brand: 'Selección desde catálogo',
            date: 'Ahora',
            amount: total,
            status: 'Pendiente',
            archived: false,
            local: true,
            items: cart.items,
        };
    },

    submitTemporaryRequest(quote) {
        if (!quote) return null;

        return this.addLocalQuote({
            ...quote,
            status: 'Solicitud preparada',
            submittedAt: new Date().toISOString(),
            note: 'Solicitud preparada. La creación real en ERP queda pendiente de aprobación.',
        });
    },

    async createQuote(payload) {
        try {
            return await apiClient.post('/api/portal/quotes', payload);
        } catch {
            return null;
        }
    },

    fetchQuotes: (params = {}) => quoteResource.fetch(params),

    state: quoteResource.state,
};

export const fetchQuotes = (params = {}) => quoteResource.fetch(params);
export const quoteState = quoteResource.state;
