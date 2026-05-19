<script setup>
import { onMounted } from 'vue';
import { useTheme } from '../../composables/useTheme.js';
import { navigateTo } from '../../composables/usePortalNavigation.js';
import { portalBrand, portalUser } from '../../portalNavigation.js';

defineProps({
    brandLabel: { type: String, default: portalBrand.topLabel },
    searchPlaceholder: { type: String, default: 'Buscar socio, factura o pedido...' },
    showSearch: { type: Boolean, default: true },
    showNotifications: { type: Boolean, default: true },
    showSettings: { type: Boolean, default: true },
    userName: { type: String, default: portalUser.name },
    userHref: { type: String, default: portalUser.href },
    avatarSrc: { type: String, default: portalUser.avatar },
});

const { isLightMode, toggleTheme, initTheme } = useTheme();

onMounted(() => {
    initTheme();
});
</script>

<template>
    <header class="portal-topbar">
        <div><strong>{{ brandLabel }}</strong><label v-if="showSearch"><span class="material-symbols-outlined">search</span><input :placeholder="searchPlaceholder" type="text"></label></div>
        <nav><button class="theme-toggle" type="button" :aria-label="isLightMode ? 'Activar modo oscuro' : 'Activar modo claro'" @click="toggleTheme"><span class="material-symbols-outlined">{{ isLightMode ? 'dark_mode' : 'light_mode' }}</span></button><span v-if="showNotifications" class="material-symbols-outlined">notifications</span><span v-if="showSettings" class="material-symbols-outlined">settings</span><a class="portal-user-link" :href="userHref" @click.prevent="navigateTo(userHref)"><span>{{ userName }}</span><img :src="avatarSrc" :alt="userName"></a></nav>
    </header>
</template>

<style scoped>
.portal-topbar{height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;background:rgba(20,28,46,.6);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.1);box-shadow:0 0 30px rgba(125,211,252,.05)}.portal-topbar>div,.portal-topbar nav{display:flex;align-items:center;gap:24px}.portal-topbar strong{letter-spacing:.12em}.portal-topbar label{display:flex;align-items:center;gap:10px;width:330px;padding:10px 16px;border-radius:999px;background:rgba(255,255,255,.05);color:#a0b4c4}.portal-topbar input{border:0;outline:0;background:transparent;color:#e0e8f0}.portal-topbar nav{color:#a0b4c4}.theme-toggle{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border:1px solid rgba(125,211,252,.24);border-radius:999px;background:rgba(125,211,252,.08);color:#7dd3fc;cursor:pointer}.theme-toggle span{font-size:20px}.portal-user-link{display:flex;align-items:center;gap:12px;padding-left:16px;border-left:1px solid rgba(255,255,255,.1);color:#e0e8f0;font-weight:700;text-decoration:none}.portal-user-link img{width:32px;height:32px;object-fit:cover;border-radius:999px;border:1px solid rgba(125,211,252,.3)}:global(.portal-light) .portal-topbar{background:linear-gradient(135deg,rgba(255,255,255,.78),rgba(244,250,255,.58) 48%,rgba(229,244,252,.46));border-bottom-color:var(--portal-glass-border);color:var(--portal-text);box-shadow:var(--portal-shadow-soft);backdrop-filter:blur(30px) saturate(175%)}:global(.portal-light) .portal-topbar label{background:linear-gradient(135deg,rgba(255,255,255,.78),rgba(240,248,253,.5));border:1px solid var(--portal-input-border);color:var(--portal-text-muted);box-shadow:inset 0 1px 0 rgba(255,255,255,.82),0 8px 24px rgba(42,80,112,.07)}:global(.portal-light) .portal-topbar input{color:var(--portal-text)}:global(.portal-light) .portal-topbar nav{color:rgba(16,38,58,.66)}:global(.portal-light) .portal-user-link{color:var(--portal-text);border-left-color:rgba(72,118,150,.18)}:global(.portal-light) .theme-toggle{background:linear-gradient(135deg,rgba(8,122,168,.13),rgba(255,255,255,.4));color:var(--portal-primary-strong);border-color:rgba(8,122,168,.28);box-shadow:0 10px 28px rgba(8,122,168,.12),inset 0 1px 0 rgba(255,255,255,.86)}@media(min-width:768px) and (max-width:1120px){.portal-topbar label{display:none}}
</style>
