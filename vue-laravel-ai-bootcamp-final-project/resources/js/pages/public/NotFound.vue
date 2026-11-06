<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';

const { t } = useI18n();
</script>

<template>
    <div class="nova-simple">
        <div class="nova-card">
            <h1>{{ t('pages.notFound') }}</h1>
            <p class="nova-muted">{{ t('shell.placeholder') }}</p>
            <p>
                <Link href="/">← {{ t('shell.backHome') }}</Link>
            </p>
            <LanguageSwitcher />
            <ThemeSwitcher />
        </div>
    </div>
</template>
