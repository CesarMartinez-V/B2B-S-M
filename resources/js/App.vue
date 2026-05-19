<script setup>
import { computed } from 'vue';
import AppModal from './components/ui/AppModal.vue';
import AppToast from './components/ui/AppToast.vue';
import ConfirmDialog from './components/ui/ConfirmDialog.vue';
import { useAuth } from './composables/useAuth.js';
import { useTheme } from './composables/useTheme.js';
import { PalabrasWeb } from './PalabrasWeb.js';
import BrandsPage from './pages/BrandsPage.vue';
import CatalogPage from './pages/CatalogPage.vue';
import AccountStatusPage from './pages/AccountStatusPage.vue';
import DashboardPage from './pages/DashboardPage.vue';
import LoginPage from './pages/LoginPage.vue';
import InvoiceHistoryPage from './pages/InvoiceHistoryPage.vue';
import OrdersPage from './pages/OrdersPage.vue';
import PortalPage from './pages/PortalPage.vue';
import ProfilePage from './pages/ProfilePage.vue';
import QuotesPage from './pages/QuotesPage.vue';

const path = window.location.pathname.replace(/\/$/, '') || '/';
const { isAuthenticated } = useAuth();
useTheme().initTheme();

const publicPages = {
    '/login': LoginPage,
    '/panel': DashboardPage,
    '/catalogo': CatalogPage,
    '/marcas': BrandsPage,
    '/pedidos': OrdersPage,
    '/estado-de-cuenta': AccountStatusPage,
    '/cotizaciones': QuotesPage,
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
    '/perfil': 'dashboard',
    '/historial-facturas': 'accountStatus',
};

const currentPublicPage = computed(() => publicPages[path]);
const currentPortalKey = computed(() => portalPages[path] || 'dashboard');
const currentPortalPage = computed(() => PalabrasWeb.pages[currentPortalKey.value]);
const isBlocked = computed(() => path !== '/login' && !isAuthenticated.value);

if (isBlocked.value) {
    window.location.replace('/login');
}
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
