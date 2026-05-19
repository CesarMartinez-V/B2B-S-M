<script setup>
import { useConfirm } from '../../composables/useConfirm.js';

const { confirmState, closeConfirm, confirm } = useConfirm();
</script>

<template>
    <Teleport to="body">
        <div v-if="confirmState.open" class="confirm-backdrop" @click.self="closeConfirm">
            <section class="confirm-card" role="dialog" aria-modal="true" :aria-label="confirmState.title">
                <span class="material-symbols-outlined">help</span>
                <h2>{{ confirmState.title }}</h2>
                <p>{{ confirmState.message }}</p>
                <footer>
                    <button type="button" class="ghost" @click="closeConfirm">{{ confirmState.cancelText }}</button>
                    <button type="button" :class="confirmState.tone" @click="confirm">{{ confirmState.confirmText }}</button>
                </footer>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
.confirm-backdrop{position:fixed;inset:0;z-index:195;display:grid;place-items:center;padding:20px;background:rgba(3,7,18,.62);backdrop-filter:blur(10px)}.confirm-card{width:min(420px,100%);padding:26px;border:1px solid var(--portal-glass-border);border-radius:24px;background:var(--portal-glass-bg);box-shadow:var(--portal-shadow-glass);color:var(--portal-text);animation:confirm-in .22s ease both}.confirm-card>span{display:grid;width:44px;height:44px;place-items:center;margin-bottom:14px;border-radius:16px;background:color-mix(in srgb,var(--portal-primary) 14%,transparent);color:var(--portal-primary)}.confirm-card h2{margin:0;color:var(--portal-text-strong);font-size:22px}.confirm-card p{margin:10px 0 0;color:var(--portal-text-muted);line-height:1.55}.confirm-card footer{display:flex;justify-content:flex-end;gap:10px;margin-top:24px}.confirm-card button{padding:11px 16px;border-radius:14px;font-weight:900;cursor:pointer}.confirm-card .ghost{border:1px solid var(--portal-glass-border);background:var(--portal-input-bg);color:var(--portal-text-muted)}.confirm-card .primary{border:1px solid var(--portal-glass-border-strong);background:color-mix(in srgb,var(--portal-primary) 14%,transparent);color:var(--portal-primary)}.confirm-card .danger{border:1px solid color-mix(in srgb,var(--portal-error) 34%,transparent);background:color-mix(in srgb,var(--portal-error) 13%,transparent);color:var(--portal-error)}@keyframes confirm-in{from{opacity:0;transform:translateY(10px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
</style>
