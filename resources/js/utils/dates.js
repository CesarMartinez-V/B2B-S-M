export const formatDate = (value, locale = 'es-ES') => new Intl.DateTimeFormat(locale, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
}).format(new Date(value));

export const nowIso = () => new Date().toISOString();
