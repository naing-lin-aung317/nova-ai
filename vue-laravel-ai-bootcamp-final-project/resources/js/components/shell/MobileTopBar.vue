<script setup lang="ts">
import { useI18n } from 'vue-i18n';

defineProps<{
    admin?: boolean;
}>();

const emit = defineEmits<{
    toggle: [];
}>();

const { t } = useI18n();
</script>

<template>
    <header class="nova-top">
        <button
            class="nova-btn"
            type="button"
            :aria-label="t('shell.menu')"
            @click="emit('toggle')"
        >
            ☰
        </button>
        <span class="nova-logo"><i>N</i>{{ t('appName') }}</span>
        <span v-if="admin" class="nova-badge">{{ t('nav.admin') }}</span>
        <span v-else class="nova-badge">{{ t('shell.staticCredits') }}</span>
    </header>
</template>
