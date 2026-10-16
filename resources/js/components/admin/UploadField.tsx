import { useEffect, useState } from 'react';
import { ArrowUpRight, FileText } from 'lucide-react';
import { Input } from '@/components/ui/input';
import type { Attachment, Field } from './types';

export function AttachmentPreview({ attachment }: { attachment: Attachment }) {
  return <div className="workspace-attachment-preview">
    <span className="workspace-attachment-label">{attachment.label}</span>
    {attachment.type === 'image' && attachment.url && <a href={attachment.url} target="_blank" rel="noopener noreferrer" aria-label={`Open ${attachment.label} image`}><img src={attachment.url} alt={`${attachment.label} preview`} className="workspace-media-preview" /></a>}
    {attachment.type === 'pdf' && attachment.url && <a className="workspace-pdf-link" href={attachment.url} target="_blank" rel="noopener noreferrer" aria-label={`Open ${attachment.label} PDF`}><FileText size={18} />Open PDF <ArrowUpRight size={15} aria-hidden="true" /></a>}
    {attachment.type === 'unavailable' && <p className="form-error">This saved file is unavailable. Upload a replacement.</p>}
    <small>{attachment.name}</small>
  </div>;
}

export function UploadField({ field, file, error, disabled, onChange, help }: { field: Field; file?: File; error: string; disabled: boolean; onChange: (file: File | undefined, error?: string) => void; help?: string }) {
  const [preview, setPreview] = useState('');
  const pdf = field.accept?.includes('.pdf');
  useEffect(() => {
    if (!file || pdf) { setPreview(''); return; }
    const url = URL.createObjectURL(file); setPreview(url);
    return () => URL.revokeObjectURL(url);
  }, [file, pdf]);
  return <div className="workspace-upload">
    <label className="form-label" htmlFor={field.name}>{field.label}</label>
    <Input id={field.name} type="file" accept={field.accept} disabled={disabled} onChange={event => {
      const input = event.currentTarget; const selected = input.files?.[0];
      if (!selected) { onChange(undefined); return; }
      const extensions = (field.accept ?? '').split(',').map(value => value.trim().toLowerCase());
      const extension = `.${selected.name.split('.').at(-1)?.toLowerCase()}`;
      const error = !extensions.includes(extension) ? pdf ? 'Choose a PDF document.' : 'Choose a JPG, PNG or WebP image.'
        : field.max_kb && selected.size > field.max_kb * 1024 ? `The file must not exceed ${field.max_kb.toLocaleString()} KB.` : '';
      if (error) { input.value = ''; onChange(undefined, error); return; }
      onChange(selected);
    }} aria-invalid={!!error} aria-describedby={`${field.name}-help ${field.name}-error`} />
    <small id={`${field.name}-help`}>{pdf ? 'PDF document' : 'JPG, PNG or WebP image'} · up to {field.max_kb?.toLocaleString()} KB</small>
    {preview && <img src={preview} alt={`Selected ${field.label} preview`} className="workspace-media-preview" />}
    {file && <p className="workspace-field-help">Selected file: {file.name}. Save changes to upload.</p>}
    {help && <p className="workspace-field-help">{help}</p>}
    <p id={`${field.name}-error`} className="form-error" role={error ? 'alert' : undefined}>{error}</p>
  </div>;
}
