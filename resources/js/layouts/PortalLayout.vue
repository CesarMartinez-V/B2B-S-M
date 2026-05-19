<script setup>
import BottomNav from '../components/navigation/BottomNav.vue';
import SideNav from '../components/navigation/SideNav.vue';
import TopBar from '../components/navigation/TopBar.vue';

defineProps({
    activeKey: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="portal-shell">
        <SideNav :active-key="activeKey" />
        <div class="portal-main-shell">
            <TopBar />
            <main class="portal-content">
                <slot />
            </main>
        </div>
        <BottomNav :active-key="activeKey" />
    </div>
</template>
