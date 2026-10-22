import { useState } from 'react';
import { CkEditorField } from './CkEditorField';
import { AttachmentPreview, UploadField } from './UploadField';
import { Link, router } from '@inertiajs/react';
import { FilePenLine, ImagePlus, LoaderCircle, Save, Settings2, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input, Textarea } from '@/components/ui/input';
import { Notice } from './Notice';
import { requestJson, RequestError, type ValidationErrors } from '@/lib/http';
import type { Attachment, Field, Row, Value } from './types';
export function ResourceForm({ resource, title, action, fields, options, record, attachments = {} }: { resource: string; title: string; action: string; fields: Field[]; options: Record<string, { value: string; label: string; disabled?: boolean }[]>; record: Row; attachments?: Record<string, Attachment> }) {
  const [values, setValues] = useState<Row>(() => Object.fromEntries(fields.filter(field => field.type !== 'file').map(field => [field.name, record[field.name] ?? (field.type === 'checkbox' ? field.name === 'is_checked' : field.type === 'multiselect' ? [] : '')])));
  const [searches, setSearches] = useState<Record<string, string>>({});
  const [files, setFiles] = useState<Record<string, File>>({});
  const [errors, setErrors] = useState<ValidationErrors>({});
  const [message, setMessage] = useState(''); const [failed, setFailed] = useState(false); const [busy, setBusy] = useState(false);
  const setValue = (name: string, value: Value) => { setValues(previous => ({ ...previous, [name]: value })); setErrors(previous => Object.fromEntries(Object.entries(previous).filter(([key]) => key !== name && !key.startsWith(`${name}.`)))); setMessage(''); };
  const fieldErrors = (name: string) => Object.entries(errors).filter(([key]) => key === name || key.startsWith(`${name}.`)).flatMap(([, messages]) => messages);
  function showErrors(next: ValidationErrors) { setErrors(next); setFailed(true); setMessage('Please correct the highlighted fields and try again.'); requestAnimationFrame(() => document.getElementById(Object.keys(next)[0]?.split('.')[0])?.focus()); }
  async function save() {
    const invalidUploads = Object.fromEntries(fields.filter(field => field.type === 'file' && errors[field.name]?.length).map(field => [field.name, errors[field.name]]));
    if (Object.keys(invalidUploads).length) { showErrors(invalidUploads); return; }
    setMessage(''); setErrors({});
    const empty = Object.fromEntries(fields.filter(field => field.type === 'richtext' && field.required && !new DOMParser().parseFromString(String(values[field.name] ?? ''), 'text/html').body.textContent?.trim()).map(field => [field.name, [`${field.label} is required.`]]));
    if (Object.keys(empty).length) { showErrors(empty); return; }
    setBusy(true); const data = new FormData();
    Object.entries(values).forEach(([name, value]) => { if (Array.isArray(value)) { if (name === 'technology_ids' && value.length === 0) data.append(name, ''); else value.forEach(item => data.append(`${name}[]`, item)); } else data.append(name, typeof value === 'boolean' ? String(value) : String(value ?? '')); });
    Object.entries(files).forEach(([name, file]) => data.append(name, file)); if (action === 'edit') data.append('_method', 'PUT');
    try { const result = await requestJson(`/${resource}${action === 'edit' ? `/${record.id}` : ''}`, 'POST', data); try { sessionStorage.setItem('workspace-success', JSON.stringify({ resource, message: result.success ?? 'Changes saved successfully.' })); } catch { /* Restricted browser storage. */ } router.visit(`/${resource}`); }
    catch (error) { if (error instanceof RequestError && Object.keys(error.errors).length) showErrors(error.errors); else { setFailed(true); setMessage(error instanceof Error ? error.message : 'Unable to save your changes.'); } }
    finally { setBusy(false); }
  }
  async function removeMedia(field: string) { setBusy(true); try { const result = await requestJson(resource === 'projects' ? '/project-file-delete' : '/basics-file-delete', 'POST', { id: record.id, field_name: field }); setFailed(false); setMessage(result.success ?? 'File removed.'); router.reload({ only: ['record', 'attachments'] }); } catch (error) { setFailed(true); setMessage(error instanceof Error ? error.message : 'Unable to remove file.'); } finally { setBusy(false); } }
  function renderField(item: Field) {
    const invalid = fieldErrors(item.name).length > 0;
    const selection = values[item.name];
    const selectedValues = Array.isArray(selection) ? selection : [];
    const common = { id: item.name, required: item.required, 'aria-invalid': invalid, 'aria-describedby': `${item.name}-error` };
    return <div key={item.name} className={`workspace-field ${['richtext', 'textarea', 'multiselect'].includes(item.type) || item.name === 'title' ? 'is-wide' : ''}`}><label id={`${item.name}-label`} className="form-label" htmlFor={item.name}>{item.label}{item.required ? ' *' : ''}</label>
      {item.type === 'richtext' ? <CkEditorField id={item.name} label={item.label} required={item.required} invalid={invalid} disabled={busy} value={String(values[item.name] ?? '')} onChange={value => setValue(item.name, value)} />
        : item.type === 'textarea' ? <Textarea {...common} value={String(values[item.name] ?? '')} onChange={event => setValue(item.name, event.target.value)} className={item.name === 'codebase' ? 'font-mono text-sm' : ''} />
          : item.type === 'select' ? <select {...common} value={String(values[item.name] ?? '')} className="form-control" onChange={event => setValue(item.name, event.target.value)}><option value="">Select an option</option>{options[item.name]?.map(option => <option value={option.value} key={option.value}>{option.label}</option>)}</select>
            : item.type === 'multiselect' ? <div id={item.name} role="group" aria-labelledby={`${item.name}-label`} className="workspace-choice-list">{item.searchable && <Input type="search" aria-label={`Search ${item.label.toLowerCase()}`} placeholder="Search technologies…" value={searches[item.name] ?? ''} onChange={event => setSearches(previous => ({ ...previous, [item.name]: event.target.value }))} />}{options[item.name]?.length ? options[item.name].filter(option => selectedValues.includes(option.value) || option.label.toLowerCase().includes((searches[item.name] ?? '').toLowerCase())).map(option => <label key={option.value}><input type="checkbox" disabled={option.disabled && !selectedValues.includes(option.value)} checked={selectedValues.includes(option.value)} onChange={event => { const previous = selectedValues; setValue(item.name, event.target.checked ? [...previous, option.value] : previous.filter(value => value !== option.value)); }} /><span>{option.label}</span></label>) : <p>No options available yet.</p>}</div>
              : <Input {...common} type={item.type} step={item.step} value={String(values[item.name] ?? '')} onChange={event => setValue(item.name, event.target.value)} autoComplete={item.type === 'password' ? 'new-password' : undefined} />}
      <p id={`${item.name}-error`} role={invalid ? 'alert' : undefined} className="form-error">{fieldErrors(item.name).join(' ')}</p></div>;
  }
  const media = fields.filter(field => field.type === 'file'); const settings = fields.filter(field => field.type === 'checkbox');
  return <form onSubmit={event => { event.preventDefault(); void save(); }} className="workspace-resource-form" aria-label={`${title} form`} aria-busy={busy}><fieldset className="workspace-form-fieldset" disabled={busy}>
    {message && <div className="workspace-form-notice"><Notice message={message} error={failed} onDismiss={() => setMessage('')} /></div>}
    <section className="workspace-card workspace-form-main"><div className="workspace-card-heading"><span className="workspace-card-icon"><FilePenLine size={20} /></span><div><h2>{action === 'create' ? 'Create a new record' : 'Record details'}</h2><p>Make your changes below. Required fields are marked *.</p></div></div><div className="workspace-form-grid">{fields.filter(field => !['file', 'checkbox'].includes(field.type)).map(renderField)}</div></section>
    <aside className="workspace-form-aside">{settings.length > 0 && <section className="workspace-card"><div className="workspace-card-heading"><Settings2 size={19} /><div><h2>Visibility & settings</h2><p>Choose how this record is used.</p></div></div><div className="workspace-toggle-list">{settings.map(item => <div key={item.name}><label><span>{item.label}</span><input id={item.name} type="checkbox" role="switch" checked={values[item.name] === true || Number(values[item.name]) === 1} onChange={event => setValue(item.name, event.target.checked)} /></label>{fieldErrors(item.name).length > 0 && <p role="alert" className="form-error">{fieldErrors(item.name).join(' ')}</p>}</div>)}</div></section>}
      {media.length > 0 && <section className="workspace-card"><div className="workspace-card-heading"><ImagePlus size={19} /><div><h2>Media & attachments</h2><p>Keep your content looking its best.</p></div></div>{media.map(item => <div key={item.name}><UploadField field={item} file={files[item.name]} disabled={busy} error={fieldErrors(item.name).join(' ')} help={resource === 'projects' && item.name === 'uploaded_image' ? 'Upload a real project screenshot. It appears as a compact, uncropped thumbnail in your portfolio.' : undefined} onChange={(file, error) => {
        setFiles(previous => { const next = { ...previous }; if (file) next[item.name] = file; else delete next[item.name]; return next; });
        setErrors(previous => { const next = Object.fromEntries(Object.entries(previous).filter(([key]) => key !== item.name)); if (error) next[item.name] = [error]; return next; }); setMessage('');
      }} />{item.storage && attachments[item.storage] && <div className="workspace-existing-media" data-storage-field={item.storage}><AttachmentPreview attachment={attachments[item.storage]} />{['projects', 'basics'].includes(resource) && <button type="button" className="workspace-remove-file" disabled={busy} onClick={() => void removeMedia(item.storage!)}><Trash2 size={13} aria-hidden="true" />Remove file</button>}</div>}</div>)}</section>}
      <div className="workspace-form-tip"><span>Good to know</span><p>Your changes are saved when you select Save changes. Review visibility settings before publishing.</p></div></aside>
    <div className="workspace-form-actions"><span>{action === 'edit' ? `Editing record #${record.id}` : 'Changes are not saved yet.'}</span><div><Button asChild variant="outline"><Link href={`/${resource}`}>Cancel</Link></Button><Button disabled={busy}>{busy ? <LoaderCircle size={17} className="animate-spin" /> : <Save size={17} />}{busy ? 'Saving…' : 'Save changes'}</Button></div></div>
  </fieldset></form>;
}
