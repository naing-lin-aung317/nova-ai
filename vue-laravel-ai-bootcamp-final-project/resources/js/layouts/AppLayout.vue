<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';
import MobileTopBar from '@/components/shell/MobileTopBar.vue';
import SideNav from '@/components/shell/SideNav.vue';
import { userNav } from '@/shell/navigation';

const menuOpen = ref(false);

const stop = router.on('navigate', () => {
    menuOpen.value = false;
});

onBeforeUnmount(stop);
</script>

<template>
    <div>
        <MobileTopBar @toggle="menuOpen = !menuOpen" />
        <div class="nova-shell">
            <SideNav
                :items="userNav"
                :open="menuOpen"
                @navigate="menuOpen = false"
            />
            <main class="nova-main">
                <slot />
            </main>
        </div>
    </div>
</template>
