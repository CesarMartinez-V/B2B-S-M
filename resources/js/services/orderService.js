import { desktopItems, desktopSteps, mobileEvents, mobileItems } from '../data/mockOrders.js';
import { toLempiraLabel } from '../utils/currency.js';
import { createDataAdapter } from './dataAdapter.js';

const normalizeItems = (items) => items.map((item) => ({ ...item, price: toLempiraLabel(item.price) }));
const fallbackOrder = { desktopSteps, desktopItems, mobileEvents, mobileItems };

const normalizeTimeline = (timeline = []) => {
    const currentIndex = Math.max(0, timeline.findLastIndex((step) => step.done));

    return timeline.map((step, index) => ({
        label: step.label,
        meta: step.date || (step.done ? 'Completado' : 'Pendiente'),
        done: Boolean(step.done),
        active: Boolean(step.done) && index === currentIndex,
        muted: !step.done,
    }));
};

const normalizeOrder = (order = {}) => {
    const items = normalizeItems(order.items ?? order.desktopItems ?? desktopItems);
    const steps = order.timeline ? normalizeTimeline(order.timeline) : (order.steps ?? order.desktopSteps ?? desktopSteps);

    return {
        ...order,
        id: order.id ?? order.number ?? '',
        number: order.number ?? order.id ?? '',
        status: order.status ?? 'Facturación',
        date: order.date ?? '',
        estimatedDate: order.estimatedDate ?? order.estimated_date ?? null,
        total: order.total ?? 'L. 0.00',
        totalValue: Number(order.totalValue ?? 0),
        destination: order.destination ?? { name: '', address: '' },
        tracking: order.tracking ?? { number: null, guideUrlAvailable: false, latitude: null, longitude: null },
        documents: order.documents ?? [],
        desktopSteps: steps,
        desktopItems: items,
        mobileEvents: order.mobile_events ?? order.mobileEvents ?? steps.map((step) => ({ title: step.label, place: step.meta, active: step.active })),
        mobileItems: order.mobile_items ?? order.mobileItems ?? items.map((item) => ({ icon: 'minor_crash', name: item.name, meta: `SKU: ${item.sku} - Cant: ${item.qty}`, price: item.price })),
    };
};

const orderResource = createDataAdapter({
    mock: () => ({ orders: [], active_order: fallbackOrder, desktopSteps, desktopItems, mobileEvents, mobileItems }),
    endpoint: '/api/portal/orders',
    extract: (response) => response?.data ?? {},
    normalize: (payload) => {
        const orders = Array.isArray(payload.orders) ? payload.orders.map(normalizeOrder) : [];
        const activeOrder = payload.active_order ? normalizeOrder(payload.active_order) : (orders[0] ?? null);

        return {
            orders,
            active_order: activeOrder,
            meta: payload._meta ?? {},
            source: payload._source ?? 'mock',
            desktopSteps: activeOrder?.desktopSteps ?? desktopSteps,
            desktopItems: activeOrder?.desktopItems ?? desktopItems,
            mobileEvents: activeOrder?.mobileEvents ?? mobileEvents,
            mobileItems: activeOrder?.mobileItems ?? mobileItems,
        };
    },
});

export const getDesktopOrderSteps = () => orderResource.list().desktopSteps;
export const getDesktopOrderItems = () => orderResource.list().desktopItems;
export const getMobileOrderEvents = () => orderResource.list().mobileEvents;
export const getMobileOrderItems = () => orderResource.list().mobileItems;
export const fetchOrders = (params = {}) => orderResource.fetch(params);
export const orderState = orderResource.state;
