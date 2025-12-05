import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Suppress browser extension message channel errors (harmless)
// These occur when browser extensions (ad blockers, password managers, etc.)
// intercept postMessage calls from third-party scripts like Stripe, hCaptcha, etc.
// The extensions return true to indicate async handling but then close the channel,
// causing these harmless errors that don't affect functionality.
if (typeof window !== 'undefined') {
  // Suppress unhandled promise rejections from browser extensions
  window.addEventListener('unhandledrejection', (event) => {
    const message = event.reason?.message || event.reason?.toString() || '';
    if (message.includes('message channel closed') || 
        message.includes('asynchronous response') ||
        message.includes('listener indicated an asynchronous response')) {
      event.preventDefault();
      return false;
    }
  });
}

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
