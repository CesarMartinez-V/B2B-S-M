<script setup>
import { useModal } from '../../composables/useModal.js';

const { modal, closeModal, confirmModal } = useModal();
</script>

<template>
    <Teleport to="body">
        <div v-if="modal.open" class="modal-backdrop" @click.self="closeModal">
            <section class="modal-card" role="dialog" aria-modal="true" :aria-label="modal.title">
                <button class="modal-close" type="button" aria-label="Cerrar modal" @click="closeModal"><span class="material-symbols-outlined">close</span></button>
                <span class="modal-icon material-symbols-outlined">{{ modal.icon }}</span>
                <h2>{{ modal.title }}</h2>
                <p>{{ modal.message }}</p>
                <footer><button v-if="modal.cancelText" type="button" class="ghost" @click="closeModal">{{ modal.cancelText }}</button><button type="button" @click="confirmModal">{{ modal.confirmText }}</button></footer>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
.modal-backdrop{position:fixed;inset:0;z-index:190;display:grid;place-items:center;padding:20px;background:rgba(3,7,18,.62);backdrop-filter:blur(10px)}.modal-card{position:relative;width:min(460px,100%);padding:28px;border:1px solid var(--portal-glass-border);border-radius:26px;background:var(--portal-glass-bg);box-shadow:var(--portal-shadow-glass);color:var(--portal-text);animation:modal-in .24s ease both}.modal-close{position:absolute;right:14px;top:14px;border:0;background:transparent;color:var(--portal-text-muted);cursor:pointer}.modal-icon{display:grid;width:48px;height:48px;place-items:center;margin-bottom:16px;border-radius:16px;background:color-mix(in srgb,var(--portal-primary) 14%,transparent);color:var(--portal-primary)}.modal-card h2{margin:0;color:var(--portal-text-strong);font-size:24px;letter-spacing:-.03em}.modal-card p{margin:10px 0 0;color:var(--portal-text-muted);line-height:1.6;white-space:pre-line}.modal-card footer{display:flex;justify-content:flex-end;gap:10px;margin-top:24px}.modal-card footer button{padding:11px 16px;border:1px solid var(--portal-glass-border-strong);border-radius:14px;background:color-mix(in srgb,var(--portal-primary) 14%,transparent);color:var(--portal-primary);font-weight:900;cursor:pointer}.modal-card footer .ghost{border-color:var(--portal-glass-border);background:var(--portal-input-bg);color:var(--portal-text-muted)}@keyframes modal-in{from{opacity:0;transform:translateY(12px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
</style>
