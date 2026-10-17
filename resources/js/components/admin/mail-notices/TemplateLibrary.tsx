import { useEffect, useState } from 'react';
import { Trash2 } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { requestJson } from '@/lib/http';
import { base, emptyPage, Pagination, useNoticeWorkspace, type Page, type Template } from './workspace';

export function TemplateLibrary({ installed, revision = 0, onUse, onEdit, onDelete }: { installed: boolean; revision?: number; onUse: (id: number) => void; onEdit?: (id: number) => void; onDelete?: (template: Template) => void }) {
  const [templates, setTemplates] = useState<Page<Template>>(emptyPage);
  const [query, setQuery] = useState(''); const [page, setPage] = useState(1);
  const { actions: { busy, setNotice, setFailed } } = useNoticeWorkspace();
  useEffect(() => {
    if (!installed) return;
    const controller = new AbortController();
    const timer = setTimeout(() => { requestJson<Page<Template>>(`${base}/data/templates?key=${encodeURIComponent(query)}&page=${page}`, 'GET', undefined, controller.signal).then(setTemplates).catch(error => { if (!controller.signal.aborted) { setNotice(error.message); setFailed(true); } }); }, 250);
    return () => { clearTimeout(timer); controller.abort(); };
  }, [installed, query, page, revision, setNotice, setFailed]);
  return <><label className="form-label" htmlFor="notice-template-search">Find a saved template</label><Input id="notice-template-search" type="search" maxLength={100} value={query} onChange={event => { setQuery(event.target.value); setPage(1); }} /><ul className="notice-template-list">{templates.data.map(template => <li key={template.id}><div><strong>{template.name}</strong><small>{template.subject}</small></div><div><button type="button" disabled={busy} onClick={() => onUse(template.id)}>Use template</button>{onEdit && <button type="button" disabled={busy} onClick={() => onEdit(template.id)}>Edit</button>}{onDelete && <button type="button" disabled={busy} aria-label={`Delete template ${template.name}`} onClick={() => onDelete(template)}><Trash2 size={14} /></button>}</div></li>)}</ul>{!templates.total && <p className="notice-help">{query ? 'No templates match your search.' : onEdit ? 'No saved templates yet. Create your first template.' : 'No saved templates yet. Open Templates to create one.'}</p>}<Pagination page={templates} change={setPage} /></>;
}
