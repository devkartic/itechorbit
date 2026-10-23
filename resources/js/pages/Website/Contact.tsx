import PublicLayout from '@/layouts/website/PublicLayout';
import { Seo } from '@/components/shared/Seo';
import { Eyebrow } from '@/components/website/Content';
import { ContactForm } from '@/components/shared/ContactForm';
import type { Seo as SeoType } from '@/lib/types';
import { Globe, Mail, MapPin, Phone, Smartphone } from 'lucide-react';

type ContactDetail = { label: string; value: string; href: string | null };
const contactIcons = { Website: Globe, Email: Mail, Phone, Mobile: Smartphone, Address: MapPin };

export default function Contact({ seo, contactTitle, contactSubtitle, existingContent, contactDetails = [], recipient }: { seo: SeoType; contactTitle: string; contactSubtitle: string; existingContent: string; contactDetails: ContactDetail[]; recipient?: { name: string; portfolio_id: string } | null }) {
  return <PublicLayout><Seo seo={seo} /><section className="contact-page shell">
    <header className="contact-page-intro"><Eyebrow>Let’s talk</Eyebrow><h1>{contactTitle}</h1><p>{contactSubtitle}</p></header>
    <div className="contact-grid">
      <aside className="contact-copy" aria-labelledby="contact-panel-title"><span className="contact-panel-label">Get in touch</span><h2 id="contact-panel-title">Let’s start a conversation.</h2>
        {existingContent ? <div className="contact-details prose-content" dangerouslySetInnerHTML={{ __html: existingContent }} /> : <p className="contact-details">Tell us what you’re building or where you need help. Use the form to discuss your project.</p>}
        {contactDetails.length > 0 && <dl className="contact-business-details">{contactDetails.map(detail => {
          const Icon = contactIcons[detail.label as keyof typeof contactIcons] ?? MapPin;
          return <div key={detail.label}><dt><Icon size={17} aria-hidden="true" /><span>{detail.label}</span></dt><dd>{detail.href ? <a href={detail.href}>{detail.value}</a> : detail.value}</dd></div>;
        })}</dl>}
      </aside>
      <section className="contact-form-card" aria-labelledby="contact-form-title"><h2 id="contact-form-title">Tell us about your project</h2><ContactForm recipient={recipient} /></section>
    </div>
  </section></PublicLayout>;
}
