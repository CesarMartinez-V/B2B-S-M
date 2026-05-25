<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AppShell from '../components/portal/AppShell.vue';
import StatusBadge from '../components/ui/StatusBadge.vue';
import { useModal } from '../composables/useModal.js';
import { usePortalActions } from '../composables/usePortalActions.js';
import { usePdfExport } from '../composables/usePdfExport.js';
import { useToast } from '../composables/useToast.js';
import { useAccountStore } from '../stores/accountStore.js';

const avatar = 'https://lh3.googleusercontent.com/aida-public/AB6AXuCE7oCQ5DCsDpItHirFVe-xmQkqHWP-b-Up_KyarNR_PTsG_wP9N4eTgmvb69nhxcaA9ZoqQ74dJdGWhtQd772wgZygIwbvO1wkBkZor5KVJcOvjbvzE3mOHyuWdkYWT_kYTyBraZHYRW9TzVP41V_yJ6gBAgMB4NMTeJzrEkk7pr-GwLRxgwdi_F79HnLH-wQ0cWs4O7ibC0hGw96CxaeRUut2_pIN9MxorxpVRD7qr2jDSaIN6mONFWxGbyjhQQEvLSeUWVNJrA4';

const { openModal } = useModal();
const { openContactSeller } = usePortalActions();
const { printDocument } = usePdfExport();
const { info, success } = useToast();

const accountStore = useAccountStore();
const accountData = ref({
    overview: { debtTotal: 'L. 0.00', creditAvailable: 'L. 0.00', creditTrend: 'Cargando...', creditUsed: 'Consultar', lastPayment: 'L. 0.00', lastPaymentDate: '', nextDue: '', nextDueText: 'Cargando movimientos...' },
    creditUsage: [],
    accountChart: { months: [], bars: [] },
    movements: [],
    payments: [],
    filters: [{ label: 'Todos', value: 'all', count: 0 }],
    periods: [],
    meta: {},
});
const loadedMovements = ref([]);
const accountMeta = ref({});
const movementFilter = ref('all');
const selectedPeriod = ref('');
const accountQuery = ref('');
const loadingInitial = ref(false);
const loadingMore = ref(false);
let requestSeq = 0;
const overview = computed(() => accountData.value?.overview ?? {});
const firstText = (...values) => values.find((value) => String(value ?? '').trim() !== '') ?? '';
const accountOverview = computed(() => ({
    debtTotal: firstText(overview.value.debt_total, overview.value.debtTotal, overview.value.debt_total_value, overview.value.debtTotalValue, 'L. 0.00'),
    creditAvailable: firstText(overview.value.credit_available, overview.value.creditAvailable, 'Consultar con vendedor'),
    creditTrend: firstText(overview.value.credit_trend, overview.value.creditTrend, 'Saldo actualizado'),
    creditUsed: firstText(overview.value.credit_used, overview.value.creditUsed, 'Consultar'),
    lastPayment: firstText(overview.value.last_payment, overview.value.lastPayment, 'Sin pagos recientes'),
    lastPaymentDate: firstText(overview.value.last_payment_date, overview.value.lastPaymentDate),
    nextDue: firstText(overview.value.next_due, overview.value.nextDue),
    nextDueText: firstText(overview.value.next_due_text, overview.value.nextDueText, 'Sin vencimiento próximo'),
}));
const creditUsage = computed(() => accountData.value?.creditUsage ?? []);
const supportedFilterValues = ['all', 'Vencida', 'Pendiente'];
const filters = computed(() => {
    const source = accountData.value?.filters?.length ? accountData.value.filters : [{ label: 'Todos', value: 'all', count: 0 }];
    const filtered = source.filter((filter) => supportedFilterValues.includes(filter.value));
    return filtered.length ? filtered : [{ label: 'Todos', value: 'all', count: 0 }];
});
const periods = computed(() => accountData.value?.periods ?? []);
const dateRange = computed(() => accountData.value?.dateRange ?? { min: '', max: '' });
const chart = computed(() => ({ ...(accountData.value?.accountChart ?? { months: [], bars: [] }), period: selectedPeriod.value }));
const desktopTransactions = computed(() => movementFilter.value === 'Aplicado' ? (accountData.value?.payments ?? []) : loadedMovements.value);
const allFilteredMovements = computed(() => desktopTransactions.value);
const mobileTransactions = computed(() => desktopTransactions.value.map((row) => ({ ...row, meta: row.meta ?? row.date })));
const hasMoreMovements = computed(() => movementFilter.value !== 'Aplicado' && Number(accountMeta.value.current_page ?? accountMeta.value.page ?? 1) < Number(accountMeta.value.last_page ?? 1));
const isRefreshing = computed(() => accountStore.state.refreshing);
const months = computed(() => chart.value.months);
const bars = computed(() => chart.value.bars);

const accountParams = (page = 1) => ({
    page,
    per_page: 20,
    limit: 20,
    filter: movementFilter.value === 'all' ? '' : movementFilter.value,
    period: selectedPeriod.value,
    query: accountQuery.value,
    include_summary: page === 1 ? 1 : 0,
    include_filters: page === 1 ? 1 : 0,
});

const loadAccount = async ({ page = 1, append = false } = {}) => {
    const seq = ++requestSeq;
    const params = accountParams(page);
    const cached = accountStore.getCached(params);
    if (append) loadingMore.value = true;
    else loadingInitial.value = true;

    if (cached && !append) {
        accountData.value = cached;
        accountMeta.value = cached.meta ?? {};
        loadedMovements.value = [...(cached.movements ?? [])];
    }

    const data = cached && !accountStore.isFresh(params)
        ? await accountStore.refreshInBackground(params)
        : await accountStore.fetchSummary(params);
    if (seq !== requestSeq) return;
    if (data) {
        const includesSummary = data.meta?.includes_summary !== false;
        const includesFilters = data.meta?.includes_filters !== false;
        accountData.value = {
            ...data,
            overview: !append && includesSummary && data.overview && Object.keys(data.overview).length ? data.overview : accountData.value.overview,
            filters: !append && includesFilters && data.filters?.length ? data.filters : accountData.value.filters,
            periods: !append && includesFilters && data.periods?.length ? data.periods : accountData.value.periods,
            dateRange: !append && includesFilters && (data.dateRange?.min || data.dateRange?.max) ? data.dateRange : accountData.value.dateRange,
            payments: data.payments?.length ? data.payments : accountData.value.payments,
        };
        accountMeta.value = data.meta ?? {};
        if (append) {
            const seen = new Set(loadedMovements.value.map((row) => `${row.id}-${row.date}`));
            const nextRows = (data.movements ?? []).filter((row) => !seen.has(`${row.id}-${row.date}`));
            loadedMovements.value = [...loadedMovements.value, ...nextRows];
        } else {
            loadedMovements.value = [...(data.movements ?? [])];
        }
    }

    if (!append && Number(data?.meta?.last_page ?? 1) > 1) void accountStore.prefetch({ ...accountParams(2), page: 2 });

    loadingInitial.value = false;
    loadingMore.value = false;
};

watch([movementFilter, selectedPeriod, accountQuery], () => {
    loadedMovements.value = [];
    void loadAccount({ page: 1 });
});

onMounted(() => {
    void loadAccount({ page: 1 });
});

const movementLines = (row) => [`ID: ${row.id || row.title}`, `Fecha: ${row.date || row.meta}`, `Detalle: ${row.title}`, `Tipo: ${row.type || row.status}`, `Monto: ${row.amount}`, `Estado: ${row.status}`];

const exportAccount = () => {
    const lines = [
        `Deuda total: ${accountOverview.value.debtTotal}`,
        `Crédito disponible: ${accountOverview.value.creditAvailable}`,
        `Último pago: ${accountOverview.value.lastPayment} (${accountOverview.value.lastPaymentDate})`,
        `Periodo: ${selectedPeriod.value}`,
        ...allFilteredMovements.value.map((row) => `${row.id} | ${row.date} | ${row.title} | ${row.amount} | ${row.status}`),
    ];
    if (printDocument({ title: 'Estado de Cuenta', sections: [{ heading: 'Resumen financiero', lines }] })) success('Estado de cuenta listo para imprimir o guardar como PDF');
};

const contactSeller = () => {
    openContactSeller({ title: 'Contactar con vendedor', reason: `Revisión de estado de cuenta. Deuda: ${accountOverview.value.debtTotal}. Crédito disponible: ${accountOverview.value.creditAvailable}.` });
};

const showReceipt = () => {
    if (!accountOverview.value.lastPaymentDate || accountOverview.value.lastPayment === 'Sin pagos recientes') {
        openModal({ title: 'Comprobante no disponible', message: 'Sin comprobante disponible para el último pago en la vista actual.', icon: 'payments', confirmText: 'Cerrar' });
        return;
    }

    openModal({ title: 'Comprobante de último pago', message: `Pago recibido: ${accountOverview.value.lastPayment}\nFecha: ${accountOverview.value.lastPaymentDate || 'Sin fecha registrada'}\nEstado: Aplicado`, icon: 'payments', confirmText: 'Cerrar' });
    info('Comprobante abierto');
};

const showMovement = (row) => {
    openModal({ title: `Movimiento ${row.id || row.title}`, message: movementLines(row).join('\n'), icon: 'visibility', confirmText: 'Cerrar' });
};

const loadMore = () => {
    if (!hasMoreMovements.value || loadingMore.value) return;
    const nextPage = Number(accountMeta.value.current_page ?? accountMeta.value.page ?? 1) + 1;
    void loadAccount({ page: nextPage, append: true }).then(() => success('Se cargaron más movimientos'));
};
</script>

<template>
    <div class="account-page">
        <AppShell active-route="/estado-de-cuenta" desktop-search-placeholder="Buscar socio, factura o pedido..." mobile-title="Inversiones S&amp;M" :avatar-src="avatar">
            <template #desktop>
                <section class="desktop-content shell-content">
                    <header class="page-title"><div><h2>Estado de Cuenta</h2><p>Resumen financiero detallado y control de crédito B2B.</p></div><div><button class="ghost" @click="exportAccount"><span class="material-symbols-outlined">download</span>Exportar PDF</button><button class="pay" @click="contactSeller">Contactar con vendedor</button></div></header>
                    <section class="metric-grid"><article class="metric elevated"><div class="glow"></div><header><span class="metric-icon material-symbols-outlined">account_balance</span><b class="danger">{{ accountOverview.creditTrend }}</b></header><p>Deuda Total</p><h3>{{ accountOverview.debtTotal }}</h3><small><span class="material-symbols-outlined">info</span>{{ accountOverview.nextDueText }}</small></article><article class="metric elevated"><div class="glow tertiary"></div><header><span class="metric-icon tertiary material-symbols-outlined">speed</span><b>{{ accountOverview.creditUsed }}</b></header><p>Crédito Disponible</p><h3>{{ accountOverview.creditAvailable }}</h3><div class="progress"><i></i></div></article><article class="last-payment glass"><p>Último Pago</p><h3>{{ accountOverview.lastPayment }}</h3><small>{{ accountOverview.lastPaymentDate ? `Recibido el ${accountOverview.lastPaymentDate}` : 'Sin fecha registrada' }}</small><button type="button" class="receipt-link" @click="showReceipt">Ver comprobante <span class="material-symbols-outlined">arrow_forward</span></button></article></section>
                    <section class="visual-grid"><article class="chart-card elevated"><header><h4>Flujo de Caja Anual</h4><select v-model="selectedPeriod"><option value="">Todos</option><option v-for="period in periods" :key="period.value" :value="period.value">{{ period.label }}{{ Number.isFinite(period.count) ? ` (${period.count})` : '' }}</option></select></header><div class="line-chart"><svg viewBox="0 0 400 150" preserveAspectRatio="none"><defs><linearGradient id="cashGrad" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#7dd3fc" stop-opacity="0.5"/><stop offset="100%" stop-color="#7dd3fc" stop-opacity="0"/></linearGradient></defs><path d="M0 120 Q 50 110, 100 80 T 200 40 T 300 90 T 400 30 L 400 150 L 0 150 Z" fill="url(#cashGrad)"/><path d="M0 120 Q 50 110, 100 80 T 200 40 T 300 90 T 400 30" fill="none" stroke="#7dd3fc" stroke-linecap="round" stroke-width="3"/><circle cx="100" cy="80" r="4" fill="#0a0e1a" stroke="#7dd3fc" stroke-width="2"/><circle cx="200" cy="40" r="4" fill="#0a0e1a" stroke="#7dd3fc" stroke-width="2"/><circle cx="300" cy="90" r="4" fill="#0a0e1a" stroke="#7dd3fc" stroke-width="2"/></svg></div><footer><span>ENE</span><span>MAR</span><span>MAY</span><span>JUL</span><span>SEP</span><span>NOV</span></footer></article><article class="usage-card elevated"><header><h4>Uso de Línea de Crédito</h4><div><span><i></i>Usado</span><span><i></i>Límite</span></div></header><section><div v-for="item in creditUsage" :key="item.label" class="usage-row"><p><span>{{ item.label }}</span><b :class="item.tone">{{ item.value }}%</b></p><div><i :class="item.tone" :style="{ width: `${item.value}%` }"></i></div></div></section></article></section>
                    <section class="transactions elevated"><header><h4>Movimientos Recientes <small v-if="isRefreshing">Actualizando...</small></h4><div><input v-model="accountQuery" :min="dateRange.min || undefined" :max="dateRange.max || undefined" placeholder="Buscar movimiento" type="search"><select v-model="movementFilter"><option v-for="filter in filters" :key="filter.value" :value="filter.value">{{ filter.label }}{{ Number.isFinite(filter.count) ? ` (${filter.count})` : '' }}</option></select><select v-model="selectedPeriod"><option value="">Todos los periodos</option><option v-for="period in periods" :key="period.value" :value="period.value">{{ period.label }}{{ Number.isFinite(period.count) ? ` (${period.count})` : '' }}</option></select></div></header><table :class="{ 'is-changing': loadingInitial && !desktopTransactions.length }"><thead><tr><th>ID Factura</th><th>Fecha</th><th>Descripción</th><th>Monto</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><tr v-for="row in desktopTransactions" :key="`${row.id}-${row.date}`"><td>{{ row.id }}</td><td>{{ row.date }}</td><td><strong>{{ row.title }}</strong><small>{{ row.type }}</small></td><td>{{ row.amount }}</td><td><StatusBadge :status="row.status" size="sm" /></td><td><button @click="showMovement(row)"><span class="material-symbols-outlined">visibility</span></button></td></tr><tr v-if="!desktopTransactions.length"><td colspan="6" class="empty-state">{{ loadingInitial ? 'Cargando movimientos...' : 'No hay movimientos para el filtro seleccionado.' }}</td></tr></tbody></table><footer><button v-if="hasMoreMovements || loadingMore" :disabled="loadingMore" @click="loadMore">{{ loadingMore ? 'CARGANDO...' : 'CARGAR 20 MÁS' }} <span class="material-symbols-outlined">expand_more</span></button><span v-else-if="desktopTransactions.length" class="no-more">No hay más movimientos</span></footer></section>
                </section>
            </template>
            <template #mobile>
            <main class="mobile-main"><header class="mobile-title"><h1>Estado de Cuenta</h1><p>Resumen financiero detallado y transacciones recientes</p></header><section class="mobile-summary"><article class="balance glass"><div></div><p>Total a cobrar</p><h2>{{ accountOverview.debtTotal }}</h2><small><b><span class="material-symbols-outlined">trending_up</span>{{ accountOverview.creditTrend }}</b></small></article><article class="due glass"><p>Próximo vencimiento</p><h3>{{ accountOverview.nextDue || 'Consultar' }}</h3><div><i></i></div><small>{{ accountOverview.nextDueText }}</small></article></section><section class="mobile-activity glass"><header><h3>Actividad mensual</h3><div><span><i></i>Ventas</span><span><i></i>Pagos</span></div></header><div class="bars"><i v-for="(bar, index) in bars" :key="`${bar}-${index}`" :class="index % 2 ? 'tertiary' : 'primary'" :style="{ height: `${bar}%` }"></i></div><footer><span v-for="month in months" :key="month">{{ month }}</span></footer></section><section class="mobile-table glass"><header><h3>Transacciones recientes</h3><select v-model="movementFilter" aria-label="Filtrar movimientos"><option v-for="filter in filters" :key="filter.value" :value="filter.value">{{ filter.label }}{{ Number.isFinite(filter.count) ? ` (${filter.count})` : '' }}</option></select></header><label class="mobile-account-search"><span class="material-symbols-outlined">search</span><input v-model="accountQuery" type="search" placeholder="Buscar movimiento"></label><div class="mobile-head"><b>Detalle</b><b>Monto</b></div><article v-for="row in mobileTransactions" :key="`${row.id}-${row.date}`" @click="showMovement(row)"><div><strong>{{ row.title }}</strong><small>{{ row.meta }}</small></div><div><b :class="row.tone">{{ row.amount }}</b><StatusBadge :status="row.status" size="sm" /></div></article><p v-if="!mobileTransactions.length" class="empty-state">{{ loadingInitial ? 'Cargando movimientos...' : 'No hay movimientos para el filtro seleccionado.' }}</p><footer><button v-if="hasMoreMovements || loadingMore" :disabled="loadingMore" type="button" @click="loadMore">{{ loadingMore ? 'Cargando...' : 'Cargar 20 más' }} <span class="material-symbols-outlined">expand_more</span></button><span v-else-if="mobileTransactions.length" class="no-more">No hay más movimientos</span></footer></section></main>
            <button class="pdf-fab" @click="exportAccount"><span class="material-symbols-outlined">picture_as_pdf</span>Descargar PDF</button>
            </template>
        </AppShell>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;500;600;700;800;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');
.account-page{min-height:100vh;background:#0a0e1a;color:#e0e8f0;font-family:Inter,sans-serif}.material-symbols-outlined{font-family:'Material Symbols Outlined';font-feature-settings:'liga';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;line-height:1}.glass{background:rgba(15,21,36,.6);backdrop-filter:blur(16px);border:1px solid rgba(125,211,252,.1)}.elevated{background:rgba(15,21,36,.75);backdrop-filter:blur(24px);border:1px solid rgba(125,211,252,.15)}a{text-decoration:none;color:inherit}button,input,select{font:inherit}.mobile-view{display:none}.sidebar{position:fixed;left:0;top:0;z-index:50;width:288px;height:100vh;display:flex;flex-direction:column;padding:32px 16px;background:rgba(17,24,40,.8);backdrop-filter:blur(40px);border-right:1px solid rgba(255,255,255,.05);box-shadow:0 25px 50px rgba(10,14,26,.5)}.brand{padding:0 16px;margin-bottom:40px}.brand h1{margin:0;color:#7dd3fc;font-size:20px;font-weight:900;text-shadow:0 0 10px rgba(125,211,252,.3)}.brand p{margin:8px 0 0;color:rgba(160,180,196,.6);font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase}.sidebar nav{flex:1}.sidebar nav a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:rgba(160,180,196,.7);transition:.3s}.sidebar nav a.active{transform:translateX(4px);border-left:4px solid #7dd3fc;border-radius:0 8px 8px 0;background:rgba(14,77,110,.2);color:#7dd3fc;box-shadow:0 0 15px rgba(125,211,252,.1)}.sidebar nav a:not(.active):hover{color:#e0e8f0;background:rgba(255,255,255,.05)}.sidebar-bottom{margin-top:auto;padding:0 8px}.new-quote{display:flex;align-items:center;justify-content:center;gap:8px;padding:12px;border:1px solid rgba(125,211,252,.4);border-radius:8px;background:rgba(125,211,252,.2);color:#7dd3fc;font-weight:700}.sidebar-bottom footer{margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.05)}.sidebar-bottom footer a{display:flex;align-items:center;gap:12px;padding:10px 16px;color:rgba(160,180,196,.7)}.sidebar-bottom footer a.logout{color:rgba(255,107,107,.7)}.desktop-main{min-height:100vh;margin-left:288px;padding-bottom:32px}.topbar{position:sticky;top:0;z-index:40;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;background:rgba(20,28,46,.6);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.1);box-shadow:0 0 30px rgba(125,211,252,.05)}.topbar>div{display:flex;align-items:center;gap:16px}.topbar>div:first-child>span{display:none;font-weight:700;letter-spacing:.14em;text-transform:uppercase}.topbar label{display:flex;align-items:center;gap:8px;width:340px;padding:13px 18px;border:1px solid rgba(255,255,255,.1);border-radius:999px;background:rgba(255,255,255,.05);color:#7dd3fc}.topbar input{width:100%;border:0;outline:0;background:transparent;color:#e0e8f0}.topbar button{border:0;background:transparent;color:#a0b4c4}.topbar i{width:1px;height:32px;background:rgba(255,255,255,.1);margin:0 8px}.user{display:flex;align-items:center;gap:12px;font-size:14px;font-weight:700}.user img{width:32px;height:32px;border-radius:999px;object-fit:cover;border:1px solid rgba(125,211,252,.3)}.desktop-content{max-width:1280px;margin:0 auto;padding:40px;display:flex;flex-direction:column;gap:40px}.page-title{display:flex;align-items:flex-end;justify-content:space-between;gap:24px}.page-title h2{margin:0;color:#fff;font-size:30px;font-weight:800;letter-spacing:-.04em}.page-title p{margin:8px 0 0;color:#a0b4c4}.page-title>div:last-child{display:flex;gap:12px}.page-title button{display:flex;align-items:center;gap:8px;padding:12px 20px;border-radius:10px;font-weight:700}.ghost{border:1px solid rgba(125,211,252,.1);background:rgba(15,21,36,.6);color:#e0e8f0}.pay{border:0;background:#7dd3fc;color:#001f2e;box-shadow:0 0 20px rgba(125,211,252,.3)}.metric-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}.metric,.last-payment{position:relative;overflow:hidden;border-radius:16px;padding:32px}.metric .glow{position:absolute;right:-16px;top:-16px;width:128px;height:128px;border-radius:999px;background:rgba(125,211,252,.1);filter:blur(48px)}.metric .glow.tertiary{background:rgba(200,160,240,.1)}.metric header{position:relative;display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px}.metric-icon{display:flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:16px;background:rgba(125,211,252,.1);color:#7dd3fc}.metric-icon.tertiary{background:rgba(200,160,240,.1);color:#c8a0f0}.metric header b{padding:4px 8px;border-radius:6px;background:rgba(125,211,252,.1);color:#7dd3fc;font-size:12px}.metric header b.danger{background:rgba(255,107,107,.1);color:#ff6b6b}.metric p,.last-payment p{margin:0;color:#a0b4c4;font-size:14px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.metric h3,.last-payment h3{margin:8px 0 0;color:#fff;font-size:40px;font-weight:900;letter-spacing:-.04em;text-shadow:0 0 15px rgba(125,211,252,.2)}.metric small{display:flex;align-items:center;gap:4px;margin-top:24px;color:#a0b4c4}.metric small span{color:#7dd3fc;font-size:14px}.progress{height:8px;margin-top:24px;overflow:hidden;border-radius:999px;background:rgba(255,255,255,.05)}.progress i{display:block;width:75%;height:100%;background:#c8a0f0;box-shadow:0 0 10px rgba(200,160,240,.5)}.last-payment{display:flex;flex-direction:column;justify-content:center;border-style:dashed}.last-payment h3{font-size:30px}.last-payment small{margin-top:4px;color:#a0b4c4}.last-payment a{display:flex;align-items:center;gap:4px;margin-top:24px;color:#7dd3fc;font-weight:800}.last-payment a span{font-size:16px}.visual-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}.chart-card,.usage-card{border-radius:16px;padding:24px}.chart-card header,.usage-card header{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px}.chart-card h4,.usage-card h4{margin:0;color:#fff}.chart-card select{padding:10px 16px;border:1px solid rgba(255,255,255,.1);border-radius:12px;background:rgba(255,255,255,.05);color:#a0b4c4}.line-chart{height:256px;padding:12px}.line-chart svg{width:100%;height:100%;filter:drop-shadow(0 0 8px rgba(125,211,252,.7))}.chart-card footer{display:flex;justify-content:space-between;padding:0 12px;color:rgba(160,180,196,.4);font-size:10px}.usage-card header>div{display:flex;gap:16px}.usage-card header span{display:flex;align-items:center;gap:6px;color:#a0b4c4;font-size:10px}.usage-card header i{width:8px;height:8px;border-radius:999px;background:#7dd3fc}.usage-card header span:last-child i{background:rgba(255,255,255,.1)}.usage-card section{display:flex;flex-direction:column;gap:24px}.usage-row p{display:flex;justify-content:space-between;margin:0 0 8px;font-size:12px;font-weight:700}.usage-row b.primary{color:#7dd3fc}.usage-row b.tertiary{color:#c8a0f0}.usage-row>div{height:12px;overflow:hidden;border:1px solid rgba(255,255,255,.05);border-radius:999px;background:rgba(255,255,255,.05)}.usage-row i{display:block;height:100%;background:linear-gradient(90deg,rgba(125,211,252,.4),#7dd3fc);box-shadow:0 0 15px rgba(125,211,252,.3)}.usage-row i.tertiary{background:linear-gradient(90deg,rgba(200,160,240,.4),#c8a0f0);box-shadow:0 0 15px rgba(200,160,240,.3)}.transactions{overflow:hidden;border-radius:16px}.transactions>header{display:flex;align-items:center;justify-content:space-between;padding:24px;border-bottom:1px solid rgba(255,255,255,.05)}.transactions h4{margin:0;font-size:18px}.transactions header div{display:flex;gap:8px}.transactions header button{padding:8px 16px;border:1px solid rgba(255,255,255,.1);border-radius:8px;background:rgba(255,255,255,.05);color:#a0b4c4}.transactions table{width:100%;border-collapse:collapse}.transactions th{padding:16px 24px;background:rgba(255,255,255,.05);color:#a0b4c4;font-size:10px;font-weight:900;letter-spacing:.14em;text-align:left;text-transform:uppercase}.transactions th:nth-child(4),.transactions td:nth-child(4){text-align:right}.transactions th:nth-child(5),.transactions td:nth-child(5){text-align:center}.transactions td{padding:20px 24px;border-top:1px solid rgba(255,255,255,.05);color:#e0e8f0}.transactions td:first-child{color:#7dd3fc;font-family:monospace}.transactions td:nth-child(2){color:#a0b4c4}.transactions td strong,.transactions td small{display:block}.transactions td small{margin-top:4px;color:rgba(160,180,196,.6);font-size:10px;text-transform:uppercase}.transactions td:nth-child(5) span{display:inline-flex;padding:4px 12px;border:1px solid rgba(125,211,252,.2);border-radius:999px;background:rgba(125,211,252,.1);color:#7dd3fc;font-size:10px;font-weight:800}.transactions td:nth-child(5) span.danger{border-color:rgba(255,107,107,.2);background:rgba(255,107,107,.1);color:#ff6b6b}.transactions td button{border:0;background:transparent;color:#a0b4c4}.transactions footer{display:flex;justify-content:center;padding:16px;background:rgba(255,255,255,.05)}.transactions footer button{display:flex;align-items:center;gap:8px;border:0;background:transparent;color:#a0b4c4;font-size:12px;font-weight:800}
@media(max-width:767px){.desktop-view{display:none}.mobile-view{display:block;min-height:max(884px,100dvh);padding-bottom:112px}.mobile-top{position:fixed;top:0;left:0;right:0;z-index:50;height:72px;display:flex;align-items:center;justify-content:space-between;padding:0 26px;background:rgba(20,28,46,.6);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.1);box-shadow:0 0 30px rgba(125,211,252,.05)}.mobile-top strong{font-size:22px;letter-spacing:.16em;text-transform:uppercase}.mobile-top div{display:flex;gap:20px;color:#7dd3fc}.mobile-main{padding:96px 18px 0}.mobile-title{margin-bottom:36px}.mobile-title h1{margin:0;color:#fff;font-size:28px;font-weight:900;letter-spacing:-.04em}.mobile-title p{max-width:360px;margin:8px 0 0;color:#a0b4c4;line-height:1.45}.mobile-summary{display:grid;gap:20px;margin-bottom:48px}.balance,.due{position:relative;overflow:hidden;border-radius:12px;padding:28px}.balance div{position:absolute;right:-16px;top:-16px;width:128px;height:128px;border-radius:999px;background:rgba(125,211,252,.1);filter:blur(48px)}.balance p,.due p{margin:0 0 8px;color:#7dd3fc;font-size:12px;font-weight:900;letter-spacing:.18em;text-transform:uppercase}.balance h2{position:relative;margin:0;color:#fff;font-size:54px;font-weight:900;letter-spacing:-.05em;text-shadow:0 0 15px rgba(125,211,252,.4)}.balance small{display:flex;align-items:center;gap:8px;margin-top:16px;color:#a0b4c4}.balance small b{display:flex;align-items:center;color:#ff6b6b;font-weight:500}.balance small span{font-size:14px}.due p{color:#c8a0f0}.due h3{margin:0 0 20px;color:#fff;font-size:28px}.due div{height:10px;overflow:hidden;border-radius:999px;background:#202c42}.due div i{display:block;width:66%;height:100%;background:#c8a0f0;box-shadow:0 0 10px rgba(200,160,240,.5)}.due small{display:block;margin-top:10px;color:#a0b4c4;font-size:10px}.mobile-activity{margin-bottom:48px;padding:28px;border-radius:12px}.mobile-activity header{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:28px}.mobile-activity h3{margin:0;color:#a0b4c4;font-size:16px;font-weight:900;letter-spacing:.12em;text-transform:uppercase}.mobile-activity header div{display:flex;gap:8px}.mobile-activity header span{display:flex;align-items:center;gap:4px;font-size:10px}.mobile-activity header i{width:10px;height:10px;border-radius:999px;background:#7dd3fc}.mobile-activity header span:last-child i{background:#c8a0f0}.bars{height:160px;display:flex;align-items:flex-end;justify-content:space-between;gap:10px}.bars i{flex:1;max-width:42px;border:1px solid rgba(125,211,252,.4);border-radius:2px 2px 0 0;background:rgba(125,211,252,.2)}.bars i.tertiary{border-color:rgba(200,160,240,.4);background:rgba(200,160,240,.2)}.mobile-activity footer{display:flex;justify-content:space-between;margin-top:12px;color:#a0b4c4;font-size:10px;font-family:monospace}.mobile-table{overflow:hidden;border-radius:12px;margin-bottom:32px}.mobile-table>header{display:flex;align-items:center;justify-content:space-between;padding:20px 18px;border-bottom:1px solid rgba(255,255,255,.1);background:rgba(20,28,46,.5)}.mobile-table h3{margin:0;color:#fff;font-size:20px}.mobile-table>header span{color:#a0b4c4}.mobile-head{display:flex;justify-content:space-between;padding:14px 18px;background:#202c42;color:#a0b4c4}.mobile-table article{display:flex;justify-content:space-between;gap:16px;padding:20px 18px;border-top:1px solid rgba(255,255,255,.05)}.mobile-table article strong,.mobile-table article small,.mobile-table article b{display:block}.mobile-table article strong{color:#fff}.mobile-table article small{color:#a0b4c4}.mobile-table article>div:last-child{text-align:right}.mobile-table article b.primary{color:#7dd3fc}.mobile-table article b.tertiary{color:#c8a0f0}.mobile-table article small.danger{color:#ff6b6b}.mobile-table article>div:last-child small{margin-top:4px;font-size:10px;text-transform:uppercase}.pdf-fab{position:fixed;right:26px;bottom:92px;z-index:40;display:flex;align-items:center;gap:10px;padding:16px 28px;border:0;border-radius:999px;background:#7dd3fc;color:#001f2e;font-weight:900;box-shadow:0 10px 30px rgba(125,211,252,.3)}.bottom-nav{position:fixed;left:0;right:0;bottom:0;z-index:50;height:80px;display:flex;align-items:center;justify-content:space-around;padding:0 16px;border-top:1px solid rgba(125,211,252,.2);border-radius:12px 12px 0 0;background:rgba(15,21,36,.9);backdrop-filter:blur(16px);box-shadow:0 -10px 40px rgba(0,0,0,.8)}.bottom-nav a{display:flex;flex-direction:column;align-items:center;color:rgba(160,180,196,.5)}.bottom-nav a.active{color:#7dd3fc;filter:drop-shadow(0 0 8px rgba(125,211,252,.6));transform:scale(1.1)}.bottom-nav small{font-size:10px;text-transform:uppercase}}
@media(min-width:768px) and (max-width:1180px){.desktop-content{padding:32px 24px}.metric-grid,.visual-grid{grid-template-columns:1fr}.topbar label{display:none}.topbar>div:first-child>span{display:block}.transactions{overflow-x:auto}.transactions table{min-width:880px}}
.desktop-content{width:100%!important;max-width:none!important;margin:0!important;padding:18px!important}.metric-grid{grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:clamp(16px,1.4vw,24px)!important}.visual-grid{grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;gap:clamp(16px,1.4vw,24px)!important}.transactions{width:100%!important}.glass,.elevated{background:linear-gradient(135deg,rgba(18,27,45,.76),rgba(9,14,26,.58))!important;border-color:rgba(125,211,252,.18)!important;box-shadow:0 24px 70px rgba(0,0,0,.28),inset 0 1px 0 rgba(255,255,255,.07)}.metric,.last-payment,.chart-card,.usage-card,.transactions{animation:portal-enter .48s ease both;transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease}.metric:hover,.last-payment:hover,.chart-card:hover,.usage-card:hover{transform:translateY(-3px);border-color:rgba(125,211,252,.28)!important;box-shadow:0 30px 90px rgba(56,189,248,.1),inset 0 1px 0 rgba(255,255,255,.08)}.page-title{gap:20px}.page-title>div:last-child{flex-wrap:wrap}.pay{white-space:nowrap}@media(max-width:1180px){.metric-grid,.visual-grid{grid-template-columns:1fr!important}}@media(max-width:767px){.mobile-main{padding-left:16px;padding-right:16px}.pdf-fab{right:18px}}
.page-title,.metric,.last-payment,.chart-card,.usage-card,.transactions{position:relative;overflow:hidden;border:1px solid rgba(125,211,252,.18);background:linear-gradient(135deg,rgba(28,43,64,.78),rgba(10,16,30,.62) 48%,rgba(45,38,72,.58)),radial-gradient(circle at 18% 0%,rgba(125,211,252,.18),transparent 34%),radial-gradient(circle at 92% 12%,rgba(200,160,240,.16),transparent 30%)!important;box-shadow:0 28px 90px rgba(0,0,0,.3),0 0 42px rgba(56,189,248,.075),inset 0 1px 0 rgba(255,255,255,.11)!important;backdrop-filter:blur(22px) saturate(155%)}.page-title{padding:26px 28px;border-radius:28px}.metric,.last-payment{border-radius:22px}.chart-card,.usage-card,.transactions{border-radius:26px}.metric::after,.last-payment::after,.chart-card::after,.usage-card::after,.transactions::after,.page-title::after{position:absolute;right:-28px;top:-28px;width:132px;height:132px;border-radius:999px;content:'';background:rgba(125,211,252,.13);filter:blur(12px);pointer-events:none}.metric:nth-child(2)::after,.usage-card::after{background:rgba(200,160,240,.15)}.transactions header{background:linear-gradient(90deg,rgba(125,211,252,.15),rgba(200,160,240,.1),rgba(255,255,255,.025));border-bottom:1px solid rgba(255,255,255,.08)}.transactions thead th{background:rgba(255,255,255,.055)!important;color:#b7c6d4}.page-title,.metric,.last-payment,.chart-card,.usage-card,.transactions{animation:portal-enter .48s ease both}.metric:nth-child(2),.visual-grid{animation-delay:.08s}.metric:nth-child(3),.transactions{animation-delay:.14s}.receipt-link{display:inline-flex;align-items:center;gap:6px;margin-top:12px;border:0;background:transparent;color:#7dd3fc;cursor:pointer;font-weight:800}.transactions select,.chart-card select{position:relative;z-index:1;border:1px solid rgba(125,211,252,.22);border-radius:12px;background:rgba(10,16,30,.62);color:#e0e8f0;padding:9px 12px}.transactions button,.mobile-table button{cursor:pointer}.empty-state{padding:18px;text-align:center;color:#a9bac8}.mobile-table header button{border:0;background:transparent;color:#7dd3fc}.mobile-table article{cursor:pointer}
.no-more{position:relative;z-index:1;color:#a9bac8;font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.transactions button:disabled{cursor:not-allowed;opacity:.55}.transactions table.is-changing{opacity:.62}@media(max-width:767px){.account-page{overflow-x:hidden}.mobile-main{max-width:100%;padding-bottom:132px;overflow-x:hidden}.balance h2{font-size:clamp(32px,11vw,46px);line-height:1.05;overflow-wrap:anywhere}.due h3{font-size:clamp(22px,7vw,28px);overflow-wrap:anywhere}.mobile-table{overflow:hidden}.mobile-table header{gap:12px}.mobile-table header select{max-width:52%;min-width:0;border:1px solid rgba(125,211,252,.22);border-radius:12px;background:rgba(10,16,30,.62);color:#e0e8f0;padding:9px 10px}.mobile-account-search{position:relative;z-index:1;display:flex;align-items:center;gap:8px;margin:0 16px 12px;padding:10px 12px;border:1px solid rgba(125,211,252,.16);border-radius:14px;background:rgba(255,255,255,.04);color:#a9bac8}.mobile-account-search input{min-width:0;flex:1;border:0;outline:0;background:transparent;color:#e0e8f0}.mobile-table article{grid-template-columns:minmax(0,1fr) auto;gap:10px}.mobile-table article strong,.mobile-table article small{overflow:hidden;text-overflow:ellipsis}.mobile-table article b{max-width:128px;overflow-wrap:anywhere;text-align:right}.mobile-table footer{position:relative;z-index:1;display:flex;justify-content:center;padding:14px 16px 18px}.mobile-table footer button{display:inline-flex;align-items:center;gap:6px;border:1px solid rgba(125,211,252,.28);border-radius:999px;background:rgba(125,211,252,.12);color:#9ee8ff;padding:10px 14px;font-weight:900}.pdf-fab{right:16px;bottom:96px;max-width:calc(100vw - 32px);white-space:nowrap}}
</style>
