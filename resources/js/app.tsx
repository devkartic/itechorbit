import '../css/app.css';
import '../css/fonts.css';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
  resolve: async name => (await resolvePageComponent<{ default: ResolvedComponent }>(`./pages/${name}.tsx`, import.meta.glob<{ default: ResolvedComponent }>('./pages/**/*.tsx'))).default,
  setup({ el, App, props }) {
    const page = <App {...props} />;
    if (el.hasChildNodes()) hydrateRoot(el, page);
    else createRoot(el).render(page);
  },
  progress: { delay: 0, color: '#ffffff', showSpinner: false },
});
