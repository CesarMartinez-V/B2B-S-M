import { useModal } from './useModal.js';
import { navigateTo } from './usePortalNavigation.js';
import { useToast } from './useToast.js';
import { usePdfExport } from './usePdfExport.js';
import { useWhatsAppContact } from './useWhatsAppContact.js';

export const usePortalActions = () => {
    const { openModal } = useModal();
    const { success, info } = useToast();
    const { printDocument } = usePdfExport();
    const { openWhatsApp } = useWhatsAppContact();

    const resolveMessage = (context = {}, fallback = 'Hola, necesito soporte con el Portal B2B de Inversiones S&M.') => context.whatsappMessage || context.message || fallback;

    const openSupportModal = (context = {}) => {
        const message = resolveMessage(context, 'Hola, necesito soporte con el Portal B2B de Inversiones S&M.');

        openModal({
            title: context.title || 'Soporte B2B',
            message: `Se abrirá WhatsApp para contactar al equipo B2B.${context.reference ? `\nReferencia: ${context.reference}` : ''}${context.reason ? `\nMotivo: ${context.reason}` : ''}`,
            icon: 'support_agent',
            confirmText: 'Enviar por WhatsApp',
            cancelText: 'Cerrar',
            size: 'md',
            detail: {
                rows: [
                    { label: 'Acción', value: 'Contacto por WhatsApp' },
                    { label: 'ERP', value: 'No modifica Fastevo' },
                ],
                observations: message,
            },
            onConfirm: () => {
                openWhatsApp(message);
                success('Solicitud abierta en WhatsApp.');
            },
        });
    };

    const openContactSeller = (context = {}) => {
        const fallback = context.document
            ? `Hola, necesito apoyo con el documento ${context.document} en el Portal B2B.`
            : 'Hola, necesito apoyo con mi estado de cuenta en el Portal B2B.';
        const message = resolveMessage(context, fallback);

        openModal({
            title: context.title || 'Contactar con vendedor',
            message: `Se abrirá WhatsApp para contactar al asesor comercial.${context.document ? `\nDocumento: ${context.document}` : ''}${context.reason ? `\nMotivo: ${context.reason}` : ''}`,
            icon: 'support_agent',
            confirmText: 'Enviar por WhatsApp',
            cancelText: 'Cerrar',
            size: 'md',
            detail: {
                rows: [
                    { label: 'Seguimiento', value: 'WhatsApp comercial' },
                    { label: 'ERP', value: 'Sin escritura real' },
                ],
                observations: message,
            },
            onConfirm: () => {
                openWhatsApp(message);
                success('Solicitud abierta en WhatsApp.');
            },
        });
    };

    const openDocumentUnavailableModal = (context = {}) => openModal({
        title: context.title || 'Documento no disponible',
        message: `Para descargar este documento se necesita un endpoint seguro de documentos en Fastevo.${context.document ? `\nDocumento: ${context.document}` : ''}\nNo se exponen enlaces internos ni rutas administrativas desde el portal.`,
        icon: 'description',
        confirmText: 'Entendido',
        size: 'md',
        detail: {
            rows: [
                { label: 'Estado', value: 'Integración pendiente' },
                { label: 'Seguridad', value: 'Sin enlaces internos expuestos' },
            ],
            observations: 'Cuando Fastevo habilite un endpoint firmado de documentos, esta acción podrá descargar o previsualizar el archivo sin exponer rutas administrativas.',
        },
    });

    const explainPendingIntegration = (action = 'Esta acción') => openModal({
        title: 'Integración ERP pendiente',
        message: `${action} requiere integración final aprobada con ERP. En esta fase el portal solo ejecuta consultas read-only o acciones locales temporales.`,
        icon: 'engineering',
        confirmText: 'Entendido',
        size: 'md',
        detail: {
            rows: [
                { label: 'Acción solicitada', value: action },
                { label: 'Cambios reales', value: 'No ejecutados' },
            ],
            observations: 'No se crean documentos, no se modifica inventario y no se escriben datos administrativos hasta aprobar el contrato backend correspondiente.',
        },
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
