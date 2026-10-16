import { Reply } from 'lucide-react';
import type { Row } from './types';

const text = (value: Row[string]) => typeof value === 'string' ? value.trim() : '';

export function ContactMessage({ record }: { record: Row }) {
  const name = text(record.name) || 'Unknown sender';
  const email = text(record.email);
  const subject = text(record.subject) || 'No subject';
  const message = typeof record.message === 'string' ? record.message : '';
  const validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  const address = email.split('@').map(encodeURIComponent).join('@');
  const replySubject = /^re:/i.test(subject) ? subject : `Re: ${subject}`;
  const initials = name.split(/\s+/).slice(0, 2).map(word => word[0]).join('').toUpperCase();
  const received = text(record.created_at);
  const date = received ? new Date(received) : null;
  const validDate = date && !Number.isNaN(date.getTime());
  const dateLabel = validDate ? `${new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'UTC' }).format(date)} UTC` : 'Date unavailable';

  return <article className="workspace-card workspace-contact-message" aria-labelledby="contact-message-subject">
    <header className="workspace-message-header">
      <div className="workspace-message-title"><p className="workspace-eyebrow">Received message</p><h2 id="contact-message-subject">{subject}</h2></div>
      {validEmail && <a className="workspace-message-reply" href={`mailto:${address}?subject=${encodeURIComponent(replySubject)}`}><Reply size={15} aria-hidden="true" />Reply</a>}
      <div className="workspace-message-sender"><span className="workspace-message-avatar" aria-hidden="true">{initials}</span><div><strong>{name}</strong>{email && (validEmail ? <a href={`mailto:${address}`}>{email}</a> : <span>{email}</span>)}</div></div>
      <div className="workspace-message-date"><span>Received</span>{validDate ? <time dateTime={date.toISOString()}>{dateLabel}</time> : <span>{dateLabel}</span>}</div>
    </header>
    <div className="workspace-message-body">{message.trim() ? message : 'No message provided.'}</div>
  </article>;
}
