<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PublicLayout from '@/layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const { t } = useI18n();
</script>

<template>
    <section>
        <h1>{{ t('pages.landing') }}</h1>
        <p class="nova-muted">{{ t('shell.landingSub') }}</p>
        <p>
            <Link class="nova-btn nova-btn-primary" href="/register">
                {{ t('shell.startFree') }}
            </Link>
        </p>
        <p class="nova-muted">{{ t('shell.placeholder') }}</p>
    </section>
</template>
