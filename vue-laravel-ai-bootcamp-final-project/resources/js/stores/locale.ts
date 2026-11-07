import { defineStore } from 'pinia';
import { ref } from 'vue';
import { i18n } from '@/i18n';

export type AppLocale = 'en' | 'my';

const storageKey = 'lang';

function isLocale(value: string | null): value is AppLocale {
    return value === 'en' || value === 'my';
}

export const useLocaleStore = defineStore('locale', () => {
    const locale = ref<AppLocale>('en');

    function apply(next: AppLocale): void {
        locale.value = next;
        i18n.global.locale.value = next;
        document.documentElement.lang = next;
        localStorage.setItem(storageKey, next);
    }

    function init(serverLocale?: AppLocale): void {
        if (serverLocale === 'en' || serverLocale === 'my') {
            apply(serverLocale);

            return;
        }

        const stored = localStorage.getItem(storageKey);

        if (isLocale(stored)) {
            apply(stored);

            return;
        }

        const browser = navigator.language.toLowerCase();
        apply(browser.startsWith('my') ? 'my' : 'en');
    }

    return { locale, apply, init };
});
