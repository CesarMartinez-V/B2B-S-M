import { computed, ref } from 'vue';
import { apiClient } from '../services/apiClient.js';
import { useAccountStore } from '../stores/accountStore.js';
import { useCatalogStore } from '../stores/catalogStore.js';
import { useDashboardStore } from '../stores/dashboardStore.js';
import { useInvoiceStore } from '../stores/invoiceStore.js';
import { useOrderStore } from '../stores/orderStore.js';
import { usePortalPrefetchStore } from '../stores/portalPrefetchStore.js';
import { useProfileStore } from '../stores/profileStore.js';
import { useQuoteStore } from '../stores/quoteStore.js';
import { useQuoteCartStore } from '../quoteCartStore.js';

const AUTH_KEY = 'portal-auth-session';

const readSession = () => {
    try {
        if (typeof window === 'undefined') return null;
        return JSON.parse(window.sessionStorage.getItem(AUTH_KEY) || 'null');
    } catch {
        return null;
    }
};

const writeSession = (value) => {
    try {
        if (typeof window === 'undefined') return;

        if (!value) {
            window.sessionStorage.removeItem(AUTH_KEY);
            return;
        }

        window.sessionStorage.setItem(AUTH_KEY, JSON.stringify(value));
    } catch {
        // Session storage can fail in private mode; keep runtime state available.
    }
};

const removeClientStorage = () => {
    try {
        window.localStorage.removeItem('sm_quotes_temp');
        window.sessionStorage.removeItem('portal.catalog.cache.v1');
        window.sessionStorage.removeItem('portal_catalog_brand');
    } catch {
        // Ignore storage cleanup failures.
    }
};

export const clearPortalClientData = () => {
    useAccountStore().clearAccountCache?.();
    useCatalogStore().clearCatalogCache?.();
    useDashboardStore().clearDashboardCache?.();
    useInvoiceStore().clearInvoiceCache?.();
    useOrderStore().clearOrderCache?.();
    useProfileStore().clearProfileCache?.();
    useQuoteStore().clearQuoteCache?.();
    usePortalPrefetchStore().reset?.();
    useQuoteCartStore().clear?.();
    removeClientStorage();
};

const session = ref(readSession());

if (typeof window !== 'undefined') {
    window.addEventListener('portal:session-expired', () => {
        session.value = null;
        writeSession(null);
        clearPortalClientData();
    });
}

export const useAuth = () => {
    const isAuthenticated = computed(() => {
        if (!session.value?.token) return false;
        if (!session.value?.expiresAt) return true;

        return Date.parse(session.value.expiresAt) > Date.now();
    });

    const login = async ({ identity }) => {
        const previousClientCode = session.value?.client?.code || '';
        const response = await apiClient.post('/api/portal/auth/identity', { identity });

        if (!response?.data?.authenticated || !response.data.token) {
            return { authenticated: false };
        }

        const nextSession = {
            token: response.data.token,
            client: {
                name: response.data.client?.name || 'Cliente B2B',
                code: response.data.client?.code || '',
            },
            createdAt: new Date().toISOString(),
            expiresAt: response.data.expiresAt || '',
        };

        if (previousClientCode && previousClientCode !== nextSession.client.code) {
            clearPortalClientData();
        } else if (!previousClientCode) {
            clearPortalClientData();
        }

        session.value = nextSession;
        writeSession(nextSession);

        return { authenticated: true, session: nextSession };
    };

    const logout = () => {
        session.value = null;
        writeSession(null);
        clearPortalClientData();
    };

    return {
        session,
        isAuthenticated,
        login,
        logout,
    };
};
