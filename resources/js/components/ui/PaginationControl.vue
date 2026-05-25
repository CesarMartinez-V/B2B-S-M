<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentPage: { type: Number, required: true },
    lastPage: { type: Number, required: true },
    total: { type: Number, default: 0 },
    perPage: { type: Number, default: 20 },
    disabled: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['page-change']);

const pageCount = computed(() => Math.max(1, Number(props.lastPage) || 1));
const visiblePages = computed(() => {
    const total = pageCount.value;
    const current = Math.min(Math.max(1, Number(props.currentPage) || 1), total);
    const radius = props.compact ? 1 : 2;

    if (total <= (props.compact ? 5 : 10)) return Array.from({ length: total }, (_, index) => index + 1);

    const pages = new Set([1, total]);
    for (let page = current - radius; page <= current + radius; page += 1) {
        if (page > 1 && page < total) pages.add(page);
    }

    return [...pages].sort((a, b) => a - b).reduce((result, page, index, source) => {
        if (index > 0 && page - source[index - 1] > 1) result.push('ellipsis');
        result.push(page);
        return result;
    }, []);
});

const from = computed(() => props.total === 0 ? 0 : ((props.currentPage - 1) * props.perPage) + 1);
const to = computed(() => Math.min(props.total, from.value + props.perPage - 1));

const goToPage = (page) => {
    if (props.disabled || page === 'ellipsis') return;
    const nextPage = Math.min(Math.max(1, Number(page) || 1), pageCount.value);
    if (nextPage === props.currentPage) return;
    emit('page-change', nextPage);
};
</script>

<template>
    <nav v-if="pageCount > 1" :class="['pagination-control', { compact }]" aria-label="Paginacion">
        <small v-if="!compact">Mostrando {{ from }}-{{ to }} de {{ total }}</small>
        <div>
            <button type="button" :disabled="disabled || currentPage === 1" @click="goToPage(1)"><span class="material-symbols-outlined">first_page</span></button>
            <button type="button" :disabled="disabled || currentPage === 1" @click="goToPage(currentPage - 1)"><span class="material-symbols-outlined">chevron_left</span></button>
            <template v-for="(page, index) in visiblePages" :key="`${page}-${index}`">
                <span v-if="page === 'ellipsis'" class="pagination-ellipsis">...</span>
                <button v-else :class="{ active: currentPage === page }" type="button" :disabled="disabled" @click="goToPage(page)">{{ page }}</button>
            </template>
            <button type="button" :disabled="disabled || currentPage === pageCount" @click="goToPage(currentPage + 1)"><span class="material-symbols-outlined">chevron_right</span></button>
            <button type="button" :disabled="disabled || currentPage === pageCount" @click="goToPage(pageCount)"><span class="material-symbols-outlined">last_page</span></button>
        </div>
    </nav>
</template>

<style scoped>
.pagination-control{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:16px;width:100%;padding:16px}.pagination-control>small{color:#a9bac8;font-size:12px;font-weight:900}.pagination-control>div{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:8px;margin-left:auto}.pagination-control button{display:inline-flex;align-items:center;justify-content:center;min-width:38px;border:1px solid rgba(125,211,252,.24);border-radius:999px;background:rgba(125,211,252,.1);color:#9ee8ff;padding:9px 12px;font-weight:900;cursor:pointer}.pagination-control button.active{border-color:rgba(125,211,252,.42);background:rgba(125,211,252,.18);color:#7dd3fc}.pagination-control button:disabled{cursor:not-allowed;opacity:.45}.pagination-control .material-symbols-outlined{font-size:18px}.pagination-ellipsis{padding:0 4px;color:#c8d8e6;font-weight:900}.pagination-control.compact{justify-content:center;padding:14px 8px}.pagination-control.compact>div{margin-left:0}.pagination-control.compact button{min-width:34px;padding:8px 10px}@media(max-width:767px){.pagination-control{justify-content:center}.pagination-control>small{display:none}.pagination-control button{min-width:34px;padding:8px 10px}}
</style>
