import '../css/app.css';
import '@fontsource/inter/latin-400.css';
import '@fontsource/inter/latin-500.css';
import '@fontsource/inter/latin-600.css';
import '@fontsource/inter/latin-700.css';
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
  progress: { color: '#2563eb', showSpinner: false },
});
