<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();
const heroTitle = computed(() =>
    page.component === 'auth/Register'
        ? t('shell.heroRegister')
        : t('shell.heroLogin'),
);
</script>

<template>
    <div class="nova-auth-split">
        <aside class="nova-hero">
            <Link class="nova-logo" href="/">
                <i>N</i>{{ t('appName') }}
            </Link>
            <div>
                <h2>{{ heroTitle }}</h2>
                <p>{{ t('shell.landingSub') }}</p>
            </div>
            <p>{{ t('shell.placeholder') }}</p>
        </aside>
        <div class="nova-auth-panel">
            <slot />
        </div>
    </div>
</template>
