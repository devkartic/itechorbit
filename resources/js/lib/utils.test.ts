import { describe, expect, it } from 'vitest';
import { dateLabel, jsonLd, paginationLabel } from './utils';
describe('public content encoding', () => {
  it('keeps untrusted article titles from closing a structured-data script', () => {
    const title = '</script><script>alert("x")</script>';
    const encoded = jsonLd({ '@type': 'BlogPosting', headline: title });
    expect(encoded).not.toContain('<');
    expect(JSON.parse(encoded).headline).toBe(title);
  });
  it('renders article dates in a fixed timezone for server/client hydration', () => {
    expect(dateLabel('2026-01-01T00:30:00Z')).toBe('Jan 1, 2026');
  });
  it('turns server pagination labels into text instead of raw markup', () => {
    expect(paginationLabel('<span onclick="attack()">&laquo; Previous</span>')).toBe('‹ Previous');
  });
});
