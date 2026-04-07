import axios from 'axios';
import { showSessionExpiredToastThenReload } from './sessionExpiredToast';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

function readXsrfCookie() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : null;
}

function applyCsrfHeaders(config) {
    const meta = document.head?.querySelector('meta[name="csrf-token"]');
    const token = meta?.getAttribute('content');
    if (token) {
        config.headers = config.headers || {};
        config.headers['X-CSRF-TOKEN'] = token;
    }
    const xsrf = readXsrfCookie();
    if (xsrf) {
        config.headers = config.headers || {};
        config.headers['X-XSRF-TOKEN'] = xsrf;
    }
    return config;
}

window.axios.interceptors.request.use(
    (config) => applyCsrfHeaders(config),
    (error) => Promise.reject(error)
);

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error?.response?.status;
        if (status === 419) {
            showSessionExpiredToastThenReload();
        }
        return Promise.reject(error);
    }
);

const initialMeta = document.head?.querySelector('meta[name="csrf-token"]');
if (initialMeta?.content) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = initialMeta.content;
}
