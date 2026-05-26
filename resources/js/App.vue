<script setup>
import { computed, watch } from 'vue';
import AppModal from './components/ui/AppModal.vue';
import AppToast from './components/ui/AppToast.vue';
import ConfirmDialog from './components/ui/ConfirmDialog.vue';
import { useAuth } from './composables/useAuth.js';
import { navigateTo, usePortalNavigation } from './composables/usePortalNavigation.js';
import { useTheme } from './composables/useTheme.js';
import { PalabrasWeb } from './PalabrasWeb.js';
import { usePortalPrefetchStore } from './stores/portalPrefetchStore.js';
import BrandsPage from './pages/BrandsPage.vue';
import CatalogPage from './pages/CatalogPage.vue';
import AccountStatusPage from './pages/AccountStatusPage.vue';
import DashboardPage from './pages/DashboardPage.vue';
import LoginPage from './pages/LoginPage.vue';
import InvoiceHistoryPage from './pages/InvoiceHistoryPage.vue';
import OrdersPage from './pages/OrdersPage.vue';
import PortalPage from './pages/PortalPage.vue';
import ProfilePage from './pages/ProfilePage.vue';
import NewQuotePage from './pages/NewQuotePage.vue';
import QuotesPage from './pages/QuotesPage.vue';

const { currentPath } = usePortalNavigation();
const { isAuthenticated } = useAuth();
useTheme().initTheme();
const portalPrefetch = usePortalPrefetchStore();

const publicPages = {
    '/login': LoginPage,
    '/panel': DashboardPage,
    '/catalogo': CatalogPage,
    '/marcas': BrandsPage,
    '/pedidos': OrdersPage,
    '/estado-de-cuenta': AccountStatusPage,
    '/cotizaciones': QuotesPage,
    '/cotizaciones/nueva': NewQuotePage,
    '/quotes/create': NewQuotePage,
    '/perfil': ProfilePage,
    '/historial-facturas': InvoiceHistoryPage,
};

const portalPages = {
    '/panel': 'dashboard',
    '/catalogo': 'catalog',
    '/marcas': 'brands',
    '/pedidos': 'orders',
    '/estado-de-cuenta': 'accountStatus',
    '/cotizaciones': 'quotes',
    '/cotizaciones/nueva': 'quotes',
    '/quotes/create': 'quotes',
    '/perfil': 'dashboard',
    '/historial-facturas': 'accountStatus',
};

const currentPublicPage = computed(() => publicPages[currentPath.value]);
const currentPortalKey = computed(() => portalPages[currentPath.value] || 'dashboard');
const currentPortalPage = computed(() => PalabrasWeb.pages[currentPortalKey.value]);
const isBlocked = computed(() => currentPath.value !== '/login' && !isAuthenticated.value);

watch([currentPath, isAuthenticated], ([pathValue, authenticated]) => {
    if (pathValue !== '/login' && !authenticated) {
        navigateTo('/login', { replace: true });
        return;
    }

    if (pathValue !== '/login' && authenticated) {
        portalPrefetch.run({ priorityPath: pathValue });
    }
}, { immediate: true });
</script>

<template>
    <template v-if="!isBlocked">
        <component :is="currentPublicPage" v-if="currentPublicPage" />
        <PortalPage v-else :page-key="currentPortalKey" :page="currentPortalPage" />
    </template>
    <AppToast />
    <AppModal />
    <ConfirmDialog />
</template>
