import { createInertiaApp, router } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import { i18n } from '@/i18n';
import { useLocaleStore, type AppLocale } from '@/stores/locale';
import { useThemeStore, type ThemePreference } from '@/stores/theme';
import type { AuthUser } from '@/types/auth';

type SharedProps = {
    auth?: {
        user: AuthUser | null;
    };
};

function syncStoresFromUser(user: AuthUser | null | undefined): void {
    const theme = user?.theme === 'dark' || user?.theme === 'light' ? user.theme : undefined;
    const language =
        user?.preferred_language === 'en' || user?.preferred_language === 'my'
            ? user.preferred_language
            : undefined;

    useThemeStore().init(theme as ThemePreference | undefined);
    useLocaleStore().init(language as AppLocale | undefined);
}

createInertiaApp({
    title: (title) => (title ? `${title} - NOVA AI` : 'NOVA AI'),
    resolve: (name) => {
        const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');
        const page = pages[`./pages/${name}.vue`];

        if (!page) {
            throw new Error(`Unknown page [${name}]`);
        }

        return page();
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const shared = props.initialPage.props as SharedProps;

        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.use(pinia);
        app.use(i18n);

        syncStoresFromUser(shared.auth?.user ?? null);

        router.on('success', (event) => {
            const next = event.detail.page.props as SharedProps;
            syncStoresFromUser(next.auth?.user ?? null);
        });

        app.mount(el);
    },
});
