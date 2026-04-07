import './bootstrap';
import { showSessionExpiredToastThenReload } from './sessionExpiredToast';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'MedEquip';

function syncCsrfFromPage(page) {
    const token = page?.props?.csrf_token;
    if (typeof token !== 'string' || !token) {
        return;
    }
    const meta = document.head?.querySelector('meta[name="csrf-token"]');
    if (meta) {
        meta.setAttribute('content', token);
    }
    if (window.axios?.defaults?.headers?.common) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
}

document.addEventListener('inertia:success', (e) => {
    syncCsrfFromPage(e.detail?.page);
});

document.addEventListener('inertia:invalid', (e) => {
    const status = e.detail?.response?.status;
    if (status === 419) {
        e.preventDefault();
        showSessionExpiredToastThenReload();
    }
});

router.on('navigate', (event) => {
    syncCsrfFromPage(event.detail?.page);
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
