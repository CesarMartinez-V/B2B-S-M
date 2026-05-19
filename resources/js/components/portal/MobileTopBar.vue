<script setup>
import { portalBrand, portalUser } from '../../portalNavigation.js';

defineProps({
    title: { type: String, default: portalBrand.topLabel },
    showNotifications: { type: Boolean, default: true },
    showSettings: { type: Boolean, default: false },
    showAvatar: { type: Boolean, default: true },
    avatarSrc: { type: String, default: portalUser.avatar },
    avatarHref: { type: String, default: portalUser.href },
});
</script>

<template>
    <header class="portal-mobile-top">
        <strong>{{ title }}</strong>
        <div><span v-if="showNotifications" class="material-symbols-outlined">notifications</span><span v-if="showSettings" class="material-symbols-outlined">settings</span><a v-if="showAvatar" :href="avatarHref"><img :src="avatarSrc" alt="Roberto Martínez"></a></div>
    </header>
</template>

<style scoped>
.portal-mobile-top{position:fixed;inset:0 0 auto;z-index:50;height:72px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;background:rgba(20,28,46,.6);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.1);box-shadow:0 0 30px rgba(125,211,252,.05)}.portal-mobile-top strong{font-size:20px;letter-spacing:.16em;text-transform:uppercase}.portal-mobile-top div{display:flex;align-items:center;gap:18px;color:#7dd3fc}.portal-mobile-top a{color:inherit;text-decoration:none}.portal-mobile-top img{width:38px;height:38px;border-radius:999px;object-fit:cover;border:1px solid rgba(125,211,252,.3)}:global(.portal-light) .portal-mobile-top{background:linear-gradient(135deg,rgba(255,255,255,.8),rgba(244,250,255,.6) 52%,rgba(229,244,252,.48));border-bottom-color:var(--portal-glass-border);color:var(--portal-text);box-shadow:var(--portal-shadow-soft);backdrop-filter:blur(30px) saturate(175%)}:global(.portal-light) .portal-mobile-top div{color:var(--portal-primary-strong)}
</style>
