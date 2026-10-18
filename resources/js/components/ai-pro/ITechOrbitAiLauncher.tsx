import { useEffect } from 'react';

export function ITechOrbitAiLauncher() {
  useEffect(() => {
    const controller = new AbortController();
    let script: HTMLScriptElement | undefined;
    let site: string | undefined;
    fetch('/ai-pro/local-config', { signal: controller.signal, headers: { Accept: 'application/json' } })
      .then(response => response.ok ? response.json() : null)
      .then((config: { site?: string; widget_version?: string } | null) => {
        if (!config?.site || controller.signal.aborted) return;
        site = config.site;
        script = document.createElement('script'); script.src = `/ai-pro/widget.js?v=${encodeURIComponent(config.widget_version || '')}`; script.dataset.theme = 'auto'; script.dataset.subtitle = 'iTechorbit Assistant'; script.dataset.color = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim(); script.dataset.site = site; script.async = true;
        document.body.append(script);
      }).catch(() => { /* The public page remains usable when the optional assistant is unavailable. */ });
    return () => { controller.abort(); script?.remove(); if (site) document.getElementById(`ai-pro-${site}`)?.dispatchEvent(new Event('ai-pro:dispose')); };
  }, []);
  return null;
}
