<script setup>
import { navigateTo } from '../../composables/usePortalNavigation.js';
import { PalabrasWeb } from '../../PalabrasWeb';

const words = PalabrasWeb.portal;
</script>

<template>
    <header class="top-bar glass-strip">
        <a class="brand-wordmark" :href="words.routes.dashboard.href" @click.prevent="navigateTo(words.routes.dashboard.href)">{{ words.brandName }}</a>

        <label class="top-search">
            <span aria-hidden="true">⌕</span>
            <input type="search" :placeholder="words.search.placeholder">
        </label>

        <div class="top-actions">
            <button type="button" :aria-label="words.actions.notifications">⌁</button>
            <button type="button" :aria-label="words.actions.settings">⚙</button>
            <div class="user-chip" :aria-label="words.user.label">
                <span>{{ words.user.initials }}</span>
                <strong>{{ words.user.name }}</strong>
            </div>
        </div>
    </header>
</template>
