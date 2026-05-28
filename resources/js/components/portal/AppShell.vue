<script setup>
import { portalUser } from '../../portalNavigation.js';
import MobileTopBar from './MobileTopBar.vue';
import PortalBottomNav from './PortalBottomNav.vue';
import PortalSideBar from './PortalSideBar.vue';
import PortalTopBar from './PortalTopBar.vue';

defineProps({
    activeRoute: { type: String, required: true },
    desktopSearchPlaceholder: { type: String, default: 'Buscar socio, factura o pedido...' },
    showDesktopSearch: { type: Boolean, default: true },
    mobileTitle: { type: String, default: 'Inversiones S&M' },
    showMobileAvatar: { type: Boolean, default: true },
    showBottomNav: { type: Boolean, default: true },
    avatarSrc: { type: String, default: portalUser.avatar },
    mobileAvatarSrc: { type: String, default: '' },
    userName: { type: String, default: portalUser.name },
    userHref: { type: String, default: portalUser.href },
});
</script>

<template>
    <div class="app-shell">
        <section class="app-shell-desktop">
            <PortalSideBar :active-route="activeRoute" />
            <main class="app-shell-desktop-main">
                <PortalTopBar :search-placeholder="desktopSearchPlaceholder" :show-search="showDesktopSearch" :avatar-src="avatarSrc" :user-name="userName" :user-href="userHref" />
                <slot name="desktop" />
            </main>
        </section>
        <section class="app-shell-mobile">
            <MobileTopBar :title="mobileTitle" :show-avatar="showMobileAvatar" :avatar-src="mobileAvatarSrc || avatarSrc" :avatar-href="userHref" />
            <slot name="mobile" />
            <PortalBottomNav v-if="showBottomNav" :active-route="activeRoute" />
        </section>
    </div>
</template>

<style scoped>
.app-shell{min-height:100vh;background:var(--portal-bg);color:var(--portal-text);font-family:Inter,sans-serif;overflow-x:hidden;transition:background var(--portal-transition),color var(--portal-transition)}.app-shell-mobile{display:none}.app-shell-desktop-main{min-height:100vh;margin-left:288px;overflow-x:hidden}:global(.shell-content){width:100%!important;max-width:none!important;margin-left:0!important;margin-right:0!important;padding:clamp(16px,1.25vw,24px) clamp(16px,1.5vw,28px) 32px!important}@media(max-width:1200px){:global(.shell-content){padding:18px!important}}@media(max-width:767px){.app-shell-desktop{display:none}.app-shell-mobile{display:block;min-height:100vh;padding-bottom:112px}}

.app-shell :global(.glass),
.app-shell :global(.glass-card),
.app-shell :global(.glass-panel),
.app-shell :global(.elevated),
.app-shell :global(.elevated-card),
.app-shell :global(.glass-elevated),
.app-shell :global(.liquid-panel),
.app-shell :global(.summary-card) {
    position: relative;
    overflow: hidden;
    border-color: var(--portal-glass-border) !important;
    background:
        var(--portal-glass-bg),
        radial-gradient(circle at 18% 0%, color-mix(in srgb, var(--portal-primary) 22%, transparent), transparent 34%),
        radial-gradient(circle at 88% 12%, color-mix(in srgb, var(--portal-tertiary) 18%, transparent), transparent 30%) !important;
    box-shadow: var(--portal-shadow-glass) !important;
    backdrop-filter: blur(24px) saturate(155%) !important;
    -webkit-backdrop-filter: blur(24px) saturate(155%) !important;
    transition: transform 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease, background 0.22s ease;
}

.app-shell :global(.glass:hover),
.app-shell :global(.glass-card:hover),
.app-shell :global(.glass-panel:hover),
.app-shell :global(.elevated:hover),
.app-shell :global(.elevated-card:hover),
.app-shell :global(.glass-elevated:hover),
.app-shell :global(.liquid-panel:hover),
.app-shell :global(.summary-card:hover) {
    border-color: var(--portal-glass-border-strong) !important;
    box-shadow: var(--portal-shadow-hover) !important;
}

.app-shell :global(.shell-content > *),
.app-shell :global(.wrap > *),
.app-shell :global(.canvas > *),
.app-shell :global(.desktop-content > *),
.app-shell :global(.invoice-main > *),
.app-shell :global(.portal-enter),
.app-shell :global(.product-card),
.app-shell :global(.brand-card),
.app-shell :global(.stat-card),
.app-shell :global(.metric),
.app-shell :global(.invoice-card),
.app-shell :global(.kpi-card),
.app-shell :global(.chart-card),
.app-shell :global(.activity-card),
.app-shell :global(.quick-card) {
    animation: portal-liquid-enter 0.5s ease both;
}

.app-shell :global(.shell-content > *:nth-child(2)),
.app-shell :global(.wrap > *:nth-child(2)),
.app-shell :global(.canvas > *:nth-child(2)),
.app-shell :global(.desktop-content > *:nth-child(2)),
.app-shell :global(.invoice-main > *:nth-child(2)) {
    animation-delay: 0.08s;
}

.app-shell :global(.shell-content > *:nth-child(3)),
.app-shell :global(.wrap > *:nth-child(3)),
.app-shell :global(.canvas > *:nth-child(3)),
.app-shell :global(.desktop-content > *:nth-child(3)),
.app-shell :global(.invoice-main > *:nth-child(3)) {
    animation-delay: 0.14s;
}

.app-shell :global(.apply-filter),
.app-shell :global(.export-button),
.app-shell :global(.pay),
.app-shell :global(.desktop-submit),
.app-shell :global(.actions button:last-child) {
    animation: portal-liquid-pulse 3.2s ease-in-out infinite;
    box-shadow: 0 0 26px rgba(125, 211, 252, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.22) !important;
}

@keyframes portal-liquid-enter {
    from {
        opacity: 0;
        transform: translateY(14px) scale(0.992);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes portal-liquid-pulse {
    0%,
    100% {
        filter: drop-shadow(0 0 0 rgba(125, 211, 252, 0));
    }

    50% {
        filter: drop-shadow(0 0 12px rgba(125, 211, 252, 0.28));
    }
}

@media (prefers-reduced-motion: reduce) {
    .app-shell :global(*) {
        animation-duration: 0.001ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.001ms !important;
    }
}

</style>
