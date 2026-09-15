import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue, route } from 'ziggy-js';
import { Ziggy } from './ziggy';

if (typeof window !== 'undefined') {
    Ziggy.url = window.location.origin;
    window.Ziggy = Ziggy;   // Route factory reads globalThis?.Ziggy as fallback
    window.route = route;
}

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .mount(el);
    },
});
