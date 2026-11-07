<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import MobileTopBar from '@/components/shell/MobileTopBar.vue';
import SideNav from '@/components/shell/SideNav.vue';
import { userNav } from '@/shell/navigation';
import type { AuthUser } from '@/types/auth';

const menuOpen = ref(false);
const { t } = useI18n();
const page = usePage();

const user = computed(
    () => (page.props.auth as { user: AuthUser | null }).user,
);

const needsVerification = computed(
    () => user.value && user.value.email_verified_at === null,
);

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
                <div v-if="needsVerification" class="nova-alert">
                    {{ t('auth.verifyBanner') }}
                    <Link href="/email/verify">{{ t('pages.verify') }}</Link>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
