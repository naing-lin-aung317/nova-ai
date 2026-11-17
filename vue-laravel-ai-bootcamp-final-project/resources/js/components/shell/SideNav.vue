<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useWallet } from '@/composables/useWallet';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';
import type { NavItem } from '@/shell/navigation';
import type { AuthUser } from '@/types/auth';

defineProps<{
    items: NavItem[];
    admin?: boolean;
    open: boolean;
}>();

const emit = defineEmits<{
    navigate: [];
}>();

const { t } = useI18n();
const page = usePage();
const { availableCredits } = useWallet();

const isSuperAdmin = computed(() => {
    const user = (page.props.auth as { user: AuthUser | null }).user;

    return user?.role === 'super_admin';
});

function isActive(href: string): boolean {
    return page.url.split('?')[0] === href;
}
</script>

<template>
    <nav class="nova-side" :class="{ 'is-open': open }">
        <Link class="nova-logo" href="/" @click="emit('navigate')">
            <i>N</i>
            {{ t('appName') }}
            <span v-if="admin" class="nova-badge">{{ t('nav.admin') }}</span>
        </Link>
        <Link
            v-for="item in items"
            :key="item.to"
            :class="{ 'is-active': isActive(item.to) }"
            :href="item.to"
            @click="emit('navigate')"
        >
            {{ t(item.label) }}
        </Link>
        <div class="nova-side-spacer"></div>
        <Link
            v-if="isSuperAdmin && !admin"
            class="nova-side-admin-link"
            href="/admin"
            @click="emit('navigate')"
        >
            {{ t('nav.adminPanel') }}
        </Link>
        <Link v-if="admin" href="/dashboard" @click="emit('navigate')">
            ← {{ t('nav.backToApp') }}
        </Link>
        <div v-else class="nova-card">
            <p class="nova-muted">{{ t('nav.credits') }}</p>
            <p class="nova-credits">{{ availableCredits }}</p>
        </div>
        <div class="nova-pref-toggles-row nova-side-prefs">
            <LanguageSwitcher />
            <ThemeSwitcher />
        </div>
        <Link
            v-if="!admin"
            as="button"
            class="nova-btn w-100"
            href="/logout"
            method="post"
            @click="emit('navigate')"
        >
            {{ t('nav.logout') }}
        </Link>
    </nav>
</template>
