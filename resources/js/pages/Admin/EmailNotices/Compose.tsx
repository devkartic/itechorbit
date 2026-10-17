import { useEffect, useState } from 'react';
import { flushSync } from 'react-dom';
import { router } from '@inertiajs/react';
import { CheckCircle2, Eye, FileText, LoaderCircle, Send, TriangleAlert, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { CkEditorField } from '@/components/admin/CkEditorField';
import { requestJson } from '@/lib/http';
import { TemplateLibrary } from '@/components/admin/mail-notices/TemplateLibrary';
import { base, emptyPage, emptyCompose, key, noticeLayout, Pagination, useComposeField, useNoticeWorkspace, type NoticeProps, type Person, type Page, type Template } from '@/components/admin/mail-notices/workspace';

export default function Compose({ installed, sendingReady, eligibleCount, initialTemplate }: NoticeProps & { initialTemplate: Template | null }) {
  const { setCompose, actions: { busy, perform, setNotice, setFailed, errorFor } } = useNoticeWorkspace();
  const [subject, setSubject] = useComposeField('subject'); const [body, setBody] = useComposeField('body'); const [templateId] = useComposeField('templateId');
  const [audience, setAudience] = useComposeField('audience'); const [selected, setSelected] = useComposeField('selected'); const [submissionKey, setSubmissionKey] = useComposeField('submissionKey');
  const [people, setPeople] = useState<Page<Person>>(emptyPage); const [personQuery, setPersonQuery] = useState(''); const [personPage, setPersonPage] = useState(1);
  const [operation, setOperation] = useState<'template' | 'preview' | 'send' | null>(null);
  const [preview, setPreview] = useState(''); const [previewStamp, setPreviewStamp] = useState('');
  const signature = JSON.stringify([subject, body, audience, Object.keys(selected).sort(), templateId]);
  const count = audience === 'all' ? eligibleCount : Object.keys(selected).length;
  useEffect(() => {
    if (initialTemplate) setCompose(previous => previous.sourceTemplate === initialTemplate.id ? previous : { ...previous, subject: initialTemplate.subject, body: initialTemplate.body ?? '', templateId: initialTemplate.id, sourceTemplate: initialTemplate.id });
  }, [initialTemplate, setCompose]);
  useEffect(() => {
    if (!installed || audience !== 'selected') return;
    const controller = new AbortController();
    const timer = setTimeout(() => { requestJson<Page<Person>>(`${base}/recipients?key=${encodeURIComponent(personQuery)}&page=${personPage}`, 'GET', undefined, controller.signal).then(setPeople).catch(error => { if (!controller.signal.aborted) { setNotice(error.message); setFailed(true); } }); }, 250);
    return () => { clearTimeout(timer); controller.abort(); };
  }, [installed, audience, personQuery, personPage, setNotice, setFailed]);
  async function loadTemplate(id: number) {
    setOperation('template');
    const result = await perform(() => requestJson<Template>(`${base}/templates/${id}`, 'GET'));
    if (result) setCompose(previous => ({ ...previous, subject: result.subject, body: result.body ?? '', templateId: result.id, sourceTemplate: result.id }));
    setOperation(null);
  }
  async function showPreview() {
    if (!count) { setNotice('Select at least one eligible recipient.'); setFailed(true); return; }
    setOperation('preview');
    const result = await perform(() => requestJson<{ html: string }>(`${base}/preview`, 'POST', { subject, body }));
    if (result) { setPreview(result.html); setPreviewStamp(signature); }
    setOperation(null);
  }
  async function send() {
    setOperation('send');
    const stableKey = submissionKey || key(); setSubmissionKey(stableKey);
    const result = await perform(() => requestJson<{ success: string; campaign_id: number }>(`${base}/send`, 'POST', { subject, body, audience, user_ids: Object.keys(selected).map(Number), template_id: templateId, submission_key: stableKey }));
    setOperation(null);
    if (!result) return;
    flushSync(() => { setCompose(emptyCompose()); setNotice(result.success); });
    router.visit(`${base}/history?campaign=${result.campaign_id}`);
  }
  const previewCurrent = !!preview && previewStamp === signature;
  const readyToSend = installed && sendingReady && count > 0 && previewCurrent && !busy;

  return (
    <section aria-label="Compose notice" className="notice-compose">
      <div className="notice-grid">
        <div className="notice-stack">
          <section className="workspace-card notice-card">
            <div className="notice-compose-card-title"><span className="notice-compose-step" aria-hidden="true">1</span><h2>Message</h2></div>
            <p className="notice-help">Write your update or start with a saved template.</p>
            <details className="notice-template-picker">
              <summary><FileText size={16} />Start from a template</summary>
              <p className="notice-help">Choose a reusable message, then customize the copy below.</p>
              <TemplateLibrary installed={installed} onUse={id => void loadTemplate(id)} />
            </details>
            <label className="form-label" htmlFor="notice-subject">Subject</label>
            <Input id="notice-subject" value={subject} placeholder="e.g. Scheduled maintenance update" maxLength={255} disabled={busy || !installed} onChange={event => setSubject(event.target.value)} aria-invalid={!!errorFor('subject')} />
            <p className="form-error">{errorFor('subject')}</p>
            <label className="form-label" htmlFor="notice-body">Message</label>
            <CkEditorField id="notice-body" label="Notice message" value={body} disabled={busy || !installed} invalid={!!errorFor('body')} onChange={setBody} />
            <p className="form-error">{errorFor('body')}</p>
          </section>

          <section className="workspace-card notice-card">
            <div className="notice-compose-card-title"><span className="notice-compose-step" aria-hidden="true">2</span><h2>Recipients</h2></div>
            <p className="notice-help">Choose active users with verified email addresses. Each recipient receives an individual email.</p>
            <fieldset className="notice-audience" disabled={busy || !installed}>
              <legend className="sr-only">Choose recipients</legend>
              <label className={audience === 'all' ? 'is-selected' : ''}>
                <input type="radio" name="notice-audience" aria-label={`All eligible users (${eligibleCount})`} checked={audience === 'all'} onChange={() => setAudience('all')} />
                <span><strong>All eligible users ({eligibleCount})</strong><small>Send to everyone eligible.</small></span>
              </label>
              <label className={audience === 'selected' ? 'is-selected' : ''}>
                <input type="radio" name="notice-audience" aria-label="Selected users" checked={audience === 'selected'} onChange={() => setAudience('selected')} />
                <span><strong>Selected users</strong><small>Choose specific recipients.</small></span>
              </label>
            </fieldset>
            {audience === 'selected' && <>
              <label className="form-label" htmlFor="notice-recipient-search">Find users by name or email</label>
              <Input id="notice-recipient-search" type="search" maxLength={100} value={personQuery} disabled={busy || !installed} onChange={event => { setPersonQuery(event.target.value); setPersonPage(1); }} />
              <div className="notice-selected" aria-label="Selected recipients">{Object.values(selected).map(person => <span key={person.id}>{person.name}<button type="button" disabled={busy} aria-label={`Remove recipient ${person.name}`} onClick={() => setSelected(previous => { const next = { ...previous }; delete next[person.id]; return next; })}><X size={13} /></button></span>)}</div>
              <ul className="notice-recipient-list">{people.data.map(person => <li key={person.id}><label><input type="checkbox" disabled={busy} checked={!!selected[person.id]} onChange={event => setSelected(previous => { const next = { ...previous }; if (event.target.checked) next[person.id] = person; else delete next[person.id]; return next; })} /><span><strong>{person.name}</strong><small>{person.email}</small></span></label></li>)}</ul>
              {!people.total && <p className="notice-help">No eligible users match your search.</p>}
              <Pagination page={people} change={setPersonPage} />
            </>}
            <p className="form-error">{errorFor('user_ids')}</p>
            <p className="notice-recipient-count" role="status">{count} {count === 1 ? 'recipient' : 'recipients'} {audience === 'all' ? 'in all eligible users' : 'selected'} · duplicate email addresses receive one copy.</p>
          </section>
        </div>

        <aside className="notice-stack" aria-label="Review and send notice">
          <section className="workspace-card notice-card notice-compose-review">
            <div className="notice-compose-card-title"><span className="notice-compose-step" aria-hidden="true">3</span><h2>Review &amp; send</h2></div>
            <p className="notice-help">Check the message and audience before sending.</p>
            <dl className="notice-compose-summary">
              <div className="notice-compose-summary-subject"><dt>Subject</dt><dd>{subject.trim() || 'Add a subject'}</dd></div>
              <div><dt>Audience</dt><dd>{audience === 'all' ? 'All eligible users' : 'Selected users'}</dd></div>
              <div><dt>Recipients</dt><dd><strong>{count}</strong></dd></div>
            </dl>
            <Button type="button" variant="outline" className="notice-compose-action is-preview" aria-busy={operation === 'preview'} disabled={busy || !installed || !count} onClick={() => void showPreview()}>
              {operation === 'preview' ? <LoaderCircle size={16} className="notice-compose-spinner" aria-hidden="true" /> : <Eye size={16} aria-hidden="true" />}{operation === 'preview' ? 'Previewing…' : 'Preview email'}
            </Button>
            {preview && <>
              <iframe title="Notice email preview" sandbox="" srcDoc={preview} className="notice-preview" />
              <p className="notice-help notice-compose-preview-note">Each recipient sees a personalized greeting.</p>
            </>}
            <div aria-live="polite" className="notice-compose-feedback-area">
              {preview && !previewCurrent ? <p className="notice-compose-feedback is-warning"><TriangleAlert size={16} aria-hidden="true" /><span>The message or recipients changed. Preview again before sending.</span></p>
                : readyToSend ? <p className="notice-compose-feedback is-ready"><CheckCircle2 size={16} aria-hidden="true" /><span>Preview is up to date. Ready to send.</span></p>
                  : <p className="notice-compose-guidance">{operation === 'send' ? 'Your notice is being queued.' : !count ? 'Choose at least one recipient to continue.' : !sendingReady ? 'Email sending is currently unavailable.' : 'Preview this notice to enable sending.'}</p>}
            </div>
            <Button type="button" className="notice-compose-action is-send" aria-busy={operation === 'send'} disabled={!readyToSend} onClick={() => void send()}>
              {operation === 'send' ? <LoaderCircle size={16} className="notice-compose-spinner" aria-hidden="true" /> : <Send size={16} aria-hidden="true" />}{operation === 'send' ? 'Sending…' : 'Send notice'}
            </Button>
            <p className="notice-help notice-compose-delivery-note">Sent means accepted by the mail transport. After a connection error, check Send history before starting another notice.</p>
          </section>
        </aside>
      </div>
    </section>
  );
}
Compose.layout = noticeLayout;
