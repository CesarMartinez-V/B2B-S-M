import { computed, onMounted, onUnmounted, ref } from 'vue';

const internalPaths = new Set([
    '/panel',
    '/catalogo',
    '/marcas',
    '/pedidos',
    '/estado-de-cuenta',
    '/historial-facturas',
    '/cotizaciones',
    '/cotizaciones/nueva',
    '/quotes/create',
    '/perfil',
]);

const currentPath = ref(normalizePath(window.location.pathname));

export function normalizePath(path = '/') {
    const url = new URL(path, window.location.origin);
    return url.pathname.replace(/\/$/, '') || '/';
}

export function isInternalPortalPath(path = '') {
    return internalPaths.has(normalizePath(path));
}

export function navigateTo(path, { replace = false } = {}) {
    const nextPath = normalizePath(path);

    if (!isInternalPortalPath(nextPath) && nextPath !== '/login') {
        window.location.href = path;
        return;
    }

    if (nextPath === currentPath.value) return;

    window.history[replace ? 'replaceState' : 'pushState']({}, '', nextPath);
    currentPath.value = nextPath;
    window.dispatchEvent(new CustomEvent('portal:navigated', { detail: { path: nextPath } }));
}

export const usePortalNavigation = () => {
    const handlePopState = () => {
        currentPath.value = normalizePath(window.location.pathname);
    };

    onMounted(() => window.addEventListener('popstate', handlePopState));
    onUnmounted(() => window.removeEventListener('popstate', handlePopState));

    return {
        currentPath: computed(() => currentPath.value),
        navigateTo,
        isInternalPortalPath,
        normalizePath,
    };
};
