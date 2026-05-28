<script setup>
import { computed } from 'vue';
import AppShell from '../components/portal/AppShell.vue';
import { usePortalActions } from '../composables/usePortalActions.js';
import { navigateTo } from '../composables/usePortalNavigation.js';
import { getDashboardActivity, getDashboardChart, getDashboardClientConfigured, getDashboardKpis, getDashboardOverview, getDashboardProfile, getDashboardPromotions, getDashboardQuickActions } from '../services/dashboardService.js';

const { openSupportModal, explainPendingIntegration, goToCatalog, goToQuotes, goToInvoices, goToOrders } = usePortalActions();

const heroImage = '/images/dashboard-engine.png';
const promoImages = [
    'https://lh3.googleusercontent.com/aida-public/AB6AXuDgXi9a2yg8HV_YL1Bjq2aGdcqoZTsU85OQ5tMLk9YVaK6Oy_Ae6kGBbGt5nXi1_Dv0PLnHNPexEpxV3frod-GCDz3BUKC5vtsX61JAsp87FasL3al--G7gRmmCMLfzFlSQu2rhnplTHe2YSjDmXoEkizVZ_iu5gGoj-8raNCJV2Kw0OTtiOGR5Ef2KN2KkQWMNyBtR8s_dUjeJBuK2GuSjna375vyaoOXchNG66WwFW2Lw9jFzdpENCTNM3rJNd88QYgc_z0gDm8I',
    'https://lh3.googleusercontent.com/aida-public/AB6AXuDbAymn8CbivADNk1blzouw7I4IGinITq73dW68e3l36YDx8ziTVjl0gg1f8oS4SnmQTsJs1pqSLi_Aw9oW_82icNM2FAmFEp8EcngIIkxBAtgGppZ49z821x0m3ZbJUdjusABxECi5adKM6hdb_eJMDbX6pTkcRffwLEv5MXX_7x_7yroSwz5G1C1FlB1OmVH5ERcz1yt2S6HEpkltPXtZ249noIrestkci1oHLxaU8tWp6uYURcKYf_XOKPvT3LF7dKtsYvaWnSM',
];

const profile = computed(() => getDashboardProfile());
const overview = computed(() => getDashboardOverview());
const kpis = computed(() => getDashboardKpis());
const activities = computed(() => getDashboardActivity());
const chart = computed(() => getDashboardChart());
const quickActions = computed(() => getDashboardQuickActions());
const promotions = computed(() => getDashboardPromotions());
const clientConfigured = computed(() => getDashboardClientConfigured());
const avatarDesktop = computed(() => profile.value.avatar);
const avatarMobile = computed(() => profile.value.avatar);
const displayName = computed(() => profile.value.name || profile.value.company || 'Cliente B2B');
const companyName = computed(() => profile.value.company || 'Cliente B2B');
const partnerLevel = computed(() => profile.value.partnerLevel || 'Socio B2B');
const promotion = computed(() => promotions.value[0] ?? { title: 'Promociones disponibles', text: 'Contenido editorial temporal', tone: 'secondary' });
const primaryKpi = computed(() => kpis.value[0] ?? { icon: 'account_balance_wallet', label: 'Deuda total', value: 'L. 0.00', meta: 'Sin saldo pendiente', tone: 'primary' });
const secondaryKpi = computed(() => kpis.value[1] ?? { icon: 'speed', label: 'Crédito disponible', value: 'L. 0.00', meta: 'Crédito disponible', tone: 'tertiary' });
const tertiaryKpi = computed(() => kpis.value[2] ?? { icon: 'receipt_long', label: 'Facturas vencidas', value: '0', meta: 'Sin vencidas', tone: 'success' });
const mobileActions = computed(() => quickActions.value.slice(0, 4));
const maxChartValue = computed(() => Math.max(...(chart.value.invoices ?? []), ...(chart.value.payments ?? []), 1));
const chartBars = computed(() => (chart.value.months ?? []).map((label, index) => ({
    label,
    height: `${Math.max(12, Math.round(((chart.value.invoices?.[index] ?? 0) / maxChartValue.value) * 100))}%`,
    tone: index % 2 ? 'tertiary' : 'primary',
})));

const runAction = (action) => {
    const label = typeof action === 'string' ? action : action.label;
    const href = typeof action === 'object' ? action.href : null;
    const routes = { Historial: goToInvoices, Facturas: goToInvoices, Catálogo: goToCatalog, Catalogo: goToCatalog, 'Nueva cotización': () => navigateTo('/cotizaciones/nueva'), 'Nueva cotizacion': () => navigateTo('/cotizaciones/nueva'), 'Estado de cuenta': () => navigateTo('/estado-de-cuenta'), Rastreo: goToOrders, Cotizar: goToQuotes, Pedido: goToOrders };

    if (href) {
        navigateTo(href);
        return;
    }

    if (routes[label]) {
        routes[label]();
        return;
    }

    if (label === 'Soporte') {
        openSupportModal({ reference: 'Panel principal', reason: 'Consulta general del portal B2B', whatsappMessage: 'Hola, necesito soporte con el Portal B2B de Inversiones S&M.' });
        return;
    }

    explainPendingIntegration(label);
};
</script>

<template>
    <div class="dashboard-page">
        <AppShell active-route="/panel" desktop-search-placeholder="Buscar socio, factura o pedido..." mobile-title="Inversiones S&amp;M" :avatar-src="avatarDesktop" :mobile-avatar-src="avatarMobile" :user-name="displayName">
            <template #desktop>
            <main class="desktop-main shell-content">
                <header class="hero-card glass-card">
                    <div class="hero-bg">
                        <img alt="High-tech industrial machinery" :src="heroImage">
                        <div></div>
                    </div>
                    <div class="hero-content">
                        <div>
                            <h1>Panel Principal</h1>
                            <p>{{ clientConfigured ? `Bienvenido de nuevo, ${displayName}. Cuenta ${companyName} sincronizada con Fastevo.` : 'No hay cliente B2B configurado para esta sesión.' }}</p>
                        </div>
                        <div class="market-card glass-card">
                            <div><span class="material-symbols-outlined filled">trending_up</span></div>
                            <div>
                                <span>{{ profile.code }}</span>
                                <strong>{{ partnerLevel }}</strong>
                            </div>
                        </div>
                    </div>
                </header>

                <section class="kpi-grid">
                    <article class="kpi-card elevated-card">
                        <div class="orb primary"></div>
                        <div class="kpi-top"><div><span class="material-symbols-outlined">{{ primaryKpi.icon }}</span></div><em>{{ primaryKpi.meta }}</em></div>
                        <h3>{{ primaryKpi.label }}</h3>
                        <p><strong>{{ primaryKpi.value }}</strong><span>HNL</span></p>
                        <div class="progress"><i style="width: 66%"></i></div>
                    </article>
                    <article class="kpi-card elevated-card">
                        <div class="orb tertiary"></div>
                        <div class="kpi-top"><div class="tertiary"><span class="material-symbols-outlined">{{ secondaryKpi.icon }}</span></div><em class="tertiary">{{ secondaryKpi.meta }}</em></div>
                        <h3>{{ secondaryKpi.label }}</h3>
                        <p><strong>{{ secondaryKpi.value }}</strong></p>
                        <div class="kpi-buttons"><button type="button" @click="runAction('Estado de cuenta')">Ver cuenta</button><button type="button" @click="runAction('Historial')">Facturas</button></div>
                    </article>
                    <article class="kpi-card elevated-card">
                        <div class="orb secondary"></div>
                        <div class="kpi-top"><div class="secondary"><span class="material-symbols-outlined">{{ tertiaryKpi.icon }}</span></div><div class="promo-stack"><img v-for="image in promoImages" :key="image" :src="image" alt="Premium automotive part"></div></div>
                        <h3>{{ tertiaryKpi.label }}</h3>
                        <p><strong>{{ tertiaryKpi.value }}</strong></p>
                        <small>{{ tertiaryKpi.meta }} · {{ promotion.text }}</small>
                    </article>
                </section>

                <section class="desktop-lower-grid">
                    <article class="chart-card glass-card">
                        <div class="chart-head">
                            <div><h3>Flujo mensual</h3><p>Facturas emitidas de los últimos meses</p></div>
                            <div class="period-tabs"><button type="button" @click="runAction('Semana')">Semana</button><button type="button" @click="runAction('Mes')">Mes</button><button type="button" @click="runAction('Año')">Año</button></div>
                        </div>
                        <div class="chart-area">
                            <div class="chart-lines"><span></span><span></span><span></span><span></span></div>
                            <div v-for="bar in chartBars" :key="bar.label" :class="['chart-bar', bar.tone]" :style="{ height: bar.height }"><span>{{ bar.label }}</span></div>
                        </div>
                    </article>

                    <article class="activity-card glass-card">
                        <div class="activity-head"><h3>Actividad Reciente</h3><button class="material-symbols-outlined" type="button" @click="runAction('Filtro de actividad')">filter_list</button></div>
                        <div class="activity-list">
                            <div v-for="(activity, index) in activities" :key="`${activity.title}-${activity.time}`" class="activity-item">
                                <div class="activity-icon-wrap">
                                    <div :class="['activity-icon', activity.tone]"><span class="material-symbols-outlined">{{ activity.icon }}</span></div>
                                    <i v-if="index < activities.length - 1"></i>
                                </div>
                                <div>
                                    <header><h4>{{ activity.title }}</h4><span>{{ activity.time }}</span></header>
                                    <p>{{ activity.text }}</p>
                                    <em :class="activity.tone">{{ activity.status }}</em>
                                </div>
                            </div>
                            <p v-if="!activities.length" class="empty-state">Sin actividad reciente para mostrar.</p>
                        </div>
                    </article>
                </section>

                <section class="quick-section">
                    <h3>Accesos Rápidos</h3>
                    <div>
                        <button v-for="action in quickActions" :key="action.label" class="quick-card glass-card" type="button" @click="runAction(action)">
                            <span class="material-symbols-outlined">{{ action.icon }}</span>
                            <span>{{ action.label }}</span>
                        </button>
                    </div>
                </section>
            </main>
            <button class="desktop-fab material-symbols-outlined" type="button" @click="runAction('Soporte')">support</button>
            </template>
            <template #mobile>
            <main class="mobile-main">
                <section class="welcome-mobile"><h1>Bienvenido, {{ displayName }}</h1><p>{{ companyName }}</p></section>
                <section class="mobile-kpis">
                    <article class="mobile-kpi glass-card glow-cyan"><div><p>{{ primaryKpi.label }}</p><h2>{{ primaryKpi.value }}</h2><span><i class="material-symbols-outlined">trending_up</i>{{ primaryKpi.meta }}</span></div><div class="mini-chart"><i></i><i></i><i></i><i></i></div></article>
                    <article class="mobile-kpi glass-card glow-cyan"><div><p>{{ tertiaryKpi.label }}</p><h2>{{ tertiaryKpi.value }}</h2><span>{{ tertiaryKpi.meta }}</span></div><span class="material-symbols-outlined filled tertiary-icon">{{ tertiaryKpi.icon }}</span></article>
                    <article class="mobile-kpi glass-card glow-cyan"><div><p>{{ secondaryKpi.label }}</p><h2>{{ secondaryKpi.value }}</h2><div class="mobile-credit"><i></i></div></div><span class="material-symbols-outlined wallet-icon">{{ secondaryKpi.icon }}</span></article>
                </section>
                <section class="mobile-actions"><header><h3>Acciones Rápidas</h3><span @click="runAction('Catálogo')">Ver Todo</span></header><div><button v-for="action in mobileActions" :key="action.label" type="button" @click="runAction(action)"><div class="glass-elevated"><span class="material-symbols-outlined">{{ action.icon }}</span></div><span>{{ action.label }}</span></button></div></section>
                <section class="mobile-feed glass-elevated glow-cyan"><h3>Actividad Reciente</h3><div><article v-for="activity in activities.slice(0, 3)" :key="`${activity.title}-${activity.time}`"><span class="material-symbols-outlined">{{ activity.icon }}</span><div><p>{{ activity.title }}</p><small>{{ activity.text }}</small><em>{{ activity.time }}</em></div></article><p v-if="!activities.length" class="empty-state">Sin actividad reciente para mostrar.</p></div></section>
                <div class="mobile-technical-bg"><div></div></div>
            </main>
            </template>
        </AppShell>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

.dashboard-page{min-height:100vh;background:#050816;color:#e0e8f0;font-family:Inter,sans-serif;overflow-x:hidden}.material-symbols-outlined{font-family:'Material Symbols Outlined';font-feature-settings:'liga';font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;font-weight:normal;line-height:1}.filled{font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24}.glass-card{background:rgba(15,21,36,.6);backdrop-filter:blur(16px);border:1px solid rgba(125,211,252,.1)}.elevated-card,.glass-elevated{background:rgba(15,21,36,.75);backdrop-filter:blur(24px);border:1px solid rgba(125,211,252,.15)}.glow-cyan,.hud-glow{box-shadow:0 0 30px rgba(125,211,252,.05)}a{text-decoration:none;color:inherit}button,input{font:inherit}.dashboard-mobile{display:none}.desktop-topbar{position:fixed;inset:0 0 auto 0;z-index:50;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;background:rgba(20,28,46,.6);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.1);box-shadow:0 0 30px rgba(125,211,252,.05)}.topbar-left,.topbar-right,.desktop-links,.user-area{display:flex;align-items:center}.topbar-left{gap:32px}.brand{font-size:18px;font-weight:700;letter-spacing:.1em}.desktop-links{gap:24px}.desktop-links a{padding:4px 0;color:#a0b4c4;transition:.2s}.desktop-links a.active{color:#7dd3fc;border-bottom:2px solid #7dd3fc;font-weight:700}.desktop-links a:not(.active){padding:4px 12px;border-radius:4px}.desktop-links a:not(.active):hover{background:rgba(255,255,255,.05);color:#7dd3fc}.topbar-right{gap:16px}.search-pill{position:relative}.search-pill input{width:256px;padding:7px 38px 7px 16px;border:1px solid rgba(42,58,72,.5);border-radius:999px;outline:none;background:rgba(26,36,56,.4);color:#e0e8f0;font-size:14px;transition:.2s}.search-pill input:focus{border-color:#7dd3fc;box-shadow:0 0 0 1px #7dd3fc}.search-pill span{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#a0b4c4;font-size:14px}.icon-button{padding:8px;border:0;border-radius:999px;background:transparent;color:#a0b4c4;transition:.2s}.icon-button:hover{background:rgba(255,255,255,.05);color:#7dd3fc}.user-area{gap:8px;padding-left:16px;border-left:1px solid rgba(255,255,255,.1)}.user-area span{color:#a0b4c4;font-size:14px;font-weight:500}.user-area img{width:32px;height:32px;border-radius:999px;border:1px solid rgba(125,211,252,.3)}.desktop-sidebar{position:fixed;left:0;top:0;z-index:40;width:288px;height:100vh;display:flex;flex-direction:column;padding:96px 0 32px;background:rgba(17,24,40,.8);backdrop-filter:blur(40px);border-right:1px solid rgba(255,255,255,.05);box-shadow:0 25px 50px rgba(10,14,26,.5)}.side-title{display:flex;align-items:center;gap:12px;margin:0 24px 32px}.side-icon{width:40px;height:40px;display:grid;place-items:center;border:1px solid rgba(125,211,252,.4);border-radius:12px;background:rgba(125,211,252,.2);color:#7dd3fc}.side-title h2{margin:0;color:#7dd3fc;font-size:20px;font-weight:900;text-shadow:0 0 10px rgba(125,211,252,.3)}.side-title p{margin:0;color:rgba(160,180,196,.7);font-size:10px;letter-spacing:.12em;text-transform:uppercase}.side-menu{flex:1}.side-menu a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:rgba(160,180,196,.7);transition:.3s}.side-menu a.active{border-left:4px solid #7dd3fc;border-radius:0 8px 8px 0;background:rgba(14,77,110,.2);color:#7dd3fc;box-shadow:0 0 15px rgba(125,211,252,.1)}.side-menu a:not(.active):hover{background:rgba(255,255,255,.05);color:#e0e8f0}.side-menu span:last-child{font-size:14px;font-weight:500}.side-bottom{margin-top:auto;padding:0 16px}.new-quote{width:100%;box-sizing:border-box;display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:16px;padding:10px;border:1px solid rgba(125,211,252,.3);border-radius:8px;background:rgba(125,211,252,.1);color:#7dd3fc;font-size:14px;font-weight:700}.side-bottom>div{padding-top:16px;border-top:1px solid rgba(255,255,255,.05)}.side-bottom>div a{display:flex;align-items:center;gap:12px;padding:8px 16px;color:rgba(160,180,196,.5);font-size:12px;transition:.2s}.side-bottom>div a:hover{color:#e0e8f0}.desktop-main{max-width:1600px;margin-left:288px;padding:80px 32px 32px}.hero-card{position:relative;height:256px;margin-bottom:40px;display:flex;align-items:flex-end;overflow:hidden;border-radius:16px}.hero-bg,.hero-bg div{position:absolute;inset:0}.hero-bg img{width:100%;height:100%;object-fit:cover;opacity:.4;mix-blend-mode:overlay;transform:scale(1.1);transition:.7s}.hero-card:hover .hero-bg img{transform:scale(1.05)}.hero-bg div{background:linear-gradient(to top,#0a0e1a,transparent,transparent)}.hero-content{position:relative;z-index:1;width:100%;display:flex;align-items:flex-end;justify-content:space-between;gap:24px;padding:32px}.hero-content h1{margin:0 0 8px;font-size:36px;line-height:40px;font-weight:900;letter-spacing:-.025em}.hero-content p{max-width:512px;margin:0;color:#a0b4c4}.market-card{display:flex;align-items:center;gap:16px;padding:16px 24px;border-radius:12px}.market-card>div:first-child{padding:8px;border-radius:8px;background:rgba(200,160,240,.2);color:#c8a0f0}.market-card span{display:block;color:#a0b4c4;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.market-card strong{font-size:18px}.kpi-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;margin-bottom:40px}.kpi-card{position:relative;overflow:hidden;padding:24px;border-radius:16px;box-shadow:0 0 30px rgba(125,211,252,.05)}.orb{position:absolute;right:-40px;top:-40px;width:160px;height:160px;border-radius:999px;filter:blur(48px);transition:.2s}.orb.primary{background:rgba(125,211,252,.1)}.orb.tertiary{background:rgba(200,160,240,.1)}.orb.secondary{background:rgba(136,180,204,.1)}.kpi-top{position:relative;display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px}.kpi-top>div:first-child{padding:12px;border:1px solid rgba(125,211,252,.3);border-radius:12px;background:rgba(125,211,252,.2);color:#7dd3fc}.kpi-top>div.tertiary{border-color:rgba(200,160,240,.3);background:rgba(200,160,240,.2);color:#c8a0f0}.kpi-top>div.secondary{border-color:rgba(136,180,204,.3);background:rgba(136,180,204,.2);color:#88b4cc}.kpi-top em{padding:4px 8px;border-radius:999px;background:rgba(125,211,252,.1);color:#7dd3fc;font-size:10px;font-style:normal;font-weight:700}.kpi-top em.tertiary{background:rgba(200,160,240,.1);color:#c8a0f0}.kpi-card h3{margin:0 0 4px;color:#a0b4c4;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.kpi-card p{margin:0;display:flex;align-items:baseline;gap:8px}.kpi-card strong{font-size:30px;font-weight:900;text-shadow:0 0 8px rgba(125,211,252,.4)}.kpi-card p span{color:rgba(160,180,196,.5);font-size:14px}.progress{height:4px;margin-top:24px;overflow:hidden;border-radius:999px;background:rgba(255,255,255,.05)}.progress i{display:block;height:100%;background:#7dd3fc;box-shadow:0 0 10px #7dd3fc}.kpi-buttons{display:flex;gap:8px;margin-top:24px}.kpi-buttons button{padding:4px 12px;border:1px solid rgba(200,160,240,.3);border-radius:999px;background:transparent;color:#c8a0f0;font-size:10px;font-weight:700;text-transform:uppercase}.kpi-buttons button:last-child{background:#c8a0f0;color:#1a002e}.promo-stack{display:flex;margin-left:auto}.promo-stack img{width:24px;height:24px;margin-left:-8px;border-radius:999px;border:1px solid #0a0e1a}.kpi-card small{display:block;margin-top:16px;color:#a0b4c4;font-size:11px;line-height:1.25}.desktop-lower-grid{display:grid;grid-template-columns:2fr 1fr;gap:32px}.chart-card,.activity-card{padding:32px;border-radius:16px}.chart-head,.activity-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:40px}.chart-head h3,.activity-head h3{margin:0;color:#e0e8f0;font-size:20px}.chart-head p{margin:0;color:#a0b4c4;font-size:14px}.period-tabs{display:flex;padding:4px;border-radius:8px;background:rgba(255,255,255,.05)}.period-tabs button{padding:6px 16px;border:0;border-radius:6px;background:transparent;color:#a0b4c4;font-size:12px;font-weight:700}.period-tabs button:first-child{background:#7dd3fc;color:#001f2e;box-shadow:0 10px 15px rgba(125,211,252,.2)}.chart-area{position:relative;height:288px;display:flex;align-items:flex-end;justify-content:space-between;padding:0 16px;border-left:1px solid rgba(255,255,255,.1);border-bottom:1px solid rgba(255,255,255,.1)}.chart-lines{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:space-between;padding:8px 0;pointer-events:none}.chart-lines span{border-top:1px solid rgba(255,255,255,.05)}.chart-bar{position:relative;width:48px;border-radius:8px 8px 0 0;background:rgba(125,211,252,.2);border-top:1px solid rgba(125,211,252,.4);border-left:1px solid rgba(125,211,252,.4);border-right:1px solid rgba(125,211,252,.4)}.chart-bar.tertiary{background:rgba(200,160,240,.2);border-color:rgba(200,160,240,.4)}.chart-bar span{position:absolute;top:-40px;left:50%;transform:translateX(-50%);padding:4px 8px;border:1px solid rgba(125,211,252,.2);border-radius:4px;background:#141c2e;color:#7dd3fc;font-size:10px;font-weight:700;opacity:0;transition:.2s}.chart-bar:hover span{opacity:1}.activity-head{margin-bottom:32px}.activity-head button{border:0;background:transparent;color:#a0b4c4}.activity-list{display:grid;gap:24px}.activity-item{display:flex;gap:16px}.activity-icon-wrap{position:relative}.activity-icon{position:relative;z-index:1;width:40px;height:40px;display:grid;place-items:center;border-radius:999px;border:1px solid rgba(125,211,252,.3);background:rgba(125,211,252,.2);color:#7dd3fc}.activity-icon.tertiary{border-color:rgba(200,160,240,.3);background:rgba(200,160,240,.2);color:#c8a0f0}.activity-icon.error{border-color:rgba(255,107,107,.3);background:rgba(255,107,107,.2);color:#ff6b6b}.activity-icon-wrap i{position:absolute;top:40px;left:50%;width:1px;height:100%;background:rgba(255,255,255,.1);transform:translateX(-50%)}.activity-item header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:4px}.activity-item h4{margin:0;font-size:14px}.activity-item header span{color:rgba(160,180,196,.5);font-size:10px}.activity-item p{margin:0;color:#a0b4c4;font-size:12px;line-height:1.5}.activity-item em{display:inline-block;margin-top:8px;padding:2px 8px;border-radius:4px;background:rgba(34,197,94,.1);color:#4ade80;font-size:10px;font-style:normal;font-weight:700;text-transform:uppercase}.activity-item em.tertiary{background:rgba(125,211,252,.1);color:#7dd3fc}.activity-item em.error{background:rgba(255,107,107,.1);color:#ff6b6b}.quick-section{margin-top:40px}.quick-section h3{margin:0 0 24px;color:#a0b4c4;font-size:12px;font-weight:700;letter-spacing:.2em;text-transform:uppercase}.quick-section>div{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:16px}.quick-card{display:flex;flex-direction:column;align-items:center;gap:12px;padding:16px;border-radius:12px;color:#e0e8f0;transition:.2s}.quick-card:hover{border-color:rgba(125,211,252,.4);background:rgba(125,211,252,.1)}.quick-card .material-symbols-outlined{color:#7dd3fc;font-size:30px}.quick-card span:last-child{font-size:12px}.desktop-bottom-nav{display:none}.desktop-fab{position:fixed;right:24px;bottom:32px;z-index:50;width:56px;height:56px;border:0;border-radius:999px;background:#7dd3fc;color:#001f2e;box-shadow:0 0 30px rgba(125,211,252,.3)}
@media(max-width:767px){.dashboard-desktop{display:none}.dashboard-mobile{display:block;min-height:max(884px,100dvh);padding-bottom:96px;background:#0a0e1a;color:#e0e8f0}.mobile-topbar{position:fixed;top:0;left:0;right:0;z-index:50;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;background:rgba(20,28,46,.6);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.1);box-shadow:0 0 30px rgba(125,211,252,.05);color:#7dd3fc}.mobile-topbar>span{color:#e0e8f0;font-size:18px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.mobile-topbar>div{display:flex;align-items:center;gap:16px}.mobile-topbar button{padding:8px;border:0;border-radius:999px;background:transparent;color:#e0e8f0}.mobile-topbar img{width:32px;height:32px;object-fit:cover;border-radius:999px;border:1px solid rgba(125,211,252,.3)}.mobile-topbar>div>div{width:32px;height:32px;overflow:hidden;border-radius:999px}.mobile-main{max-width:448px;margin:0 auto;padding:80px 16px 0;display:grid;gap:24px}.welcome-mobile{margin-top:16px}.welcome-mobile h1{margin:0;color:#e0e8f0;font-size:24px;font-weight:900;letter-spacing:-.025em}.welcome-mobile p{margin:4px 0 0;color:#a0b4c4;font-size:14px}.mobile-kpis{display:grid;gap:16px}.mobile-kpi{display:flex;align-items:center;justify-content:space-between;padding:20px;border-radius:12px}.mobile-kpi p{margin:0;color:#a0b4c4;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.mobile-kpi h2{margin:4px 0 0;color:#7dd3fc;font-size:30px;font-weight:900}.mobile-kpi span{display:flex;align-items:center;gap:4px;margin-top:4px;color:#88b4cc;font-size:12px}.mobile-kpi span i{font-size:14px}.mini-chart{width:96px;height:64px;display:flex;align-items:flex-end;gap:4px;padding:0 8px 8px;border:1px solid rgba(125,211,252,.1);border-radius:8px;background:rgba(125,211,252,.05)}.mini-chart i{width:8px;border-radius:2px 2px 0 0}.mini-chart i:nth-child(1){height:50%;background:rgba(125,211,252,.2)}.mini-chart i:nth-child(2){height:75%;background:rgba(125,211,252,.4)}.mini-chart i:nth-child(3){height:66%;background:rgba(125,211,252,.6)}.mini-chart i:nth-child(4){height:100%;background:#7dd3fc}.tertiary-icon{color:rgba(200,160,240,.4)!important;font-size:40px}.wallet-icon{color:rgba(160,180,196,.2)!important;font-size:40px!important}.mobile-credit{width:128px;height:4px;margin-top:12px;overflow:hidden;border-radius:999px;background:#202c42}.mobile-credit i{display:block;width:66%;height:100%;background:#7dd3fc;box-shadow:0 0 8px rgba(125,211,252,.8)}.mobile-actions header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}.mobile-actions h3{margin:0;color:#a0b4c4;font-size:14px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.mobile-actions header span{color:#7dd3fc;font-size:12px;font-weight:500}.mobile-actions>div{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}.mobile-actions button{display:flex;flex-direction:column;align-items:center;gap:8px;border:0;background:transparent}.mobile-actions button>div{width:56px;height:56px;display:grid;place-items:center;border-radius:16px}.mobile-actions .material-symbols-outlined{color:#7dd3fc;font-size:24px}.mobile-actions button>span{color:#a0b4c4;font-size:10px;font-weight:600}.mobile-feed{padding:24px;border-radius:16px}.mobile-feed h3{margin:0 0 16px;color:#a0b4c4;font-size:14px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.mobile-feed>div{display:grid;gap:24px}.mobile-feed article{display:flex;gap:16px}.mobile-feed article>span{width:40px;height:40px;display:grid;place-items:center;flex-shrink:0;border-radius:999px;background:rgba(125,211,252,.1);color:#7dd3fc}.mobile-feed .tertiary{background:rgba(200,160,240,.1);color:#c8a0f0}.mobile-feed .secondary{background:rgba(136,180,204,.1);color:#88b4cc}.mobile-feed p{margin:0;color:#e0e8f0;font-size:14px;font-weight:600}.mobile-feed small{display:block;color:#a0b4c4;font-size:12px}.mobile-feed em{display:block;margin-top:4px;color:#7dd3fc;font-size:10px;font-style:normal;font-weight:500}.mobile-feed .faded{opacity:.7}.mobile-technical-bg{position:fixed;top:50%;right:0;z-index:-1;opacity:.2;pointer-events:none;transform:translateX(50%);filter:blur(32px)}.mobile-technical-bg div{width:256px;height:256px;border-radius:999px;background:#7dd3fc;filter:blur(48px)}.mobile-bottom-nav{position:fixed;left:0;right:0;bottom:0;z-index:50;height:80px;display:flex;align-items:center;justify-content:space-around;padding:0 16px;background:rgba(15,21,36,.9);backdrop-filter:blur(16px);border-top:1px solid rgba(125,211,252,.2);border-radius:12px 12px 0 0;box-shadow:0 -10px 40px rgba(0,0,0,.8)}.mobile-bottom-nav a{display:flex;flex-direction:column;align-items:center;color:rgba(160,180,196,.5);transition:.3s}.mobile-bottom-nav a.active{color:#7dd3fc;filter:drop-shadow(0 0 8px rgba(125,211,252,.6))}.mobile-bottom-nav .material-symbols-outlined{font-size:24px}.mobile-bottom-nav span:last-child{margin-top:4px;font-size:10px;letter-spacing:-.04em;text-transform:uppercase}}
@media(min-width:768px) and (max-width:1100px){.desktop-main{padding-left:16px;padding-right:16px}.kpi-grid,.desktop-lower-grid{grid-template-columns:1fr}.quick-section>div{grid-template-columns:repeat(3,1fr)}.desktop-topbar .search-pill,.desktop-links{display:none}.desktop-bottom-nav{position:fixed;left:0;right:0;bottom:0;z-index:50;height:80px;display:flex;align-items:center;justify-content:space-around;padding:0 16px;background:rgba(15,21,36,.9);backdrop-filter:blur(16px);border-top:1px solid rgba(125,211,252,.2);border-radius:12px 12px 0 0;box-shadow:0 -10px 40px rgba(0,0,0,.8)}.desktop-bottom-nav a{display:flex;flex-direction:column;align-items:center;color:rgba(160,180,196,.5)}.desktop-bottom-nav a.active{color:#7dd3fc;filter:drop-shadow(0 0 8px rgba(125,211,252,.6))}.desktop-bottom-nav span:last-child{font-size:10px;text-transform:uppercase}.desktop-main{padding-bottom:96px}.desktop-fab{bottom:96px}}
.shell-content{width:100%!important;max-width:none!important;margin:0!important;padding:18px!important}.desktop-fab{right:24px}.hero-card{width:100%!important}.kpi-grid{grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:clamp(16px,1.4vw,24px)!important}.desktop-lower-grid{grid-template-columns:minmax(0,1.35fr) minmax(360px,.7fr)!important;gap:clamp(16px,1.4vw,24px)!important}.quick-section>div{grid-template-columns:repeat(auto-fit,minmax(180px,1fr))!important}.glass-card,.elevated-card,.glass-elevated{background:linear-gradient(135deg,rgba(18,27,45,.76),rgba(9,14,26,.58))!important;border-color:rgba(125,211,252,.18)!important;box-shadow:0 24px 70px rgba(0,0,0,.28),inset 0 1px 0 rgba(255,255,255,.07)}.hero-bg img,.promo-stack img{image-rendering:auto;transform:translateZ(0);backface-visibility:hidden;filter:contrast(1.08) saturate(1.08)!important}.hero-bg div{backdrop-filter:none!important}.hero-card,.kpi-card,.chart-card,.activity-card,.quick-card{animation:portal-enter .48s ease both;transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease}.kpi-card:hover,.chart-card:hover,.activity-card:hover,.quick-card:hover{transform:translateY(-3px);border-color:rgba(125,211,252,.3)!important;box-shadow:0 30px 90px rgba(56,189,248,.11),inset 0 1px 0 rgba(255,255,255,.08)}.kpi-card:nth-child(2),.chart-card{animation-delay:.08s}.kpi-card:nth-child(3),.activity-card,.quick-card{animation-delay:.14s}@media(max-width:767px){.mobile-main{padding-left:16px;padding-right:16px}}@media(min-width:768px) and (max-width:1100px){.shell-content{padding:18px!important}.kpi-grid,.desktop-lower-grid{grid-template-columns:1fr!important}}
.hero-bg img{filter:none!important;image-rendering:auto;object-position:center 45%;transform:none!important}.hero-bg div{background:linear-gradient(90deg,rgba(5,8,22,.86),rgba(5,8,22,.34) 44%,rgba(5,8,22,.58))!important;filter:none!important;backdrop-filter:none!important}.promo-stack img{filter:none!important;transform:none!important}
.hero-card,.kpi-card,.chart-card,.activity-card,.quick-card{position:relative;overflow:hidden;border:1px solid rgba(125,211,252,.18);background:linear-gradient(135deg,rgba(28,43,64,.78),rgba(10,16,30,.62) 48%,rgba(45,38,72,.58)),radial-gradient(circle at 18% 0%,rgba(125,211,252,.18),transparent 34%),radial-gradient(circle at 92% 12%,rgba(200,160,240,.16),transparent 30%)!important;box-shadow:0 28px 90px rgba(0,0,0,.3),0 0 42px rgba(56,189,248,.075),inset 0 1px 0 rgba(255,255,255,.11)!important;backdrop-filter:blur(22px) saturate(155%)}.hero-card{border-radius:28px}.kpi-card,.chart-card,.activity-card,.quick-card{border-radius:22px}.hero-bg img{filter:none!important;opacity:.92;transform:none!important}.hero-bg div{background:linear-gradient(90deg,rgba(5,8,22,.78),rgba(5,8,22,.24) 46%,rgba(5,8,22,.58))!important;backdrop-filter:none!important}.kpi-card::after,.chart-card::after,.activity-card::after,.quick-card::after{position:absolute;right:-28px;top:-28px;width:132px;height:132px;border-radius:999px;content:'';background:rgba(125,211,252,.13);filter:blur(12px);pointer-events:none}.kpi-card:nth-child(2)::after,.activity-card::after{background:rgba(200,160,240,.15)}.kpi-card:nth-child(3)::after{background:rgba(110,231,183,.12)}.chart-head,.activity-head{border-bottom:1px solid rgba(255,255,255,.07);padding-bottom:14px}.hero-card,.kpi-card,.chart-card,.activity-card,.quick-card{animation:portal-enter .48s ease both}.kpi-card:nth-child(2),.chart-card{animation-delay:.08s}.kpi-card:nth-child(3),.activity-card{animation-delay:.14s}
</style>
