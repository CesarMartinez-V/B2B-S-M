import { reactive } from 'vue';

const confirmState = reactive({
    open: false,
    title: '',
    message: '',
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
    tone: 'primary',
    onConfirm: null,
});

export const useConfirm = () => {
    const askConfirm = ({ title = '', message = '', confirmText = 'Confirmar', cancelText = 'Cancelar', tone = 'primary', onConfirm = null } = {}) => {
        confirmState.open = true;
        confirmState.title = title;
        confirmState.message = message;
        confirmState.confirmText = confirmText;
        confirmState.cancelText = cancelText;
        confirmState.tone = tone;
        confirmState.onConfirm = onConfirm;
    };

    const closeConfirm = () => {
        confirmState.open = false;
        confirmState.onConfirm = null;
    };

    const confirm = () => {
        const callback = confirmState.onConfirm;
        closeConfirm();

        if (callback) callback();
    };

    return {
        confirmState,
        askConfirm,
        closeConfirm,
        confirm,
    };
};
