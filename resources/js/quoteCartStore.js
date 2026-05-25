import { computed } from 'vue';
import { useLocalStorage } from './composables/useLocalStorage.js';

const items = useLocalStorage('portal-quote-cart', []);

const numberOrNull = (value) => {
    const number = Number(value);

    return Number.isFinite(number) ? number : null;
};

const normalizeItem = (product, quantity = 1) => {
    const availableQty = numberOrNull(product.availableQty ?? product.stock);
    const priceValue = Number(product.priceValue ?? product.price ?? 0) || 0;

    return {
        id: product.id ?? product.sku,
        sku: product.sku ?? product.id,
        name: product.name ?? 'Producto sin nombre',
        brand: product.brand ?? 'Marca por confirmar',
        category: product.category ?? 'General',
        priceValue,
        quantity,
        availableQty,
        isAvailable: product.isAvailable === true,
    };
};

export const useQuoteCartStore = () => {
    const updateQty = (productId, qty) => {
        const quantity = Math.max(1, Number(qty) || 1);
        const item = items.value.find((current) => current.id === productId || current.sku === productId);

        if (!item) return false;

        const maxQty = numberOrNull(item.availableQty);
        if (maxQty !== null && maxQty > 0 && quantity > maxQty) return false;

        item.quantity = quantity;
        items.value = [...items.value];
        return true;
    };

    const addProduct = (product) => {
        if (!product.isAvailable) return false;

        const existing = items.value.find((item) => item.id === product.id);
        const maxQty = numberOrNull(product.availableQty ?? product.stock);

        if (existing) {
            if (maxQty !== null && maxQty > 0 && existing.quantity >= maxQty) return false;

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
