<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <figure class="nova-auth-preview" aria-hidden="true">
        <p class="nova-auth-preview-kicker">{{ t('auth.previewPromptLabel') }}</p>
        <p class="nova-auth-preview-prompt">{{ t('auth.previewPrompt') }}</p>
        <p class="nova-auth-preview-kicker is-output">{{ t('auth.previewOutputLabel') }}</p>
        <p class="nova-auth-preview-output">{{ t('auth.previewOutput') }}</p>
    </figure>
</template>
