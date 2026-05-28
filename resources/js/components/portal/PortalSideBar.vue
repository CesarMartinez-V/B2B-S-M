<script setup>
import { computed } from 'vue';
import { useAuth } from '../../composables/useAuth.js';
import { useConfirm } from '../../composables/useConfirm.js';
import { usePortalActions } from '../../composables/usePortalActions.js';
import { navigateTo } from '../../composables/usePortalNavigation.js';
import { useToast } from '../../composables/useToast.js';
import { portalBrand, portalNavItems } from '../../portalNavigation.js';
import { useCatalogStore } from '../../stores/catalogStore.js';

const props = defineProps({
    activeRoute: { type: String, required: true },
    items: { type: Array, default: () => portalNavItems },
    ctaLabel: { type: String, default: 'Nueva Cotización' },
    ctaHref: { type: String, default: '/cotizaciones/nueva' },
    ctaIcon: { type: String, default: 'add_circle' },
    supportHref: { type: String, default: '/panel' },
    logoutHref: { type: String, default: '/login' },
});

const navItems = computed(() => props.items.map((item) => ({ ...item, active: item.href === props.activeRoute })));
const { logout } = useAuth();
const { askConfirm } = useConfirm();
const { openSupportModal: openPortalSupportModal } = usePortalActions();
const { success } = useToast();

const openQuoteModal = () => {
    navigateTo(props.ctaHref);
};

const handleNavigate = (href) => {
    navigateTo(href);
};

const openSupportModal = () => {
    openPortalSupportModal({
        title: 'Soporte B2B',
        reference: 'Menú lateral',
        reason: 'Consulta general del portal',
        whatsappMessage: 'Hola, necesito soporte con el Portal B2B de Inversiones S&M.',
    });
};

const handleLogout = () => {
    askConfirm({
        title: 'Cerrar sesión',
        message: 'Se cerrará la sesión temporal de este navegador.',
        confirmText: 'Cerrar sesión',
        cancelText: 'Cancelar',
        tone: 'danger',
        onConfirm: () => {
            logout();
            useCatalogStore().clearCatalogCache();
            success('Sesión temporal cerrada correctamente.');
            navigateTo(props.logoutHref);
        },
    });
};
</script>

<template>
    <aside class="portal-sidebar">
        <div class="portal-sidebar-brand"><h2>{{ portalBrand.title }}</h2><p>{{ portalBrand.subtitle }}</p></div>
        <nav><a v-for="item in navItems" :key="item.label" :class="{ active: item.active }" :href="item.href" @click.prevent="handleNavigate(item.href)"><span class="material-symbols-outlined" :class="{ filled: item.active }">{{ item.icon }}</span><span>{{ item.label }}</span></a></nav>
        <a class="portal-new-quote" :href="ctaHref" @click.prevent="openQuoteModal"><span class="material-symbols-outlined">{{ ctaIcon }}</span>{{ ctaLabel }}</a>
        <footer><a :href="supportHref" @click.prevent="openSupportModal"><span class="material-symbols-outlined">support_agent</span>Soporte</a><a :href="logoutHref" @click.prevent="handleLogout"><span class="material-symbols-outlined">logout</span>Cerrar Sesión</a></footer>
    </aside>
</template>

<style scoped>
.portal-sidebar{position:fixed;left:0;top:0;z-index:40;width:288px;height:100vh;display:flex;flex-direction:column;padding:32px 16px;background:rgba(17,24,40,.8);backdrop-filter:blur(40px);border-right:1px solid rgba(255,255,255,.05);box-shadow:0 25px 50px rgba(10,14,26,.5)}.portal-sidebar-brand{padding:0 16px;margin-bottom:40px}.portal-sidebar-brand h2{margin:0;color:#7dd3fc;font-size:20px;font-weight:900;text-shadow:0 0 10px rgba(125,211,252,.3)}.portal-sidebar-brand p{margin:8px 0 0;color:rgba(160,180,196,.7);font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase}.portal-sidebar nav{flex:1;overflow-y:auto;padding-right:2px}.portal-sidebar nav a,.portal-sidebar footer a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:rgba(160,180,196,.7);border-radius:8px;text-decoration:none}.portal-sidebar nav a.active{border-left:4px solid #7dd3fc;border-radius:0 8px 8px 0;background:rgba(14,77,110,.2);color:#7dd3fc;box-shadow:0 0 15px rgba(125,211,252,.1)}.portal-new-quote{display:flex;align-items:center;justify-content:center;gap:8px;margin:0 8px 16px;padding:14px;border:1px solid rgba(125,211,252,.3);border-radius:12px;background:linear-gradient(135deg,rgba(125,211,252,.2),rgba(125,211,252,.05));color:#7dd3fc;font-weight:800;text-decoration:none}.portal-sidebar footer{padding-top:16px;border-top:1px solid rgba(255,255,255,.05)}:global(.portal-light) .portal-sidebar{top:0;bottom:0;height:100dvh;min-height:100dvh;background:linear-gradient(135deg,rgba(255,255,255,.86),rgba(245,251,255,.68) 48%,rgba(233,246,253,.56));border-right-color:var(--portal-glass-border);box-shadow:var(--portal-shadow-glass)}:global(.portal-light) .portal-sidebar-brand h2{color:var(--portal-primary-strong);text-shadow:0 0 18px rgba(4,120,168,.14)}:global(.portal-light) .portal-sidebar-brand p,:global(.portal-light) .portal-sidebar nav a,:global(.portal-light) .portal-sidebar footer a{color:rgba(16,32,51,.68)}:global(.portal-light) .portal-sidebar nav{flex:0 0 auto}:global(.portal-light) .portal-new-quote{margin-top:auto}:global(.portal-light) .portal-sidebar nav a.active{background:linear-gradient(135deg,rgba(4,120,168,.12),rgba(255,255,255,.58));color:var(--portal-primary-strong);box-shadow:0 10px 28px rgba(4,120,168,.1),inset 0 1px 0 rgba(255,255,255,.9)}:global(.portal-light) .portal-new-quote{background:linear-gradient(135deg,rgba(4,120,168,.12),rgba(255,255,255,.6));border-color:rgba(4,120,168,.26);color:var(--portal-primary-strong);box-shadow:0 14px 34px rgba(4,120,168,.1),inset 0 1px 0 rgba(255,255,255,.92)}
:global(.portal-light) .portal-sidebar{position:fixed!important;inset:0 auto 0 0!important;height:100vh!important;min-height:100vh!important;max-height:100vh!important}
:global(.portal-light) .portal-sidebar nav{flex:1 1 auto!important;min-height:0!important;overflow-y:auto!important}
:global(.portal-light) .portal-new-quote{margin-top:0!important;background:linear-gradient(135deg,#2563eb 0%,#3b82f6 42%,#8b5cf6 100%)!important;color:#fff!important;border-color:rgba(37,99,235,.28)!important;box-shadow:0 18px 46px rgba(37,99,235,.22),0 8px 24px rgba(139,92,246,.14),inset 0 1px 0 rgba(255,255,255,.34)!important}
:global(.portal-light) .portal-sidebar footer{margin-top:0!important;flex-shrink:0!important}
</style>
