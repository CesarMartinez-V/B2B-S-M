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
        quotes: payload.quotes ?? mockQuotes,
        rows: payload.rows ?? quoteRows,
        mobileQuotes: payload.mobileQuotes ?? payload.mobile_quotes ?? mobileQuotes,
    }),
});

const normalizeCartItems = (cart) => {
    const source = Array.isArray(cart) ? cart : cart?.items || cart?.productos || cart?.products || [];

    return source.map((item, index) => {
        const qty = Number(item.qty || item.quantity || item.cantidad || 1);
        const rawPrice = item.priceValue ?? item.price ?? item.precio ?? item.amount ?? 0;
        const price = typeof rawPrice === 'number' ? rawPrice : parseCurrency(rawPrice);

        return {
            name: item.name || item.nombre || item.title || `Item ${index + 1}`,
            sku: item.sku || item.id || item.code || `TMP-${index + 1}`,
            qty,
            price,
        };
    });
};

export const getQuoteStats = () => quoteResource.list().stats;
export const getQuoteRows = () => quoteResource.list().rows;
export const getMobileQuotes = () => quoteResource.list().mobileQuotes;

export const quoteService = {
    loadQuotes() {
        if (typeof window === 'undefined') return clone(mockQuotes);

        return readJson(STORAGE_KEY, clone(quoteResource.list().quotes));
    },

    saveQuotes(quotes) {
        if (typeof window === 'undefined') return;

        writeJson(STORAGE_KEY, quotes);
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
            vehicle: 'Vehiculo por definir',
            brand: 'Seleccion desde catalogo',
            date: 'Ahora',
            amount: total,
            status: 'pending',
            archived: false,
            items: cart.items,
        };
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
