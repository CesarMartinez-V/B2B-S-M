<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import { useModal } from '../../composables/useModal.js';

const { modal, closeModal, closeModalFromBackdrop, confirmModal } = useModal();

const handleKeydown = (event) => {
    if (event.key === 'Escape' && modal.open) closeModal();
};

onMounted(() => window.addEventListener('keydown', handleKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', handleKeydown));
</script>

<template>
    <Teleport to="body">
        <div v-if="modal.open" class="modal-backdrop" @click.self="closeModalFromBackdrop">
            <section :class="['modal-card', `modal-card--${modal.size || 'md'}`, { 'modal-card--scrollable': modal.scrollable !== false }]" role="dialog" aria-modal="true" :aria-label="modal.title">
                <button class="modal-close" type="button" aria-label="Cerrar modal" @click="closeModal"><span class="material-symbols-outlined">close</span></button>
                <header class="modal-head">
                    <span class="modal-icon material-symbols-outlined">{{ modal.icon }}</span>
                    <div>
                        <h2>{{ modal.title }}</h2>
                        <p v-if="modal.message">{{ modal.message }}</p>
                    </div>
                </header>
                <div v-if="modal.detail" class="modal-detail">
                    <div v-if="modal.detail.badge" class="detail-badge">{{ modal.detail.badge }}</div>
                    <dl v-if="modal.detail.rows?.length" class="detail-grid">
                        <div v-for="row in modal.detail.rows" :key="row.label">
                            <dt>{{ row.label }}</dt>
                            <dd>{{ row.value }}</dd>
                        </div>
                    </dl>
                    <section v-if="modal.detail.items?.length" class="detail-section">
                        <h3>{{ modal.detail.itemsTitle || 'Productos' }}</h3>
                        <article v-for="item in modal.detail.items" :key="item.id || item.sku || item.name">
                            <div><strong>{{ item.name }}</strong><small>{{ item.sku ? `SKU: ${item.sku}` : (item.meta || 'Detalle disponible') }}</small></div>
                            <span>{{ item.qty ? `${item.qty} x ` : '' }}{{ item.priceLabel || item.value || '' }}</span>
                        </article>
                    </section>
                    <section v-if="modal.detail.sections?.length" class="detail-section">
                        <article v-for="section in modal.detail.sections" :key="section.title">
                            <div><strong>{{ section.title }}</strong><small>{{ section.text }}</small></div>
                            <span>{{ section.value }}</span>
                        </article>
                    </section>
                    <section v-if="modal.detail.observations !== undefined" class="detail-section observations-block">
                        <h3>Observaciones</h3>
                        <p>{{ modal.detail.observations || 'Sin observaciones.' }}</p>
                    </section>
                </div>
                <footer><button v-if="modal.cancelText" type="button" class="ghost" @click="closeModal">{{ modal.cancelText }}</button><button type="button" @click="confirmModal">{{ modal.confirmText }}</button></footer>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
.modal-backdrop{position:fixed;inset:0;z-index:190;display:grid;place-items:center;padding:20px;background:rgba(3,7,18,.62);backdrop-filter:blur(10px)}.modal-card{position:relative;width:min(520px,100%);max-height:min(86vh,760px);display:flex;flex-direction:column;overflow:hidden;padding:26px;border:1px solid var(--portal-glass-border);border-radius:26px;background:linear-gradient(135deg,rgba(20,30,50,.92),rgba(8,13,25,.88));box-shadow:var(--portal-shadow-glass);color:var(--portal-text);animation:modal-in .24s ease both}.modal-card--sm{width:min(380px,calc(100vw - 32px))}.modal-card--md{width:min(540px,calc(100vw - 32px))}.modal-card--lg{width:min(920px,calc(100vw - 32px))}.modal-card--xl{width:min(1120px,calc(100vw - 32px))}.modal-close{position:absolute;right:14px;top:14px;z-index:2;border:0;background:transparent;color:var(--portal-text-muted);cursor:pointer}.modal-head{display:grid;grid-template-columns:48px minmax(0,1fr);gap:14px;align-items:flex-start;padding-right:32px}.modal-icon{display:grid;width:48px;height:48px;place-items:center;border-radius:16px;background:color-mix(in srgb,var(--portal-primary) 14%,transparent);color:var(--portal-primary)}.modal-card h2{margin:0;color:var(--portal-text-strong);font-size:24px;letter-spacing:-.03em}.modal-card p{margin:8px 0 0;color:var(--portal-text-muted);line-height:1.6;white-space:pre-line}.modal-detail{display:grid;gap:14px;margin-top:18px;min-height:0}.modal-card--scrollable .modal-detail{overflow:auto;padding-right:4px}.detail-badge{width:max-content;max-width:100%;border:1px solid rgba(250,204,21,.26);border-radius:999px;background:rgba(250,204,21,.1);color:#facc15;padding:6px 12px;font-size:12px;font-weight:900;text-transform:uppercase}.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:0}.detail-grid div,.detail-section{min-width:0;border:1px solid var(--portal-glass-border);border-radius:18px;background:rgba(255,255,255,.045);padding:12px}.detail-grid dt{color:var(--portal-text-muted);font-size:11px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.detail-grid dd{margin:4px 0 0;color:var(--portal-text-strong);font-weight:800;overflow-wrap:anywhere}.detail-section h3{margin:0 0 10px;color:var(--portal-text-strong);font-size:14px}.detail-section article{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;border-top:1px solid var(--portal-glass-border);padding:10px 0}.detail-section article:first-of-type{border-top:0;padding-top:0}.detail-section strong,.detail-section small{display:block;overflow:hidden;text-overflow:ellipsis}.detail-section small{margin-top:3px;color:var(--portal-text-muted)}.detail-section article>span{color:var(--portal-text-strong);font-weight:900;text-align:right;white-space:nowrap}.observations-block p{max-height:180px;overflow:auto;overflow-wrap:anywhere}footer{display:flex;justify-content:flex-end;gap:10px;margin-top:22px;padding-top:16px;border-top:1px solid var(--portal-glass-border)}footer button{border:1px solid var(--portal-glass-border-strong);border-radius:14px;background:color-mix(in srgb,var(--portal-primary) 16%,transparent);color:var(--portal-primary);padding:11px 16px;font-weight:900;cursor:pointer}footer .ghost{background:var(--portal-input-bg);color:var(--portal-text-muted)}@keyframes modal-in{from{opacity:0;transform:translateY(10px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}@media(max-width:767px){.modal-backdrop{align-items:end;padding:12px 12px calc(92px + env(safe-area-inset-bottom))}.modal-card{width:100%;max-height:calc(100dvh - 116px);padding:22px;border-radius:24px}.modal-head{grid-template-columns:40px minmax(0,1fr)}.modal-icon{width:40px;height:40px;border-radius:14px}.modal-card h2{font-size:21px}.detail-grid{grid-template-columns:1fr}.detail-section article{grid-template-columns:1fr}.detail-section article>span{text-align:left;white-space:normal}footer{display:grid;grid-template-columns:1fr}footer button{width:100%}}
</style>
