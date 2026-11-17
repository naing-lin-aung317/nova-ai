<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';
import MobileTopBar from '@/components/shell/MobileTopBar.vue';
import SideNav from '@/components/shell/SideNav.vue';
import { adminNav } from '@/shell/navigation';

const menuOpen = ref(false);

const stop = router.on('navigate', () => {
    menuOpen.value = false;
});

onBeforeUnmount(stop);
</script>

<template>
    <div class="nova-admin">
        <MobileTopBar admin @toggle="menuOpen = !menuOpen" />
        <div class="nova-shell">
            <SideNav
                admin
                :items="adminNav"
                :open="menuOpen"
                @navigate="menuOpen = false"
            />
            <main class="nova-main">
                <slot />
            </main>
        </div>
    </div>
</template>
