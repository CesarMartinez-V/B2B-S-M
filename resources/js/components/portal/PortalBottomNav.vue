<script setup>
import { computed } from 'vue';
import { navigateTo } from '../../composables/usePortalNavigation.js';
import { portalBottomNavItems } from '../../portalNavigation.js';

const props = defineProps({
    activeRoute: { type: String, required: true },
    items: { type: Array, default: () => portalBottomNavItems },
});

const navItems = computed(() => props.items.map((item) => ({ ...item, active: item.href === props.activeRoute })));
</script>

<template>
    <nav class="portal-bottom-nav"><a v-for="item in navItems" :key="item.label" :class="{ active: item.active }" :href="item.href" @click.prevent="navigateTo(item.href)"><span class="material-symbols-outlined" :class="{ filled: item.active }">{{ item.icon }}</span><small>{{ item.label }}</small></a></nav>
</template>

<style scoped>
.portal-bottom-nav{position:fixed;left:0;right:0;bottom:0;z-index:50;height:82px;display:grid;grid-template-columns:repeat(6,minmax(0,1fr));align-items:center;gap:2px;padding:8px 6px max(8px,env(safe-area-inset-bottom));border-top:1px solid rgba(125,211,252,.2);border-radius:12px 12px 0 0;background:rgba(15,21,36,.9);backdrop-filter:blur(16px);box-shadow:0 -10px 40px rgba(0,0,0,.8)}.portal-bottom-nav a{min-width:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;color:rgba(160,180,196,.5);text-decoration:none}.portal-bottom-nav a.active{color:#7dd3fc;filter:drop-shadow(0 0 8px rgba(125,211,252,.6));transform:scale(1.04)}.portal-bottom-nav span{font-size:22px}.portal-bottom-nav small{max-width:100%;overflow:hidden;font-size:8px;font-weight:800;text-overflow:ellipsis;text-transform:uppercase;white-space:nowrap}@media(max-width:374px){.portal-bottom-nav{height:78px;padding-left:4px;padding-right:4px}.portal-bottom-nav span{font-size:20px}.portal-bottom-nav small{font-size:7px}}:global(.portal-light) .portal-bottom-nav{background:linear-gradient(135deg,rgba(255,255,255,.82),rgba(244,250,255,.62) 52%,rgba(229,244,252,.5));border-top-color:var(--portal-glass-border);box-shadow:0 -20px 60px rgba(42,80,112,.14),inset 0 1px 0 rgba(255,255,255,.9);backdrop-filter:blur(30px) saturate(175%)}:global(.portal-light) .portal-bottom-nav a{color:rgba(16,38,58,.54)}:global(.portal-light) .portal-bottom-nav a.active{color:var(--portal-primary-strong);filter:drop-shadow(0 8px 18px rgba(8,122,168,.16))}
</style>
