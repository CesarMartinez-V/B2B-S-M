import { reactive } from 'vue';

const modal = reactive({
    open: false,
    title: '',
    message: '',
    icon: 'info',
    confirmText: 'Aceptar',
    cancelText: '',
    size: 'md',
    detail: null,
    onConfirm: null,
});

export const useModal = () => {
    const openModal = ({ title = '', message = '', icon = 'info', confirmText = 'Aceptar', cancelText = '', size = 'md', detail = null, onConfirm = null } = {}) => {
        modal.open = true;
        modal.title = title;
        modal.message = message;
        modal.icon = icon;
        modal.confirmText = confirmText;
        modal.cancelText = cancelText;
        modal.size = size;
        modal.detail = detail;
        modal.onConfirm = onConfirm;
    };

    const closeModal = () => {
        modal.open = false;
        modal.detail = null;
        modal.onConfirm = null;
    };

    const confirmModal = () => {
        const callback = modal.onConfirm;
        closeModal();

        if (callback) callback();
    };

    return {
        modal,
        openModal,
        closeModal,
        confirmModal,
    };
};
