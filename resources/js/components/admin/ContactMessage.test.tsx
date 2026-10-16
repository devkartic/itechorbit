import { describe, expect, it } from 'vitest';
import { renderToStaticMarkup } from 'react-dom/server';
import { ContactMessage } from './ContactMessage';

describe('contact message safety and fallbacks', () => {
  it('escapes message HTML and encodes reply fields without adding a second Re prefix', () => {
    const html = renderToStaticMarkup(<ContactMessage record={{ name: 'Sender', email: 'sender+tag@example.test', subject: 'Re: Role & scope?', message: '<script>alert(1)</script>\nSecond line', created_at: '2026-10-17T10:30:00Z' }} />);
    expect(html).toContain('&lt;script&gt;alert(1)&lt;/script&gt;\nSecond line');
    expect(html).not.toContain('<script>');
    expect(html).toContain('mailto:sender%2Btag@example.test?subject=Re%3A%20Role%20%26%20scope%3F');
    expect(html).toContain('2026-10-17T10:30:00.000Z');
  });
  it('uses empty-field fallbacks and excludes invalid reply destinations', () => {
    const html = renderToStaticMarkup(<ContactMessage record={{ email: 'sender@example.test\r\nBcc:other@example.test', created_at: 'invalid' }} />);
    for (const label of ['Unknown sender', 'No subject', 'No message provided.', 'Date unavailable']) expect(html).toContain(label);
    expect(html).not.toContain('mailto:');
    expect(html).not.toContain('<time');
  });
});
