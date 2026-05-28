const WHATSAPP_NUMBER = '50494855657';

const cleanLine = (value) => String(value ?? '').replace(/[\r\n\t]+/g, ' ').trim();

export const buildWhatsAppUrl = (message = '') => {
    const safeMessage = cleanLine(message) || 'Hola, necesito apoyo con el Portal B2B de Inversiones S&M.';

    return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(safeMessage)}`;
};

export const useWhatsAppContact = () => {
    const openWhatsApp = (message = '') => {
        if (typeof window === 'undefined') return false;

        window.open(buildWhatsAppUrl(message), '_blank', 'noopener,noreferrer');
        return true;
    };

    return { openWhatsApp, buildWhatsAppUrl };
};
