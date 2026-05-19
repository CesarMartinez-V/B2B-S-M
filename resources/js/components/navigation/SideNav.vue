<script setup>
import { navigateTo } from '../../composables/usePortalNavigation.js';
import { PalabrasWeb } from '../../PalabrasWeb';

defineProps({
    activeKey: {
        type: String,
        required: true,
    },
});

const words = PalabrasWeb.portal;
</script>

<template>
    <aside class="side-nav glass-strip" :aria-label="words.navigationLabel">
        <div class="side-brand">
            <div class="brand-icon" aria-hidden="true">◇</div>
            <div>
                <strong>{{ words.portalName }}</strong>
                <span>{{ words.portalTagline }}</span>
            </div>
        </div>

        <nav>
            <a
                v-for="item in words.navigation"
                :key="item.key"
                :class="{ active: item.key === activeKey }"
                :href="item.href"
                @click.prevent="navigateTo(item.href)"
            >
                <span aria-hidden="true">{{ item.icon }}</span>
                {{ item.label }}
            </a>
        </nav>

        <div class="side-footer">
            <a class="primary-link" :href="words.routes.quotes.href" @click.prevent="navigateTo(words.routes.quotes.href)">{{ words.actions.newQuote }}</a>
            <a :href="words.support.href" @click.prevent="navigateTo(words.support.href)">{{ words.support.label }}</a>
            <a :href="words.logout.href" @click.prevent="navigateTo(words.logout.href)">{{ words.logout.label }}</a>
        </div>
    </aside>
</template>
