<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    tone: { type: String, default: '' },
    size: { type: String, default: 'md', validator: (value) => ['sm', 'md'].includes(value) },
    variant: { type: String, default: 'glass', validator: (value) => ['glass', 'solid'].includes(value) },
});

const normalize = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
    .replace(/[\s-]+/g, '_');

const toneMap = {
    approved: 'success',
    aprobado: 'success',
    aprobada: 'success',
    pending: 'warning',
    pendiente: 'warning',
    rejected: 'danger',
    rechazado: 'danger',
    rechazada: 'danger',
    archived: 'muted',
    archivada: 'muted',
    paid: 'success',
    pagada: 'success',
    pagado: 'success',
    aplicado: 'success',
    aplicada: 'success',
    overdue: 'danger',
    vencida: 'danger',
    vencido: 'danger',
    anulada: 'danger',
    anulado: 'danger',
    in_transit: 'info',
    en_transito: 'info',
    completed: 'success',
    completado: 'success',
    completada: 'success',
    converted: 'success',
    convertida: 'success',
    convertido: 'success',
    processing: 'info',
    procesando: 'info',
    procesado: 'info',
    ingresado: 'warning',
    picking: 'warning',
    facturacion: 'warning',
    packing: 'warning',
    despacho: 'info',
    draft: 'muted',
    borrador: 'muted',
    solicitud_preparada: 'success',
};

const labelMap = {
    approved: 'Aprobado',
    pending: 'Pendiente',
    rejected: 'Rechazado',
    archived: 'Archivado',
    paid: 'Pagada',
    overdue: 'Vencida',
    anulada: 'Anulada',
    anulado: 'Anulada',
    in_transit: 'En transito',
    completed: 'Completado',
    converted: 'Convertida',
    convertida: 'Convertida',
    convertido: 'Convertida',
    processing: 'Procesando',
    ingresado: 'Ingresado',
    picking: 'Picking',
    facturacion: 'Facturación',
    packing: 'Packing',
    despacho: 'Despacho',
    draft: 'Borrador',
    solicitud_preparada: 'Solicitud preparada',
};

const normalizedStatus = computed(() => normalize(props.status));
const resolvedTone = computed(() => toneMap[normalize(props.tone)] || props.tone || toneMap[normalizedStatus.value] || 'muted');
const label = computed(() => labelMap[normalizedStatus.value] || props.status);
</script>

<template>
    <span :class="['status-badge', `status-badge--${resolvedTone}`, `status-badge--${size}`, `status-badge--${variant}`]" role="status">
        <i aria-hidden="true"></i>
        {{ label }}
    </span>
</template>

<style scoped>
.status-badge{--badge-rgb:125,211,252;--badge-color:#7dd3fc;display:inline-flex;align-items:center;justify-content:center;gap:8px;width:max-content;max-width:100%;border:1px solid rgba(var(--badge-rgb),.28);border-radius:999px;background:rgba(var(--badge-rgb),.12);color:var(--badge-color);font-weight:900;letter-spacing:.05em;line-height:1;text-transform:uppercase;white-space:nowrap;box-shadow:0 0 22px rgba(var(--badge-rgb),.08),inset 0 1px 0 rgba(255,255,255,.12);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}.status-badge i{width:7px;height:7px;flex:0 0 auto;border-radius:999px;background:var(--badge-color);box-shadow:0 0 12px rgba(var(--badge-rgb),.78)}.status-badge--sm{padding:6px 10px;font-size:10px}.status-badge--md{padding:9px 14px;font-size:12px}.status-badge--solid{background:rgba(var(--badge-rgb),.2);border-color:rgba(var(--badge-rgb),.42)}.status-badge--success{--badge-rgb:125,211,252;--badge-color:#7dd3fc}.status-badge--warning{--badge-rgb:200,160,240;--badge-color:#c8a0f0}.status-badge--danger{--badge-rgb:248,113,113;--badge-color:#f87171}.status-badge--muted{--badge-rgb:160,180,196;--badge-color:#a0b4c4}.status-badge--info{--badge-rgb:96,165,250;--badge-color:#93c5fd}:global(.portal-light) .status-badge{background:rgba(var(--badge-rgb),.16);border-color:rgba(var(--badge-rgb),.34);box-shadow:0 12px 28px rgba(var(--badge-rgb),.12),inset 0 1px 0 rgba(255,255,255,.45)}
</style>
