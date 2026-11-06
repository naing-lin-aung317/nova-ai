<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthCard from '@/components/shell/AuthCard.vue';
import AuthSplitLayout from '@/layouts/AuthSplitLayout.vue';

defineOptions({ layout: AuthSplitLayout });

const { t } = useI18n();
</script>

<template>
    <AuthCard title-key="pages.register">
        <p>
            <Link href="/login">{{ t('pages.login') }}</Link>
        </p>
    </AuthCard>
</template>
