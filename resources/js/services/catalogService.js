import { mobileCatalogProducts } from '../data/mockCatalog.js';
import { useCatalogStore } from '../stores/catalogStore.js';

const catalogStore = useCatalogStore();

export const catalogService = catalogStore;

export const getCatalogProducts = () => catalogStore.listProducts();
export const getMobileCatalogProducts = () => mobileCatalogProducts;
