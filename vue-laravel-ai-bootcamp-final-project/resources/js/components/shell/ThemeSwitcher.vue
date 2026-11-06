<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useThemeStore, type ThemePreference } from '@/stores/theme';

const { t } = useI18n();
const theme = useThemeStore();

const options: ThemePreference[] = ['light', 'dark'];

function label(option: ThemePreference): string {
    return t(`shell.${option}`);
}
</script>

<template>
    <div>
        <div class="nova-seg" role="group" :aria-label="t('shell.theme')">
            <button
                v-for="option in options"
                :key="option"
                type="button"
                :aria-pressed="theme.preference === option"
                @click="theme.apply(option)"
            >
                {{ label(option) }}
            </button>
        </div>
    </div>
</template>
