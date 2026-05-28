<script setup>
import { computed } from 'vue';
import { navigateTo } from '../../composables/usePortalNavigation.js';
import { portalBrand, portalUser } from '../../portalNavigation.js';
import { getProfileUser } from '../../services/profileService.js';

const props = defineProps({
    title: { type: String, default: portalBrand.topLabel },
    showAvatar: { type: Boolean, default: true },
    avatarSrc: { type: String, default: portalUser.avatar },
    avatarHref: { type: String, default: portalUser.href },
});

const profileUser = computed(() => getProfileUser() || {});
const resolvedAvatarSrc = computed(() => profileUser.value.avatar || props.avatarSrc);
const resolvedUserName = computed(() => profileUser.value.name || portalUser.name);
</script>

<template>
    <header class="portal-mobile-top">
        <strong>{{ title }}</strong>
        <div><a v-if="showAvatar" :href="avatarHref" @click.prevent="navigateTo(avatarHref)"><img :src="resolvedAvatarSrc" :alt="resolvedUserName"></a></div>
    </header>
</template>

<style scoped>
.portal-mobile-top{position:fixed;inset:0 0 auto;z-index:50;height:72px;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:0 18px;background:rgba(20,28,46,.6);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.1);box-shadow:0 0 30px rgba(125,211,252,.05)}.portal-mobile-top strong{min-width:0;overflow:hidden;font-size:clamp(16px,5vw,20px);letter-spacing:.1em;text-overflow:ellipsis;text-transform:uppercase;white-space:nowrap}.portal-mobile-top div{display:flex;flex:0 0 auto;align-items:center;gap:14px;color:#7dd3fc}.portal-mobile-top a{color:inherit;text-decoration:none}.portal-mobile-top img{width:38px;height:38px;border-radius:999px;object-fit:cover;border:1px solid rgba(125,211,252,.3)}@media(max-width:374px){.portal-mobile-top{padding:0 14px}.portal-mobile-top div{gap:10px}.portal-mobile-top img{width:34px;height:34px}}:global(.portal-light) .portal-mobile-top{background:linear-gradient(135deg,rgba(255,255,255,.8),rgba(244,250,255,.6) 52%,rgba(229,244,252,.48));border-bottom-color:var(--portal-glass-border);color:var(--portal-text);box-shadow:var(--portal-shadow-soft);backdrop-filter:blur(30px) saturate(175%)}:global(.portal-light) .portal-mobile-top div{color:var(--portal-primary-strong)}
</style>
