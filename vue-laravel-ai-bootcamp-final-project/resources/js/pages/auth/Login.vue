<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthCard from '@/components/shell/AuthCard.vue';
import AuthSplitLayout from '@/layouts/AuthSplitLayout.vue';

defineOptions({ layout: AuthSplitLayout });

const { t } = useI18n();
</script>

<template>
    <AuthCard title-key="pages.login">
        <p>
            <Link href="/forgot-password">{{ t('pages.forgot') }}</Link>
        </p>
        <p class="nova-muted">
            <Link href="/register">{{ t('pages.register') }}</Link>
        </p>
    </AuthCard>
</template>
