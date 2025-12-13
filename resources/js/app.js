import './bootstrap';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import * as Sentry from '@sentry/vue';
import router from './router';
import App from './App.vue';

const app = createApp(App);

// Initialize Sentry if DSN is configured
if (import.meta.env.VITE_SENTRY_DSN) {
    Sentry.init({
        app,
        dsn: import.meta.env.VITE_SENTRY_DSN,
        environment: import.meta.env.VITE_APP_ENV || 'production',
        integrations: [
            Sentry.browserTracingIntegration({ router }),
        ],
        // Set sample rates (adjust for production)
        tracesSampleRate: import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE || 0.1,
        // Only enable replay in production
        replaysSessionSampleRate: 0,
        replaysOnErrorSampleRate: import.meta.env.PROD ? 1.0 : 0,
    });
}

app.use(createPinia());
app.use(router);

app.mount('#app');
