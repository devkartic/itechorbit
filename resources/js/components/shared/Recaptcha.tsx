import { usePage } from '@inertiajs/react';
import { useEffect, useImperativeHandle, useRef, useState, type Ref } from 'react';
import type { Shared } from '@/lib/types';

type CaptchaApi = {
  render: (element: HTMLElement, options: { sitekey: string; callback: (token: string) => void; 'expired-callback': () => void; 'error-callback': () => void }) => number;
  reset: (id: number) => void;
  ready?: (callback: () => void) => void;
  execute?: (key: string, options: { action: string }) => Promise<string>;
};
export type RecaptchaHandle = { verify: () => Promise<string> };
declare global { interface Window { grecaptcha?: CaptchaApi; itechorbitRecaptchaReady?: () => void } }
let loading: Promise<CaptchaApi> | undefined;
function load(version: string, key: string): Promise<CaptchaApi> {
  if (window.grecaptcha?.render) return Promise.resolve(window.grecaptcha);
  if (loading) return loading;
  loading = new Promise<CaptchaApi>((resolve, reject) => {
    const script = document.createElement('script');
    const timer = window.setTimeout(() => fail(), 15000);
    const fail = () => { window.clearTimeout(timer); script.remove(); loading = undefined; reject(new Error('Unable to load reCAPTCHA.')); };
    window.itechorbitRecaptchaReady = () => {
      window.clearTimeout(timer);
      if (window.grecaptcha) resolve(window.grecaptcha); else fail();
    };
    script.src = `https://www.google.com/recaptcha/api.js?onload=itechorbitRecaptchaReady&render=${version === 'v3' ? encodeURIComponent(key) : 'explicit'}`;
    script.async = true;
    const nonce = document.querySelector<HTMLScriptElement>('script[nonce]')?.nonce;
    if (nonce) script.nonce = nonce;
    script.onerror = fail;
    document.head.appendChild(script);
  });
  return loading;
}

export function Recaptcha({ ref, onChange, resetKey = 0, error, action = 'inquiry' }: { ref?: Ref<RecaptchaHandle>; onChange: (token: string) => void; resetKey?: number; error?: string; action?: string }) {
  const { recaptcha } = usePage<Shared>().props;
  const element = useRef<HTMLDivElement>(null);
  const widget = useRef<number | null>(null);
  const token = useRef('');
  const callback = useRef(onChange);
  const [loadError, setLoadError] = useState('');
  const [attempt, setAttempt] = useState(0);
  const version = recaptcha?.version ?? 'v2';
  useEffect(() => { callback.current = value => { token.current = value; onChange(value); }; }, [onChange]);
  useImperativeHandle(ref, () => ({ verify: async () => {
    if (!recaptcha?.enabled) return '';
    if (!recaptcha.siteKey) throw new Error('Verification is unavailable. Please contact support.');
    if (version === 'v2') return token.current;
    try {
      const api = await load(version, recaptcha.siteKey);
      await new Promise<void>(resolve => api.ready ? api.ready(resolve) : resolve());
      if (!api.execute) throw new Error('Verification is unavailable.');
      const result = await Promise.race([
        api.execute(recaptcha.siteKey, { action }),
        new Promise<never>((_, reject) => window.setTimeout(() => reject(new Error('Verification timed out.')), 15000)),
      ]);
      setLoadError('');
      return result;
    } catch {
      const message = 'reCAPTCHA could not verify this submission. Please retry.';
      setLoadError(message);
      throw new Error(message);
    }
  } }), [recaptcha, version, action]);
  useEffect(() => {
    if (!recaptcha?.enabled || !recaptcha.siteKey || !element.current) return;
    let active = true;
    // A fresh child avoids re-rendering an existing v2 widget on StrictMode remounts.
    const container = document.createElement('div');
    element.current.appendChild(container);
    load(version, recaptcha.siteKey).then(api => {
      if (!active || version === 'v3') return;
      widget.current = api.render(container, {
        sitekey: recaptcha.siteKey,
        callback: value => { if (active) { setLoadError(''); callback.current(value); } },
        'expired-callback': () => { if (active) callback.current(''); },
        'error-callback': () => { if (active) { callback.current(''); setLoadError('reCAPTCHA is unavailable. Please retry.'); } },
      });
    }).catch(() => { if (active) setLoadError('reCAPTCHA could not load. Check your connection and retry.'); });
    return () => { active = false; widget.current = null; container.remove(); };
  }, [recaptcha?.enabled, recaptcha?.siteKey, version, attempt]);
  useEffect(() => {
    if (widget.current !== null) window.grecaptcha?.reset(widget.current);
    callback.current('');
  }, [resetKey]);
  if (!recaptcha?.enabled) return null;
  return <div className="grid min-w-0 gap-2"><div ref={element} className="overflow-x-auto" />{(!recaptcha.siteKey || loadError || error) && <p role="alert" className="form-error">{!recaptcha.siteKey ? 'Verification is unavailable. Please contact support.' : loadError || error}</p>}{loadError && <button className="text-left text-sm text-blue-700" type="button" onClick={() => { setLoadError(''); callback.current(''); setAttempt(value => value + 1); }}>Retry reCAPTCHA</button>}<p className="text-xs leading-6 text-slate-500">Protected by reCAPTCHA. Google’s <a href="https://policies.google.com/privacy" className="underline">Privacy Policy</a> and <a href="https://policies.google.com/terms" className="underline">Terms of Service</a> apply.</p></div>;
}
