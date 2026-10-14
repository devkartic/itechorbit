import { useEffect, useRef, type ReactNode } from 'react';
import { X } from 'lucide-react';
import { Button } from './button';
export function Dialog({ open, title, onClose, children }: { open: boolean; title: string; onClose: () => void; children: ReactNode }) {
  const ref = useRef<HTMLDialogElement>(null);
  useEffect(() => { if (open && !ref.current?.open) ref.current?.showModal(); else if (!open && ref.current?.open) ref.current?.close(); }, [open]);
  return <dialog ref={ref} onCancel={onClose} onClose={onClose} aria-labelledby="dialog-title" className="fixed inset-0 m-auto w-[calc(100%-3rem)] max-w-lg rounded-2xl border border-slate-200 bg-white p-7 text-navy shadow-xl backdrop:bg-slate-950/40"><div className="mb-5 flex items-center justify-between gap-3"><h2 id="dialog-title" className="text-xl font-semibold">{title}</h2><Button variant="ghost" size="icon" aria-label="Close dialog" onClick={onClose}><X size={20} /></Button></div>{children}</dialog>;
}
