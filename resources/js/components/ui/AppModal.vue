<script setup>
import { useModal } from '../../composables/useModal.js';

const { modal, closeModal, confirmModal } = useModal();
</script>

<template>
    <Teleport to="body">
        <div v-if="modal.open" class="modal-backdrop" @click.self="closeModal">
            <section :class="['modal-card', `modal-card--${modal.size || 'md'}`]" role="dialog" aria-modal="true" :aria-label="modal.title">
                <button class="modal-close" type="button" aria-label="Cerrar modal" @click="closeModal"><span class="material-symbols-outlined">close</span></button>
                <span class="modal-icon material-symbols-outlined">{{ modal.icon }}</span>
                <h2>{{ modal.title }}</h2>
                <p>{{ modal.message }}</p>
                <div v-if="modal.detail" class="modal-detail">
                    <div v-if="modal.detail.badge" class="detail-badge">{{ modal.detail.badge }}</div>
                    <dl v-if="modal.detail.rows?.length" class="detail-grid">
                        <div v-for="row in modal.detail.rows" :key="row.label">
                            <dt>{{ row.label }}</dt>
                            <dd>{{ row.value }}</dd>
                        </div>
                    </dl>
                    <section v-if="modal.detail.items?.length" class="detail-section">
                        <h3>Productos</h3>
                        <article v-for="item in modal.detail.items" :key="item.id || item.sku || item.name">
                            <div><strong>{{ item.name }}</strong><small>{{ item.sku ? `SKU: ${item.sku}` : 'Producto cotizado' }}</small></div>
                            <span>{{ item.qty }} x {{ item.priceLabel || '' }}</span>
                        </article>
                    </section>
                    <section class="detail-section observations-block">
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
.modal-backdrop{position:fixed;inset:0;z-index:190;display:grid;place-items:center;padding:20px;background:rgba(3,7,18,.62);backdrop-filter:blur(10px)}.modal-card{position:relative;width:min(460px,100%);max-height:85vh;overflow:auto;padding:28px;border:1px solid var(--portal-glass-border);border-radius:26px;background:var(--portal-glass-bg);box-shadow:var(--portal-shadow-glass);color:var(--portal-text);animation:modal-in .24s ease both}.modal-card--lg{width:min(920px,calc(100vw - 32px))}.modal-close{position:absolute;right:14px;top:14px;border:0;background:transparent;color:var(--portal-text-muted);cursor:pointer}.modal-icon{display:grid;width:48px;height:48px;place-items:center;margin-bottom:16px;border-radius:16px;background:color-mix(in srgb,var(--portal-primary) 14%,transparent);color:var(--portal-primary)}.modal-card h2{margin:0;color:var(--portal-text-strong);font-size:24px;letter-spacing:-.03em}.modal-card p{margin:10px 0 0;color:var(--portal-text-muted);line-height:1.6;white-space:pre-line}.modal-detail{display:grid;gap:14px;margin-top:18px}.detail-badge{width:max-content;max-width:100%;border:1px solid rgba(250,204,21,.26);border-radius:999px;background:rgba(250,204,21,.1);color:#facc15;padding:6px 12px;font-size:12px;font-weight:900;text-transform:uppercase}.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:0}.detail-grid div,.detail-section{border:1px solid var(--portal-glass-border);border-radius:18px;background:rgba(255,255,255,.045);padding:12px}.detail-grid dt{color:var(--portal-text-muted);font-size:11px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.detail-grid dd{margin:4px 0 0;color:var(--portal-text-strong);font-weight:800}.detail-section h3{margin:0 0 10px;color:var(--portal-text-strong);font-size:14px}.detail-section article{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;border-top:1px solid var(--portal-glass-border);padding:10px 0}.detail-section article:first-of-type{border-top:0;padding-top:0}.detail-section article:last-child{padding-bottom:0}.detail-section strong{display:block;color:var(--portal-text-strong)}.detail-section small{display:block;color:var(--portal-text-muted);font-size:11px}.detail-section article span{color:var(--portal-primary);font-weight:900}.observations-block{background:linear-gradient(135deg,rgba(125,211,252,.12),rgba(255,255,255,.045))}.observations-block p{margin:0;color:var(--portal-text);white-space:pre-wrap}.modal-card footer{display:flex;justify-content:flex-end;gap:10px;margin-top:24px}.modal-card footer button{padding:11px 16px;border:1px solid var(--portal-glass-border-strong);border-radius:14px;background:color-mix(in srgb,var(--portal-primary) 14%,transparent);color:var(--portal-primary);font-weight:900;cursor:pointer}.modal-card footer .ghost{border-color:var(--portal-glass-border);background:var(--portal-input-bg);color:var(--portal-text-muted)}@keyframes modal-in{from{opacity:0;transform:translateY(12px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}@media(max-width:640px){.modal-backdrop{padding:10px}.modal-card,.modal-card--lg{width:100%;max-height:86vh;padding:20px;border-radius:22px}.detail-grid{grid-template-columns:1fr}.detail-section article{grid-template-columns:1fr}.modal-card footer{display:grid;grid-template-columns:1fr}}
</style>
