import { useRef, useState } from 'react';
import { Recaptcha, type RecaptchaHandle } from './Recaptcha';
import { useForm, usePage } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { Button } from '../ui/button';
import { Input, Textarea } from '../ui/input';
import type { Shared } from '@/lib/types';
export function ContactForm({ recipient, preview = false }: { recipient?: { name: string; portfolio_id: string } | null; preview?: boolean }) {
  const { props: { flash }, url } = usePage<Shared>();
  const subject = new URL(url, 'http://localhost').searchParams.get('subject') ?? (recipient ? `Opportunity for ${recipient.name}` : '');
  const captcha = useRef<RecaptchaHandle>(null);
  const [verifying, setVerifying] = useState(false);
  const [captchaReset, setCaptchaReset] = useState(0);
  const form = useForm({ 'g-recaptcha-response': '', name: '', email: '', subject, message: '', website: '', portfolio_id: recipient?.portfolio_id ?? '' });
  return <form onSubmit={async event => { event.preventDefault(); if (preview || verifying || form.processing) return; setVerifying(true); try { const token = await captcha.current?.verify() ?? ''; form.transform(data => ({ ...data, 'g-recaptcha-response': token })); form.post(recipient ? `/portfolios/${encodeURIComponent(recipient.portfolio_id)}/inquiries` : '/inquiries', { preserveScroll: true, onSuccess: () => form.reset(), onFinish: () => { setVerifying(false); setCaptchaReset(value => value + 1); } }); } catch (error) { setVerifying(false); form.setError('g-recaptcha-response', error instanceof Error ? error.message : 'Please retry verification.'); } }} className="grid gap-6" aria-label={recipient ? `Contact ${recipient.name}` : 'Project inquiry'}><fieldset disabled={preview || verifying || form.processing} className="grid gap-5 border-0 p-0 m-0 min-w-0">
    {recipient && <p className="text-sm text-slate-600">Your message is sent directly to {recipient.name}.</p>}
    {!preview && flash.success && <div role="status" className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{flash.success}</div>}
    {Object.keys(form.errors).length > 0 && <p role="alert" className="text-sm text-red-700">Please correct the highlighted fields.</p>}
    <div className="grid gap-6 sm:grid-cols-2">{(['name', 'email'] as const).map(field => <div key={field}><label className="form-label" htmlFor={`inquiry-${field}`}>{field === 'name' ? 'Your name' : 'Email address'}</label><Input id={`inquiry-${field}`} type={field === 'email' ? 'email' : 'text'} autoComplete={field === 'email' ? 'email' : 'name'} required maxLength={255} value={form.data[field]} onChange={e => form.setData(field, e.target.value)} aria-invalid={!!form.errors[field]} aria-describedby={form.errors[field] ? `${field}-error` : undefined} />{form.errors[field] && <p id={`${field}-error`} className="form-error">{form.errors[field]}</p>}</div>)}</div>
    <div><label className="form-label" htmlFor="inquiry-subject">{recipient ? 'Subject' : 'What can we help with?'}</label><Input id="inquiry-subject" required maxLength={255} value={form.data.subject} onChange={e => form.setData('subject', e.target.value)} placeholder="A project, an upgrade, or an engineering opportunity" aria-invalid={!!form.errors.subject} aria-describedby={form.errors.subject ? 'subject-error' : undefined} />{form.errors.subject && <p className="form-error" id="subject-error">{form.errors.subject}</p>}</div>
    <div><label className="form-label" htmlFor="inquiry-message">{recipient ? 'Your message' : 'Tell us a little about it'}</label><Textarea id="inquiry-message" required maxLength={10000} value={form.data.message} onChange={e => form.setData('message', e.target.value)} placeholder="Your goals, the current situation and any constraints…" aria-invalid={!!form.errors.message} aria-describedby={form.errors.message ? 'message-error' : undefined} />{form.errors.message && <p className="form-error" id="message-error">{form.errors.message}</p>}</div>
    <div hidden aria-hidden="true"><label htmlFor="website">Website</label><Input id="website" tabIndex={-1} autoComplete="off" value={form.data.website} onChange={e => form.setData('website', e.target.value)} /></div>
    {!preview && <Recaptcha ref={captcha} action={recipient ? 'portfolio_inquiry' : 'inquiry'} resetKey={captchaReset} onChange={token => form.setData('g-recaptcha-response', token)} error={form.errors['g-recaptcha-response']} />}
    <p className="text-xs leading-6 text-slate-500">Your information is used to respond to this inquiry. Please avoid sharing passwords or sensitive account details.</p><Button type="submit" size="lg" disabled={verifying || form.processing} className="justify-self-start">{verifying || form.processing ? 'Sending…' : recipient ? 'Send personal message' : 'Send inquiry'} <ArrowUpRight size={17} /></Button>
  </fieldset></form>;
}
