<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthCard from '@/components/shell/AuthCard.vue';
import AuthSimpleLayout from '@/layouts/AuthSimpleLayout.vue';

defineOptions({ layout: AuthSimpleLayout });

const { t } = useI18n();
</script>

<template>
    <AuthCard title-key="pages.forgot">
        <p>
            <Link href="/login">{{ t('pages.login') }}</Link>
        </p>
    </AuthCard>
</template>
