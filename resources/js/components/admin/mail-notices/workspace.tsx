import { createContext, useCallback, useContext, useEffect, useState, type Dispatch, type SetStateAction, type ReactNode } from 'react';
import { Link, router, usePage, useRemember } from '@inertiajs/react';
import { FileText, Mail, Send } from 'lucide-react';
import AdminLayout, { type Navigation } from '@/layouts/admin/AdminLayout';
import { Notice } from '@/components/admin/Notice';
import { RequestError } from '@/lib/http';
import type { Shared } from '@/lib/types';

export type Person = { id: number; name: string; email: string };
export type Template = { id: number; name: string; subject: string; body?: string };
export type Status = 'queued' | 'sending' | 'sent' | 'failed' | 'unconfirmed';
export type Counts = Record<Status | 'total', number>;
export type Campaign = { id: number; subject: string; audience: string; created_at: string; counts: Counts };
export type Recipient = Person & { status: Status; attempts: number; last_error: string | null; sent_at: string | null };
export type Page<T> = { data: T[]; total: number; current_page: number; last_page: number };
export type Detail = { campaign: Omit<Campaign, 'counts'>; counts: Counts; recipients: Page<Recipient> };
export const base = '/workspace/email-notices';
export const statuses: Status[] = ['queued', 'sending', 'sent', 'failed', 'unconfirmed'];
export const label = (status: string) => status[0].toUpperCase() + status.slice(1);
export const emptyPage = <T,>(): Page<T> => ({ data: [], total: 0, current_page: 1, last_page: 1 });
export const key = () => typeof crypto.randomUUID === 'function' ? crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => { const n = Math.floor(Math.random() * 16); return (c === 'x' ? n : (n & 3) | 8).toString(16); });


export type NoticeProps = { navigation: Navigation; installed: boolean; sendingReady: boolean; eligibleCount: number; section: 'compose' | 'templates' | 'history' };
export type ComposeDraft = { subject: string; body: string; templateId: number | null; sourceTemplate: number | null; audience: 'all' | 'selected'; selected: Record<number, Person>; submissionKey: string };
export type TemplateDraft = { editTemplate: Template | null; templateName: string; templateSubject: string; templateBody: string };
export const emptyCompose = (): ComposeDraft => ({ subject: '', body: '', templateId: null, sourceTemplate: null, audience: 'all', selected: {}, submissionKey: '' });
export const emptyTemplate = (): TemplateDraft => ({ editTemplate: null, templateName: '', templateSubject: '', templateBody: '' });
function useActions() {
  const [busy, setBusy] = useState(false); const [notice, setNotice] = useState(''); const [failed, setFailed] = useState(false);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const perform = useCallback(async <T,>(action: () => Promise<T>): Promise<T | null> => {
    setBusy(true); setNotice(''); setErrors({});
    try { const result = await action(); setFailed(false); return result; }
    catch (error) { setFailed(true); setNotice(error instanceof Error ? error.message : 'Unable to complete this action.'); if (error instanceof RequestError) setErrors(error.errors); return null; }
    finally { setBusy(false); }
  }, []);
  return { busy, notice, setNotice, failed, setFailed, errors, setErrors, perform, errorFor: (field: string) => errors[field]?.join(' ') };
}
type Workspace = { compose: ComposeDraft; setCompose: Dispatch<SetStateAction<ComposeDraft>>; template: TemplateDraft; setTemplate: Dispatch<SetStateAction<TemplateDraft>>; actions: ReturnType<typeof useActions> };
const Context = createContext<Workspace | null>(null);
export function useNoticeWorkspace() {
  const workspace = useContext(Context);
  if (!workspace) throw new Error('Email notice pages require their shared workspace.');
  return workspace;
}
function field<T, K extends keyof T>(draft: T, setDraft: Dispatch<SetStateAction<T>>, name: K): [T[K], Dispatch<SetStateAction<T[K]>>] {
  return [draft[name], value => setDraft(previous => ({ ...previous, [name]: typeof value === 'function' ? (value as (current: T[K]) => T[K])(previous[name]) : value }))];
}
export function useComposeField<K extends keyof ComposeDraft>(name: K) {
  const { compose, setCompose } = useNoticeWorkspace(); return field(compose, setCompose, name);
}
export function useTemplateField<K extends keyof TemplateDraft>(name: K) {
  const { template, setTemplate } = useNoticeWorkspace(); return field(template, setTemplate, name);
}
const sections = {
  compose: { title: 'Compose', description: 'Write a notice, choose your recipients, and review it before sending.', icon: Send },
  templates: { title: 'Templates', description: 'Organize reusable messages and keep future notices consistent.', icon: FileText },
  history: { title: 'Send history', description: 'Find previous notices and track delivery for each recipient.', icon: Mail },
};
export function NoticeWorkspace({ children }: { children: ReactNode }) {
  const { props, url } = usePage<Shared & NoticeProps>();
  const composeKey = `email-notices:${props.auth.user?.id}:compose`;
  const templateKey = `email-notices:${props.auth.user?.id}:template`;
  const [compose, setCompose] = useRemember(emptyCompose(), composeKey);
  const [template, setTemplate] = useRemember(emptyTemplate(), templateKey);
  useEffect(() => {
    // Persistent layouts must also remember drafts in each newly visited history entry.
    router.remember(compose, composeKey);
    router.remember(template, templateKey);
  }, [url, compose, template, composeKey, templateKey]);
  const actions = useActions();
  const section = sections[props.section];
  return <Context.Provider value={{ compose, setCompose, template, setTemplate, actions }}><AdminLayout title={`Email notices · ${section.title}`} navigation={props.navigation}>
    <div className="workspace-page-heading"><div><p className="workspace-eyebrow">Administration · Email notices</p><h1>{section.title}</h1><p>{section.description}</p></div></div>
    <nav className="notice-page-links" aria-label="Email notice pages">{Object.entries(sections).map(([name, item]) => <Link key={name} href={`${base}/${name}`} aria-current={props.section === name ? 'page' : undefined} onClick={() => actions.setErrors({})}><item.icon size={16} />{item.title}</Link>)}</nav>
    {!props.installed && <Notice error message="Apply the email notices migration to enable this page." />}
    {props.installed && !props.sendingReady && <Notice message="Email sending is not configured. Set up a real mail transport and an asynchronous queue. You can still edit templates and preview messages." />}
    <Notice message={actions.notice} error={actions.failed} onDismiss={() => actions.setNotice('')} />
    {children}
  </AdminLayout></Context.Provider>;
}
export const noticeLayout = (page: ReactNode) => <NoticeWorkspace>{page}</NoticeWorkspace>;
export function openTemplate(id: number) { router.visit(`${base}/compose?template=${id}`); }
export function Pagination({ page, change }: { page: Page<unknown>; change: (value: number) => void }) {
  return <div className="notice-pagination"><span>{page.total} results · Page {page.current_page} of {page.last_page}</span><div><button type="button" disabled={page.current_page <= 1} onClick={() => change(page.current_page - 1)}>Previous</button><button type="button" disabled={page.current_page >= page.last_page} onClick={() => change(page.current_page + 1)}>Next</button></div></div>;
}
export function CountSummary({ counts }: { counts: Counts }) {
  return <div className="notice-counts" aria-label="Send progress">{statuses.map(status => <div key={status}><span>{label(status)}</span><strong>{counts[status]}</strong></div>)}</div>;
}
