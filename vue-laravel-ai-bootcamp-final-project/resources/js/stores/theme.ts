import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ThemePreference = 'light' | 'dark';

const storageKey = 'theme';

export const useThemeStore = defineStore('theme', () => {
    const preference = ref<ThemePreference>('light');

    function apply(next: ThemePreference): void {
        preference.value = next;
        localStorage.setItem(storageKey, next);
        document.documentElement.dataset.theme = next;
    }

    function init(serverPreference?: ThemePreference): void {
        if (serverPreference === 'light' || serverPreference === 'dark') {
            apply(serverPreference);

            return;
        }

        const stored = localStorage.getItem(storageKey);

        apply(stored === 'dark' ? 'dark' : 'light');
    }

    return { preference, apply, init };
});
