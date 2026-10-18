(() => {
  const script = document.currentScript;
  if (!(script instanceof HTMLScriptElement) || !script.dataset.site) return;
  const site = script.dataset.site;
  const backend = new URL(script.src).origin;
  const id = `ai-pro-${site}`;
  if (document.getElementById(id)) return;
  const host = document.createElement('div'); host.id = id;
  const root = host.attachShadow({ mode: 'open' });
  const style = document.createElement('style');
  if (script.nonce) style.nonce = script.nonce;
  style.textContent = `:host{all:initial;--ai-color:#7040cf;--ai-on-accent:#fff;--ai-bg:#fff;--ai-soft:#f8fafc;--ai-ink:#18152c;--ai-muted:#64748b;--ai-line:#e2e8f0;--ai-link:#6031b9;--ai-tint:#f4f1ff;--ai-error-bg:#fff2f2;--ai-error:#a22e3c;--ai-shadow:0 24px 80px #27164a26,0 4px 16px #27164a12;position:fixed;bottom:max(20px,env(safe-area-inset-bottom));right:max(20px,env(safe-area-inset-right));z-index:2147483000;font:14px/1.6 Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--ai-ink);color-scheme:light;text-align:left;-webkit-text-size-adjust:100%}
:host([data-theme=dark]){--ai-bg:#182237;--ai-soft:#1e2a42;--ai-ink:#e8ecf8;--ai-muted:#aab4cc;--ai-line:#303b53;--ai-link:#c7b5ff;--ai-tint:#2c2540;--ai-error-bg:#38242c;--ai-error:#ffb7c2;--ai-shadow:0 24px 80px #0008,0 4px 16px #0004;color-scheme:dark}
*{box-sizing:border-box}button,textarea{font:inherit}button,a,textarea{outline-offset:3px}button{cursor:pointer}button:disabled{cursor:not-allowed;opacity:.55}button{transition:background .18s,box-shadow .18s,transform .18s}button:hover:not(:disabled){filter:brightness(.97)}button:focus-visible,a:focus-visible,textarea:focus-visible{outline:2px solid var(--ai-link)}svg{width:20px;height:20px;flex:none;display:block}button{touch-action:manipulation}.launch{display:flex;align-items:center;justify-content:center;gap:10px;background:var(--ai-color);color:var(--ai-on-accent);border:0;border-radius:9999px;padding:10px 20px;min-height:44px;font-size:14px;font-weight:600;box-shadow:0 5px 15px -6px #7040cf80}.launch:hover:not(:disabled){transform:translateY(-2px)}.launch[aria-expanded=true]{box-shadow:0 0 0 4px var(--ai-tint)}.launch svg{width:23px;height:23px}
.panel{position:absolute;bottom:60px;right:0;width:min(400px,calc(100vw - 40px));max-height:min(660px,var(--ai-available-height,calc(100dvh - 112px)));background:var(--ai-bg);border:1px solid var(--ai-line);border-radius:24px;box-shadow:var(--ai-shadow);overflow:hidden;display:flex;flex-direction:column;animation:arrive .2s ease-out}.panel[hidden]{display:none}.panel.conversing{height:min(660px,var(--ai-available-height,calc(100dvh - 112px)))}
header{display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid var(--ai-line);flex:none}.avatar{display:grid;place-items:center;width:36px;height:36px;flex:none;border-radius:10px;background:var(--ai-tint);color:var(--ai-link)}.avatar svg{width:20px;height:20px}.heading{flex:1;min-width:0}.heading strong{display:block;font-size:15px;line-height:1.4;letter-spacing:-.025em}.heading small{display:block;color:var(--ai-muted);font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:180px}.icon-button{border:0;background:transparent;color:var(--ai-muted);display:grid;place-items:center;border-radius:10px;width:40px;height:40px;flex:none}.icon-button:hover{background:var(--ai-soft)}.icon-button svg{width:18px;height:18px}.actions{display:flex;gap:0}
.thread{flex:1;min-height:0;overflow:auto;padding:20px;overscroll-behavior:contain;scrollbar-width:thin;scrollbar-color:var(--ai-line) transparent}.intro{padding:4px 0 16px}.intro .eyebrow{font-size:10px;font-weight:750;letter-spacing:.12em;text-transform:uppercase;color:var(--ai-link);margin:0 0 6px}.intro h2{margin:0 0 8px;font-size:24px;line-height:1.25;font-weight:700;letter-spacing:-.04em}.intro p{font-size:13px;color:var(--ai-muted);margin:0;white-space:pre-wrap;overflow-wrap:anywhere}.suggestions{display:grid;gap:8px;margin:2px 0 4px}.suggestions button{display:flex;justify-content:space-between;gap:12px;align-items:center;background:var(--ai-bg);border:1px solid var(--ai-line);padding:12px 14px;border-radius:12px;color:var(--ai-ink);font-size:12px;line-height:1.5;text-align:left;min-height:44px}.suggestions button:hover{background:var(--ai-soft);border-color:var(--ai-link)}.suggestions svg{width:15px;height:15px;color:var(--ai-link)}
.message{margin:0 0 18px;display:flex;flex-direction:column;align-items:flex-start;gap:5px}.message-label{font-size:10px;text-transform:uppercase;letter-spacing:.07em;font-weight:700;color:var(--ai-muted);padding:0 3px}.message:has(.question){align-items:flex-end}.bubble{padding:12px 14px;border-radius:14px;white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px;line-height:1.75;max-width:94%}.question{background:var(--ai-color);color:var(--ai-on-accent);border-bottom-right-radius:4px}.answer{background:var(--ai-soft);color:var(--ai-ink);border:1px solid var(--ai-line);border-bottom-left-radius:4px}.error{background:var(--ai-error-bg);color:var(--ai-error)}.sources{display:grid;gap:6px;margin-top:10px;padding-top:10px;border-top:1px solid var(--ai-line);white-space:normal}.sources a{display:flex;align-items:center;gap:8px;color:var(--ai-link);font-size:12px;text-decoration:none;line-height:1.5;border:1px solid var(--ai-line);border-radius:9px;padding:9px 10px;background:var(--ai-bg);min-height:40px}.sources a:hover{text-decoration:underline}.sources a span{flex:1;overflow-wrap:anywhere}.sources svg{width:14px;height:14px}
.composer-area{padding:12px 16px 14px;border-top:1px solid var(--ai-line);background:var(--ai-bg);flex:none}form{display:flex;align-items:flex-end;gap:8px;background:var(--ai-soft);border:1px solid var(--ai-line);border-radius:15px;padding:6px}form:focus-within{border-color:var(--ai-link);box-shadow:0 0 0 3px var(--ai-tint)}textarea{resize:none;min-width:0;flex:1;border:0;border-radius:10px;padding:9px 8px;background:transparent;color:var(--ai-ink);line-height:1.5;font-size:14px;min-height:40px;max-height:100px;outline:none!important}textarea::placeholder{color:var(--ai-muted)}.send{display:grid;place-items:center;background:var(--ai-color);color:var(--ai-on-accent);border:0;border-radius:10px;min-width:40px;height:40px;flex:none}.send svg{width:18px;height:18px}.notice{display:flex;gap:6px;align-items:flex-start;font-size:10px;line-height:1.5;color:var(--ai-muted);margin:10px 0 0}.notice svg{width:12px;height:12px;margin-top:2px}.status{display:flex;align-items:center;gap:7px;color:var(--ai-muted);font-size:12px;padding:2px 0 12px}.dots{display:flex;gap:3px}.dots i{display:block;width:4px;height:4px;border-radius:50%;background:var(--ai-link);animation:pulse 1.2s infinite}.dots i:nth-child(2){animation-delay:.15s}.dots i:nth-child(3){animation-delay:.3s}
@keyframes arrive{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}@keyframes pulse{50%{opacity:.35;transform:translateY(-2px)}}
@media(max-width:480px){:host{bottom:max(12px,env(safe-area-inset-bottom));right:max(12px,env(safe-area-inset-right))}.launch{width:48px;min-height:48px;padding:12px;border-radius:9999px}.launch span{display:none}.panel{bottom:64px;width:calc(100vw - 24px);max-height:var(--ai-available-height,calc(100dvh - 96px));border-radius:20px}.panel.conversing{height:var(--ai-available-height,calc(100dvh - 96px))}header{padding:10px 12px}.thread{padding:16px}.composer-area{padding:10px 12px 12px}textarea{font-size:16px}.intro h2{font-size:23px}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}}
`;
  root.append(style);
  const make = (tag, text, className) => { const node = document.createElement(tag); if (text) node.textContent = text; if (className) node.className = className; return node; };
  const paths = {
    spark: 'm12 3 2.4 6.6L21 12l-6.6 2.4L12 21l-2.4-6.6L3 12l6.6-2.4L12 3Z',
    arrow: 'M7 17 17 7M7 7h10v10', close: 'm6 6 12 12M6 18 18 6',
    reset: 'M3 10a9 9 0 1 1 2 8M3 4v6h6', send: 'm5 12 7-7 7 7M12 5v14',
    source: 'M14 3H6v18h12V7l-4-4ZM14 3v5h5M9 12h6M9 16h4',
    shield: 'M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6l-8-3Z',
  };
  const icon = name => {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.7'); svg.setAttribute('stroke-linecap', 'round'); svg.setAttribute('stroke-linejoin', 'round'); svg.setAttribute('aria-hidden', 'true');
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path'); path.setAttribute('d', paths[name]); svg.append(path); return svg;
  };
  const launch = make('button', '', 'launch'); launch.append(icon('spark'), make('span', 'Ask iTechOrbit AI'));
  launch.type = 'button'; launch.setAttribute('aria-label', 'Ask iTechOrbit AI'); launch.setAttribute('aria-expanded', 'false'); launch.setAttribute('aria-controls', 'ai-panel');
  const panel = make('section', '', 'panel'); panel.id = 'ai-panel'; panel.hidden = true; panel.setAttribute('role', 'region'); panel.setAttribute('aria-label', 'iTechOrbit AI website assistant');
  const header = make('header'); const avatar = make('div', '', 'avatar'); avatar.append(icon('spark'));
  const heading = make('div', '', 'heading'); heading.append(make('strong', 'iTechOrbit AI'), make('small', script.dataset.subtitle || 'Your website assistant'));
  const actions = make('div', '', 'actions');
  const reset = make('button', '', 'icon-button'); reset.append(icon('reset')); reset.type = 'button'; reset.title = 'Reset conversation'; reset.setAttribute('aria-label', 'Reset conversation');
  const close = make('button', '', 'icon-button'); close.append(icon('close')); close.type = 'button'; close.title = 'Close assistant'; close.setAttribute('aria-label', 'Close assistant'); actions.append(reset, close); header.append(avatar, heading, actions);
  const thread = make('div', '', 'thread'); thread.setAttribute('role', 'log'); thread.setAttribute('aria-live', 'polite'); thread.setAttribute('aria-relevant', 'additions');
  const status = make('div', '', 'status'); status.setAttribute('role', 'status');
  const dots = make('span', '', 'dots'); dots.setAttribute('aria-hidden', 'true'); dots.append(make('i'), make('i'), make('i')); status.append(dots, make('span', 'Finding an answer…'));
  const composer = make('div', '', 'composer-area');
  const form = make('form'); const input = make('textarea'); input.rows = 1; input.maxLength = 2000; input.placeholder = 'Ask about this website…'; input.setAttribute('aria-label', 'Your question'); input.required = true;
  const send = make('button', '', 'send'); send.append(icon('send')); send.type = 'submit'; send.title = 'Send message'; send.setAttribute('aria-label', 'Send'); form.append(input, send);
  const notice = make('p', '', 'notice'); const noticeText = make('span', 'AI answers may be imperfect. Chats are stored for 30 days. Do not share sensitive information.'); notice.append(icon('shield'), noticeText);
  composer.append(form, notice); panel.append(header, thread, composer); root.append(panel, launch); document.body.append(host);
  const media = window.matchMedia('(prefers-color-scheme: dark)');
  const updateTheme = () => host.dataset.theme = ['light', 'dark'].includes(script.dataset.theme) ? script.dataset.theme : media.matches ? 'dark' : 'light';
  updateTheme(); media.addEventListener('change', updateTheme);
  const themeObserver = new MutationObserver(updateTheme); themeObserver.observe(script, { attributes: true, attributeFilter: ['data-theme'] });
  const setAccent = color => {
    if (!/^#[0-9a-f]{6}$/i.test(color)) return;
    const rgb = [1, 3, 5].map(start => parseInt(color.slice(start, start + 2), 16) / 255).map(n => n <= .04045 ? n / 12.92 : ((n + .055) / 1.055) ** 2.4);
    const luminance = rgb[0] * .2126 + rgb[1] * .7152 + rgb[2] * .0722;
    host.style.setProperty('--ai-color', color); host.style.setProperty('--ai-on-accent', luminance > .179 ? '#111111' : '#ffffff');
  };
  const colorObserver = new MutationObserver(() => setAccent(script.dataset.color || config?.color));
  colorObserver.observe(script, { attributes: true, attributeFilter: ['data-color'] });
  const viewport = window.visualViewport;
  const resize = () => {
    const mobile = window.innerWidth <= 480;
    const visibleHeight = viewport?.height ?? window.innerHeight;
    const keyboardOffset = Math.max(0, window.innerHeight - visibleHeight - (viewport?.offsetTop ?? 0));
    const keyboardOpen = mobile && keyboardOffset > 120;
    host.style.bottom = keyboardOpen ? `${keyboardOffset + 8}px` : '';
    launch.style.visibility = keyboardOpen && !panel.hidden ? 'hidden' : '';
    panel.style.bottom = keyboardOpen ? '0px' : '';
    host.style.setProperty('--ai-available-height', `${Math.max(160, visibleHeight - (keyboardOpen ? 24 : mobile ? 96 : 112))}px`);
  };
  viewport?.addEventListener('resize', resize); viewport?.addEventListener('scroll', resize); window.addEventListener('resize', resize); resize();
  const growInput = () => { input.style.height = 'auto'; input.style.height = `${Math.min(100, input.scrollHeight)}px`; };
  input.addEventListener('input', growInput);
  input.addEventListener('keydown', event => { if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) { event.preventDefault(); form.requestSubmit(); } });
  let config = null; let session = null; let busy = false; let controller = null;
  const api = async (path, method = 'GET', data, signal) => {
    const response = await fetch(`${backend}/api/ai-pro/v1/sites/${encodeURIComponent(site)}/${path}`, { method, signal, credentials: 'omit', headers: { Accept: 'application/json', ...(data ? { 'Content-Type': 'application/json' } : {}), ...(session ? { Authorization: `Bearer ${session.token}` } : {}) }, body: data ? JSON.stringify(data) : undefined });
    const json = await response.json();
    if (!response.ok) {
      if (response.status === 403) session = null;
      throw new Error(response.status === 429 ? 'The question limit has been reached. Please try again later.' : json.message || 'The assistant is unavailable. Please try again.');
    }
    return json;
  };
  const safeUrl = value => { try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) ? url.href : null; } catch { return null; } };
  const bubble = (text, type, sources = []) => {
    const message = make('div', '', 'message'); message.append(make('span', type === 'question' ? 'You' : 'iTechOrbit AI', 'message-label'));
    const node = make('div', text, `bubble ${type}`);
    const links = make('div', '', 'sources');
    for (const source of sources) {
      const href = safeUrl(source.url); if (!href) continue;
      const link = make('a'); link.append(icon('source'), make('span', source.title), icon('arrow')); link.href = href; link.target = '_blank'; link.rel = 'noopener noreferrer'; links.append(link);
    }
    if (links.childNodes.length) node.append(links);
    message.append(node); thread.append(message); thread.scrollTop = thread.scrollHeight;
  };
  const initial = () => {
    panel.classList.remove('conversing'); thread.replaceChildren();
    const intro = make('div', '', 'intro'); intro.append(make('p', 'Your website, explained', 'eyebrow'), make('h2', 'How can I help?'), make('p', config?.welcome || 'Hi! Ask me about this website.')); thread.append(intro);
    const suggestions = make('div', '', 'suggestions');
    for (const text of config?.suggestions || []) { const button = make('button'); button.append(make('span', text), icon('arrow')); button.type = 'button'; button.onclick = () => { input.value = text; form.requestSubmit(); }; suggestions.append(button); }
    thread.append(suggestions);
  };
  const setOpen = open => { panel.hidden = !open; launch.setAttribute('aria-expanded', String(open)); resize(); if (open && window.innerWidth > 480) input.focus({ preventScroll: true }); else if (open) close.focus({ preventScroll: true }); else launch.focus({ preventScroll: true }); };
  launch.onclick = () => setOpen(panel.hidden); close.onclick = () => setOpen(false);
  const onKeydown = event => { if (event.key === 'Escape' && !panel.hidden) { event.preventDefault(); setOpen(false); } };
  document.addEventListener('keydown', onKeydown);
  reset.onclick = () => { controller?.abort(); controller = null; session = null; busy = false; send.disabled = false; reset.disabled = false; status.remove(); initial(); input.value = ''; growInput(); input.focus({ preventScroll: true }); };
  form.onsubmit = async event => {
    event.preventDefault(); const question = input.value.trim(); if (!question || busy) return;
    busy = true; send.disabled = true; input.value = ''; growInput(); panel.classList.add('conversing'); thread.querySelector('.suggestions')?.remove(); thread.querySelector('.intro')?.remove(); bubble(question, 'question');
    thread.append(status); thread.scrollTop = thread.scrollHeight; const current = new AbortController(); controller = current;
    try {
      if (!session) { const created = await api('conversations', 'POST', {}, current.signal); if (current.signal.aborted) return; session = created; }
      const result = await api('messages', 'POST', { question, conversation_id: session.conversation_id }, current.signal);
      if (current.signal.aborted) return;
      bubble(result.answer, 'answer', [...result.sources, ...(result.status === 'unsupported' && result.contact_url ? [{ title: 'Contact this website', url: result.contact_url }] : [])]);
    } catch (error) {
      if (!current.signal.aborted) bubble(error instanceof Error ? error.message : 'The assistant is unavailable.', 'answer error', config?.contact_url ? [{ title: 'Contact this website', url: config.contact_url }] : []);
    } finally {
      if (controller === current) { busy = false; send.disabled = false; status.remove(); if (!panel.hidden && window.innerWidth > 480) input.focus({ preventScroll: true }); }
    }
  };
  initial();
  api('config').then(value => { if (!host.isConnected) return; config = value; setAccent(script.dataset.color || value.color); heading.lastChild.textContent = script.dataset.subtitle || value.website; noticeText.textContent = value.notice; if (!busy && !session) initial(); }).catch(() => { if (!busy && !session) { thread.replaceChildren(); bubble('iTechOrbit AI is currently unavailable. Please try again later.', 'answer error'); } });
  // Let integrations dispose of pending requests when their host page unmounts.
  host.addEventListener('ai-pro:dispose', () => { controller?.abort(); document.removeEventListener('keydown', onKeydown); media.removeEventListener('change', updateTheme); themeObserver.disconnect(); colorObserver.disconnect(); viewport?.removeEventListener('resize', resize); viewport?.removeEventListener('scroll', resize); window.removeEventListener('resize', resize); host.remove(); });
})();
