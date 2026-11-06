<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';

const { t } = useI18n();
</script>

<template>
    <div class="nova-public">
        <header class="nova-row" style="padding: 8px 0 30px">
            <Link class="nova-logo" href="/">
                <i>N</i>{{ t('appName') }}
            </Link>
            <span class="nova-row">
                <LanguageSwitcher />
                <ThemeSwitcher />
                <Link class="nova-btn" href="/login">{{ t('nav.login') }}</Link>
                <Link class="nova-btn nova-btn-primary" href="/register">
                    {{ t('shell.startFree') }}
                </Link>
            </span>
        </header>
        <slot />
    </div>
</template>
