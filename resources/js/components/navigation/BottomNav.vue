<script setup>
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
    <nav class="bottom-nav glass-strip" :aria-label="words.mobileNavigationLabel">
        <a
            v-for="item in words.mobileNavigation"
            :key="item.key"
            :class="{ active: item.key === activeKey }"
            :href="item.href"
        >
            <span aria-hidden="true">{{ item.icon }}</span>
            {{ item.shortLabel }}
        </a>
    </nav>
</template>
