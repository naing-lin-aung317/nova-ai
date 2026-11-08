<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useWallet } from '@/composables/useWallet';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';
import type { NavItem } from '@/shell/navigation';

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
        <Link v-if="admin" href="/dashboard" @click="emit('navigate')">
            ← {{ t('nav.backToApp') }}
        </Link>
        <div v-else class="nova-card">
            <p class="nova-muted">{{ t('nav.credits') }}</p>
            <p class="nova-credits">{{ availableCredits }}</p>
        </div>
        <LanguageSwitcher />
        <ThemeSwitcher />
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
