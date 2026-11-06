<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';

defineProps<{
    titleKey: string;
}>();

const { t } = useI18n();
</script>

<template>
    <div class="nova-card">
        <p>
            <Link href="/">← {{ t('shell.backHome') }}</Link>
        </p>
        <h2>{{ t(titleKey) }}</h2>
        <p class="nova-muted">{{ t('shell.placeholder') }}</p>
        <slot />
        <LanguageSwitcher />
        <ThemeSwitcher />
    </div>
</template>
