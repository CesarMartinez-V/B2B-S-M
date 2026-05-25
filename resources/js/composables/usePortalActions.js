import { useModal } from './useModal.js';
import { navigateTo } from './usePortalNavigation.js';
import { useToast } from './useToast.js';
import { usePdfExport } from './usePdfExport.js';

export const usePortalActions = () => {
    const { openModal } = useModal();
    const { success, info } = useToast();
    const { printDocument } = usePdfExport();

    const openSupportModal = (context = {}) => openModal({
        title: context.title || 'Soporte B2B',
        message: `Se preparará una consulta segura para el equipo B2B.${context.reference ? `\nReferencia: ${context.reference}` : ''}${context.reason ? `\nMotivo: ${context.reason}` : ''}`,
        icon: 'support_agent',
        confirmText: 'Registrar solicitud',
        cancelText: 'Cerrar',
        onConfirm: () => success('Solicitud de soporte registrada localmente.'),
    });

    const openContactSeller = (context = {}) => openModal({
        title: context.title || 'Contactar con vendedor',
        message: `Se preparará una solicitud para el vendedor asignado.${context.document ? `\nDocumento: ${context.document}` : ''}${context.client ? `\nCliente: ${context.client}` : ''}${context.reason ? `\nMotivo: ${context.reason}` : ''}`,
        icon: 'support_agent',
        confirmText: 'Registrar solicitud',
        cancelText: 'Cerrar',
        onConfirm: () => success('Solicitud registrada para seguimiento comercial.'),
    });

    const openDocumentUnavailableModal = (context = {}) => openModal({
        title: context.title || 'Documento no disponible',
        message: `Para descargar este documento se necesita un endpoint seguro de documentos en Fastevo.${context.document ? `\nDocumento: ${context.document}` : ''}\nNo se exponen enlaces internos ni rutas administrativas desde el portal.`,
        icon: 'description',
        confirmText: 'Entendido',
    });

    const explainPendingIntegration = (action = 'Esta acción') => openModal({
        title: 'Integración ERP pendiente',
        message: `${action} requiere integración final aprobada con ERP. En esta fase el portal solo ejecuta consultas read-only o acciones locales temporales.`,
        icon: 'engineering',
        confirmText: 'Entendido',
    });

    const goToCatalog = (filters = {}) => {
        if (filters.brand && typeof window !== 'undefined') {
            window.sessionStorage.setItem('portal_catalog_brand', filters.brand);
        }

        navigateTo('/catalogo');
    };

    const goToQuotes = () => navigateTo('/cotizaciones');
    const goToInvoices = () => navigateTo('/historial-facturas');
    const goToOrders = () => navigateTo('/pedidos');

    const printCurrentView = (title, lines = []) => {
        if (printDocument({ title, sections: [{ heading: 'Resumen visible', lines }] })) {
            success(`${title} listo para imprimir o guardar como PDF.`);
            return true;
        }

        info('El navegador bloqueó la ventana de impresión. Permite ventanas emergentes para exportar.');
        return false;
    };

    return {
        openSupportModal,
        openContactSeller,
        openDocumentUnavailableModal,
        goToCatalog,
        goToQuotes,
        goToInvoices,
        goToOrders,
        printCurrentView,
        explainPendingIntegration,
    };
};
