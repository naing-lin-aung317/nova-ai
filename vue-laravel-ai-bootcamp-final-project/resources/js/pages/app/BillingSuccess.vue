<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const { t } = useI18n();
</script>

<template>
    <Head :title="t('billing.successTitle')" />

    <div class="nova-card" style="max-width: 520px">
        <h2 style="margin-top: 0">{{ t('billing.successTitle') }}</h2>
        <p class="nova-muted">{{ t('billing.successBody') }}</p>
        <Link class="nova-btn nova-btn-primary" href="/billing">{{ t('billing.backToBilling') }}</Link>
    </div>
</template>
