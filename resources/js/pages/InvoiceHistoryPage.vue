<script setup>
import { computed, ref } from 'vue';
import AppShell from '../components/portal/AppShell.vue';
import { useModal } from '../composables/useModal.js';
import { usePdfExport } from '../composables/usePdfExport.js';
import { useToast } from '../composables/useToast.js';
import { portalUser } from '../portalNavigation.js';
import { getInvoices, getInvoiceStatuses, getInvoiceSummary } from '../services/invoiceService.js';

const { openModal } = useModal();
const { printDocument } = usePdfExport();
const { info, success } = useToast();

const query = ref('');
const selectedStatus = ref('Todos');
const dateFrom = ref('');
const dateTo = ref('');
const summary = getInvoiceSummary();
const statuses = getInvoiceStatuses();
const invoices = computed(() => getInvoices({ query: query.value, status: selectedStatus.value, from: dateFrom.value, to: dateTo.value }));

const invoiceLines = (invoice) => [
    `Factura: ${invoice.number}`,
    `Fecha: ${invoice.date}`,
    `Vencimiento: ${invoice.due}`,
    `Estado: ${invoice.status}`,
    `Total: ${invoice.total}`,
    `Vendedor: ${invoice.seller}`,
];

const showInvoice = (invoice) => {
    openModal({ title: `Detalle ${invoice.number}`, message: invoiceLines(invoice).join('\n'), icon: 'receipt_long', confirmText: 'Cerrar' });
    info(`Detalle abierto para ${invoice.number}`);
};

const contactSeller = (invoice) => {
    openModal({ title: `Contactar a ${invoice.seller}`, message: `Se notificará al vendedor sobre la factura ${invoice.number}.\nTotal asociado: ${invoice.total}.`, icon: 'support_agent', confirmText: 'Enviar solicitud', cancelText: 'Cancelar', onConfirm: () => success(`Solicitud enviada a ${invoice.seller}`) });
};

const exportInvoice = (invoice) => {
    if (printDocument({ title: `Factura ${invoice.number}`, sections: [{ heading: 'Ficha de factura', lines: invoiceLines(invoice) }] })) {
        success(`Ficha de ${invoice.number} lista para imprimir`);
    }
};

const exportHistory = () => {
    const lines = invoices.value.length ? invoices.value.map((invoice) => `${invoice.number} | ${invoice.date} | ${invoice.status} | ${invoice.total} | ${invoice.seller}`) : ['Sin facturas para los filtros actuales.'];
    if (printDocument({ title: 'Historial de Facturas', sections: [{ heading: 'Facturas filtradas', lines }] })) {
        success('Historial listo para imprimir o guardar como PDF');
    }
};
</script>

<template>
    <AppShell active-route="/historial-facturas" desktop-search-placeholder="Buscar socio, factura o pedido..." mobile-title="Inversiones S&amp;M" :avatar-src="portalUser.avatar">
        <template #desktop>
            <main class="invoice-main shell-content">
                <section class="invoice-hero liquid-panel portal-enter">
                    <div class="hero-copy">
                        <p>Centro financiero</p>
                        <h1>Historial de Facturas</h1>
                        <span>Consulta documentos emitidos, vencimientos y el contacto comercial asignado para cada movimiento.</span>
                    </div>
                    <div class="hero-orbit" aria-hidden="true">
                        <span></span>
                        <i></i>
                    </div>
                    <button class="export-button" type="button" @click="exportHistory"><span class="material-symbols-outlined">download</span>Exportar historial</button>
                </section>

                <section class="summary-grid portal-enter portal-enter-delay-1">
                    <article v-for="item in summary" :key="item.label" :class="['summary-card', item.tone]">
                        <div class="summary-icon"><span class="material-symbols-outlined">{{ item.icon }}</span></div>
                        <div>
                            <small>{{ item.label }}</small>
                            <strong>{{ item.value }}</strong>
                            <em>{{ item.trend }}</em>
                        </div>
                    </article>
                </section>

                <section class="invoice-table liquid-panel portal-enter portal-enter-delay-2">
                    <header>
                        <div>
                            <p>Ledger B2B</p>
                            <h2>Movimientos de facturacion</h2>
                        </div>
                        <span>LIVE</span>
                    </header>
                    <div class="invoice-filters">
                        <label><span class="material-symbols-outlined">search</span><input v-model="query" type="search" placeholder="Buscar factura, vendedor o estado"></label>
                        <select v-model="selectedStatus"><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></select>
                        <input v-model="dateFrom" type="date" aria-label="Desde">
                        <input v-model="dateTo" type="date" aria-label="Hasta">
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Fecha</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Vendedor</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in invoices" :key="invoice.number">
                                <td><strong>{{ invoice.number }}</strong></td>
                                <td>{{ invoice.date }}</td>
                                <td>{{ invoice.due }}</td>
                                <td><span :class="['badge', invoice.tone]">{{ invoice.status }}</span></td>
                                <td><strong>{{ invoice.total }}</strong></td>
                                <td>{{ invoice.seller }}</td>
                                <td>
                                    <div class="actions">
                                        <button title="Ver detalle" type="button" @click="showInvoice(invoice)"><span class="material-symbols-outlined">visibility</span></button>
                                        <button title="Descargar PDF" type="button" @click="exportInvoice(invoice)"><span class="material-symbols-outlined">picture_as_pdf</span></button>
                                        <button title="Contactar vendedor" type="button" @click="contactSeller(invoice)"><span class="material-symbols-outlined">support_agent</span></button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!invoices.length"><td colspan="7" class="empty-state">No hay facturas para los filtros seleccionados.</td></tr>
                        </tbody>
                    </table>
                </section>
            </main>
        </template>
        <template #mobile>
            <main class="invoice-mobile">
                <header class="mobile-hero liquid-panel portal-enter">
                    <p>Centro financiero</p>
                    <h1>Historial de Facturas</h1>
                    <span>Facturas, vencimientos y vendedor asignado.</span>
                </header>
                <section class="mobile-invoice-filters liquid-panel portal-enter">
                    <input v-model="query" type="search" placeholder="Buscar factura">
                    <select v-model="selectedStatus"><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></select>
                    <button type="button" @click="exportHistory"><span class="material-symbols-outlined">download</span>Exportar</button>
                </section>
                <article v-for="invoice in invoices" :key="invoice.number" class="invoice-card liquid-panel portal-enter">
                    <div class="card-top"><strong>{{ invoice.number }}</strong><span :class="['badge', invoice.tone]">{{ invoice.status }}</span></div>
                    <section><p><small>Fecha</small>{{ invoice.date }}</p><p><small>Vence</small>{{ invoice.due }}</p></section>
                    <section><p><small>Total</small>{{ invoice.total }}</p><p><small>Vendedor</small>{{ invoice.seller }}</p></section>
                    <footer><button type="button" @click="showInvoice(invoice)"><span class="material-symbols-outlined">visibility</span>Ver</button><button type="button" @click="exportInvoice(invoice)"><span class="material-symbols-outlined">picture_as_pdf</span>PDF</button><button type="button" @click="contactSeller(invoice)"><span class="material-symbols-outlined">support_agent</span>Vendedor</button></footer>
                </article>
                <p v-if="!invoices.length" class="empty-state">No hay facturas para los filtros seleccionados.</p>
            </main>
        </template>
    </AppShell>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;500;600;700;800;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

.material-symbols-outlined{font-family:'Material Symbols Outlined';font-feature-settings:'liga';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;line-height:1}button{font:inherit}.invoice-main{display:grid;gap:18px}.liquid-panel{position:relative;overflow:hidden;border:1px solid rgba(125,211,252,.14);background:linear-gradient(135deg,rgba(15,21,36,.78),rgba(10,14,26,.56));box-shadow:0 28px 90px rgba(0,0,0,.28),inset 0 1px 0 rgba(255,255,255,.08);backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px)}.liquid-panel::before{position:absolute;inset:0;content:'';pointer-events:none;background:radial-gradient(circle at 18% 0%,rgba(125,211,252,.18),transparent 34%),radial-gradient(circle at 88% 12%,rgba(200,160,240,.14),transparent 28%)}.invoice-hero{min-height:216px;display:flex;align-items:flex-end;justify-content:space-between;gap:24px;padding:28px;border-radius:28px}.hero-copy{position:relative;z-index:1;max-width:650px}.hero-copy p,.mobile-hero p,.invoice-table header p{margin:0 0 7px;color:#7dd3fc;font-size:11px;font-weight:900;letter-spacing:.18em;text-transform:uppercase}.hero-copy h1,.mobile-hero h1{margin:0;color:#fff;font-size:42px;font-weight:900;letter-spacing:-.05em}.hero-copy span,.mobile-hero span{display:block;margin-top:10px;color:#a9bac8;line-height:1.6}.hero-orbit{position:absolute;right:190px;top:34px;width:140px;height:140px;border:1px solid rgba(125,211,252,.16);border-radius:999px}.hero-orbit span{position:absolute;inset:22px;border-radius:999px;background:radial-gradient(circle,rgba(125,211,252,.26),rgba(125,211,252,.03) 58%,transparent 60%);filter:drop-shadow(0 0 34px rgba(125,211,252,.28))}.hero-orbit i{position:absolute;right:13px;top:24px;width:14px;height:14px;border-radius:999px;background:#7dd3fc;box-shadow:0 0 26px rgba(125,211,252,.8)}.export-button{position:relative;z-index:1;display:flex;align-items:center;gap:9px;padding:13px 18px;border:1px solid rgba(125,211,252,.32);border-radius:16px;background:linear-gradient(135deg,rgba(125,211,252,.2),rgba(200,160,240,.12));color:#b8efff;font-weight:900;box-shadow:0 12px 40px rgba(56,189,248,.12);transition:transform .2s ease,box-shadow .2s ease}.export-button:hover{transform:translateY(-2px);box-shadow:0 18px 48px rgba(56,189,248,.2)}.summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.summary-card{position:relative;overflow:hidden;display:flex;align-items:center;gap:16px;padding:20px;border:1px solid rgba(125,211,252,.14);border-radius:22px;background:rgba(15,21,36,.58);box-shadow:inset 0 1px 0 rgba(255,255,255,.07);backdrop-filter:blur(18px);transition:transform .2s ease,border-color .2s ease}.summary-card:hover{transform:translateY(-3px);border-color:rgba(125,211,252,.28)}.summary-card::after{position:absolute;right:-24px;top:-24px;width:96px;height:96px;border-radius:999px;content:'';background:rgba(125,211,252,.14);filter:blur(8px)}.summary-card.amber::after{background:rgba(251,191,36,.16)}.summary-card.emerald::after{background:rgba(110,231,183,.16)}.summary-icon{width:48px;height:48px;display:grid;place-items:center;border-radius:16px;background:rgba(125,211,252,.12);color:#7dd3fc}.summary-card small{display:block;color:#9fb1c0;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.summary-card strong{display:block;margin-top:4px;color:#fff;font-size:26px;letter-spacing:-.04em}.summary-card em{display:block;margin-top:3px;color:#7dd3fc;font-size:12px;font-style:normal;font-weight:800}.invoice-table{border-radius:26px}.invoice-table header{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:22px 24px;border-bottom:1px solid rgba(255,255,255,.07)}.invoice-table h2{margin:0;color:#fff;font-size:22px;letter-spacing:-.03em}.invoice-table header span{padding:6px 10px;border:1px solid rgba(125,211,252,.24);border-radius:999px;background:rgba(125,211,252,.12);color:#7dd3fc;font-size:10px;font-weight:900;letter-spacing:.12em}table{position:relative;z-index:1;width:100%;border-collapse:collapse}th{padding:14px 18px;background:rgba(255,255,255,.045);color:#9fb1c0;font-size:10px;letter-spacing:.12em;text-align:left;text-transform:uppercase}td{padding:17px 18px;border-top:1px solid rgba(255,255,255,.055);color:#dce9f3;font-size:14px}tbody tr{transition:background .2s ease,transform .2s ease}tbody tr:hover{background:rgba(125,211,252,.055)}.badge{display:inline-flex;align-items:center;padding:7px 10px;border-radius:999px;font-size:11px;font-weight:900}.badge.paid{background:rgba(110,231,183,.13);color:#9ff7d5}.badge.pending{background:rgba(251,191,36,.14);color:#ffd978}.badge.overdue{background:rgba(255,107,107,.14);color:#ff9b9b}.actions{display:flex;gap:8px}.actions button{width:34px;height:34px;display:grid;place-items:center;border:1px solid rgba(125,211,252,.18);border-radius:12px;background:rgba(255,255,255,.045);color:#7dd3fc;transition:transform .2s ease,background .2s ease}.actions button:hover{transform:translateY(-2px);background:rgba(125,211,252,.12)}.invoice-mobile{max-width:448px;margin:0 auto;padding:82px 16px 0;display:grid;gap:14px}.mobile-hero{padding:22px;border-radius:24px}.mobile-hero h1{font-size:30px}.invoice-card{padding:18px;border-radius:22px}.card-top,.invoice-card section,.invoice-card footer{position:relative;z-index:1;display:flex;justify-content:space-between;gap:12px}.card-top{align-items:center;margin-bottom:16px}.card-top strong{color:#fff}.invoice-card section{padding:12px 0;border-top:1px solid rgba(255,255,255,.06)}.invoice-card p{margin:0;color:#e0e8f0;font-weight:800}.invoice-card small{display:block;margin-bottom:4px;color:#9fb1c0;font-size:10px;text-transform:uppercase}.invoice-card footer{padding-top:14px;border-top:1px solid rgba(255,255,255,.06)}.invoice-card footer button{display:flex;align-items:center;gap:5px;padding:9px 10px;border:1px solid rgba(125,211,252,.2);border-radius:12px;background:rgba(125,211,252,.08);color:#9ee8ff;font-size:12px;font-weight:800}@media(max-width:1100px){.summary-grid{grid-template-columns:1fr}.invoice-hero{align-items:flex-start;flex-direction:column}.hero-orbit{right:28px;top:28px;opacity:.55}table{min-width:940px}.invoice-table{overflow:auto}}@media(max-width:767px){.invoice-main{display:none}.invoice-mobile{padding-left:16px;padding-right:16px}}
.invoice-main.shell-content{width:100%!important;max-width:none!important;margin:0!important;padding:18px!important}.invoice-hero,.summary-grid,.invoice-table{width:100%!important}.summary-grid{grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:clamp(16px,1.4vw,24px)!important}.invoice-table table{width:100%!important}.liquid-panel,.summary-card{box-shadow:0 28px 90px rgba(0,0,0,.3),0 0 42px rgba(56,189,248,.055),inset 0 1px 0 rgba(255,255,255,.08)}.invoice-filters,.mobile-invoice-filters{position:relative;z-index:1;display:grid;grid-template-columns:minmax(220px,1fr) 180px 150px 150px;gap:10px;padding:16px;border-bottom:1px solid rgba(255,255,255,.08)}.invoice-filters label{display:flex;align-items:center;gap:8px}.invoice-filters input,.invoice-filters select,.mobile-invoice-filters input,.mobile-invoice-filters select{min-width:0;border:1px solid rgba(125,211,252,.18);border-radius:12px;background:rgba(10,16,30,.55);color:#e0e8f0;padding:10px 12px;outline:0}.mobile-invoice-filters{grid-template-columns:1fr;margin:14px 0;padding:14px;border-radius:18px}.mobile-invoice-filters button{display:flex;align-items:center;justify-content:center;gap:8px;border:1px solid rgba(125,211,252,.28);border-radius:12px;background:rgba(125,211,252,.12);color:#9ee8ff;padding:10px 12px;font-weight:900}.empty-state{position:relative;z-index:1;padding:18px;text-align:center;color:#a9bac8}@media(max-width:1100px){.summary-grid{grid-template-columns:1fr!important}.invoice-filters{grid-template-columns:1fr 1fr}}
</style>
