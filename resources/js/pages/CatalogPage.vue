<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AppShell from '../components/portal/AppShell.vue';
import { catalogService } from '../services/catalogService.js';
import { useToast } from '../composables/useToast.js';
import { formatCurrency } from '../utils/currency.js';
import { useQuoteCartStore } from '../quoteCartStore.js';

const avatarDesktop = 'https://lh3.googleusercontent.com/aida-public/AB6AXuCqtzrGqbLwoOkDv6rIRhhzGQegDQEkZQVB_scjsatOaRSt3HS9TwfGlDg5812wowf2ZOhl7KfJl3DELj7cArl-1Jrgg-DPohdjF3MUGfwDf6yfXtO7CcYko5jgtIo7l0xZ9pHja_USc2_r8T42kplSrk_8Ym24zNL8Uf3Q0Eb3u6aAOLpsrkxoxqg2Eos2w6ysa-jXV1D8rfKqQ0RHlXmZ11rmSO8uWKWK6T9tyd1gWVNwjBR8OojM1XKfRKfp_jLfiAt2TeTm4zo';
const avatarMobile = 'https://lh3.googleusercontent.com/aida-public/AB6AXuAVTBkgtu_s8_BVOjULd6oYvYpnGC3Udgs7_F_feBcgVnv79wy-jap571z2gVILrvf1oEQnOmyilHftlnr-vhwVEGFi0-_7HmxdCJeMDt4RLaYt2VvyZvxgEO_VZbFFVbfl7XfZotLJjNZpb_CHBllPGerV__oMe-ZfdPy_c98RfxRLMERYRkFlZf-rGPARqOw8OT6qJHQo0XNKyWWkFxuW2k5G9cSkUbzZQQ-fJticSGfj4CVzO0-lmOGMhKeZWDD1HVNq1sIri2M';

const allProducts = catalogService.listProducts();
const sortByCount = (items, key) => Object.entries(items.reduce((map, item) => {
    const value = item[key] || 'General';
    map[value] = (map[value] || 0) + 1;
    return map;
}, {})).sort((a, b) => b[1] - a[1]).map(([value]) => value);
const categories = computed(() => catalogState.filters.categories?.length ? catalogState.filters.categories : sortByCount(allProducts, 'category'));
const brands = computed(() => catalogState.filters.brands?.length ? catalogState.filters.brands : sortByCount(allProducts, 'brand'));
const priceValues = computed(() => allProducts.map((product) => Number(product.priceValue || 0)).filter((value) => value > 0));
const minPrice = computed(() => Math.floor(Number(catalogState.filters.min_price) || Math.min(...priceValues.value, 0)));
const maxPriceLimit = computed(() => Math.ceil(Number(catalogState.filters.max_price) || Math.max(...priceValues.value, 10000)));
const { success, info } = useToast();
const quoteCart = useQuoteCartStore();
const quoteItems = quoteCart.items;
const quoteCount = quoteCart.count;

const search = ref('');
const categorySearch = ref('');
const brandSearch = ref('');
const selectedCategories = ref([]);
const selectedBrands = ref([]);
const maxPrice = ref(10000);
const isPageChanging = ref(false);
const availability = ref('all');
const appliedFilters = ref({ search: '', categories: [], brands: [], maxPrice: 10000, availability: 'all' });
const viewMode = ref('grid');
const currentPage = ref(1);
const itemsPerPage = 8;
const showQuoteSummary = ref(false);
const catalogState = catalogService.state;
const lastSourceNotice = ref('');

const filteredProducts = computed(() => allProducts);
const pageCount = computed(() => Math.max(1, Number(catalogState.meta.last_page) || 1));
const paginatedProducts = computed(() => allProducts);
const visibleCategories = computed(() => {
    const term = categorySearch.value.trim().toLowerCase();
    return term === '' ? categories.value : categories.value.filter((category) => category.toLowerCase().includes(term));
});
const visibleBrands = computed(() => {
    const term = brandSearch.value.trim().toLowerCase();
    return term === '' ? brands.value : brands.value.filter((brand) => brand.toLowerCase().includes(term));
});
const totalProducts = computed(() => Number(catalogState.meta.total) || allProducts.length);
const displayStart = computed(() => totalProducts.value === 0 ? 0 : ((currentPage.value - 1) * itemsPerPage) + 1);
const displayEnd = computed(() => Math.min(totalProducts.value, displayStart.value + allProducts.length - 1));
const visiblePages = computed(() => {
    const total = pageCount.value;
    const current = currentPage.value;
    if (total <= 10) return Array.from({ length: total }, (_, index) => index + 1);

    const pages = new Set([1, 2, total - 1, total]);
    for (let page = current - 2; page <= current + 2; page += 1) {
        if (page > 0 && page <= total) pages.add(page);
    }

    return [...pages].sort((a, b) => a - b).reduce((result, page, index, source) => {
        if (index > 0 && page - source[index - 1] > 1) result.push('ellipsis');
        result.push(page);
        return result;
    }, []);
});
const subtotalLabel = computed(() => formatCurrency(quoteCart.subtotal.value));

const catalogParams = (page = currentPage.value) => ({
    page,
    per_page: itemsPerPage,
    include_filters: 0,
    search: search.value,
    category: selectedCategories.value.join(','),
    brand: selectedBrands.value.join(','),
    max_price: Number(maxPrice.value) < Number(maxPriceLimit.value) ? maxPrice.value : null,
    availability: availability.value === 'all' ? null : availability.value,
});

const loadCatalog = async (page = currentPage.value) => {
    currentPage.value = page;
    await catalogService.fetchProducts(catalogParams(page));
    currentPage.value = Number(catalogState.meta.current_page) || page;
};

onMounted(() => {
    void catalogService.fetchFilters();
    void loadCatalog(1);
});

watch(maxPriceLimit, (value) => {
    if (!value || maxPrice.value !== 10000) return;
    maxPrice.value = value;
    appliedFilters.value = { ...appliedFilters.value, maxPrice: value };
});

watch(() => catalogState.meta.source, (source) => {
    if (!source || lastSourceNotice.value === source) return;
    lastSourceNotice.value = source;

    if (source === 'external-cache') {
        info('Mostrando catálogo desde cache mientras se estabiliza la conexión ERP.', 'Catálogo en cache');
    }

    if (source === 'mock-fallback') {
        info('Mostrando catálogo temporal porque ERP no respondió.', 'Fallback activo');
    }
});

const applyFilters = () => {
    appliedFilters.value = {
        search: search.value,
        categories: [...selectedCategories.value],
        brands: [...selectedBrands.value],
        maxPrice: maxPrice.value,
        availability: availability.value,
    };
    void loadCatalog(1);
};

const toggleBrand = (brand) => {
    selectedBrands.value = selectedBrands.value.includes(brand)
        ? selectedBrands.value.filter((item) => item !== brand)
        : [...selectedBrands.value, brand];
};

const setMobileCategory = (category) => {
    selectedCategories.value = category ? [category] : [];
    applyFilters();
};

const addToQuote = (product) => {
    if (!quoteCart.addProduct(product)) {
        info(`${product.name} requiere confirmar disponibilidad antes de cotizar.`, 'Consultar disponibilidad');
        return;
    }

    success(`${product.name} fue agregado a la cotización temporal.`, 'Producto agregado');
};

const hasProductImage = (product) => product.image && !product.imageBroken;

const markImageError = (product) => {
    product.imageBroken = true;
};

const goToPage = async (page) => {
    if (page === 'ellipsis') return;
    const nextPage = Math.min(Math.max(page, 1), pageCount.value);
    if (nextPage === currentPage.value && catalogState.loaded) return;

    isPageChanging.value = true;
    await loadCatalog(nextPage);
    window.setTimeout(() => { isPageChanging.value = false; }, 180);
};

</script>

<template>
    <div class="catalog-page">
        <AppShell active-route="/catalogo" desktop-search-placeholder="Buscar socio, factura o pedido..." mobile-title="Inversiones S&amp;M" :avatar-src="avatarDesktop" :mobile-avatar-src="avatarMobile">
            <template #desktop>
            <main class="desktop-main shell-content">
                <div class="catalog-body">
                    <aside class="filter-panel glass-card">
                        <h2><span class="material-symbols-outlined">filter_list</span>Filtrar Catálogo</h2><p v-if="catalogState.filtersLoading || catalogState.filtersWarming" class="filter-warming">Actualizando filtros...</p>
                        <section><h3>Categoría</h3><label v-if="categories.length > 8" class="filter-search"><span class="material-symbols-outlined">search</span><input v-model="categorySearch" placeholder="Buscar categoría..." type="search"></label><div class="filter-scroll"><label v-for="category in visibleCategories" :key="category"><input v-model="selectedCategories" :value="category" type="checkbox">{{ category }}</label></div></section>
                        <section><h3>Rango de Precio</h3><input v-model="maxPrice" class="neon-slider" :max="maxPriceLimit" :min="minPrice" step="50" type="range"><div class="range-labels"><span>{{ formatCurrency(minPrice) }}</span><span>{{ formatCurrency(maxPrice) }}</span></div></section>
                        <section><h3>Marcas Premium</h3><label v-if="brands.length > 8" class="filter-search"><span class="material-symbols-outlined">search</span><input v-model="brandSearch" placeholder="Buscar marca..." type="search"></label><div class="brand-filter"><button v-for="brand in visibleBrands" :key="brand" :class="{ active: selectedBrands.includes(brand) }" type="button" @click="toggleBrand(brand)">{{ brand }}</button></div></section>
                        <section><h3>Disponibilidad</h3><div class="availability-filter"><button :class="{ active: availability === 'all' }" type="button" @click="availability = 'all'">Todos</button><button :class="{ active: availability === 'available' }" type="button" @click="availability = 'available'">Disponibles</button></div></section>
                        <section><h3>Especificaciones</h3><select><option>Tipo de Combustible</option><option>Configuración de Cilindros</option></select></section>
                        <button class="apply-filter" type="button" @click="applyFilters">Aplicar Filtros</button>
                    </aside>
                    <section class="products-area"><header><div><h2>Catálogo de Alta Ingeniería</h2><p v-if="catalogState.loading">Cargando catálogo ERP...</p><p v-else-if="catalogState.error">Mostrando fallback local por error de conexión ERP.</p><p v-else>Mostrando {{ displayStart }}-{{ displayEnd }} de {{ totalProducts.toLocaleString('en-US') }} productos</p></div><label class="catalog-search"><span class="material-symbols-outlined">search</span><input v-model="search" placeholder="Buscar producto, marca o ID..." type="search" @keyup.enter="applyFilters"></label><div class="view-toggle"><button :class="{ active: viewMode === 'grid' }" type="button" @click="viewMode = 'grid'"><span class="material-symbols-outlined">grid_view</span></button><button :class="{ active: viewMode === 'list' }" type="button" @click="viewMode = 'list'"><span class="material-symbols-outlined">list</span></button></div></header><div :class="['product-grid',{ 'list-view': viewMode === 'list', 'is-changing': isPageChanging }]">
                        <article v-for="product in paginatedProducts" :key="product.id" :class="['product-card','glass-card', product.accent, { unavailable: !product.isAvailable }]"><span v-if="product.tag" class="product-tag">{{ product.tag }}</span><div class="product-image"><img v-if="hasProductImage(product)" :src="product.image" :alt="product.name" @error="markImageError(product)"><div v-else class="product-placeholder"><span class="material-symbols-outlined">inventory_2</span><b>Sin imagen</b></div></div><div class="product-info"><div><span>{{ product.brand }} • {{ product.category }}</span><small>SKU: {{ product.sku || product.id }}</small></div><h3>{{ product.name }}</h3><p :class="['stock-label', { available: product.isAvailable }]">{{ product.stockLabel }}</p><footer><div><span>Precio B2B</span><strong>{{ product.price }}</strong></div><button type="button" :disabled="!product.isAvailable" @click="addToQuote(product)"><span class="material-symbols-outlined">{{ product.isAvailable ? 'add_shopping_cart' : 'help' }}</span><b>{{ product.isAvailable ? 'Cotizar' : 'Consultar' }}</b></button></footer></div></article>
                        <p v-if="paginatedProducts.length === 0" class="empty-results"><span class="material-symbols-outlined">inventory_2</span>No hay productos para los filtros seleccionados.</p>
                    </div><nav v-if="pageCount > 1" class="pagination"><button type="button" :disabled="currentPage === 1" @click="goToPage(1)"><span class="material-symbols-outlined">first_page</span></button><button type="button" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)"><span class="material-symbols-outlined">chevron_left</span></button><template v-for="(page, index) in visiblePages" :key="`${page}-${index}`"><span v-if="page === 'ellipsis'" class="pagination-ellipsis">...</span><button v-else :class="{ active: currentPage === page }" type="button" @click="goToPage(page)">{{ page }}</button></template><button type="button" :disabled="currentPage === pageCount" @click="goToPage(currentPage + 1)"><span class="material-symbols-outlined">chevron_right</span></button><button type="button" :disabled="currentPage === pageCount" @click="goToPage(pageCount)"><span class="material-symbols-outlined">last_page</span></button></nav></section>
                </div>
            </main>
            </template>
            <template #mobile>
            <aside class="mobile-side-placeholder"><div><h1>Portal B2B</h1><p>Premium Automotive Parts</p></div></aside>
            <main class="mobile-main"><section class="mobile-hero-copy"><h2>Automotive Excellence</h2><p>Precision-engineered components for high-performance vehicles. Browse our premium inventory.</p></section><label class="mobile-catalog-search glass-panel"><span class="material-symbols-outlined">search</span><input v-model="search" placeholder="Buscar producto..." type="search" @keyup.enter="applyFilters"><button type="button" @click="applyFilters">Aplicar</button></label><section class="category-scroller"><div :class="{ active: selectedCategories.length === 0 }" @click="setMobileCategory('')"><span>All Systems</span><i></i></div><div :class="{ active: availability === 'available' }" @click="availability = availability === 'available' ? 'all' : 'available'; applyFilters()"><span>Disponibles</span><i></i></div><div v-for="category in categories" :key="category" :class="{ active: selectedCategories.includes(category) }" @click="setMobileCategory(category)"><span>{{ category }}</span><i></i></div></section><div class="mobile-product-grid"><article v-for="product in paginatedProducts" :key="product.id" class="mobile-product glass-panel"><div class="mobile-product-image"><img v-if="hasProductImage(product)" :src="product.image" :alt="product.name" @error="markImageError(product)"><div v-else class="product-placeholder"><span class="material-symbols-outlined">inventory_2</span><b>Sin imagen</b></div><button type="button"><span class="material-symbols-outlined">favorite</span></button></div><div class="mobile-product-info"><div><h4>{{ product.name }}</h4><strong>{{ product.price }}</strong></div><p>{{ product.brand }} • {{ product.category }} • {{ product.sku || product.id }}</p><p :class="['stock-label', { available: product.isAvailable }]">{{ product.stockLabel }}</p><button type="button" :disabled="!product.isAvailable" @click="addToQuote(product)"><span class="material-symbols-outlined">{{ product.isAvailable ? 'shopping_cart' : 'help' }}</span>{{ product.isAvailable ? 'Cotizar' : 'Consultar' }}</button></div></article><p v-if="paginatedProducts.length === 0" class="empty-results">No hay productos para los filtros seleccionados.</p></div></main>
            <button class="mobile-search-fab" type="button" @click="applyFilters"><span class="material-symbols-outlined">search</span></button>
            <div class="checkout-bar"><div class="glass-elevated"><div><div><span class="material-symbols-outlined">shopping_basket</span><i>{{ quoteCount }}</i></div><div><p>Subtotal</p><strong>{{ subtotalLabel }}</strong></div></div><button type="button" @click="showQuoteSummary = true">Checkout <span class="material-symbols-outlined">arrow_forward</span></button></div></div>
            </template>
        </AppShell>
        <Teleport to="body">
            <div v-if="showQuoteSummary" class="quote-modal-backdrop" @click.self="showQuoteSummary = false">
                <section class="quote-modal glass-elevated" role="dialog" aria-modal="true" aria-label="Resumen de cotización temporal">
                    <button class="quote-modal-close" type="button" aria-label="Cerrar resumen" @click="showQuoteSummary = false"><span class="material-symbols-outlined">close</span></button>
                    <h2>Resumen de cotización temporal</h2>
                    <p>{{ quoteCount }} producto(s) agregados</p>
                    <div class="quote-modal-list"><article v-for="item in quoteItems" :key="item.id"><span>{{ item.quantity }}x</span><div><strong>{{ item.name }}</strong><small>{{ item.brand }} • {{ item.id }}</small></div><b>{{ formatCurrency(item.priceValue * item.quantity) }}</b></article><p v-if="quoteItems.length === 0">Aún no agregaste productos.</p></div>
                    <footer><strong>{{ subtotalLabel }}</strong><button type="button" @click="showQuoteSummary = false">Continuar catálogo</button></footer>
                </section>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');
.catalog-page{min-height:100vh;background:#0a0e1a;color:#e0e8f0;font-family:Inter,sans-serif}.material-symbols-outlined{font-family:'Material Symbols Outlined';font-feature-settings:'liga';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;line-height:1}.filled{font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24}.glass-card{background:rgba(15,21,36,.6);backdrop-filter:blur(16px);border:1px solid rgba(125,211,252,.1)}.glass-panel{background:rgba(15,21,36,.6);backdrop-filter:blur(16px);border:1px solid rgba(125,211,252,.1)}.glass-elevated{background:rgba(20,28,46,.75);backdrop-filter:blur(24px);border:1px solid rgba(125,211,252,.15)}a{color:inherit;text-decoration:none}button,input,select{font:inherit}.catalog-mobile{display:none}.desktop-sidebar{position:fixed;inset:0 auto 0 0;z-index:50;width:288px;display:flex;flex-direction:column;padding:32px 0;background:rgba(17,24,40,.8);backdrop-filter:blur(40px);border-right:1px solid rgba(255,255,255,.05)}.side-brand{padding:0 24px;margin-bottom:32px}.side-brand h1{margin:0;color:#7dd3fc;font-size:20px;font-weight:900;text-shadow:0 0 10px rgba(125,211,252,.3)}.side-brand p{margin:4px 0 0;color:rgba(160,180,196,.7);font-size:12px}.side-menu{flex:1;padding:0 16px}.side-menu a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:rgba(160,180,196,.7);transition:.3s}.side-menu a.active{border-left:4px solid #7dd3fc;border-radius:0 8px 8px 0;background:rgba(14,77,110,.2);color:#7dd3fc;box-shadow:0 0 15px rgba(125,211,252,.1)}.side-menu a:not(.active):hover{color:#e0e8f0}.side-menu span:last-child{font-size:14px;font-weight:500}.side-cta{padding:0 24px 16px}.side-cta a{display:block;padding:12px;border:1px solid rgba(125,211,252,.3);border-radius:8px;background:rgba(125,211,252,.1);color:#7dd3fc;text-align:center;font-weight:700}.side-footer{padding:16px 16px 0;border-top:1px solid rgba(255,255,255,.05)}.side-footer a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:rgba(160,180,196,.7)}.desktop-main{min-height:100vh;margin-left:288px;display:flex;flex-direction:column}.desktop-topbar{position:sticky;top:0;z-index:50;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;background:rgba(20,28,46,.6);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.1);box-shadow:0 0 30px rgba(125,211,252,.05)}.desktop-topbar>div:first-child span{font-size:18px;font-weight:700;letter-spacing:.1em}.desktop-topbar label{position:relative;flex:1;max-width:576px;margin:0 32px}.desktop-topbar label span{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:rgba(125,211,252,.6);font-size:18px}.desktop-topbar input{width:100%;box-sizing:border-box;padding:8px 16px 8px 48px;border:1px solid rgba(255,255,255,.1);border-radius:999px;background:rgba(255,255,255,.05);color:#e0e8f0;outline:none}.desktop-topbar input:focus{border-color:rgba(125,211,252,.5);box-shadow:0 0 0 1px rgba(125,211,252,.5)}.desktop-topbar>div:last-child,.user{display:flex;align-items:center}.desktop-topbar>div:last-child{gap:24px}.desktop-topbar button{border:0;background:transparent;color:#a0b4c4}.user{gap:12px;padding-left:24px;border-left:1px solid rgba(255,255,255,.1)}.user span{font-size:12px;font-weight:600}.user>div{width:32px;height:32px;padding:1px;border-radius:999px;background:linear-gradient(135deg,#7dd3fc,#c8a0f0)}.user img{width:100%;height:100%;object-fit:cover;border-radius:999px;background:#141c2e}.catalog-body{flex:1;display:flex;overflow:hidden}.filter-panel{width:288px;height:calc(100vh - 140px);position:sticky;top:96px;margin:24px;padding:24px;border-radius:12px;overflow:auto}.filter-panel h2{display:flex;align-items:center;margin:0 0 24px;color:#7dd3fc;font-size:18px}.filter-panel h2 span{margin-right:8px}.filter-panel section{margin-bottom:32px}.filter-panel h3{margin:0 0 16px;color:#a0b4c4;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.filter-panel label{display:flex;align-items:center;margin-bottom:8px;color:rgba(224,232,240,.8);font-size:14px}.filter-panel input[type=checkbox]{margin-right:12px;accent-color:#7dd3fc}.neon-slider{width:100%;accent-color:#7dd3fc}.range-labels{display:flex;justify-content:space-between;margin-top:12px;color:#a0b4c4;font-size:12px}.brand-filter{display:grid;grid-template-columns:1fr 1fr;gap:8px}.brand-filter button{padding:8px 12px;border:1px solid rgba(255,255,255,.1);border-radius:8px;background:rgba(255,255,255,.05);color:#e0e8f0;font-size:12px;font-weight:600}.brand-filter button.active{border-color:rgba(125,211,252,.5);background:rgba(125,211,252,.2);color:#7dd3fc}.filter-panel select{width:100%;padding:8px 12px;border:1px solid rgba(255,255,255,.1);border-radius:8px;background:rgba(255,255,255,.05);color:#e0e8f0}.apply-filter{width:100%;padding:12px;border:0;border-radius:12px;background:#7dd3fc;color:#001f2e;font-weight:900;letter-spacing:.1em;text-transform:uppercase;box-shadow:0 0 20px rgba(125,211,252,.4)}.products-area{flex:1;padding:24px 48px 24px 0}.products-area>header{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px}.products-area h2{margin:0;color:#e0e8f0;font-size:24px}.products-area p{margin:4px 0 0;color:#a0b4c4;font-size:14px}.view-toggle{display:flex;padding:4px;border-radius:8px;background:rgba(32,44,66,.5)}.view-toggle button{padding:8px;border:0;border-radius:6px;background:transparent;color:#a0b4c4}.view-toggle button:first-child{background:rgba(125,211,252,.2);color:#7dd3fc}.product-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:32px}.product-card{position:relative;overflow:hidden;border-radius:16px}.product-card.tertiary{border-color:rgba(125,211,252,.3);box-shadow:0 0 20px rgba(125,211,252,.1)}.product-tag{position:absolute;z-index:1;top:16px;right:16px;padding:4px 12px;border-radius:999px;background:rgba(125,211,252,.12);color:#7dd3fc;font-size:12px;font-weight:700}.product-card.tertiary .product-tag{left:16px;right:auto;background:rgba(200,160,240,.2);border:1px solid rgba(200,160,240,.4);color:#c8a0f0}.product-image{aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;padding:16px;background:linear-gradient(135deg,#202c42,#0a0e1a);overflow:hidden}.product-image img{width:100%;height:100%;object-fit:contain;transition:.5s}.product-card:hover img{transform:scale(1.1)}.product-info{padding:24px}.product-info>div:first-child{display:flex;justify-content:space-between;margin-bottom:8px}.product-info>div:first-child span{color:rgba(125,211,252,.7);font-size:10px;font-weight:700;letter-spacing:.1em}.product-info small{color:#a0b4c4;font-size:12px}.product-info h3{min-height:52px;margin:0 0 16px;color:#e0e8f0;font-size:18px;line-height:1.25}.product-info footer{display:flex;align-items:center;justify-content:space-between}.product-info footer span{display:block;color:#a0b4c4;font-size:10px;font-weight:700;text-transform:uppercase}.product-info strong{font-size:20px}.product-info button{width:48px;height:48px;border:1px solid rgba(125,211,252,.2);border-radius:12px;background:rgba(125,211,252,.1);color:#7dd3fc}.pagination{display:flex;justify-content:center;align-items:center;gap:8px;margin-top:64px;padding-bottom:96px}.pagination button{width:40px;height:40px;border:1px solid rgba(255,255,255,.1);border-radius:8px;background:rgba(255,255,255,.05);color:#e0e8f0}.pagination button.active{border:0;background:#7dd3fc;color:#001f2e;font-weight:700;box-shadow:0 0 15px rgba(125,211,252,.3)}.desktop-bottom-nav{display:none}
@media(max-width:767px){.catalog-desktop{display:none}.catalog-mobile{display:block;min-height:100vh;padding-bottom:128px;background:#0a0e1a}.mobile-topbar{position:fixed;top:0;left:0;right:0;z-index:50;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;background:rgba(20,28,46,.6);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.1);box-shadow:0 0 30px rgba(125,211,252,.05)}.mobile-topbar>span{font-size:18px;font-weight:700;letter-spacing:.1em}.mobile-topbar>div{display:flex;align-items:center;gap:16px}.mobile-topbar img{width:32px;height:32px;object-fit:cover;border-radius:999px}.mobile-topbar>div>div{width:32px;height:32px;overflow:hidden;border-radius:999px;border:1px solid rgba(125,211,252,.3);background:rgba(125,211,252,.2)}.mobile-main{max-width:1024px;margin:0 auto;padding:80px 16px 0}.mobile-hero-copy{margin-bottom:32px}.mobile-hero-copy h2{margin:0 0 8px;color:#e0e8f0;font-size:30px;font-weight:900;letter-spacing:-.03em}.mobile-hero-copy p{max-width:448px;margin:0;color:#a0b4c4}.category-scroller{display:flex;gap:16px;overflow-x:auto;white-space:nowrap;margin:0 -16px 40px;padding:0 16px}.category-scroller span,.category-scroller div span{display:block;padding:8px 16px;color:rgba(160,180,196,.7);font-weight:500}.category-scroller .active{position:relative;color:#7dd3fc;font-weight:700}.category-scroller .active i{position:absolute;left:16px;right:16px;bottom:0;height:2px;border-radius:999px;background:#7dd3fc;box-shadow:0 0 8px rgba(125,211,252,.6)}.mobile-product-grid{display:grid;grid-template-columns:1fr;gap:24px}.mobile-product{overflow:hidden;border-radius:12px}.mobile-featured-image{height:256px;position:relative;overflow:hidden}.mobile-featured-image img,.mobile-product-image img{width:100%;height:100%;object-fit:cover;transition:.7s}.mobile-featured-image>span{position:absolute;top:16px;right:16px;padding:4px 12px;border-radius:999px;background:rgba(20,28,46,.75);backdrop-filter:blur(24px);color:#7dd3fc;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.mobile-featured-image>div{position:absolute;inset:0;background:linear-gradient(to top,#0a0e1a,transparent);opacity:.8}.mobile-featured-image footer{position:absolute;left:24px;bottom:16px}.mobile-featured-image h3{margin:0 0 4px;color:#fff;font-size:24px}.mobile-featured-image p{margin:0;color:#7dd3fc;font-size:14px;font-weight:500}.mobile-product-image{position:relative;aspect-ratio:1/1;background:#141c2e;overflow:hidden}.mobile-product-image button{position:absolute;top:12px;right:12px;width:40px;height:40px;border:0;border-radius:999px;background:rgba(20,28,46,.75);backdrop-filter:blur(24px);color:#7dd3fc}.mobile-product-info{padding:20px;display:flex;flex-direction:column;min-height:212px}.mobile-product-info>div{display:flex;justify-content:space-between;gap:12px;margin-bottom:8px}.mobile-product-info h4{margin:0;color:#e0e8f0}.mobile-product-info strong{color:#7dd3fc}.mobile-product-info p{margin:0 0 16px;color:#a0b4c4;font-size:12px;line-height:1.5}.mobile-product-info button{margin-top:auto;width:100%;display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;border:1px solid rgba(125,211,252,.2);border-radius:8px;background:transparent;color:#7dd3fc;font-size:14px;font-weight:700}.mobile-search-fab{position:fixed;right:24px;bottom:128px;z-index:40;width:56px;height:56px;border:0;border-radius:999px;background:#7dd3fc;color:#001f2e;box-shadow:0 0 20px rgba(125,211,252,.4)}.checkout-bar{position:fixed;left:16px;right:16px;bottom:96px;z-index:50}.checkout-bar>div{display:flex;align-items:center;justify-content:space-between;padding:16px;border-radius:16px;box-shadow:0 25px 50px rgba(0,0,0,.5)}.checkout-bar>div>div{display:flex;align-items:center;gap:16px}.checkout-bar>div>div>div:first-child{position:relative;padding:8px;border-radius:8px;background:rgba(125,211,252,.2);color:#7dd3fc}.checkout-bar i{position:absolute;top:-4px;right:-4px;width:16px;height:16px;display:grid;place-items:center;border-radius:999px;background:#7dd3fc;color:#001f2e;font-size:10px;font-style:normal;font-weight:700}.checkout-bar p{margin:0;color:rgba(160,180,196,.7);font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.checkout-bar strong{display:block}.checkout-bar button{display:flex;align-items:center;gap:8px;padding:10px 24px;border:0;border-radius:12px;background:#7dd3fc;color:#001f2e;font-weight:700}.mobile-bottom-nav{position:fixed;left:0;right:0;bottom:0;z-index:50;height:80px;display:flex;align-items:center;justify-content:space-around;padding:0 16px;background:rgba(15,21,36,.9);backdrop-filter:blur(16px);border-top:1px solid rgba(125,211,252,.2);border-radius:12px 12px 0 0;box-shadow:0 -10px 40px rgba(0,0,0,.8)}.mobile-bottom-nav a{display:flex;flex-direction:column;align-items:center;color:rgba(160,180,196,.5)}.mobile-bottom-nav a.active{color:#7dd3fc;filter:drop-shadow(0 0 8px rgba(125,211,252,.6));transform:scale(1.1)}.mobile-bottom-nav span:last-child{font-size:10px;text-transform:uppercase}}
@media(min-width:768px) and (max-width:1180px){.product-grid{grid-template-columns:repeat(2,1fr)}.desktop-topbar label{display:none}.catalog-body{display:block}.filter-panel{display:none}.products-area{padding:24px}.desktop-bottom-nav{position:fixed;left:0;right:0;bottom:0;z-index:50;height:80px;display:flex;align-items:center;justify-content:space-around;padding:0 16px;background:rgba(15,21,36,.9);backdrop-filter:blur(16px);border-top:1px solid rgba(125,211,252,.2);border-radius:12px 12px 0 0;box-shadow:0 -10px 40px rgba(0,0,0,.8)}.desktop-bottom-nav a{display:flex;flex-direction:column;align-items:center;color:rgba(160,180,196,.5)}.desktop-bottom-nav a.active{color:#7dd3fc}.desktop-bottom-nav span:last-child{font-size:10px;text-transform:uppercase}}
.shell-content{width:100%!important;max-width:none!important;margin:0!important;padding:18px!important}.catalog-body{display:grid!important;grid-template-columns:minmax(260px,288px) minmax(0,1fr)!important;column-gap:clamp(32px,2.4vw,46px)!important;row-gap:24px!important;width:100%!important}.filter-panel{position:sticky;top:88px;align-self:start}.products-area{min-width:0;width:100%!important;padding-left:clamp(8px,.8vw,14px)!important;border-left:1px solid rgba(125,211,252,.12)}.products-area>header{width:100%!important}.product-grid{grid-template-columns:repeat(auto-fit,minmax(240px,1fr))!important;gap:clamp(18px,1.45vw,26px)!important;width:100%!important}.product-card{min-width:0}.glass-card,.glass-panel,.glass-elevated{background:linear-gradient(135deg,rgba(18,27,45,.76),rgba(9,14,26,.58))!important;border-color:rgba(125,211,252,.18)!important;box-shadow:0 24px 70px rgba(0,0,0,.28),inset 0 1px 0 rgba(255,255,255,.07)}.filter-panel,.product-card{animation:portal-enter .48s ease both;transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease}.filter-panel:hover,.product-card:hover{transform:translateY(-3px);border-color:rgba(125,211,252,.3)!important;box-shadow:0 30px 90px rgba(56,189,248,.11),inset 0 1px 0 rgba(255,255,255,.08)}.product-image img,.mobile-featured-image img,.mobile-product-image img{filter:contrast(1.05) saturate(1.06)!important;image-rendering:auto;transform:translateZ(0);backface-visibility:hidden}@media(max-width:1180px){.catalog-body{display:block!important}.filter-panel{display:none}.products-area{padding-left:0!important;border-left:0}.product-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}}@media(max-width:767px){.mobile-main{padding-left:16px;padding-right:16px}.checkout-bar{left:12px;right:12px}.product-grid{grid-template-columns:1fr!important}}
.desktop-main.shell-content{padding-bottom:0!important}.products-area{display:block!important;min-height:0!important;padding-bottom:0!important}.product-grid{min-height:0!important;margin-bottom:0!important;padding-bottom:0!important}.pagination{margin-top:24px!important;margin-bottom:0!important;padding-bottom:0!important}.catalog-body{align-items:start!important;margin-bottom:0!important;padding-bottom:0!important}
.filter-panel,.product-card,.products-area>header,.pagination{position:relative;overflow:hidden;border:1px solid rgba(125,211,252,.18);background:linear-gradient(135deg,rgba(28,43,64,.78),rgba(10,16,30,.62) 48%,rgba(45,38,72,.58)),radial-gradient(circle at 18% 0%,rgba(125,211,252,.18),transparent 34%),radial-gradient(circle at 92% 12%,rgba(200,160,240,.16),transparent 30%)!important;box-shadow:0 28px 90px rgba(0,0,0,.3),0 0 42px rgba(56,189,248,.075),inset 0 1px 0 rgba(255,255,255,.11)!important;backdrop-filter:blur(22px) saturate(155%)}.products-area>header{padding:22px 24px;border-radius:26px;margin-bottom:22px}.filter-panel,.product-card{border-radius:22px}.pagination{width:max-content;margin-left:auto;margin-right:auto;padding:10px 14px;border-radius:999px}.product-card::after,.filter-panel::after,.products-area>header::after{position:absolute;right:-28px;top:-28px;width:132px;height:132px;border-radius:999px;content:'';background:rgba(125,211,252,.13);filter:blur(12px);pointer-events:none}.product-card:nth-child(3n)::after{background:rgba(200,160,240,.15)}.product-card:nth-child(4n)::after{background:rgba(110,231,183,.12)}.filter-panel,.products-area>header,.product-card,.pagination{animation:portal-enter .48s ease both}.product-card:nth-child(2),.pagination{animation-delay:.08s}.product-card:nth-child(3){animation-delay:.14s}
.catalog-search,.mobile-catalog-search{display:flex;align-items:center;gap:10px;border:1px solid rgba(125,211,252,.18);background:rgba(255,255,255,.05);color:#a0b4c4}.catalog-search{min-width:min(320px,100%);padding:9px 14px;border-radius:999px}.catalog-search input,.mobile-catalog-search input{min-width:0;flex:1;border:0;outline:0;background:transparent;color:#e0e8f0}.products-area>header{display:flex;align-items:center;gap:16px}.products-area>header>div:first-child{margin-right:auto}.view-toggle button.active,.brand-filter button.active,.pagination button.active{border-color:rgba(125,211,252,.42)!important;background:rgba(125,211,252,.16)!important;color:#7dd3fc!important}.product-grid.list-view{grid-template-columns:1fr!important}.product-grid.list-view .product-card{display:grid;grid-template-columns:180px minmax(0,1fr);align-items:stretch}.product-grid.list-view .product-image{height:100%}.empty-results{grid-column:1/-1;margin:0;padding:22px;color:#a0b4c4;text-align:center}.pagination button:disabled{cursor:not-allowed;opacity:.38}.mobile-catalog-search{margin-bottom:22px;padding:10px 12px;border-radius:18px}.mobile-catalog-search button{border:0;border-radius:12px;background:rgba(125,211,252,.14);color:#9ee8ff;font-weight:800}.category-scroller div{cursor:pointer}.quote-modal-backdrop{position:fixed;inset:0;z-index:210;display:grid;place-items:center;padding:18px;background:rgba(3,7,18,.64);backdrop-filter:blur(10px)}.quote-modal{position:relative;width:min(560px,100%);max-height:min(720px,calc(100dvh - 36px));overflow:auto;padding:26px;border-radius:26px;color:#e0e8f0}.quote-modal-close{position:absolute;top:14px;right:14px;border:0;background:transparent;color:#9fb2c4;cursor:pointer}.quote-modal h2{margin:0;color:#fff;font-size:24px;letter-spacing:-.03em}.quote-modal>p{margin:8px 0 18px;color:#a0b4c4}.quote-modal-list{display:grid;gap:10px}.quote-modal-list article{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:12px;padding:12px;border:1px solid rgba(125,211,252,.14);border-radius:16px;background:rgba(255,255,255,.04)}.quote-modal-list span{color:#7dd3fc;font-weight:900}.quote-modal-list strong,.quote-modal-list small{display:block}.quote-modal-list small{margin-top:3px;color:#a0b4c4}.quote-modal footer{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-top:20px}.quote-modal footer strong{color:#fff;font-size:22px}.quote-modal footer button{padding:11px 16px;border:1px solid rgba(125,211,252,.3);border-radius:14px;background:rgba(125,211,252,.12);color:#9ee8ff;font-weight:900;cursor:pointer}@media(max-width:1180px){.products-area>header{flex-wrap:wrap}.catalog-search{order:3;width:100%}}@media(max-width:767px){.product-grid.list-view .product-card{display:block}.quote-modal{padding:22px}.quote-modal footer{align-items:stretch;flex-direction:column}.mobile-catalog-search{display:flex!important}}
.availability-filter{display:grid;grid-template-columns:1fr 1fr;gap:8px}.availability-filter button{border:1px solid rgba(125,211,252,.18);border-radius:999px;background:rgba(255,255,255,.04);color:#a0b4c4;padding:8px 10px}.availability-filter button.active{border-color:rgba(125,211,252,.42)!important;background:rgba(125,211,252,.16)!important;color:#7dd3fc!important}.stock-label{margin:10px 0 0;color:#facc15;font-size:12px;font-weight:800;letter-spacing:.04em}.stock-label.available{color:#6ee7b7}.product-card.unavailable{opacity:.78}.product-card button:disabled,.mobile-product button:disabled{cursor:not-allowed;opacity:.55}.filter-panel{max-height:calc(100vh - 136px);overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(125,211,252,.45) transparent}.filter-panel section{min-width:0}.filter-panel label{min-width:0;line-height:1.35}.brand-filter{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;max-height:176px;overflow-y:auto;padding-right:2px}.brand-filter button{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.product-grid{align-items:stretch}.product-grid.is-changing{opacity:.62;transform:translateY(4px);transition:opacity .18s ease,transform .18s ease}.product-card{display:flex!important;flex-direction:column;min-height:534px;overflow:hidden}.product-image{height:276px!important;min-height:276px;background:rgba(255,255,255,.025)}.product-image img{width:100%;height:100%;object-fit:contain!important;padding:14px}.product-info{display:flex;flex:1;flex-direction:column;min-width:0}.product-info h3{display:-webkit-box;min-height:72px;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:3}.product-info footer{margin-top:auto}.pagination{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:8px;max-width:min(100%,760px);width:max-content!important}.pagination-ellipsis{padding:0 4px;color:#c8d8e6;font-weight:900}.empty-results{display:flex;align-items:center;justify-content:center;gap:10px;border-radius:22px;background:rgba(125,211,252,.07)!important}.empty-results span{color:#7dd3fc}@media(max-width:1440px){.product-grid{grid-template-columns:repeat(auto-fit,minmax(220px,1fr))!important}.product-card{min-height:500px}.product-image{height:230px!important;min-height:230px}}@media(max-width:980px){.products-area>header{align-items:flex-start!important;flex-direction:column}.catalog-search{width:100%}.view-toggle{align-self:flex-end}.pagination{width:100%!important}}@media(max-width:767px){.filter-panel{max-height:none}.product-card{min-height:auto}.mobile-product-image{height:220px!important}.mobile-product-image img{object-fit:contain!important;padding:12px}.mobile-product-info h4{display:-webkit-box;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2}}
.product-placeholder{display:grid;width:100%;height:100%;place-items:center;align-content:center;gap:8px;border-radius:18px;background:radial-gradient(circle at 50% 0%,rgba(125,211,252,.16),transparent 48%),linear-gradient(135deg,rgba(255,255,255,.045),rgba(255,255,255,.015));color:#7dd3fc;text-align:center}.product-placeholder span{font-size:42px}.product-placeholder b{color:#a8c0d4;font-size:12px;text-transform:uppercase;letter-spacing:.12em}.product-card{min-height:500px!important}.product-image{height:236px!important;min-height:236px!important}.product-info footer{display:flex;align-items:flex-end;gap:12px;margin-top:auto}.product-info footer>div{min-width:0;flex:1}.product-info footer button{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-width:48px;max-width:116px}.product-info footer button b{font-size:11px}.filter-panel{max-height:calc(100dvh - 124px)!important;padding-bottom:18px}.filter-scroll{display:grid;gap:8px;max-height:190px;overflow-y:auto;padding-right:4px;scrollbar-width:thin;scrollbar-color:rgba(125,211,252,.45) transparent}.filter-search{display:flex!important;align-items:center;gap:8px;margin-bottom:10px!important;padding:8px 10px;border:1px solid rgba(125,211,252,.18);border-radius:14px;background:rgba(255,255,255,.045)}.filter-search span{font-size:18px;color:#7dd3fc}.filter-search input{min-width:0;width:100%;border:0;outline:0;background:transparent;color:#e0e8f0}.brand-filter{max-height:190px!important}.mobile-product-image .product-placeholder{min-height:220px}@media(min-width:1181px){.product-grid:not(.list-view){grid-template-columns:repeat(4,minmax(0,1fr))!important}}@media(max-width:1180px){.product-grid:not(.list-view){grid-template-columns:repeat(2,minmax(0,1fr))!important}}@media(max-width:767px){.product-grid:not(.list-view){grid-template-columns:1fr!important}.product-card{min-height:auto!important}.product-image{height:220px!important;min-height:220px!important}}
.filter-warming{margin:-6px 0 14px;color:#7dd3fc;font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;opacity:.8}
</style>
