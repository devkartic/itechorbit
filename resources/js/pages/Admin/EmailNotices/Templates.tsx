import { useRef, useState } from 'react';
import { Plus, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { CkEditorField } from '@/components/admin/CkEditorField';
import { requestJson } from '@/lib/http';
import { TemplateLibrary } from '@/components/admin/mail-notices/TemplateLibrary';
import { base, noticeLayout, openTemplate, useNoticeWorkspace, useTemplateField, type NoticeProps, type Template } from '@/components/admin/mail-notices/workspace';

export default function Templates({ installed }: NoticeProps) {
  const { compose, setCompose, actions: { busy, perform, setNotice, errorFor } } = useNoticeWorkspace();
  const [editTemplate, setEditTemplate] = useTemplateField('editTemplate'); const [templateName, setTemplateName] = useTemplateField('templateName');
  const [templateSubject, setTemplateSubject] = useTemplateField('templateSubject'); const [templateBody, setTemplateBody] = useTemplateField('templateBody');
  const nameInput = useRef<HTMLInputElement>(null);
  const focusEditor = () => requestAnimationFrame(() => nameInput.current?.focus());
  const [revision, setRevision] = useState(0); const [deleting, setDeleting] = useState<Template | null>(null);
  async function loadTemplate(id: number) {
    const result = await perform(() => requestJson<Template>(`${base}/templates/${id}`, 'GET'));
    if (result) { setEditTemplate(result); setTemplateName(result.name); setTemplateSubject(result.subject); setTemplateBody(result.body ?? ''); focusEditor(); }
  }
  async function saveTemplate() {
    const result = await perform(() => requestJson<{ success: string; template: Template }>(`${base}/templates${editTemplate ? `/${editTemplate.id}` : ''}`, editTemplate ? 'PUT' : 'POST', { name: templateName, subject: templateSubject, body: templateBody }));
    if (result) { setEditTemplate(result.template); setNotice(result.success); setRevision(value => value + 1); }
  }
  return <>
    <section aria-label="Manage templates" className="notice-grid"><section className="workspace-card notice-card"><div className="notice-heading"><h2>Template library</h2><Button type="button" size="sm" variant="outline" disabled={busy || !installed} onClick={() => { focusEditor(); setEditTemplate(null); setTemplateName(''); setTemplateSubject(''); setTemplateBody(''); }}><Plus size={14} />New template</Button></div><TemplateLibrary installed={installed} revision={revision} onUse={openTemplate} onEdit={id => void loadTemplate(id)} onDelete={setDeleting} /></section><section className="workspace-card notice-card"><h2>{editTemplate ? 'Edit template' : 'Create template'}</h2><p className="notice-help">Templates are shared between Admin and Super Admin. The greeting and branding are added automatically.</p><form className="notice-form" onSubmit={event => { event.preventDefault(); void saveTemplate(); }}><label className="form-label" htmlFor="notice-template-name">Template name</label><Input ref={nameInput} id="notice-template-name" maxLength={100} required disabled={busy || !installed} value={templateName} onChange={event => setTemplateName(event.target.value)} /><p className="form-error">{errorFor('name')}</p><label className="form-label" htmlFor="notice-template-subject">Template subject</label><Input id="notice-template-subject" maxLength={255} required disabled={busy || !installed} value={templateSubject} onChange={event => setTemplateSubject(event.target.value)} /><p className="form-error">{errorFor('subject')}</p><label className="form-label" htmlFor="notice-template-body">Template message</label><CkEditorField id="notice-template-body" label="Template message" value={templateBody} disabled={busy || !installed} invalid={!!errorFor('body')} onChange={setTemplateBody} /><p className="form-error">{errorFor('body')}</p><Button disabled={busy || !installed}><Save size={15} />{busy ? 'Saving…' : 'Save template'}</Button></form></section></section>
    <Dialog open={deleting !== null} title="Delete this template?" onClose={() => { if (!busy) setDeleting(null); }}><p className="notice-help">Queued and completed notices retain their saved message.</p><div className="notice-dialog-actions"><Button type="button" variant="outline" disabled={busy} onClick={() => setDeleting(null)}>Cancel</Button><Button type="button" variant="destructive" disabled={busy} onClick={async () => { if (!deleting) return; const result = await perform(() => requestJson<{ success: string }>(`${base}/templates/${deleting.id}`, 'DELETE')); if (result) { setNotice(result.success); if (editTemplate?.id === deleting.id) { setEditTemplate(null); setTemplateName(''); setTemplateSubject(''); setTemplateBody(''); } if (compose.templateId === deleting.id) setCompose(previous => ({ ...previous, templateId: null, sourceTemplate: null })); setDeleting(null); setRevision(value => value + 1); } }}>Delete template</Button></div></Dialog>
  </>;
}
Templates.layout = noticeLayout;
