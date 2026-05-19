import { computed } from 'vue';
import { useLocalStorage } from './composables/useLocalStorage.js';

const items = useLocalStorage('portal-quote-cart', []);

export const useQuoteCartStore = () => {
    const addProduct = (product) => {
        if (!product.isAvailable) return false;

        const existing = items.value.find((item) => item.id === product.id);
        const maxQty = Number(product.availableQty ?? product.stock ?? 0);

        if (existing) {
            if (maxQty > 0 && existing.quantity >= maxQty) return false;

            existing.quantity += 1;
            items.value = [...items.value];
            return true;
        }

        items.value = [...items.value, { ...product, quantity: 1 }];
        return true;
    };

    const clear = () => {
        items.value = [];
    };

    const count = computed(() => items.value.reduce((total, item) => total + item.quantity, 0));
    const subtotal = computed(() => items.value.reduce((total, item) => total + (item.priceValue * item.quantity), 0));

    return {
        items,
        count,
        subtotal,
        addProduct,
        clear,
    };
};
