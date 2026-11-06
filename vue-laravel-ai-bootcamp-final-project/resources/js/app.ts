import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import { i18n } from '@/i18n';
import { useLocaleStore } from '@/stores/locale';
import { useThemeStore } from '@/stores/theme';

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

        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.use(pinia);
        app.use(i18n);
        useThemeStore().init();
        useLocaleStore().init();
        app.mount(el);
    },
});
