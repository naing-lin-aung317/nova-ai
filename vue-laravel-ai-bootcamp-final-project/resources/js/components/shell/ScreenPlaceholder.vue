<script setup lang="ts">
import { useI18n } from 'vue-i18n';

defineProps<{
    titleKey: string;
}>();

const { t } = useI18n();
</script>

<template>
    <section>
        <h1>{{ t(titleKey) }}</h1>
        <p class="nova-muted">{{ t('shell.placeholder') }}</p>
    </section>
</template>
