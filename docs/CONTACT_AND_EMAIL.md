# Portfolio contact and inquiry emails — 2026-10-16

## Personal contact

Each portfolio now has an inline contact form in its final section. Hero contact links scroll to that owner's form. In admin template previews the form is disabled and explicitly marked as a preview.

`GET /portfolios/{key}/contact` is a standalone personal contact page with the owner's name, selected palette and a back-to-portfolio link. It does not use the agency's header, footer or business marketing content. Existing `/contact/info?portfolio_id=...` links redirect here and retain a validated subject parameter. Personal contact pages are noindex and do not expose the owner's email address.

`POST /portfolios/{key}/inquiries` resolves the recipient from the published, active portfolio in the URL. Submitted user IDs, recipient emails and other portfolio IDs cannot override it. The handler saves the inquiry under that user's ID and queues a personal notification to that user's current email address. It never falls back to the business owner for an invalid/hidden portfolio. Form Request validation, CSRF and rate limiting remain enabled. The existing honeypot is retained.

The legacy portfolio-capable `/inquiries` and `/contacts` handlers retain their recipient resolution and now dispatch the personal email variant when applicable. Business inquiries without a portfolio ID still use the configured site owner. The queue job keeps its default business behavior for older serialized jobs.

## Both email templates

The personal and business inquiry emails share a new table-based layout with inline styles, a dark header, sender/email/subject summary, a complete escaped message body, a reply button and an inbox link. Each includes a plain-text MIME alternative. Newlines are retained in the message. No external fonts or images are required to read the notification. Both inquiry variants fill the available email pane without a maximum width, inside 12px outer padding on every side (equivalent to `p-3`). Header, content and footer use 24px internal padding, reduced to 16px on mobile (600px or below), with wrapping for long sender details and message text.

- **Personal portfolio:** the header identifies the portfolio user; copy describes their personal inquiry and contains no iTechOrbit marketing branding. From uses the configured mail address with the portfolio owner's display name. The sole destination is the portfolio owner.
- **Business:** the header identifies iTechOrbit; copy describes a business website inquiry. The destination remains the site's configured business owner.

Both variants use the visitor's validated address as Reply-To. The From address remains the configured sender so the application does not impersonate the visitor's domain. HTML fields are escaped. The full inquiry remains available in the owner's existing inbox. `sent_mail_status` is marked only after the mail transport completes successfully.

## Files and verification

Backend: PublicSiteController, ContactController, ContactMailerJob, SendContactNotifications, SendPortfolioInquiry, two new portfolio routes. Frontend: ContactForm, PortfolioView, PortfolioContact and scoped contact styles. Email views live under `resources/views/mail`; business views include the same tested layout with business context.

Verification: 48 PHP tests / 1,089 assertions; 3 frontend tests; TypeScript, ESLint, Pint, client/SSR builds and Blade compilation pass. All 32 desktop/mobile browser checks pass. Coverage includes route-bound recipient protection, hidden profiles, validation/spam rejection, fake-mail destination/Reply-To, escaped HTML, plain text, personal/agency branding, real form submission, legacy redirects and all template previews.

Email HTML and screenshots use synthetic people and `example.test` addresses. Desktop/mobile browser renders were reviewed for wrapping and overflow. These renders are not an exhaustive email-client compatibility matrix. Tests use fake/array mail transports; no real customer email was sent and SMTP delivery was not claimed.

## Running and rollback

No database migration, dependency or `.env` change is required. Deploy matching PHP/client/SSR builds through the normal release process. Restart long-running queue workers and SSR after release so they load the new code. Existing configured mail transport and queue worker infrastructure are used; this feature does not start a new worker or change sender credentials.

Revert this change and deploy the previous matching builds to roll back presentation/routing. Existing inquiry records remain compatible. Do not delete records or alter the database for rollback.

## Review previews

- [Personal contact desktop](assets/portfolio-personal-contact-desktop.png), [mobile](assets/portfolio-personal-contact-mobile.png)
- [Personal email desktop](assets/portfolio-inquiry-email-desktop.png), [mobile](assets/portfolio-inquiry-email-mobile.png), [HTML](assets/portfolio-inquiry-preview.html)
- [Business email desktop](assets/business-inquiry-email-desktop.png), [mobile](assets/business-inquiry-email-mobile.png), [HTML](assets/business-inquiry-preview.html)
