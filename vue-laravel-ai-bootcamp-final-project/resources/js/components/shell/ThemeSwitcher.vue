<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { persistTheme } from '@/composables/syncPreferences';
import { useThemeStore, type ThemePreference } from '@/stores/theme';

const { t } = useI18n();
const theme = useThemeStore();

function select(option: ThemePreference): void {
    theme.apply(option);
    persistTheme(option);
}

const options: ThemePreference[] = ['light', 'dark'];

function label(option: ThemePreference): string {
    return t(`shell.${option}`);
}
</script>

<template>
    <div class="nova-pref-switcher">
        <div class="nova-seg" role="group" :aria-label="t('shell.theme')">
            <button
                v-for="option in options"
                :key="option"
                type="button"
                :aria-pressed="theme.preference === option"
                @click="select(option)"
            >
                {{ label(option) }}
            </button>
        </div>
    </div>
</template>
