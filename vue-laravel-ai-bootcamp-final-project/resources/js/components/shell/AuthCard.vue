<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';

defineProps<{
    titleKey: string;
    subtitleKey?: string;
}>();

const { t } = useI18n();
</script>

<template>
    <div class="nova-card">
        <p>
            <Link href="/">← {{ t('shell.backHome') }}</Link>
        </p>
        <div class="nova-row" style="justify-content: space-between">
            <Link class="nova-logo" href="/" style="color: var(--nova-tx)">
                <i>N</i>{{ t('appName') }}
            </Link>
            <span class="nova-row">
                <LanguageSwitcher />
                <ThemeSwitcher />
            </span>
        </div>
        <h2>{{ t(titleKey) }}</h2>
        <p v-if="subtitleKey" class="nova-muted">{{ t(subtitleKey) }}</p>
        <slot />
    </div>
</template>
