<script setup>
import PortalLayout from '../layouts/PortalLayout.vue';
import { navigateTo } from '../composables/usePortalNavigation.js';
import { PalabrasWeb } from '../PalabrasWeb';

defineProps({
    pageKey: {
        type: String,
        required: true,
    },
    page: {
        type: Object,
        required: true,
    },
});

const words = PalabrasWeb.portal;
</script>

<template>
    <PortalLayout :active-key="pageKey">
        <section class="portal-hero glass-panel glass-panel--elevated">
            <div>
                <p class="eyebrow">{{ page.eyebrow }}</p>
                <h1>{{ page.title }}</h1>
                <p>{{ page.description }}</p>
            </div>
            <div class="hero-orb" aria-hidden="true">
                <span>{{ page.code }}</span>
            </div>
        </section>

        <section class="portal-grid portal-grid--metrics" :aria-label="words.sections.metrics">
            <article v-for="metric in page.metrics" :key="metric.label" class="metric-card glass-panel">
                <p>{{ metric.label }}</p>
                <strong>{{ metric.value }}</strong>
                <span>{{ metric.detail }}</span>
            </article>
        </section>

        <section class="portal-grid portal-grid--content">
            <article class="glass-panel content-card content-card--wide">
                <p class="eyebrow">{{ words.sections.focus }}</p>
                <h2>{{ page.focus.title }}</h2>
                <p>{{ page.focus.description }}</p>
                <div class="hud-bars" aria-hidden="true">
                    <span v-for="bar in page.focus.bars" :key="bar" :style="{ height: bar }"></span>
                </div>
            </article>

            <article class="glass-panel content-card">
                <p class="eyebrow">{{ words.sections.actions }}</p>
                <h2>{{ words.quickActionsTitle }}</h2>
                <div class="quick-actions">
                    <a v-for="action in words.quickActions" :key="action.label" :href="action.href" @click.prevent="navigateTo(action.href)">
                        <span>{{ action.icon }}</span>
                        {{ action.label }}
                    </a>
                </div>
            </article>
        </section>
    </PortalLayout>
</template>
