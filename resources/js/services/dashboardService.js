import { portalUser } from '../portalNavigation.js';
import { createDataAdapter } from './dataAdapter.js';

const fallbackDashboard = () => ({
    profile: {
        name: portalUser.name,
        company: 'Cliente B2B',
        code: 'Sin configurar',
        partnerLevel: 'Socio B2B',
        avatar: portalUser.avatar,
    },
    overview: {
        debtTotal: 'L. 0.00',
        debtTotalValue: 0,
        creditAvailable: 'L. 0.00',
        creditAvailableValue: 0,
        lastPayment: 'L. 0.00',
        lastPaymentValue: 0,
        lastPaymentDate: '',
        nextDue: '',
        nextDueText: 'No hay cliente B2B configurado para esta sesión.',
    },
    kpis: [
        { key: 'debt_total', icon: 'account_balance_wallet', label: 'Deuda total', value: 'L. 0.00', meta: 'Sin saldo pendiente', tone: 'primary' },
        { key: 'credit_available', icon: 'speed', label: 'Crédito disponible', value: 'L. 0.00', meta: 'Crédito disponible', tone: 'tertiary' },
        { key: 'overdue_invoices', icon: 'receipt_long', label: 'Facturas vencidas', value: '0', meta: 'Sin vencidas', tone: 'success' },
    ],
    recentActivity: [],
    chart: { type: 'monthly_cashflow', months: [], invoices: [], payments: [] },
    quickActions: [
        { icon: 'minor_crash', label: 'Catálogo', href: '/catalogo' },
        { icon: 'request_quote', label: 'Nueva cotización', href: '/cotizaciones' },
        { icon: 'receipt_long', label: 'Facturas', href: '/historial-facturas' },
        { icon: 'account_balance_wallet', label: 'Estado de cuenta', href: '/estado-de-cuenta' },
    ],
    promotions: [{ title: 'Promociones disponibles', text: 'Contenido editorial temporal', tone: 'secondary' }],
    featuredProducts: [],
    clientConfigured: false,
});

const normalizeDashboard = (payload = {}) => {
    const fallback = fallbackDashboard();
    const profile = { ...fallback.profile, ...(payload.profile ?? {}), avatar: payload.profile?.avatar || fallback.profile.avatar };
    const overview = { ...fallback.overview, ...(payload.overview ?? {}) };
    const chart = { ...fallback.chart, ...(payload.chart ?? {}) };

    return {
        profile,
        overview,
        kpis: payload.kpis?.length ? payload.kpis : fallback.kpis,
        recentActivity: payload.recentActivity ?? payload.recent_activity ?? fallback.recentActivity,
        chart,
        quickActions: payload.quickActions ?? payload.quick_actions ?? fallback.quickActions,
        promotions: payload.promotions?.length ? payload.promotions : fallback.promotions,
        featuredProducts: payload.featuredProducts ?? payload.featured_products ?? fallback.featuredProducts,
        clientConfigured: payload.meta?.client_configured ?? payload.client_configured ?? Boolean(payload.profile),
    };
};

const dashboardResource = createDataAdapter({
    mock: fallbackDashboard,
    endpoint: '/api/portal/dashboard',
    extract: (response) => ({ ...(response?.data ?? {}), meta: response?.meta ?? {} }),
    normalize: normalizeDashboard,
    warmParams: {},
});

export const getDashboardProfile = () => dashboardResource.list().profile;
export const getDashboardOverview = () => dashboardResource.list().overview;
export const getDashboardKpis = () => dashboardResource.list().kpis;
export const getDashboardActivity = () => dashboardResource.list().recentActivity;
export const getDashboardChart = () => dashboardResource.list().chart;
export const getDashboardQuickActions = () => dashboardResource.list().quickActions;
export const getDashboardPromotions = () => dashboardResource.list().promotions;
export const getDashboardClientConfigured = () => dashboardResource.list().clientConfigured;
export const fetchDashboard = (params = {}) => dashboardResource.fetch(params);
export const dashboardState = dashboardResource.state;
