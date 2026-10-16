import { useSyncExternalStore } from 'react';

type Theme = 'light' | 'dark';
const key = 'itechorbit-workspace-theme';
let fallback: Theme | undefined;
function snapshot(): Theme {
  try { const stored = localStorage.getItem(key); if (stored === 'light' || stored === 'dark') return stored; } catch { /* Storage may be restricted. */ }
  return fallback ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
}
function subscribe(update: () => void) {
  const media = window.matchMedia('(prefers-color-scheme: dark)');
  window.addEventListener('storage', update); window.addEventListener('workspace-theme-change', update); media.addEventListener('change', update);
  return () => { window.removeEventListener('storage', update); window.removeEventListener('workspace-theme-change', update); media.removeEventListener('change', update); };
}
export function useWorkspaceTheme() {
  const theme = useSyncExternalStore(subscribe, snapshot, () => 'light' as Theme);
  const setTheme = (next: Theme) => {
    fallback = next;
    try { localStorage.setItem(key, next); } catch { /* Keep the in-memory choice when storage is unavailable. */ }
    window.dispatchEvent(new Event('workspace-theme-change'));
  };
  return { theme, setTheme };
}
