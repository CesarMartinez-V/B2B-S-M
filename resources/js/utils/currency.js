export const parseCurrency = (value) => Number(String(value).replace(/[^0-9.-]+/g, '')) || 0;

export const formatCurrency = (value) => `L. ${new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
}).format(Number(value) || 0)}`;

export const toLempiraLabel = (value) => String(value || '').replace(/\$\s?/g, 'L. ');
