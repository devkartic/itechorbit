import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { renderToString } from 'react-dom/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createServer(page => createInertiaApp({
  page, render: renderToString,
  resolve: async name => (await resolvePageComponent<{ default: ResolvedComponent }>(`./pages/${name}.tsx`, import.meta.glob<{ default: ResolvedComponent }>('./pages/**/*.tsx'))).default,
  setup: ({ App, props }) => <App {...props} />,
}), { host: '127.0.0.1' });
