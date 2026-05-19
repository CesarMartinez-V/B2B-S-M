import { ref, watch } from 'vue';

export const useLocalStorage = (key, defaultValue = null) => {
    const read = () => {
        try {
            if (typeof window === 'undefined') return defaultValue;

            const value = window.localStorage.getItem(key);
            return value === null ? defaultValue : JSON.parse(value);
        } catch {
            return defaultValue;
        }
    };

    const state = ref(read());

    watch(state, (value) => {
        try {
            if (typeof window === 'undefined') return;

            if (value === null || value === undefined) {
                window.localStorage.removeItem(key);
                return;
            }

            window.localStorage.setItem(key, JSON.stringify(value));
        } catch {
            // Storage can fail in private mode; keep runtime state available.
        }
    }, { deep: true });

    return state;
};
