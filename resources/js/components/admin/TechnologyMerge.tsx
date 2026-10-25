import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { Notice } from './Notice';
import { requestJson } from '@/lib/http';

export function TechnologyMerge({ id, name, targets }: { id: number; name: string; targets: { value: string; label: string }[] }) {
  const [target, setTarget] = useState('');
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  async function merge() {
    setBusy(true); setError('');
    try {
      const result = await requestJson(`/website-technologies/${id}/merge`, 'POST', { target_id: Number(target) });
      sessionStorage.setItem('workspace-success', JSON.stringify({ resource: 'website-technologies', message: result.success }));
      router.visit('/website-technologies');
    } catch (failure) { setError(failure instanceof Error ? failure.message : 'Unable to merge technologies.'); }
    finally { setBusy(false); }
  }
  return <section className="workspace-card mt-6"><h2 className="text-lg font-semibold">Merge technology</h2><p className="my-3 text-sm text-slate-600">Move this technology’s service assignments and previous links to another entry, then remove this entry.</p>
    <label className="form-label" htmlFor="merge-target">Merge into</label><select id="merge-target" className="form-control mb-4" value={target} onChange={event => setTarget(event.target.value)}><option value="">Choose a technology</option>{targets.map(option => <option key={option.value} value={option.value}>{option.label}</option>)}</select>
    <Button type="button" variant="outline" disabled={!target} onClick={() => setOpen(true)}>Merge technology</Button>
    <Dialog open={open} title="Merge this technology?" onClose={() => { if (!busy) setOpen(false); }}><p className="text-sm leading-7">Merge {name} into {targets.find(option => option.value === target)?.label}? All service assignments and previous links will move to the target. Its visibility settings will be kept. This entry will be removed.</p><Notice message={error} error /><div className="mt-6 flex justify-end gap-3"><Button disabled={busy} variant="outline" onClick={() => setOpen(false)}>Cancel</Button><Button disabled={busy} onClick={() => void merge()}>{busy ? 'Merging…' : 'Confirm merge'}</Button></div></Dialog>
  </section>;
}
