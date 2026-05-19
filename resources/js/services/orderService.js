import { desktopItems, desktopSteps, mobileEvents, mobileItems } from '../data/mockOrders.js';
import { toLempiraLabel } from '../utils/currency.js';
import { createDataAdapter } from './dataAdapter.js';

const normalizeItems = (items) => items.map((item) => ({ ...item, price: toLempiraLabel(item.price) }));

const orderResource = createDataAdapter({
    mock: () => ({ desktopSteps, desktopItems, mobileEvents, mobileItems }),
    endpoint: '/api/portal/orders',
    extract: (response) => response?.data?.active_order ?? {},
    normalize: (order) => ({
        desktopSteps: order.steps ?? order.desktopSteps ?? desktopSteps,
        desktopItems: normalizeItems(order.items ?? order.desktopItems ?? desktopItems),
        mobileEvents: order.mobile_events ?? order.mobileEvents ?? mobileEvents,
        mobileItems: normalizeItems(order.mobile_items ?? order.mobileItems ?? mobileItems),
    }),
});

export const getDesktopOrderSteps = () => orderResource.list().desktopSteps;
export const getDesktopOrderItems = () => orderResource.list().desktopItems;
export const getMobileOrderEvents = () => orderResource.list().mobileEvents;
export const getMobileOrderItems = () => orderResource.list().mobileItems;
export const fetchOrders = (params = {}) => orderResource.fetch(params);
export const orderState = orderResource.state;
