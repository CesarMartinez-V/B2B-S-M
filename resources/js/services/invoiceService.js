import { invoiceSummary, invoices } from '../data/mockInvoices.js';
import { toLempiraLabel } from '../utils/currency.js';
import { createDataAdapter } from './dataAdapter.js';

const normalize = (value) => String(value || '').toLowerCase().trim();

const dateValue = (value) => {
    const months = { ene: 0, jan: 0, feb: 1, mar: 2, abr: 3, apr: 3, may: 4, jun: 5, jul: 6, ago: 7, aug: 7, sep: 8, oct: 9, nov: 10, dic: 11, dec: 11 };
    const clean = String(value || '').replace(',', '').split(/\s+/);
    if (clean.length < 3) return 0;
    return new Date(Number(clean[2]), months[normalize(clean[1]).slice(0, 3)] ?? 0, Number(clean[0])).getTime();
};

const invoiceResource = createDataAdapter({
    mock: () => ({ invoiceSummary, invoices, statuses: ['Todos', ...new Set(invoices.map((invoice) => invoice.status))] }),
    endpoint: '/api/portal/invoices',
    extract: (response) => response?.data ?? {},
    normalize: (payload) => ({
        invoiceSummary: (payload.invoiceSummary ?? payload.summary ?? invoiceSummary).map((item) => ({ ...item, value: toLempiraLabel(item.value), trend: toLempiraLabel(item.trend) })),
        invoices: (payload.invoices ?? invoices).map((invoice) => ({ ...invoice, total: toLempiraLabel(invoice.total), balance: toLempiraLabel(invoice.balance) })),
        statuses: payload.statuses ?? ['Todos', ...new Set((payload.invoices ?? invoices).map((invoice) => invoice.status))],
    }),
});

export const getInvoiceSummary = () => invoiceResource.list().invoiceSummary;

export const getInvoices = ({ query = '', status = 'Todos', from = '', to = '' } = {}) => {
    const term = normalize(query);
    const fromTime = from ? new Date(from).getTime() : null;
    const toTime = to ? new Date(to).getTime() : null;

    return invoiceResource.list().invoices.filter((invoice) => {
        const matchesTerm = !term || [invoice.number, invoice.status, invoice.total, invoice.seller].some((field) => normalize(field).includes(term));
        const matchesStatus = status === 'Todos' || invoice.status === status;
        const invoiceTime = dateValue(invoice.date);
        const matchesFrom = !fromTime || invoiceTime >= fromTime;
        const matchesTo = !toTime || invoiceTime <= toTime;

        return matchesTerm && matchesStatus && matchesFrom && matchesTo;
    });
};

export const getInvoiceStatuses = () => invoiceResource.list().statuses;
export const fetchInvoices = (params = {}) => invoiceResource.fetch(params);
export const invoiceState = invoiceResource.state;
