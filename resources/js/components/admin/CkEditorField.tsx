import { useEffect, useState, type ComponentType } from 'react';
import { Textarea } from '@/components/ui/input';

export type EditorProps = { id: string; label: string; value: string; required?: boolean; invalid?: boolean; disabled?: boolean; onChange: (value: string) => void };

// CKEditor uses browser APIs. Keep it out of the server render and load it only on editor screens.
export function CkEditorField(props: EditorProps) {
  const [Client, setClient] = useState<ComponentType<EditorProps> | null>(null);
  const [failed, setFailed] = useState(false);
  useEffect(() => {
    let mounted = true;
    import('./CkEditorInput').then(module => { if (mounted) setClient(() => module.CkEditorInput); }).catch(() => { if (mounted) setFailed(true); });
    return () => { mounted = false; };
  }, []);
  if (failed) return <><p role="alert" className="form-error">CKEditor could not load. You can still edit the HTML below.</p><Textarea id={props.id} aria-label={`${props.label} HTML`} value={props.value} disabled={props.disabled} onChange={event => props.onChange(event.target.value)} /></>;
  return Client ? <Client {...props} /> : <div className="workspace-editor-loading" role="status">Loading CKEditor…</div>;
}
