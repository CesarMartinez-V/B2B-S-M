<script setup>
import { useToast } from '../../composables/useToast.js';

const { toasts, removeToast } = useToast();
</script>

<template>
    <section class="toast-stack" aria-live="polite">
        <article v-for="toast in toasts" :key="toast.id" :class="['toast-card', toast.type]">
            <span class="material-symbols-outlined">{{ toast.type === 'success' ? 'check_circle' : toast.type === 'error' ? 'error' : 'info' }}</span>
            <div>
                <strong>{{ toast.title }}</strong>
                <p>{{ toast.message }}</p>
            </div>
            <button type="button" aria-label="Cerrar notificacion" @click="removeToast(toast.id)"><span class="material-symbols-outlined">close</span></button>
        </article>
    </section>
</template>

<style scoped>
.toast-stack{position:fixed;right:18px;bottom:18px;z-index:200;display:grid;gap:12px;width:min(360px,calc(100vw - 32px));pointer-events:none}.toast-card{pointer-events:auto;display:grid;grid-template-columns:auto 1fr auto;align-items:start;gap:12px;padding:14px;border:1px solid var(--portal-glass-border);border-radius:18px;background:var(--portal-glass-bg);box-shadow:var(--portal-shadow-glass);backdrop-filter:blur(22px) saturate(160%);color:var(--portal-text);animation:toast-in .24s ease both}.toast-card>span{color:var(--portal-primary)}.toast-card.success>span{color:var(--portal-success)}.toast-card.error>span{color:var(--portal-error)}.toast-card strong{display:block;font-size:13px;color:var(--portal-text-strong)}.toast-card p{margin:3px 0 0;color:var(--portal-text-muted);font-size:12px;line-height:1.45}.toast-card button{border:0;background:transparent;color:var(--portal-text-muted);cursor:pointer}.toast-card button span{font-size:18px}@keyframes toast-in{from{opacity:0;transform:translateY(10px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}@media(max-width:767px){.toast-stack{right:12px;bottom:96px;left:12px;width:auto}}
</style>
