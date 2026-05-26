import { computed } from 'vue';
import { useLocalStorage } from './composables/useLocalStorage.js';

const items = useLocalStorage('portal-quote-cart', []);
const QUOTE_MAX_QTY = 100;

const numberOrNull = (value) => {
    const number = Number(value);

    return Number.isFinite(number) ? number : null;
};

const normalizeItem = (product, quantity = 1) => {
    const availableQty = numberOrNull(product.availableQty ?? product.stock);
    const priceValue = Number(product.priceValue ?? product.price ?? 0) || 0;
    const canQuote = product.isAvailable === true || availableQty === null;

    return {
        id: product.id ?? product.sku,
        sku: product.sku ?? product.id,
        name: product.name ?? 'Producto sin nombre',
        brand: product.brand ?? 'Marca por confirmar',
        category: product.category ?? 'General',
        priceValue,
        quantity,
        availableQty,
        maxQty: availableQty,
        isAvailable: canQuote,
        availabilityLabel: product.availabilityLabel ?? product.stockLabel ?? (availableQty === null ? 'Consultar disponibilidad' : (availableQty > 0 ? 'Disponible' : 'No disponible')),
    };
};

export const useQuoteCartStore = () => {
    const updateQty = (productId, qty) => {
        const quantity = Math.min(QUOTE_MAX_QTY, Math.max(1, Number(qty) || 1));
        const item = items.value.find((current) => current.id === productId || current.sku === productId);

        if (!item) return false;

        item.quantity = quantity;
        items.value = [...items.value];
        return true;
    };

    const addProduct = (product) => {
        const maxQty = numberOrNull(product.availableQty ?? product.stock);
        const canQuote = product.isAvailable === true || maxQty === null;

        if (!canQuote || maxQty === 0) return false;

        const existing = items.value.find((item) => item.id === product.id);

        if (existing) {
            if (existing.quantity >= QUOTE_MAX_QTY) return false;

            existing.quantity += 1;
            items.value = [...items.value];
            return true;
        }

        items.value = [...items.value, normalizeItem(product, 1)];
        return true;
    };

    const removeItem = (productId) => {
        items.value = items.value.filter((item) => item.id !== productId && item.sku !== productId);
    };

    const clear = () => {
        items.value = [];
    };

    const count = computed(() => items.value.reduce((total, item) => total + item.quantity, 0));
    const total = computed(() => items.value.reduce((sum, item) => sum + (Number(item.priceValue || 0) * Number(item.quantity || 1)), 0));
    const subtotal = total;
    const hasItems = computed(() => items.value.length > 0);

    return {
        items,
        count,
        total,
        subtotal,
        hasItems,
        addItem: addProduct,
        addProduct,
        updateQty,
        removeItem,
        clear,
    };
};
