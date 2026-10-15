import { CircleCheck, CircleAlert, X } from 'lucide-react';
export function Notice({ message, error = false, onDismiss }: { message: string; error?: boolean; onDismiss?: () => void }) {
  if (!message) return null;
  const Icon = error ? CircleAlert : CircleCheck;
  return <div role={error ? 'alert' : 'status'} className={`workspace-notice ${error ? 'is-error' : ''}`}><Icon size={20} aria-hidden="true" /><span>{message}</span>{onDismiss && <button type="button" onClick={onDismiss} aria-label="Dismiss message"><X size={16} /></button>}</div>;
}
