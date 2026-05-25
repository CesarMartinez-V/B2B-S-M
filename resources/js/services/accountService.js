import { accountChart, creditUsage, desktopTransactions, mobileTransactions } from '../data/mockAccount.js';
import { toLempiraLabel } from '../utils/currency.js';
import { createDataAdapter } from './dataAdapter.js';

const normalize = (value) => String(value || '').toLowerCase().trim();
const firstText = (...values) => values.find((value) => String(value ?? '').trim() !== '') ?? '';
const optionObject = (option) => typeof option === 'object' && option !== null
    ? { label: option.label ?? option.value ?? '', value: option.value ?? option.label ?? '', count: option.count }
    : { label: String(option), value: String(option).toLowerCase() === 'todos' ? 'all' : String(option), count: undefined };

const extraTransactions = [
    { id: '#FAC-2023-864', date: '02 Oct, 2023', title: 'Filtros Premium Alto Flujo', type: 'Inventario Local', amount: 'L. 1,980.00', status: 'PAGADO' },
    { id: '#PAY-2023-211', date: '28 Sep, 2023', title: 'Pago Recibido - Transferencia', type: 'Banco Industrial', amount: '+L. 5,200.00', status: 'APLICADO' },
    { id: '#NC-2023-019', date: '20 Sep, 2023', title: 'Nota de Crédito por Devolución', type: 'Ajuste Comercial', amount: '+L. 410.00', status: 'APLICADO' },
];

const mockAccount = () => ({
    overview: {
        debtTotal: 'L. 42,850.00',
        creditAvailable: 'L. 15,150.00',
        creditTrend: '+12% vs mes anterior',
        creditUsed: '75% Utilizado',
        lastPayment: 'L. 5,200.00',
        lastPaymentDate: '12 Oct, 2023',
        nextDue: '14 Nov',
        nextDueText: 'Vencimiento en 5 días',
    },
    creditUsage,
    accountChart,
    movements: [...desktopTransactions, ...extraTransactions],
    mobileMovements: mobileTransactions,
    filters: ['Todos', 'PENDIENTE', 'PAGADO', 'VENCIDO', 'APLICADO'],
    periods: ['2023', '2022'],
});

const normalizeAccount = (payload) => {
    const data = payload.data ?? payload;
    const meta = payload.meta ?? data.meta ?? {};
    const includesSummary = meta.includes_summary !== false;
    const includesFilters = meta.includes_filters !== false;
    const overview = data.overview ?? {};

    return {
    overview: includesSummary ? {
        debtTotal: toLempiraLabel(firstText(overview.debtTotal, overview.debt_total, overview.debtTotalValue, overview.debt_total_value, 'L. 0.00')),
        debt_total: toLempiraLabel(firstText(overview.debt_total, overview.debtTotal, overview.debt_total_value, overview.debtTotalValue, 'L. 0.00')),
        debtTotalValue: Number(overview.debtTotalValue ?? overview.debt_total_value ?? 0),
        debt_total_value: Number(overview.debt_total_value ?? overview.debtTotalValue ?? 0),
        creditAvailable: firstText(overview.creditAvailable, overview.credit_available) ? toLempiraLabel(firstText(overview.creditAvailable, overview.credit_available)) : 'Consultar con vendedor',
        credit_available: firstText(overview.credit_available, overview.creditAvailable) ? toLempiraLabel(firstText(overview.credit_available, overview.creditAvailable)) : 'Consultar con vendedor',
        creditAvailableValue: Number(overview.creditAvailableValue ?? overview.credit_available_value ?? 0),
        credit_available_value: Number(overview.credit_available_value ?? overview.creditAvailableValue ?? 0),
        creditTrend: firstText(overview.creditTrend, overview.credit_trend, 'Saldo actualizado'),
        credit_trend: firstText(overview.credit_trend, overview.creditTrend, 'Saldo actualizado'),
        creditUsed: firstText(overview.creditUsed, overview.credit_used, 'Consultar'),
        credit_used: firstText(overview.credit_used, overview.creditUsed, 'Consultar'),
        lastPayment: firstText(overview.lastPayment, overview.last_payment) ? toLempiraLabel(firstText(overview.lastPayment, overview.last_payment)) : 'Sin pagos recientes',
        last_payment: firstText(overview.last_payment, overview.lastPayment) ? toLempiraLabel(firstText(overview.last_payment, overview.lastPayment)) : 'Sin pagos recientes',
        lastPaymentValue: Number(overview.lastPaymentValue ?? overview.last_payment_value ?? 0),
        last_payment_value: Number(overview.last_payment_value ?? overview.lastPaymentValue ?? 0),
        lastPaymentDate: firstText(overview.lastPaymentDate, overview.last_payment_date),
        last_payment_date: firstText(overview.last_payment_date, overview.lastPaymentDate),
        nextDue: firstText(overview.nextDue, overview.next_due),
        next_due: firstText(overview.next_due, overview.nextDue),
        nextDueText: firstText(overview.nextDueText, overview.next_due_text, 'Sin vencimiento próximo'),
        next_due_text: firstText(overview.next_due_text, overview.nextDueText, 'Sin vencimiento próximo'),
    } : {},
    creditUsage: data.creditUsage ?? data.credit_usage ?? creditUsage,
    accountChart: data.accountChart ?? data.chart ?? accountChart,
    movements: (data.movements ?? [...desktopTransactions, ...extraTransactions]).map((movement) => ({ ...movement, amount: toLempiraLabel(movement.amount), balance: toLempiraLabel(movement.balance) })),
    payments: (data.payments ?? []).map((movement) => ({ ...movement, meta: movement.meta ?? movement.date, amount: toLempiraLabel(movement.amount) })),
    mobileMovements: (data.mobileMovements ?? data.mobile_movements ?? data.movements ?? mobileTransactions).map((movement) => ({ ...movement, meta: movement.meta ?? movement.date, tone: movement.tone ?? (normalize(movement.status).includes('pendiente') ? 'danger' : 'success'), amount: toLempiraLabel(movement.amount) })),
    filters: includesFilters ? (data.filters ?? ['Todos', 'Pendiente', 'Pagado', 'Vencido', 'Aplicado']).map(optionObject) : [],
    periods: includesFilters ? (data.periods ?? ['2023', '2022']).map(optionObject) : [],
    dateRange: includesFilters ? (data.dateRange ?? { min: '', max: '' }) : { min: '', max: '' },
    meta,
    };
};

const accountResource = createDataAdapter({
    mock: mockAccount,
    endpoint: '/api/portal/account',
    extract: (response) => response ?? {},
    normalize: normalizeAccount,
    warmParams: { page: 1, per_page: 20, limit: 20 },
});

export const getAccountOverview = () => accountResource.list().overview;

export const getCreditUsage = () => accountResource.list().creditUsage;

export const getAccountChart = (period = '') => ({ ...accountResource.list().accountChart, period });

export const getAccountMovements = ({ filter = 'Todos', period = '', limit = 3 } = {}) => {
    const source = accountResource.list().movements;
    const filtered = source.filter((movement) => {
        const status = normalize(movement.status);
        const matchesFilter = filter === 'Todos' || status === normalize(filter);
        const matchesPeriod = !period || String(movement.date).includes(period);
        return matchesFilter && matchesPeriod;
    });

    return filtered.slice(0, limit);
};

export const getMobileMovements = () => accountResource.list().mobileMovements;

export const getMovementFilters = () => accountResource.list().filters;

export const getPeriods = () => accountResource.list().periods;
export const fetchAccount = (params = {}) => accountResource.fetch(params);
export const accountState = accountResource.state;
