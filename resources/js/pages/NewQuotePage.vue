<script setup>
import { computed, onMounted, ref } from 'vue';
import AppShell from '../components/portal/AppShell.vue';
import { useConfirm } from '../composables/useConfirm.js';
import { useModal } from '../composables/useModal.js';
import { navigateTo } from '../composables/usePortalNavigation.js';
import { useToast } from '../composables/useToast.js';
import { useWhatsAppContact } from '../composables/useWhatsAppContact.js';
import { fetchProfile, getProfileCompany, getProfileCommercial, getProfileUser } from '../services/profileService.js';
import { quoteService } from '../services/quoteService.js';
import { useCatalogStore } from '../stores/catalogStore.js';
import { useQuoteStore } from '../stores/quoteStore.js';
import { useQuoteCartStore } from '../quoteCartStore.js';
import { formatCurrency } from '../utils/currency.js';

const avatarDesktop = 'https://lh3.googleusercontent.com/aida-public/AB6AXuAA2uUCAzzGRBT2EF-JfPnZA1glzMCDJJq2k91tgB4O8jMr3sqLBZDNb8CpO_m2G1M9PpzV2HdCyveiSl3pY0QVPzfD7wBseFch6YK8pIyOmoFY7lBQ8OhSiXvQ1CD2J__RASaGITJJ7Rjrs9pwD-_QPhgQgscSYhrIPMLC0Hi4Hgqo1k_jP1_lNec1Ln-fsJtl3eLvho5iGDca08R1G3qO2iXQecdTR7vr0o12y0Mlyh-jDwC5nd0Na7wpBSsKu_0kX3g7tsS3-uA';
const avatarMobile = avatarDesktop;
const quoteCart = useQuoteCartStore();
const catalogStore = useCatalogStore();
const quoteStore = useQuoteStore();
const { success, info } = useToast();
const { askConfirm } = useConfirm();
const { openModal } = useModal();
const { openWhatsApp } = useWhatsAppContact();
const QUOTE_MAX_QTY = 100;

const search = ref('');
const selectedProductId = ref('');
const selectedQty = ref(1);
const observations = ref('');
const submitted = ref(false);
const productTouched = ref(false);

const products = computed(() => catalogStore.listProducts());
const company = computed(() => getProfileCompany());
const user = computed(() => getProfileUser());
const commercial = computed(() => getProfileCommercial());
const cartItems = quoteCart.items;
const cartCount = quoteCart.count;
const subtotal = quoteCart.total;
const total = subtotal;
const money = formatCurrency;
const selectedProduct = computed(() => products.value.find((product) => String(product.id ?? product.sku) === selectedProductId.value) || null);
const filteredProducts = computed(() => {
    const term = search.value.trim().toLowerCase();
    const source = products.value.filter((product) => canQuoteProduct(product));

    if (!term) return source.slice(0, 12);

    return source.filter((product) => [product.sku, product.id, product.name, product.brand, product.category].join(' ').toLowerCase().includes(term)).slice(0, 12);
});
const lineErrors = computed(() => cartItems.value.reduce((errors, item) => {
    const quantity = Number(item.quantity || 0);
    const maxQty = QUOTE_MAX_QTY;

    if (quantity <= 0) errors[item.id || item.sku] = 'La cantidad debe ser mayor a 0.';
    if (quantity > maxQty) errors[item.id || item.sku] = `La cantidad máxima para cotizar es ${maxQty}.`;
    if (!canQuoteItem(item)) errors[item.id || item.sku] = 'Producto no disponible para cotizar.';

    return errors;
}, {}));
const formErrors = computed(() => {
    const errors = {};

    if (!cartItems.value.length) errors.products = 'Debe agregar al menos un producto.';
    if (observations.value.length > 500) errors.observations = 'Las observaciones no deben superar 500 caracteres.';
    if (Object.keys(lineErrors.value).length > 0) errors.lines = 'Corrija las cantidades antes de guardar.';

    return errors;
});
const canSubmit = computed(() => Object.keys(formErrors.value).length === 0);
const observationCounter = computed(() => `${observations.value.length}/500`);

function numberOrNull(value) {
    const number = Number(value);

    return Number.isFinite(number) ? number : null;
}

function availabilityLabel(product) {
    const qty = numberOrNull(product.availableQty ?? product.stock ?? product.maxQty);

    if (product.isAvailable === true || (qty !== null && qty > 0)) return 'Disponible';
    if (qty === 0) return 'No disponible';

    return 'Consultar disponibilidad';
}

function canQuoteProduct(product) {
    const qty = numberOrNull(product.availableQty ?? product.stock);

    return product.isAvailable === true || qty === null;
}

function canQuoteItem(item) {
    const qty = numberOrNull(item.availableQty ?? item.maxQty);

    return item.isAvailable === true || qty === null;
}

function productOptionLabel(product) {
    return `${product.sku || product.id} · ${product.name} · ${availabilityLabel(product)}`;
}

function selectProduct(product) {
    selectedProductId.value = String(product.id ?? product.sku);
    selectedQty.value = 1;
    productTouched.value = true;
}

function addSelectedProduct() {
    productTouched.value = true;

    if (!selectedProduct.value) {
        info('Seleccione un producto del buscador antes de agregarlo.', 'Producto requerido');
        return;
    }

    const qty = Math.max(1, Number(selectedQty.value) || 1);
    const maxQty = QUOTE_MAX_QTY;

    if (!canQuoteProduct(selectedProduct.value) || maxQty === 0) {
        info('Este producto no está disponible para cotizar.', 'No disponible');
        return;
    }

    if (qty > maxQty) {
        info(`La cantidad máxima para cotizar es ${maxQty}.`, 'Cantidad no disponible');
        return;
    }

    if (!quoteCart.addProduct({ ...selectedProduct.value, availabilityLabel: availabilityLabel(selectedProduct.value) })) {
        info('No fue posible agregar el producto al carrito temporal.', 'Producto no agregado');
        return;
    }

    quoteCart.updateQty(selectedProduct.value.id ?? selectedProduct.value.sku, qty);
    success(`${selectedProduct.value.name} agregado a la cotización temporal.`, 'Producto agregado');
    selectedProductId.value = '';
    selectedQty.value = 1;
    search.value = '';
}

function updateQty(item, event) {
    const nextQty = Number(event.target.value) || 1;

    if (!quoteCart.updateQty(item.id || item.sku, nextQty)) {
        event.target.value = item.quantity || 1;
        info(`La cantidad máxima para cotizar es ${QUOTE_MAX_QTY}.`, 'Cantidad no disponible');
        return;
    }

    event.target.value = item.quantity || 1;
}

function removeItem(item) {
    quoteCart.removeItem(item.id || item.sku);
    success('Producto removido de la solicitud.');
}

function clearQuote() {
    askConfirm({
        title: 'Vaciar cotización',
        message: 'Se eliminarán los productos de esta solicitud temporal. No se modifica Fastevo ni inventario.',
        confirmText: 'Vaciar',
        tone: 'danger',
        onConfirm: () => {
            quoteCart.clear();
            success('Cotización temporal vaciada.');
        },
    });
}

function saveTemporaryRequest() {
    submitted.value = true;

    if (!canSubmit.value) {
        info(Object.values(formErrors.value)[0], 'Revise la solicitud');
        return;
    }

    const cart = quoteService.getTemporaryCart(quoteCart.items.value);
    const quote = quoteService.submitTemporaryRequest(quoteService.createQuoteFromCart(cart, {
        clientName: company.value.name || user.value.name || 'Cliente B2B',
        comments: observations.value.trim(),
        observations: observations.value.trim(),
    }));

    quoteStore.clearQuoteCache();
    success('Solicitud de cotización preparada. La creación real en ERP queda pendiente de aprobación.');
    openModal({
        title: 'Solicitud preparada',
        message: `${quote.id} fue guardada localmente. No se creó documento real en ERP. Puede enviarla por WhatsApp para revisión de un asesor.`,
        icon: 'outgoing_mail',
        confirmText: 'Enviar por WhatsApp',
        cancelText: 'Cerrar',
        size: 'md',
        detail: {
            rows: [
                { label: 'Solicitud', value: quote.id },
                { label: 'Estado', value: 'Temporal local' },
                { label: 'ERP', value: 'No enviado' },
                { label: 'Total interno', value: formatCurrency(quote.amount) },
            ],
            observations: observations.value.trim() || 'Sin observaciones.',
        },
        onConfirm: () => {
            openWhatsApp('Hola, preparé una solicitud de cotización en el Portal B2B y necesito que un asesor la revise.');
            quoteCart.clear();
            success('Solicitud abierta en WhatsApp.');
            navigateTo('/cotizaciones');
        },
    });
}

onMounted(() => {
    void fetchProfile();
    void catalogStore.fetchProducts({ page: 1, per_page: 24, include_filters: 0, availability: 'available' });
});
</script>

<template>
    <div class="new-quote-page">
        <AppShell active-route="/cotizaciones" desktop-search-placeholder="Buscar producto para cotizar..." mobile-title="Nueva Cotización" :avatar-src="avatarDesktop" :mobile-avatar-src="avatarMobile" :user-name="user.name || company.name">
            <template #desktop>
                <main class="quote-workspace shell-content">
                    <header class="quote-hero glass-card">
                        <div>
                            <span class="eyebrow">Solicitud B2B temporal</span>
                            <h1>Nueva Cotización</h1>
                            <p>Basada en la estructura de Fastevo: cliente, productos, cantidades, disponibilidad, comentarios y resumen. No crea documentos reales.</p>
                        </div>
                        <div class="hero-actions">
                            <button type="button" class="ghost-btn" @click="navigateTo('/cotizaciones')"><span class="material-symbols-outlined">arrow_back</span>Volver a cotizaciones</button>
                            <button type="button" class="primary-btn" :disabled="!canSubmit" @click="saveTemporaryRequest"><span class="material-symbols-outlined">save</span>Guardar solicitud</button>
                        </div>
                    </header>

                    <section class="quote-grid">
                        <div class="main-column">
                            <section class="client-card glass-card">
                                <header><span class="material-symbols-outlined">badge</span><div><h2>Datos del cliente</h2><p>Cliente autenticado del portal B2B</p></div></header>
                                <dl>
                                    <div><dt>Empresa</dt><dd>{{ company.name || 'Cliente B2B' }}</dd></div>
                                    <div><dt>Código</dt><dd>{{ company.code || 'Sin configurar' }}</dd></div>
                                    <div><dt>Contacto</dt><dd>{{ user.name || 'No registrado' }}</dd></div>
                                    <div><dt>Condición</dt><dd>{{ commercial.creditCondition || 'Consultar' }}</dd></div>
                                </dl>
                            </section>

                            <section class="product-search-card glass-card">
                                <header><div><h2>Agregar productos</h2><p>Busque por SKU, nombre, marca o categoría. Los productos sin inventario explícito quedan como “Consultar disponibilidad”.</p></div><button type="button" class="ghost-btn" @click="navigateTo('/catalogo')"><span class="material-symbols-outlined">add_shopping_cart</span>Seguir agregando productos</button></header>
                                <div class="search-row">
                                    <label><span>Producto</span><input v-model="search" type="search" placeholder="Buscar producto o SKU..."></label>
                                    <label><span>Seleccionado</span><select v-model="selectedProductId" @change="productTouched = true"><option value="">Seleccione producto</option><option v-for="product in filteredProducts" :key="product.id || product.sku" :value="String(product.id ?? product.sku)">{{ productOptionLabel(product) }}</option></select></label>
                                    <label><span>Cantidad</span><input v-model.number="selectedQty" min="1" :max="QUOTE_MAX_QTY" type="number"></label>
                                    <button type="button" class="primary-btn" @click="addSelectedProduct"><span class="material-symbols-outlined">add</span>Agregar</button>
                                </div>
                                <div v-if="filteredProducts.length" class="product-suggestions">
                                    <button v-for="product in filteredProducts.slice(0, 6)" :key="`suggest-${product.id || product.sku}`" type="button" @click="selectProduct(product)"><strong>{{ product.sku || product.id }}</strong><span>{{ product.name }}</span><em>{{ availabilityLabel(product) }}</em></button>
                                </div>
                                <p v-else class="field-error">No hay productos disponibles para este filtro.</p>
                            </section>

                            <section class="items-card glass-card">
                                <header><div><h2>Productos seleccionados</h2><p>{{ cartCount }} producto(s) en preparación</p></div><button type="button" class="danger-btn" :disabled="cartCount === 0" @click="clearQuote"><span class="material-symbols-outlined">delete_sweep</span>Vaciar</button></header>
                                <div v-if="cartItems.length" class="items-table">
                                    <table>
                                        <thead><tr><th>Producto</th><th>Marca</th><th>Disponibilidad</th><th>Cantidad</th><th>Total</th><th></th></tr></thead>
                                    <tbody><tr v-for="item in cartItems" :key="item.id || item.sku"><td><strong>{{ item.name }}</strong><small>SKU: {{ item.sku || item.id }} · {{ item.category }}</small><p v-if="lineErrors[item.id || item.sku]" class="field-error">{{ lineErrors[item.id || item.sku] }}</p></td><td>{{ item.brand }}</td><td><span :class="['availability-pill', { available: item.isAvailable, warning: item.availableQty === null }]">{{ item.availabilityLabel || availabilityLabel(item) }}</span></td><td><input :value="item.quantity || 1" min="1" :max="QUOTE_MAX_QTY" type="number" @change="updateQty(item, $event)"></td><td><strong>{{ money(Number(item.priceValue || 0) * Number(item.quantity || 1)) }}</strong></td><td><button type="button" class="icon-btn" @click="removeItem(item)"><span class="material-symbols-outlined">delete</span></button></td></tr></tbody>
                                    </table>
                                </div>
                                <div v-else class="empty-state"><span class="material-symbols-outlined">request_quote</span><h3>Cotización vacía</h3><p>Agregue productos desde el buscador o continúe en catálogo.</p><button type="button" class="ghost-btn" @click="navigateTo('/catalogo')">Ir al catálogo</button></div>
                            </section>
                        </div>

                        <aside class="summary-column">
                            <section class="summary-card glass-card">
                                <h2>Resumen</h2>
                                <dl><div><dt>Subtotal</dt><dd>{{ money(subtotal) }}</dd></div><div><dt>Impuesto</dt><dd>{{ money(0) }}</dd></div><div class="total"><dt>Total</dt><dd>{{ money(total) }}</dd></div><div><dt># Productos</dt><dd>{{ cartCount }}</dd></div></dl>
                                <label class="comments"><span>Observaciones</span><textarea v-model="observations" maxlength="500" rows="6" placeholder="Comentarios para el asesor comercial..."></textarea><small :class="{ danger: observations.length > 500 }">{{ observationCounter }}</small></label>
                                <p v-if="submitted && formErrors.products" class="field-error">{{ formErrors.products }}</p>
                                <p v-if="formErrors.observations" class="field-error">{{ formErrors.observations }}</p>
                                <button type="button" class="primary-btn full" :disabled="!canSubmit" @click="saveTemporaryRequest"><span class="material-symbols-outlined">outgoing_mail</span>Guardar solicitud</button>
                                <button type="button" class="ghost-btn full" @click="navigateTo('/cotizaciones')">Volver a cotizaciones</button>
                                <p class="safe-note"><span class="material-symbols-outlined">lock</span>No llama endpoints de proforma, no crea invoice real y no modifica inventario.</p>
                            </section>
                        </aside>
                    </section>
                </main>
            </template>

            <template #mobile>
                <main class="mobile-quote-main">
                    <section class="mobile-title"><span class="eyebrow">Solicitud temporal</span><h1>Nueva Cotización</h1><p>{{ company.name || 'Cliente B2B' }}</p></section>
                    <section class="client-card glass-card"><dl><div><dt>Código</dt><dd>{{ company.code || 'Sin configurar' }}</dd></div><div><dt>Condición</dt><dd>{{ commercial.creditCondition || 'Consultar' }}</dd></div></dl></section>
                    <section class="product-search-card glass-card"><h2>Agregar producto</h2><label><span>Buscar</span><input v-model="search" type="search" placeholder="SKU o producto"></label><label><span>Producto</span><select v-model="selectedProductId"><option value="">Seleccione producto</option><option v-for="product in filteredProducts" :key="product.id || product.sku" :value="String(product.id ?? product.sku)">{{ productOptionLabel(product) }}</option></select></label><div class="mobile-add-row"><input v-model.number="selectedQty" min="1" :max="QUOTE_MAX_QTY" type="number"><button type="button" class="primary-btn" @click="addSelectedProduct">Agregar</button></div></section>
                    <section class="mobile-items"><article v-for="item in cartItems" :key="item.id || item.sku" class="glass-card"><header><div><strong>{{ item.name }}</strong><small>SKU: {{ item.sku || item.id }}</small></div><button type="button" class="icon-btn" @click="removeItem(item)"><span class="material-symbols-outlined">delete</span></button></header><p>{{ item.brand }} · {{ item.category }}</p><span class="availability-pill">{{ item.availabilityLabel || availabilityLabel(item) }}</span><footer><label>Cantidad<input :value="item.quantity || 1" min="1" :max="QUOTE_MAX_QTY" type="number" @change="updateQty(item, $event)"></label><strong>{{ money(Number(item.priceValue || 0) * Number(item.quantity || 1)) }}</strong></footer><p v-if="lineErrors[item.id || item.sku]" class="field-error">{{ lineErrors[item.id || item.sku] }}</p></article><div v-if="!cartItems.length" class="empty-state glass-card"><span class="material-symbols-outlined">request_quote</span><h3>Sin productos</h3><p>Agregue productos para preparar la solicitud.</p></div></section>
                    <section class="mobile-summary glass-card"><div><span>Subtotal</span><strong>{{ money(subtotal) }}</strong></div><div><span>Total</span><strong>{{ money(total) }}</strong></div><label><span>Observaciones</span><textarea v-model="observations" maxlength="500" rows="4"></textarea><small>{{ observationCounter }}</small></label><button type="button" class="primary-btn full" :disabled="!canSubmit" @click="saveTemporaryRequest">Guardar solicitud</button><div class="mobile-actions"><button type="button" class="ghost-btn" @click="navigateTo('/catalogo')">Seguir agregando</button><button type="button" class="ghost-btn" @click="navigateTo('/cotizaciones')">Volver</button></div></section>
                </main>
            </template>
        </AppShell>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');
.new-quote-page{min-height:100vh;background:var(--portal-bg);color:var(--portal-text);font-family:Inter,sans-serif}.material-symbols-outlined{font-family:'Material Symbols Outlined';font-feature-settings:'liga';line-height:1}.quote-workspace{display:grid;gap:22px}.quote-hero,.client-card,.product-search-card,.items-card,.summary-card{border:1px solid var(--portal-glass-border);border-radius:28px}.quote-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:28px}.eyebrow{display:inline-flex;margin-bottom:8px;color:var(--portal-primary);font-size:12px;font-weight:900;letter-spacing:.14em;text-transform:uppercase}.quote-hero h1,.mobile-title h1{margin:0;color:var(--portal-heading);font-size:clamp(34px,4vw,56px);line-height:.95;letter-spacing:-.05em}.quote-hero p,.product-search-card p,.client-card p,.empty-state p,.safe-note{color:var(--portal-muted)}.hero-actions,.product-search-card header,.items-card header{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.quote-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,360px);gap:22px;align-items:start}.main-column{display:grid;gap:18px}.client-card,.product-search-card,.items-card,.summary-card{padding:22px}.client-card header{display:flex;align-items:center;gap:12px;margin-bottom:18px}.client-card header>span{display:grid;width:46px;height:46px;place-items:center;border-radius:16px;background:rgba(125,211,252,.14);color:var(--portal-primary)}.client-card h2,.product-search-card h2,.items-card h2,.summary-card h2{margin:0;color:var(--portal-heading);font-size:20px}.client-card dl{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.client-card dl div,.summary-card dl div{min-width:0;border:1px solid var(--portal-glass-border);border-radius:16px;background:rgba(255,255,255,.04);padding:12px}.client-card dt,.summary-card dt{color:var(--portal-muted);font-size:11px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.client-card dd,.summary-card dd{margin:5px 0 0;color:var(--portal-heading);font-weight:900}.search-row{display:grid;grid-template-columns:minmax(180px,1fr) minmax(260px,1.2fr) 110px auto;gap:12px;align-items:end;margin-top:18px}label span{display:block;margin-bottom:6px;color:var(--portal-muted);font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}input,select,textarea{width:100%;min-width:0;border:1px solid var(--portal-glass-border);border-radius:14px;background:var(--portal-input-bg,rgba(8,13,25,.62));color:var(--portal-text);outline:0;padding:12px 13px}textarea{resize:vertical}.primary-btn,.ghost-btn,.danger-btn,.icon-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--portal-glass-border);border-radius:14px;padding:12px 16px;font-weight:900;cursor:pointer}.primary-btn{background:linear-gradient(135deg,var(--portal-primary),#67bde8);color:#082033}.ghost-btn{background:rgba(255,255,255,.05);color:var(--portal-text)}.danger-btn{background:rgba(248,113,113,.1);color:#fca5a5}.icon-btn{width:42px;height:42px;padding:0;background:rgba(248,113,113,.09);color:#fca5a5}.primary-btn:disabled,.danger-btn:disabled{cursor:not-allowed;opacity:.48}.product-suggestions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:14px}.product-suggestions button{min-width:0;border:1px solid var(--portal-glass-border);border-radius:14px;background:rgba(255,255,255,.04);color:var(--portal-text);padding:12px;text-align:left}.product-suggestions strong,.product-suggestions span,.product-suggestions em{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.product-suggestions strong{color:var(--portal-primary)}.product-suggestions span{margin:4px 0;color:var(--portal-heading);font-weight:800}.product-suggestions em{color:var(--portal-muted);font-size:12px;font-style:normal}.items-table{overflow:auto;border-radius:18px}.items-table table{width:100%;min-width:820px;border-collapse:collapse}.items-table th,.items-table td{border-bottom:1px solid var(--portal-glass-border);padding:14px;text-align:left}.items-table th{color:var(--portal-muted);font-size:11px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.items-table small{display:block;margin-top:4px;color:var(--portal-muted);font-size:11px}.items-table input{max-width:96px}.availability-pill{display:inline-flex;border:1px solid rgba(250,204,21,.24);border-radius:999px;background:rgba(250,204,21,.1);color:#facc15;padding:6px 10px;font-size:12px;font-weight:900}.availability-pill.available{border-color:rgba(110,231,183,.25);background:rgba(110,231,183,.1);color:#6ee7b7}.availability-pill.warning{border-color:rgba(250,204,21,.24);background:rgba(250,204,21,.1);color:#facc15}.summary-column{position:sticky;top:88px}.summary-card{display:grid;gap:16px}.summary-card dl{display:grid;gap:10px;margin:0}.summary-card dl .total{background:rgba(125,211,252,.12)}.summary-card dl .total dd{color:var(--portal-primary);font-size:24px}.comments small{display:block;margin-top:6px;color:var(--portal-muted);text-align:right}.comments small.danger,.field-error{color:#fca5a5}.field-error{margin:7px 0 0;font-size:12px;font-weight:800}.full{width:100%}.safe-note{display:flex;align-items:flex-start;gap:8px;margin:0;font-size:12px}.safe-note span{color:#6ee7b7}.empty-state{display:grid;place-items:center;gap:8px;padding:44px 18px;text-align:center}.empty-state>span{color:var(--portal-primary);font-size:42px}:global(.portal-light) .new-quote-page{background:var(--portal-bg);color:var(--portal-text)}:global(.portal-light) input,:global(.portal-light) select,:global(.portal-light) textarea{background:rgba(255,255,255,.72);color:#102033}:global(.portal-light) .ghost-btn{background:rgba(255,255,255,.58);color:#102033}:global(.portal-light) .client-card dl div,:global(.portal-light) .summary-card dl div,:global(.portal-light) .product-suggestions button{background:rgba(255,255,255,.52)}.mobile-quote-main{display:grid;gap:14px;padding:92px 14px 130px}.mobile-title h1{font-size:32px}.mobile-title p{margin:8px 0 0;color:var(--portal-muted)}.mobile-quote-main .client-card dl{grid-template-columns:1fr 1fr}.mobile-quote-main .product-search-card{display:grid;gap:12px}.mobile-add-row{display:grid;grid-template-columns:96px 1fr;gap:10px}.mobile-items{display:grid;gap:12px}.mobile-items article{padding:14px;border-radius:18px}.mobile-items article header{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}.mobile-items strong{color:var(--portal-heading)}.mobile-items small{display:block;color:var(--portal-muted);font-size:10px;font-weight:900}.mobile-items p{color:var(--portal-muted)}.mobile-items footer{display:flex;align-items:end;justify-content:space-between;gap:10px;margin-top:12px}.mobile-items footer label{max-width:110px}.mobile-summary{display:grid;gap:12px;padding:16px;border-radius:20px}.mobile-summary>div{display:flex;align-items:center;justify-content:space-between}.mobile-actions{display:grid!important;grid-template-columns:1fr 1fr;gap:10px}@media(max-width:1180px){.quote-grid{grid-template-columns:1fr}.summary-column{position:static}.client-card dl{grid-template-columns:repeat(2,minmax(0,1fr))}.search-row{grid-template-columns:1fr 1fr 110px}}@media(max-width:767px){.quote-workspace{display:none}.client-card,.product-search-card,.items-card,.summary-card{border-radius:20px}.quote-grid,.quote-hero{display:block}.search-row,.product-suggestions{grid-template-columns:1fr}.new-quote-page{overflow-x:hidden}.items-table table{min-width:760px}}
</style>
