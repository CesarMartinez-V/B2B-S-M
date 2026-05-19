import { accountChart, creditUsage, desktopTransactions, mobileTransactions } from '../data/mockAccount.js';
import { toLempiraLabel } from '../utils/currency.js';
import { createDataAdapter } from './dataAdapter.js';

const normalize = (value) => String(value || '').toLowerCase().trim();

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

const normalizeAccount = (payload) => ({
    overview: {
        debtTotal: toLempiraLabel(payload.overview?.debtTotal ?? payload.overview?.debt_total ?? 'L. 42,850.00'),
        creditAvailable: toLempiraLabel(payload.overview?.creditAvailable ?? payload.overview?.credit_available ?? 'L. 15,150.00'),
        creditTrend: payload.overview?.creditTrend ?? payload.overview?.credit_trend ?? '+12% vs mes anterior',
        creditUsed: payload.overview?.creditUsed ?? payload.overview?.credit_used ?? '75% Utilizado',
        lastPayment: toLempiraLabel(payload.overview?.lastPayment ?? payload.overview?.last_payment ?? 'L. 5,200.00'),
        lastPaymentDate: payload.overview?.lastPaymentDate ?? payload.overview?.last_payment_date ?? '12 Oct, 2023',
        nextDue: payload.overview?.nextDue ?? payload.overview?.next_due ?? '14 Nov',
        nextDueText: payload.overview?.nextDueText ?? payload.overview?.next_due_text ?? 'Vencimiento en 5 días',
    },
    creditUsage: payload.creditUsage ?? payload.credit_usage ?? creditUsage,
    accountChart: payload.accountChart ?? payload.chart ?? accountChart,
    movements: (payload.movements ?? [...desktopTransactions, ...extraTransactions]).map((movement) => ({ ...movement, amount: toLempiraLabel(movement.amount) })),
    mobileMovements: (payload.mobileMovements ?? payload.mobile_movements ?? mobileTransactions).map((movement) => ({ ...movement, amount: toLempiraLabel(movement.amount) })),
    filters: payload.filters ?? ['Todos', 'PENDIENTE', 'PAGADO', 'VENCIDO', 'APLICADO'],
    periods: payload.periods ?? ['2023', '2022'],
});

const accountResource = createDataAdapter({
    mock: mockAccount,
    endpoint: '/api/portal/account',
    extract: (response) => response?.data ?? {},
    normalize: normalizeAccount,
});

export const getAccountOverview = () => accountResource.list().overview;

export const getCreditUsage = () => accountResource.list().creditUsage;

export const getAccountChart = (period = '2023') => ({ ...accountResource.list().accountChart, period });

export const getAccountMovements = ({ filter = 'Todos', period = '2023', limit = 3 } = {}) => {
    const source = accountResource.list().movements;
    const filtered = source.filter((movement) => {
        const status = normalize(movement.status);
        const matchesFilter = filter === 'Todos' || status === normalize(filter);
        const matchesPeriod = String(movement.date).includes(period) || period === '2023';
        return matchesFilter && matchesPeriod;
    });

    return filtered.slice(0, limit);
};

export const getMobileMovements = () => accountResource.list().mobileMovements;

export const getMovementFilters = () => accountResource.list().filters;

export const getPeriods = () => accountResource.list().periods;
export const fetchAccount = (params = {}) => accountResource.fetch(params);
export const accountState = accountResource.state;
