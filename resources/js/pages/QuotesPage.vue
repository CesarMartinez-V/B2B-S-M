<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AppShell from '../components/portal/AppShell.vue';
import StatusBadge from '../components/ui/StatusBadge.vue';
import PaginationControl from '../components/ui/PaginationControl.vue';
import { useConfirm } from '../composables/useConfirm.js';
import { useModal } from '../composables/useModal.js';
import { useToast } from '../composables/useToast.js';
import { navigateTo } from '../composables/usePortalNavigation.js';
import { useWhatsAppContact } from '../composables/useWhatsAppContact.js';
import { quoteService } from '../services/quoteService.js';
import { useQuoteStore } from '../stores/quoteStore.js';
import { formatCurrency } from '../utils/currency.js';
import { useQuoteCartStore } from '../quoteCartStore.js';

const avatarDesktop = 'https://lh3.googleusercontent.com/aida-public/AB6AXuAA2uUCAzzGRBT2EF-JfPnZA1glzMCDJJq2k91tgB4O8jMr3sqLBZDNb8CpO_m2G1M9PpzV2HdCyveiSl3pY0QVPzfD7wBseFch6YK8pIyOmoFY7lBQ8OhSiXvQ1CD2J__RASaGITJJ7Rjrs9pwD-_QPhgQgscSYhrIPMLC0Hi4Hgqo1k_jP1_lNec1Ln-fsJtl3eLvho5iGDca08R1G3qO2iXQecdTR7vr0o12y0Mlyh-jDwC5nd0Na7wpBSsKu_0kX3g7tsS3-uA';
const avatarMobile = 'https://lh3.googleusercontent.com/aida-public/AB6AXuAkq2btBqAbfIcVa079OXu2QrsBIrIGwVNeQOgzgkU-LXg0n44_6OnsqQt0j0qkm4oHTealPykt2qPRdBdwdfd-gVWhEidqwvIbotGpoAjsafp5MNnrzPmX0Ea3obB4yq3DyvrHjeO04MDJwdU6zVCVoODrwE6Ajwi4E_cJSmXcDOc8m4PtKvHHHfFgZKdfnQDQI7fBeTG71m4RoSxGPaJMJq9KGyQccVlzoJgW3qfcSr-fUnEMSo9KbU9S8RE1cIhPZfwbGYxP-Fw';

const tabs = [
    { key: 'all', label: 'TODAS' },
    { key: 'recent', label: 'RECIENTES' },
    { key: 'archived', label: 'ARCHIVADAS' },
];
const statusOptions = [
    { key: 'all', label: 'Todos los estados' },
    { key: 'pending', label: 'Pendientes' },
    { key: 'converted', label: 'Convertidas' },
    { key: 'prepared', label: 'Solicitudes preparadas' },
    { key: 'rejected', label: 'Rechazadas' },
];
const statusLabels = { pending: 'Pendiente', converted: 'Convertida', approved: 'Aprobada', rejected: 'Rechazada', prepared: 'Solicitud preparada' };
const statusTones = { pending: 'pending', converted: 'approved', approved: 'approved', rejected: 'rejected', prepared: 'approved', Pendiente: 'pending', Convertida: 'approved', 'Solicitud preparada': 'approved' };
const normalizeStatus = (status) => {
    const value = String(status || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    if (['convertida', 'convertido', 'converted', 'approved', 'aprobada'].includes(value)) return 'converted';
    if (['rechazada', 'rechazado', 'rejected'].includes(value)) return 'rejected';
    if (['solicitud preparada', 'prepared'].includes(value)) return 'prepared';
    return 'pending';
};

const { openModal } = useModal();
const { success, info } = useToast();
const { askConfirm } = useConfirm();
const { openWhatsApp } = useWhatsAppContact();
const quoteCart = useQuoteCartStore();
const quoteStore = useQuoteStore();
const quoteCartItems = quoteCart.items;
const quoteCartCount = quoteCart.count;
const quoteCartTotal = quoteCart.total;
const quotes = ref(quoteService.loadQuotes());
const activeTab = ref('all');
const search = ref('');
const statusFilter = ref('all');
const currentPage = ref(1);
const perPage = 20;
const sendingTemporaryRequest = ref(false);

const quoteParams = (page = currentPage.value) => ({
    page,
    per_page: perPage,
    query: search.value,
    status: statusFilter.value === 'all' ? '' : statusFilter.value,
});

const loadQuotesPage = async (page = currentPage.value) => {
    currentPage.value = page;
    const data = await quoteStore.fetchPage(quoteParams(page));
    if (data?.quotes) {
        const localQuotes = quoteService.loadLocalQuotes();
        const localIds = new Set(localQuotes.map((quote) => quote.id));
        quotes.value = [...localQuotes, ...data.quotes.filter((quote) => !localIds.has(quote.id))];
    }
    currentPage.value = Number(data?.meta?.current_page ?? data?.meta?.page ?? page) || page;
};

onMounted(() => {
    void loadQuotesPage(1);
});

watch([search, statusFilter], () => {
    void loadQuotesPage(1);
});

watch(quotes, (value) => quoteService.saveQuotes(value), { deep: true });

const money = formatCurrency;
const quoteMeta = computed(() => quoteStore.state.data?.meta ?? {});
const pageCount = computed(() => Math.max(1, Number(quoteMeta.value.last_page) || 1));
const normalize = (value) => String(value || '').toLowerCase();
const visibleBase = computed(() => {
    if (activeTab.value === 'archived') return quotes.value.filter((quote) => quote.archived);
    if (activeTab.value === 'recent') return quotes.value.filter((quote) => !quote.archived).slice(0, 3);

    return quotes.value.filter((quote) => !quote.archived);
});
const filteredQuotes = computed(() => visibleBase.value.filter((quote) => {
    const haystack = normalize([quote.id, quote.client, quote.ref, quote.vehicle, quote.brand].join(' '));
    const matchesSearch = !search.value || haystack.includes(normalize(search.value));
    const matchesStatus = statusFilter.value === 'all' || normalizeStatus(quote.status) === statusFilter.value;

    return matchesSearch && matchesStatus;
}));
const stats = computed(() => {
    const activeQuotes = quotes.value.filter((quote) => !quote.archived);
    const pending = activeQuotes.filter((quote) => normalizeStatus(quote.status) === 'pending').length;
    const approved = activeQuotes.filter((quote) => normalizeStatus(quote.status) === 'converted').length;
    const total = activeQuotes.reduce((sum, quote) => sum + Number(quote.amount || 0), 0);

    if (quoteStore.state.data?.stats?.length) return quoteStore.state.data.stats;

    return [
        { icon: 'request_quote', value: String(activeQuotes.length), label: 'Total Cotizaciones', meta: `${quotes.value.filter((quote) => quote.archived).length} archivadas`, tone: 'primary' },
        { icon: 'hourglass_empty', value: String(pending), label: 'Pendientes', meta: `${pending} activas`, tone: 'tertiary' },
        { icon: 'check_circle', value: String(approved), label: 'Convertidas', meta: activeQuotes.length ? `${Math.round((approved / activeQuotes.length) * 100)}% éxito` : '0% éxito', tone: 'success' },
        { icon: 'trending_up', value: formatCurrency(total), label: 'Valor Estimado Mes', tone: 'highlight' },
    ];
});

const quoteObservations = (quote) => quote.observations ?? quote.comments ?? quote.note ?? '';
const quoteDetailRows = (quote) => [
    { label: 'Número', value: quote.id },
    { label: 'Cliente', value: quote.client || 'Cliente B2B' },
    { label: 'Fecha', value: quote.date || 'Ahora' },
    { label: 'Estado', value: statusLabels[normalizeStatus(quote.status)] || quote.status || 'Pendiente' },
    { label: 'Total', value: formatCurrency(quote.amount) },
    { label: 'Origen', value: quote.local ? 'Solicitud local temporal' : 'Cotización ERP B2B' },
];
const quoteDetailItems = (quote) => (quote.items || []).map((item) => ({
    id: item.id,
    sku: item.sku,
    name: item.name || item.title || item.sku || 'Producto',
    qty: item.qty || item.quantity || 1,
    priceLabel: formatCurrency(Number(item.price ?? item.priceValue ?? 0) || 0),
}));
const showDetail = (quote) => openModal({
    title: 'Detalle de cotización',
    message: quote.local ? 'Solicitud temporal / No enviada al ERP.' : 'Cotización real sincronizada desde Fastevo B2B.',
    icon: 'request_quote',
    confirmText: 'Cerrar',
    size: 'lg',
    detail: {
        badge: quote.local ? 'Temporal · No enviada al ERP' : 'ERP B2B',
        rows: quoteDetailRows(quote),
        items: quoteDetailItems(quote),
        observations: quoteObservations(quote) || 'Sin observaciones.',
    },
});
const tempCartLines = computed(() => quoteCartItems.value.map((item) => `${item.quantity || 1} x ${item.name} (${item.sku || item.id}) - ${formatCurrency(Number(item.priceValue || 0) * Number(item.quantity || 1))}`));
const updateTempQty = (item, event) => {
    const qty = Number(event.target.value) || 1;
    if (!quoteCart.updateQty(item.id || item.sku, qty)) {
        event.target.value = item.quantity || 1;
        info('La cantidad máxima para cotizar es 100.', 'Cantidad no disponible');
        return;
    }

    event.target.value = item.quantity || 1;
};
const removeTempItem = (item) => {
    quoteCart.removeItem(item.id || item.sku);
    success('Producto removido de la cotización temporal.');
};
const clearTempQuote = () => askConfirm({
    title: 'Vaciar cotización temporal',
    message: 'Se eliminarán los productos seleccionados solo de este portal. No se modifica ERP.',
    confirmText: 'Vaciar',
    tone: 'danger',
    onConfirm: () => {
        quoteCart.clear();
        success('Cotización temporal vaciada.');
    },
});
const submitTemporaryRequest = () => {
    if (sendingTemporaryRequest.value) return;

    const cart = quoteService.getTemporaryCart(quoteCart.items.value);
    if (!cart?.items?.length) {
        info('Agrega productos desde el catálogo antes de preparar la solicitud.');
        return;
    }

    openModal({
        title: 'Enviar solicitud temporal',
        message: `Se preparará una solicitud local con ${cart.items.length} producto(s) por ${formatCurrency(quoteCartTotal.value)} y se abrirá WhatsApp para revisión de un asesor.\nLa creación real en ERP queda pendiente de aprobación.`,
        icon: 'outgoing_mail',
        confirmText: 'Enviar por WhatsApp',
        cancelText: 'Cancelar',
        onConfirm: () => {
            sendingTemporaryRequest.value = true;
            const quote = quoteService.submitTemporaryRequest(quoteService.createQuoteFromCart(cart));
            quotes.value = [quote, ...quotes.value.filter((item) => item.id !== quote.id && item.temp_quote_id !== quote.temp_quote_id && item.cart_signature !== quote.cart_signature)];
            openWhatsApp('Hola, preparé una solicitud de cotización en el Portal B2B y necesito que un asesor la revise.');
            quoteCart.clear();
            activeTab.value = 'all';
            sendingTemporaryRequest.value = false;
            success('Solicitud preparada y abierta en WhatsApp. No se creó documento real en ERP.');
        },
    });
};
const updateQuote = (quote, changes, message) => {
    Object.assign(quote, changes);
    success(message);
};
const confirmAction = (quote, action, changes, tone = 'primary') => askConfirm({
    title: `${action} cotización`,
    message: `Se aplicará como marca local de vista sobre ${quote.id}. No crea ni modifica documentos reales en ERP.`,
    confirmText: action,
    tone,
    onConfirm: () => updateQuote(quote, changes, `${quote.id} actualizada correctamente.`),
});
const openNewQuote = () => {
    navigateTo('/cotizaciones/nueva');
};
</script>

<template>
    <div class="quotes-page">
        <AppShell active-route="/cotizaciones" desktop-search-placeholder="Buscar socio, factura o pedido..." mobile-title="Inversiones S&amp;M" :avatar-src="avatarDesktop" :mobile-avatar-src="avatarMobile">
            <template #desktop><div class="wrap shell-content"><section class="page-head"><div><h1>Cotizaciones</h1><p>Gestione y revise sus solicitudes de repuestos premium.</p></div><div class="quote-head-actions"><div class="tabs glass"><button v-for="tab in tabs" :key="tab.key" :class="{ active: activeTab === tab.key }" @click="activeTab = tab.key">{{ tab.label }}</button></div><button class="new-quote" type="button" @click="openNewQuote"><span class="material-symbols-outlined">add</span>Nueva Cotización</button></div></section><section class="stats-grid"><article v-for="stat in stats" :key="stat.label" :class="['stat-card','glass',stat.tone]"><header><span class="material-symbols-outlined">{{ stat.icon }}</span><b v-if="stat.meta">{{ stat.meta }}</b></header><strong>{{ stat.value }}</strong><p>{{ stat.label }}</p></article></section><section class="table-card glass"><header><h3>{{ activeTab === 'archived' ? 'Archivadas' : activeTab === 'recent' ? 'Recientes' : 'Todas' }} <span>Actualizado</span></h3><div><label class="filter-select"><span class="material-symbols-outlined">filter_list</span><select v-model="statusFilter"><option v-for="option in statusOptions" :key="option.key" :value="option.key">{{ option.label }}</option></select></label><label><span class="material-symbols-outlined">search</span><input v-model="search" placeholder="Filtrar por cliente o ID..." type="text"></label></div></header><div class="table-scroll"><table><thead><tr><th>ID Cotización</th><th>Vehículo / Marca</th><th>Fecha</th><th>Importe Total</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><tr v-for="row in filteredQuotes" :key="row.id"><td><strong>{{ row.id }}</strong><small>{{ row.ref }}</small></td><td><div class="vehicle"><span class="material-symbols-outlined">directions_car</span><div><strong>{{ row.vehicle }}</strong><small>{{ row.brand }}</small></div></div></td><td>{{ row.date }}</td><td><strong>{{ money(row.amount) }}</strong></td><td><StatusBadge :status="row.status" size="sm" /></td><td><div class="row-actions"><button type="button" title="Ver detalle" @click="showDetail(row)"><span class="material-symbols-outlined">visibility</span></button><button v-if="row.status !== 'approved' && !row.archived" type="button" title="Marcar localmente" @click="confirmAction(row, 'Aprobar', { status: 'approved' })"><span class="material-symbols-outlined">check_circle</span></button><button v-if="row.status !== 'rejected' && !row.archived" type="button" title="Marcar localmente" @click="confirmAction(row, 'Rechazar', { status: 'rejected' }, 'danger')"><span class="material-symbols-outlined">cancel</span></button><button v-if="!row.archived" type="button" title="Archivar vista" @click="confirmAction(row, 'Archivar', { archived: true })"><span class="material-symbols-outlined">archive</span></button><button v-else type="button" title="Restaurar vista" @click="confirmAction(row, 'Restaurar', { archived: false })"><span class="material-symbols-outlined">unarchive</span></button></div></td></tr><tr v-if="!filteredQuotes.length"><td colspan="6" class="empty-row">No hay cotizaciones para los filtros seleccionados.</td></tr></tbody></table></div><footer><span>Mostrando {{ filteredQuotes.length }} de {{ visibleBase.length }} resultados</span><div><button disabled><span class="material-symbols-outlined">chevron_left</span></button><button class="active">1</button><button disabled><span class="material-symbols-outlined">chevron_right</span></button></div></footer></section></div></template>
            <template #mobile><main class="mobile-main"><section class="mobile-title"><h1>Cotizaciones</h1><p>Solicitudes y estados comerciales del cliente.</p></section><section v-if="quoteCartCount > 0" class="temp-quote-cart glass"><header><div><span class="material-symbols-outlined">shopping_basket</span><strong>Cotización temporal</strong></div><b>{{ quoteCartCount }} producto(s)</b></header><article v-for="item in quoteCartItems" :key="item.id || item.sku"><div><strong>{{ item.name }}</strong><small>SKU: {{ item.sku || item.id }}</small></div><span>x{{ item.quantity || 1 }}</span></article><button type="button" @click="openNewQuote"><span class="material-symbols-outlined">add_task</span>Crear cotización local</button></section><section class="mobile-stats"><article v-for="stat in stats.slice(0, 3)" :key="stat.label" class="glass"><strong>{{ stat.value }}</strong><span>{{ stat.label }}</span></article></section><div class="mobile-tabs"><button v-for="tab in tabs" :key="tab.key" :class="{ active: activeTab === tab.key }" @click="activeTab = tab.key">{{ tab.label }}</button></div><label class="mobile-search glass"><span class="material-symbols-outlined">search</span><input v-model="search" placeholder="Buscar número o cliente" type="text"><select v-model="statusFilter" aria-label="Filtrar estado"><option v-for="option in statusOptions" :key="option.key" :value="option.key">{{ option.label }}</option></select></label><section class="mobile-list"><article v-for="quote in filteredQuotes" :key="quote.id" class="quote-card glass" @click="showDetail(quote)"><header><div><span :class="statusTones[quote.status]">{{ quote.id }}</span><h3>{{ quote.client }}</h3></div><StatusBadge :status="quote.status" size="sm" /></header><div class="quote-meta"><div><small>Referencia</small><strong>{{ quote.items[0]?.name || quote.ref }}</strong></div><div><small>Total</small><strong>{{ money(quote.amount) }}</strong></div></div><footer><span class="material-symbols-outlined">schedule</span><span>{{ quote.date }}</span><button v-if="quote.status !== 'approved' && !quote.archived" type="button" title="Marcar localmente" @click.stop="confirmAction(quote, 'Marcar localmente', { status: 'approved' })"><span class="material-symbols-outlined">check_circle</span></button><button v-if="quote.status !== 'rejected' && !quote.archived" type="button" title="Marcar localmente" @click.stop="confirmAction(quote, 'Marcar localmente', { status: 'rejected' }, 'danger')"><span class="material-symbols-outlined">cancel</span></button><button type="button" :title="quote.archived ? 'Restaurar vista' : 'Archivar vista'" @click.stop="quote.archived ? confirmAction(quote, 'Restaurar vista', { archived: false }) : confirmAction(quote, 'Archivar vista', { archived: true })"><span class="material-symbols-outlined">{{ quote.archived ? 'unarchive' : 'archive' }}</span></button></footer></article><p v-if="!filteredQuotes.length" class="mobile-empty">No hay cotizaciones para los filtros seleccionados.</p></section><PaginationControl :current-page="currentPage" :last-page="pageCount" :total="Number(quoteMeta.total) || filteredQuotes.length" :per-page="Number(quoteMeta.per_page) || perPage" :disabled="quoteStore.state.loading" compact @page-change="loadQuotesPage" /></main><button class="fab" type="button" @click="openNewQuote"><span class="material-symbols-outlined">add</span></button></template>
        </AppShell>
        <section v-if="quoteCartCount > 0" class="quote-request-panel glass">
            <header><div><span class="material-symbols-outlined">shopping_basket</span><strong>Cotización temporal</strong></div><b>{{ quoteCartCount }} producto(s) · {{ money(quoteCartTotal) }}</b></header>
            <article v-for="item in quoteCartItems" :key="item.id || item.sku">
                <div><strong>{{ item.name }}</strong><small>SKU: {{ item.sku || item.id }} · {{ item.brand }}</small></div>
                <input :value="item.quantity || 1" min="1" max="100" type="number" aria-label="Cantidad" @change="updateTempQty(item, $event)">
                <button type="button" aria-label="Quitar producto" @click="removeTempItem(item)"><span class="material-symbols-outlined">delete</span></button>
            </article>
            <footer><span>Total interno: {{ money(quoteCartTotal) }}</span><button type="button" @click="navigateTo('/catalogo')">Seguir cotizando</button><button type="button" @click="clearTempQuote">Vaciar</button><button type="button" :disabled="sendingTemporaryRequest" @click="submitTemporaryRequest">Enviar solicitud</button></footer>
        </section>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;500;600;700;800;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');
.quotes-page{min-height:100vh;background:#0a0e1a;color:#e0e8f0;font-family:Inter,sans-serif}.material-symbols-outlined{font-family:'Material Symbols Outlined';font-feature-settings:'liga';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;line-height:1}.filled{font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24}.glass{background:rgba(15,21,36,.6);backdrop-filter:blur(16px);border:1px solid rgba(125,211,252,.1)}a{text-decoration:none;color:inherit}button,input{font:inherit}.mobile-view{display:none}.topbar{position:fixed;top:0;left:0;right:0;z-index:50;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;background:rgba(20,28,46,.6);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.1);box-shadow:0 0 30px rgba(125,211,252,.05)}.topbar>div,.topbar nav{display:flex;align-items:center;gap:32px}.topbar strong{font-size:18px;letter-spacing:.12em}.topbar label{display:flex;align-items:center;gap:12px;width:320px;padding:10px 16px;border-radius:999px;background:#202c42;color:#a0b4c4}.topbar input{border:0;outline:0;background:transparent;color:#e0e8f0}.topbar button{border:0;background:transparent;color:#a0b4c4}.user{display:flex;align-items:center;gap:12px;padding-left:16px;border-left:1px solid rgba(255,255,255,.1);font-weight:700}.user img{width:32px;height:32px;object-fit:cover;border-radius:999px;border:1px solid rgba(125,211,252,.2)}.sidebar{position:fixed;left:0;top:0;z-index:40;width:288px;height:100vh;display:flex;flex-direction:column;padding:32px 16px;background:rgba(17,24,40,.8);backdrop-filter:blur(40px);border-right:1px solid rgba(255,255,255,.05);box-shadow:0 25px 50px rgba(10,14,26,.5)}.brand{padding:0 16px;margin-bottom:40px}.brand h2{margin:0;color:#7dd3fc;font-size:20px;font-weight:900;text-shadow:0 0 10px rgba(125,211,252,.3)}.brand p{margin:8px 0 0;color:rgba(160,180,196,.7);font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase}.sidebar nav{flex:1}.sidebar nav a,.sidebar footer a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:rgba(160,180,196,.7);border-radius:8px}.sidebar nav a.active{border-left:4px solid #7dd3fc;border-radius:0 8px 8px 0;background:rgba(14,77,110,.2);color:#7dd3fc;box-shadow:0 0 15px rgba(125,211,252,.1)}.new-quote{display:flex;align-items:center;justify-content:center;gap:8px;margin:0 8px 16px;padding:14px;border:1px solid rgba(125,211,252,.3);border-radius:12px;background:linear-gradient(135deg,rgba(125,211,252,.2),rgba(125,211,252,.05));color:#7dd3fc;font-weight:800;box-shadow:0 0 20px rgba(125,211,252,.1)}.sidebar footer{padding-top:16px;border-top:1px solid rgba(255,255,255,.05)}.desktop-main{min-height:100vh;margin-left:288px;padding:64px 40px 32px}.wrap{max-width:1280px;margin:0 auto;padding-top:32px}.page-head{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:40px}.page-head h1{margin:0 0 8px;color:#fff;font-size:42px;line-height:1;font-weight:800;letter-spacing:-.04em}.page-head p{margin:0;color:#a0b4c4;font-size:18px}.tabs{display:flex;align-items:center;padding:4px;border-radius:16px}.tabs button{padding:10px 22px;border:0;background:transparent;color:#a0b4c4;font-size:12px;font-weight:800}.tabs button:first-child{border-radius:10px;background:rgba(125,211,252,.1);color:#7dd3fc}.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;margin-bottom:40px}.stat-card{min-height:154px;padding:30px;border-radius:18px;display:flex;flex-direction:column;justify-content:space-between}.stat-card.highlight{background:linear-gradient(135deg,rgba(125,211,252,.1),transparent),rgba(15,21,36,.6);border-color:rgba(125,211,252,.2)}.stat-card header{display:flex;align-items:flex-start;justify-content:space-between}.stat-card header span{padding:12px;border-radius:18px;background:rgba(125,211,252,.1);color:#7dd3fc}.stat-card.tertiary header span{background:rgba(200,160,240,.1);color:#c8a0f0}.stat-card.success header span{background:rgba(52,211,153,.1);color:#34d399}.stat-card b{color:#34d399;font-size:12px}.stat-card.tertiary b{color:#a0b4c4}.stat-card strong{display:block;margin-top:20px;color:#fff;font-size:30px}.stat-card p{margin:2px 0 0;color:#a0b4c4;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}.table-card{overflow:hidden;border-radius:18px;box-shadow:0 20px 50px rgba(0,0,0,.3)}.table-card>header{display:flex;align-items:center;justify-content:space-between;padding:32px}.table-card h3{display:flex;align-items:center;gap:10px;margin:0;font-size:24px}.table-card h3 span{padding:6px 10px;border-radius:999px;background:rgba(125,211,252,.2);color:#7dd3fc;font-size:10px}.table-card>header>div{display:flex;gap:10px}.table-card header button{width:42px;height:42px;border-radius:999px;border:1px solid rgba(125,211,252,.1);background:rgba(15,21,36,.6);color:#a0b4c4}.table-card label{display:flex;align-items:center;gap:10px;width:300px;padding:0 16px;border:1px solid rgba(255,255,255,.05);border-radius:18px;background:rgba(32,44,66,.3);color:#a0b4c4}.table-card input{border:0;outline:0;background:transparent;color:#e0e8f0}.table-scroll{overflow-x:auto}table{width:100%;border-collapse:collapse}th{padding:22px 32px;background:rgba(255,255,255,.05);color:#a0b4c4;font-size:11px;font-weight:900;letter-spacing:.16em;text-align:left;text-transform:uppercase}td{padding:26px 32px;border-top:1px solid rgba(255,255,255,.05);color:#a0b4c4}td strong{display:block;color:#e0e8f0;font-size:16px}td small{display:block;margin-top:4px;color:#a0b4c4;font-size:10px}.vehicle{display:flex;align-items:center;gap:14px}.vehicle>span{padding:10px;border-radius:10px;background:#202c42;color:#7dd3fc}.status{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;font-size:12px;font-weight:800}.status i{width:7px;height:7px;border-radius:999px}.status.approved{border:1px solid rgba(52,211,153,.2);background:rgba(52,211,153,.1);color:#34d399}.status.approved i{background:#34d399}.status.pending{border:1px solid rgba(245,158,11,.2);background:rgba(245,158,11,.1);color:#f59e0b}.status.pending i{background:#f59e0b}.status.rejected{border:1px solid rgba(255,107,107,.2);background:rgba(255,107,107,.1);color:#ff6b6b}.status.rejected i{background:#ff6b6b}td:last-child{text-align:right}td:last-child button{border:0;background:transparent;color:#a0b4c4}.table-card>footer{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-top:1px solid rgba(255,255,255,.05);color:#a0b4c4}.table-card footer div{display:flex;gap:6px}.table-card footer button{min-width:32px;height:32px;border:0;border-radius:999px;background:transparent;color:#a0b4c4}.table-card footer button:first-child,.table-card footer button:last-child{border:1px solid rgba(125,211,252,.1);background:rgba(15,21,36,.6)}.table-card footer button.active{background:rgba(125,211,252,.1);color:#7dd3fc;font-weight:800}
@media(max-width:767px){.desktop-view{display:none}.mobile-view{display:block;min-height:max(884px,100dvh);padding-bottom:112px}.mobile-top{position:fixed;inset:0 0 auto;z-index:50;height:104px;display:flex;align-items:center;justify-content:space-between;padding:0 40px 0 40px;background:rgba(20,28,46,.6);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.1);box-shadow:0 0 30px rgba(125,211,252,.05)}.mobile-top strong{font-size:28px;letter-spacing:.18em;text-transform:uppercase}.mobile-top>div{display:flex;align-items:center;gap:22px}.mobile-top span{color:#a0b4c4;font-size:32px}.mobile-top img{width:48px;height:48px;object-fit:cover;border-radius:999px;border:1px solid rgba(125,211,252,.2);box-shadow:0 0 20px rgba(125,211,252,.25)}.mobile-main{padding:136px 26px 0}.mobile-title{margin-bottom:42px}.mobile-title h1{margin:0 0 12px;color:#fff;font-size:40px;line-height:1;font-weight:900;letter-spacing:-.04em}.mobile-title p{margin:0;color:#a0b4c4;font-size:24px}.mobile-search{height:78px;display:flex;align-items:center;gap:26px;margin-bottom:38px;padding:0 30px;border-radius:12px}.mobile-search span:first-child{color:#a0b4c4;font-size:38px}.mobile-search span:last-child{margin-left:auto;color:#7dd3fc;font-size:34px}.mobile-search input{min-width:0;flex:1;border:0;outline:0;background:transparent;color:#e0e8f0;font-size:24px}.mobile-list{display:flex;flex-direction:column;gap:28px}.quote-card{padding:34px;border-radius:18px}.quote-card header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}.quote-card header span{display:block;margin-bottom:12px;font-size:16px;font-weight:900;letter-spacing:.16em}.quote-card h3{max-width:330px;margin:0;color:#fff;font-size:30px;line-height:1.45}.quote-card .approved{color:#7dd3fc}.quote-card .pending{color:#c8a0f0}.quote-card .sent{color:#a0b4c4}.pill{display:flex;align-items:center;gap:12px;padding:11px 18px;border-radius:999px;font-size:16px;letter-spacing:.05em;text-transform:uppercase}.pill i{width:14px;height:14px;border-radius:999px}.pill.approved{border:1px solid rgba(125,211,252,.25);background:rgba(125,211,252,.1);box-shadow:0 0 24px rgba(125,211,252,.35)}.pill.approved i{background:#7dd3fc}.pill.pending{border:1px solid rgba(200,160,240,.25);background:rgba(200,160,240,.1);box-shadow:0 0 24px rgba(200,160,240,.35)}.pill.pending i{background:#c8a0f0}.pill.sent{border:1px solid rgba(255,255,255,.05);background:#1a2438}.pill.sent i{background:#a0b4c4}.quote-meta{display:grid;grid-template-columns:1fr auto;gap:24px;margin-top:34px}.quote-meta small{display:block;color:#a0b4c4;font-size:16px;text-transform:uppercase}.quote-meta strong{display:block;margin-top:8px;color:#fff;font-size:24px}.quote-meta div:last-child{text-align:right}.quote-meta div:last-child strong{color:#7dd3fc;font-size:30px}.quote-card footer{display:flex;align-items:center;gap:12px;margin-top:34px;padding-top:24px;border-top:1px solid rgba(255,255,255,.05);color:#a0b4c4;font-size:22px}.quote-card footer .material-symbols-outlined{font-size:20px}.mini-avatars{margin-left:auto;display:flex;align-items:center}.mini-avatars i,.mini-avatars b{width:36px;height:36px;border-radius:999px;border:1px solid #0a0e1a;background:#202c42}.mini-avatars b{display:flex;align-items:center;justify-content:center;margin-left:-12px;background:#88b4cc;color:#001f2e;font-size:16px}.fab{position:fixed;right:40px;bottom:96px;z-index:40;width:92px;height:92px;border:0;border-radius:24px;background:#7dd3fc;color:#001f2e;box-shadow:0 0 20px rgba(125,211,252,.4)}.fab span{font-size:48px}.bottom-nav{position:fixed;left:0;right:0;bottom:0;z-index:50;height:80px;display:flex;align-items:center;justify-content:space-around;padding:0 16px;border-top:1px solid rgba(125,211,252,.2);border-radius:12px 12px 0 0;background:rgba(15,21,36,.9);backdrop-filter:blur(16px);box-shadow:0 -10px 40px rgba(0,0,0,.8)}.bottom-nav a{display:flex;flex-direction:column;align-items:center;color:rgba(160,180,196,.5)}.bottom-nav a.active{color:#7dd3fc;filter:drop-shadow(0 0 8px rgba(125,211,252,.6));transform:scale(1.1)}.bottom-nav small{font-size:10px;text-transform:uppercase}}
@media(min-width:768px) and (max-width:1160px){.stats-grid{grid-template-columns:repeat(2,1fr)}.page-head{align-items:flex-start;flex-direction:column}.topbar label{display:none}.table-scroll table{min-width:980px}}
.wrap{width:100%!important;max-width:none!important;margin:0!important;padding-top:18px!important}.stats-grid{grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:clamp(16px,1.4vw,24px)!important}.table-card{width:100%!important}.table-scroll table{width:100%!important}.desktop-main{padding-left:18px;padding-right:18px}.glass{background:linear-gradient(135deg,rgba(18,27,45,.76),rgba(9,14,26,.58))!important;border-color:rgba(125,211,252,.18)!important;box-shadow:0 24px 70px rgba(0,0,0,.28),inset 0 1px 0 rgba(255,255,255,.07)}.stat-card,.table-card{animation:portal-enter .48s ease both;transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease}.stat-card:hover,.table-card:hover{border-color:rgba(125,211,252,.28)!important;box-shadow:0 30px 90px rgba(56,189,248,.1),inset 0 1px 0 rgba(255,255,255,.08)}.mobile-main{padding-left:18px;padding-right:18px}@media(max-width:1180px){.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}}@media(max-width:767px){.mobile-main{padding-top:92px}.mobile-title{margin-bottom:28px}.mobile-search{height:64px;margin-bottom:28px}.quote-card{padding:24px}.fab{right:24px;width:68px;height:68px}}
.page-head,.stat-card,.table-card{position:relative;overflow:hidden;border:1px solid rgba(125,211,252,.18);background:linear-gradient(135deg,rgba(28,43,64,.78),rgba(10,16,30,.62) 48%,rgba(45,38,72,.58)),radial-gradient(circle at 18% 0%,rgba(125,211,252,.18),transparent 34%),radial-gradient(circle at 92% 12%,rgba(200,160,240,.16),transparent 30%)!important;box-shadow:0 28px 90px rgba(0,0,0,.3),0 0 42px rgba(56,189,248,.075),inset 0 1px 0 rgba(255,255,255,.11)!important;backdrop-filter:blur(22px) saturate(155%)}.page-head{padding:26px 28px;border-radius:28px}.stat-card{border-radius:22px}.table-card{border-radius:26px}.table-card>header{background:linear-gradient(90deg,rgba(125,211,252,.16),rgba(200,160,240,.12),rgba(255,255,255,.03));border-bottom:1px solid rgba(255,255,255,.08)}.table-scroll thead th{background:rgba(255,255,255,.055)!important;color:#b7c6d4}.table-scroll tbody tr{background:linear-gradient(90deg,rgba(125,211,252,.035),transparent 55%,rgba(200,160,240,.025));transition:background .22s ease}.table-scroll tbody tr:hover{background:rgba(125,211,252,.075)}.stat-card::after,.table-card::after,.page-head::after{position:absolute;right:-28px;top:-28px;width:130px;height:130px;border-radius:999px;content:'';background:rgba(125,211,252,.13);filter:blur(12px);pointer-events:none}.stat-card:nth-child(2)::after{background:rgba(200,160,240,.15)}.stat-card:nth-child(3)::after{background:rgba(110,231,183,.12)}.page-head,.stat-card,.table-card{animation:portal-enter .48s ease both}.stat-card:nth-child(2),.table-card{animation-delay:.08s}.stat-card:nth-child(3),.stat-card:nth-child(4){animation-delay:.14s}.quote-head-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:flex-end}.tabs button.active,.mobile-tabs button.active{background:rgba(125,211,252,.18)!important;color:#7dd3fc!important}.new-quote{display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(125,211,252,.28);border-radius:999px;background:rgba(125,211,252,.12);color:#9ee8ff;font-weight:900;padding:11px 16px;cursor:pointer}.filter-select select,.mobile-search select{border:0;outline:0;background:transparent;color:#e0e8f0;font:inherit}.filter-select option,.mobile-search option{background:#111827;color:#e0e8f0}.row-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.row-actions button{display:grid;place-items:center;width:34px;height:34px;border:1px solid rgba(125,211,252,.16);border-radius:10px;background:rgba(255,255,255,.04);color:#a8dff5;cursor:pointer}.row-actions button span{font-size:18px}.empty-row,.mobile-empty{padding:28px!important;text-align:center;color:#a0b4c4}.status.rejected,.pill.rejected{color:#ff9b9b}.mobile-tabs{display:none}.quote-card footer button{margin-left:auto;border:0;background:transparent;color:#7dd3fc}.quote-card{cursor:pointer}@media(max-width:767px){.mobile-tabs{display:flex;gap:8px;margin:-10px 0 18px;overflow:auto}.mobile-tabs button{white-space:nowrap;border:1px solid rgba(125,211,252,.16);border-radius:999px;background:rgba(255,255,255,.04);color:#a0b4c4;padding:9px 12px;font-weight:900}.mobile-search select{max-width:150px;font-size:12px}.quote-card .rejected{color:#ff9b9b}.quote-card footer{align-items:center}.quote-card footer button span{font-size:24px!important;margin:0!important}}
@media(max-width:767px){.quotes-page{overflow-x:hidden}.mobile-main{max-width:100%;padding:88px 14px 128px!important;display:grid;gap:14px;overflow-x:hidden}.mobile-title{margin-bottom:2px!important}.mobile-title h1{font-size:clamp(24px,7vw,30px)!important;line-height:1.05}.mobile-title p{font-size:14px!important;line-height:1.35}.mobile-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.mobile-stats article{min-width:0;padding:10px;border-radius:16px}.mobile-stats strong{display:block;overflow:hidden;color:#7dd3fc;font-size:clamp(14px,4.5vw,18px);text-overflow:ellipsis;white-space:nowrap}.mobile-stats span{display:block;margin-top:3px;overflow:hidden;color:#a0b4c4;font-size:9px;font-weight:900;text-overflow:ellipsis;text-transform:uppercase;white-space:nowrap}.mobile-tabs{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin:0!important}.mobile-tabs button{min-height:38px;padding:8px 6px!important;border-radius:12px!important;font-size:10px!important}.mobile-search{height:auto!important;display:grid!important;grid-template-columns:auto minmax(0,1fr);gap:8px!important;margin:0!important;padding:10px!important;border-radius:16px!important}.mobile-search span:first-child{font-size:22px!important}.mobile-search input{font-size:14px!important}.mobile-search select{grid-column:1/-1;min-width:0;width:100%;border:1px solid rgba(125,211,252,.18);border-radius:12px;background:rgba(10,16,30,.62);color:#e0e8f0;padding:9px 10px}.mobile-list{gap:12px!important}.quote-card{padding:14px!important;border-radius:18px!important}.quote-card header{gap:10px}.quote-card header>div{min-width:0}.quote-card header span{margin-bottom:4px!important;font-size:10px!important}.quote-card h3{display:-webkit-box;max-width:100%!important;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2;font-size:16px!important;line-height:1.25!important}.quote-meta{display:grid!important;grid-template-columns:minmax(0,1fr) auto;gap:10px;margin-top:12px}.quote-meta strong{display:-webkit-box;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2;font-size:13px!important}.quote-meta small{font-size:10px}.quote-card footer{gap:8px;margin-top:12px}.quote-card footer>span:nth-child(2){min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.quote-card footer button{width:34px;height:34px;min-width:34px;border-radius:10px}.fab{right:16px!important;bottom:94px!important;width:54px!important;height:54px!important}.mobile-empty{padding:16px;text-align:center;color:#a0b4c4}.pagination-control{margin-top:0}}
@media(max-width:767px){.temp-quote-cart{display:grid;gap:10px;padding:14px;border-radius:18px}.temp-quote-cart header{display:flex;align-items:center;justify-content:space-between;gap:10px}.temp-quote-cart header>div{display:flex;align-items:center;gap:8px;min-width:0}.temp-quote-cart header strong{color:#fff;font-size:14px}.temp-quote-cart header b{flex:0 0 auto;color:#7dd3fc;font-size:12px}.temp-quote-cart article{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:center;border:1px solid rgba(125,211,252,.12);border-radius:12px;background:rgba(255,255,255,.035);padding:9px 10px}.temp-quote-cart article strong{display:-webkit-box;overflow:hidden;color:#e0e8f0;font-size:13px;line-height:1.25;-webkit-box-orient:vertical;-webkit-line-clamp:2}.temp-quote-cart article small{display:block;overflow:hidden;margin-top:3px;color:#7f98ad;font-size:10px;font-weight:900;text-overflow:ellipsis;text-transform:uppercase;white-space:nowrap}.temp-quote-cart article span{color:#9ee8ff;font-weight:900}.temp-quote-cart button{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid rgba(125,211,252,.28);border-radius:14px;background:rgba(125,211,252,.14);color:#9ee8ff;padding:10px 12px;font-weight:900}}
.quote-request-panel{position:fixed;right:22px;bottom:22px;z-index:85;display:grid;gap:10px;width:min(520px,calc(100vw - 44px));max-height:min(62vh,560px);overflow:auto;padding:16px;border-radius:22px;color:#e0e8f0}.quote-request-panel header,.quote-request-panel footer{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}.quote-request-panel header>div{display:flex;align-items:center;gap:8px}.quote-request-panel header span{color:#7dd3fc}.quote-request-panel header strong{color:#fff}.quote-request-panel header b,.quote-request-panel footer span{color:#9ee8ff;font-size:12px}.quote-request-panel article{display:grid;grid-template-columns:minmax(0,1fr) 76px auto;gap:10px;align-items:center;padding:10px;border:1px solid rgba(125,211,252,.14);border-radius:14px;background:rgba(255,255,255,.04)}.quote-request-panel article strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.quote-request-panel article small{display:block;margin-top:3px;color:#8fa6b8;font-size:10px;font-weight:900;text-transform:uppercase}.quote-request-panel input{width:100%;border:1px solid rgba(125,211,252,.22);border-radius:12px;background:rgba(10,16,30,.72);color:#e0e8f0;padding:8px 10px}.quote-request-panel button{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid rgba(125,211,252,.28);border-radius:12px;background:rgba(125,211,252,.12);color:#9ee8ff;padding:9px 12px;font-weight:900}.quote-request-panel footer button:last-child{background:linear-gradient(135deg,#7dd3fc,#67bde8);color:#082033}@media(max-width:767px){.mobile-main>.temp-quote-cart{display:none!important}.quote-request-panel{left:12px;right:12px;bottom:92px;width:auto;max-height:46vh;padding:12px;border-radius:18px}.quote-request-panel article{grid-template-columns:minmax(0,1fr) 62px auto}.quote-request-panel footer{display:grid;grid-template-columns:1fr 1fr;align-items:stretch}.quote-request-panel footer span{grid-column:1/-1}.quote-request-panel footer button:last-child{grid-column:1/-1}}
</style>
