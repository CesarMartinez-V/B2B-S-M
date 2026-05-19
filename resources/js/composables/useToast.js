import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

export const useToast = () => {
    const removeToast = (id) => {
        toasts.value = toasts.value.filter((toast) => toast.id !== id);
    };

    const showToast = ({ title = '', message = '', type = 'info', duration = 3200 }) => {
        const id = ++toastId;
        toasts.value.push({ id, title, message, type });

        if (duration > 0) {
            window.setTimeout(() => removeToast(id), duration);
        }

        return id;
    };

    return {
        toasts,
        showToast,
        removeToast,
        success: (message, title = 'Acción completada') => showToast({ title, message, type: 'success' }),
        error: (message, title = 'No se pudo completar') => showToast({ title, message, type: 'error' }),
        info: (message, title = 'Información') => showToast({ title, message, type: 'info' }),
    };
};
