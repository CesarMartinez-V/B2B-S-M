import { reactive } from 'vue';

const modal = reactive({
    open: false,
    title: '',
    message: '',
    icon: 'info',
    confirmText: 'Aceptar',
    cancelText: '',
    onConfirm: null,
});

export const useModal = () => {
    const openModal = ({ title = '', message = '', icon = 'info', confirmText = 'Aceptar', cancelText = '', onConfirm = null } = {}) => {
        modal.open = true;
        modal.title = title;
        modal.message = message;
        modal.icon = icon;
        modal.confirmText = confirmText;
        modal.cancelText = cancelText;
        modal.onConfirm = onConfirm;
    };

    const closeModal = () => {
        modal.open = false;
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
