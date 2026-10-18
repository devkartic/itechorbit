import { Sparkles, Globe2, LayoutDashboard, BookOpen, RefreshCw, MessagesSquare, Code2, CheckCircle2, AlertCircle, Inbox } from 'lucide-react';
import '../../../../css/ai-pro.css';
import { useCallback, useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout, { type Navigation } from '@/layouts/admin/AdminLayout';
import { requestJson } from '@/lib/http';

export type Site = { id: number; public_id: string; name: string; url: string; origins: string[]; enabled: boolean; is_local: boolean; welcome: string; color: string; contact_url: string | null; minute_limit: number; daily_limit: number };
export type Source = { id: number; kind: 'faq' | 'page' | 'sitemap' | 'local'; title: string; url: string; content: string; enabled: boolean; status: string };
export type Page<T> = { data: T[]; current_page: number; last_page: number; total: number };
export type Run = { id: number; status: string; processed: number; errors: string[] | null; created_at: string };
export type Conversation = { id: number; created_at: string; expires_at: string };
export type Message = { id: number; question: string; answer: string | null; status: string; created_at: string };
type Data = { sites: Site[]; selected: number | null; sources?: Page<Source>; runs?: Page<Run>; recentRuns?: Run[]; conversations?: Page<Conversation>; counts?: { sources: number; ready: number; conversations: number; questionsToday: number } };
export type Section = 'overview' | 'websites' | 'knowledge' | 'synchronization' | 'conversations' | 'installation';
export type Props = { navigation: Navigation; installed: boolean; providerReady: boolean; enabled: boolean; provider: string; embeddingProvider: string; model: string; embeddingModel: string; backendUrl: string; widgetVersion: string; selection: { site?: number; page?: number; conversation?: number; message_page?: number } };
export const base = '/workspace/ai-pro';
export const input = 'form-control w-full';
export const sections: [Section, string][] = [['overview', 'Overview'], ['websites', 'Websites'], ['knowledge', 'Knowledge'], ['synchronization', 'Synchronization'], ['conversations', 'Conversations'], ['installation', 'Installation & Providers']];
const blank = { name: '', url: 'https://', origins: '', enabled: false, welcome: 'Hi! Ask me about this website.', color: '#6844cf', contact_url: '', minute_limit: 10, daily_limit: 500 };
export const emptySource = { id: 0, kind: 'faq' as 'faq' | 'page' | 'sitemap', title: '', url: '', content: '', enabled: true };

export function useWorkspace(props: Props, section: Section) {
  const [data, setData] = useState<Data>({ sites: [], selected: null });
  const [revision, setRevision] = useState(0); const [notice, setNotice] = useState(''); const [failed, setFailed] = useState(false); const [busy, setBusy] = useState(false);
  const [form, setForm] = useState(blank); const [editing, setEditing] = useState<number | null>(null); const [source, setSource] = useState(emptySource);
  const [messages, setMessages] = useState<Page<Message> | null>(null);
  const selected = props.selection.site; const page = props.selection.page || 1;
  const conversation = props.selection.conversation; const messagePage = props.selection.message_page || 1;
  const site = data.sites.find(item => item.id === data.selected); const siteId = site?.id;
  const navigate = (query: Record<string, number | undefined>) => router.get(`${base}/${section}`, { ...props.selection, site: siteId, ...query }, { preserveScroll: true });
  useEffect(() => {
    if (!props.installed) return;
    const controller = new AbortController();
    requestJson<Data>(`${base}/data?${new URLSearchParams({ section, ...(selected ? { site: String(selected) } : {}), page: String(page) })}`, 'GET', undefined, controller.signal)
      .then(setData).catch(error => { if (!controller.signal.aborted) { setFailed(true); setNotice(error.message); } });
    return () => controller.abort();
  }, [props.installed, selected, page, section, revision]);
  const activeRun = section === 'synchronization' && !!data.runs?.data.some(run => ['queued', 'running'].includes(run.status));
  useEffect(() => {
    if (!activeRun) return;
    const timer = setInterval(() => setRevision(n => n + 1), 5000);
    return () => clearInterval(timer);
  }, [activeRun]);
  useEffect(() => {
    if (section !== 'conversations' || !conversation || !siteId) return;
    const controller = new AbortController();
    requestJson<Page<Message>>(`${base}/sites/${siteId}/conversations/${conversation}?page=${messagePage}`, 'GET', undefined, controller.signal)
      .then(setMessages).catch(error => { if (!controller.signal.aborted) { setFailed(true); setNotice(error.message); } });
    return () => controller.abort();
  }, [section, conversation, siteId, messagePage]);
  async function perform(action: () => Promise<unknown>, success: string) {
    setBusy(true); setNotice(''); setFailed(false);
    try { await action(); setRevision(n => n + 1); setNotice(success); return true; }
    catch (error) { setFailed(true); setNotice(error instanceof Error ? error.message : 'The action could not be completed.'); return false; }
    finally { setBusy(false); }
  }
  async function saveSite(event: FormEvent) {
    event.preventDefault();
    await perform(async () => {
      const result = await requestJson<{ id: number }>(`${base}/sites${editing ? `/${editing}` : ''}`, editing ? 'PUT' : 'POST', { ...form, origins: form.origins.split('\n').map(v => v.trim()).filter(Boolean), contact_url: form.contact_url || null });
      setEditing(result.id); router.get(`${base}/websites`, { site: result.id }, { preserveState: true, preserveScroll: true });
    }, 'Website saved.');
  }
  async function saveSource(event: FormEvent) {
    event.preventDefault(); if (!site) return;
    if (await perform(() => requestJson(`${base}/sites/${site.id}/sources${source.id ? `/${source.id}` : ''}`, source.id ? 'PUT' : 'POST', { ...source, url: source.url || null }), 'Knowledge saved. Synchronization queued.')) setSource(emptySource);
  }
  const editSite = useCallback((item: Site) => { setEditing(item.id); setForm({ name: item.name, url: item.url, origins: item.origins.join('\n'), enabled: item.enabled, welcome: item.welcome, color: item.color, contact_url: item.contact_url || '', minute_limit: item.minute_limit, daily_limit: item.daily_limit }); }, []);
  const embed = site ? `<script src="${props.backendUrl}/ai-pro/widget.js?v=${props.widgetVersion}" data-site="${site.public_id}" data-theme="auto" defer></script>` : '';
  return { data, site, busy, notice, failed, setNotice, perform, form, setForm, editing, setEditing, source, setSource, messages, page, messagePage, conversation, saveSite, saveSource, editSite, embed,
    newSite: () => { setEditing(null); setForm(blank); },
    selectSite: (id: number) => router.get(`${base}/${section}`, { site: id }),
    setPage: (value: number | ((p: number) => number)) => navigate({ page: typeof value === 'function' ? value(page) : value }),
    selectConversation: (id: number) => { setMessages(null); navigate({ conversation: id, message_page: 1 }); },
    setMessagePage: (value: number | ((p: number) => number)) => navigate({ message_page: typeof value === 'function' ? value(messagePage) : value }),
  };
}

export function WorkspaceLayout({ props, section, workspace, children }: { props: Props; section: Section; workspace: ReturnType<typeof useWorkspace>; children: ReactNode }) {
  const title = sections.find(([key]) => key === section)![1];
  const { data, site, notice, failed, selectSite } = workspace;
  return <AdminLayout title={`iTechOrbit AI · ${title}`} navigation={props.navigation.map(item => item.url.startsWith(`${base}/`) && (site?.id || props.selection.site) ? { ...item, url: `${item.url}?site=${site?.id || props.selection.site}` } : item)}><div className="ai-pro-workspace">
    <div className="ai-pro-heading"><div><span className="ai-pro-eyebrow">AI Web Assistance</span><h1>iTechOrbit AI</h1><p>{title}</p></div><span className="ai-pro-badge"><Sparkles aria-hidden="true" />Website assistant</span></div>
    <nav className="ai-pro-tabs" aria-label="iTechOrbit AI pages">{sections.map(([key, label]) => <Link key={key} href={`${base}/${key}${site ? `?site=${site.id}` : props.selection.site ? `?site=${props.selection.site}` : ''}`} aria-current={section === key ? 'page' : undefined}>{(() => { const Icon = sectionIcons[key]; return <Icon aria-hidden="true" />; })()}{label}</Link>)}</nav>
    <div role="status" aria-live="polite">{notice && <div className="ai-pro-notice" data-tone={failed ? 'danger' : 'positive'}>{failed ? <AlertCircle aria-hidden="true" /> : <CheckCircle2 aria-hidden="true" />}<span>{notice}</span></div>}</div>
    {!props.installed ? <section className="workspace-card ai-pro-card"><h2>Setup required</h2><p>Apply the iTechOrbit AI migration, then register this website with the setup command.</p></section> : <>
      {(!props.providerReady || !props.enabled) && <section className="workspace-card ai-pro-card ai-pro-warning"><AlertCircle aria-hidden="true" /><div><h2>Configuration pending</h2><p>{!props.providerReady && 'The AI provider configuration is incomplete. '}{!props.enabled && 'iTechOrbit AI is globally disabled. '}Check Installation & Providers.</p></div></section>}
      <div className="ai-pro-selector"><Globe2 aria-hidden="true" /><label className="form-label ai-pro-grow" htmlFor="ai-pro-site">Website<select id="ai-pro-site" aria-label="Website" className={input} value={data.selected || ''} onChange={event => selectSite(Number(event.target.value))}>{!data.sites.length && <option value="">No websites yet</option>}{data.sites.map(item => <option key={item.id} value={item.id}>{item.name}{item.is_local ? ' · This project' : ''}</option>)}</select></label></div>
      {children}
    </>}
  </div></AdminLayout>;
}

export function Pagination({ page, change, label = 'items' }: { page?: Page<unknown>; change: (page: number) => void; label?: string }) {
  if (!page || page.last_page <= 1) return null;
  return <div className="ai-pro-pagination"><button className="ai-pro-button" disabled={page.current_page <= 1} onClick={() => change(page.current_page - 1)}>Previous {label}</button><span>Page {page.current_page} of {page.last_page}</span><button className="ai-pro-button" disabled={page.current_page >= page.last_page} onClick={() => change(page.current_page + 1)}>Next {label}</button></div>;
}

const sectionIcons = { overview: LayoutDashboard, websites: Globe2, knowledge: BookOpen, synchronization: RefreshCw, conversations: MessagesSquare, installation: Code2 };
export function StatusBadge({ status }: { status: string }) {
  const tone = ['ready', 'completed', 'answered', 'enabled'].includes(status) ? 'positive' : ['failed', 'unavailable'].includes(status) ? 'danger' : ['queued', 'running', 'pending'].includes(status) ? 'accent' : ['partial', 'quota_exceeded'].includes(status) ? 'warning' : 'neutral';
  return <span className="ai-pro-status" data-tone={tone}>{status}</span>;
}
export function EmptyState({ children }: { children: ReactNode }) {
  return <div className="ai-pro-empty"><Inbox aria-hidden="true" />{children}</div>;
}
