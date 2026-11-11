import { ArrowUpRight, BriefcaseBusiness, FileUser, FolderKanban, LayoutTemplate, MessagesSquare, UserRound } from 'lucide-react';
import PublicLayout from '@/layouts/website/PublicLayout';
import { Seo } from '@/components/shared/Seo';
import { SiteLink as Link } from '@/components/shared/SiteLink';
import { Breadcrumbs, Eyebrow } from '@/components/website/Content';
import { Button } from '@/components/ui/button';
import type { Seo as SeoType } from '@/lib/types';

const inquiryUrl = '/contact/info?subject=Professional%20portfolio%20service';
const features = [
  { icon: UserRound, title: 'Profile management', description: 'Maintain your professional introduction, skills and social links as your experience develops.' },
  { icon: FolderKanban, title: 'Project showcase', description: 'Present your published projects with clear descriptions and the technologies used.' },
  { icon: BriefcaseBusiness, title: 'Experience and services', description: 'Present your career experience and services to prospective clients and employers.' },
  { icon: LayoutTemplate, title: 'Template selection', description: 'Select from five portfolio templates to present your profile and projects in a consistent design.' },
  { icon: FileUser, title: 'Optional CV sharing', description: 'Provide access to your uploaded CV when you choose to enable public sharing.' },
  { icon: MessagesSquare, title: 'Professional inquiries', description: 'Provide a direct channel for project inquiries and professional opportunities.' },
];
const steps = [
  { title: 'Initial consultation', description: 'Discuss your objectives, intended audience and the experience you want to present.' },
  { title: 'Content and design', description: 'Organize your profile, projects and career experience, and select an appropriate portfolio template.' },
  { title: 'Publication and updates', description: 'Publish your portfolio, share its public link and update your content as your career progresses.' },
];

const templateDescriptions: Record<string, string> = {
  studio: 'A violet design with layered visuals and a clear emphasis on professional skills.',
  executive: 'A navy design with mint accents and a structured presentation of career experience.',
  blueprint: 'A dark technical design with mint accents and organized sections for skills, experience and projects.',
  editorial: 'An ivory design with prominent typography, orange accents and detailed project presentations.',
  spotlight: 'A blue design with bold typography and a visual arrangement of project highlights.',
};

type Template = { key: string; name: string; description: string };

function PortfolioPreview() {
  return <figure className="portfolio-service-preview">
    <div className="ps-preview-window" aria-hidden="true">
      <div className="ps-preview-toolbar"><span><i /><i /><i /></span><span>your professional portfolio</span><ArrowUpRight size={14} /></div>
      <div className="ps-preview-body">
        <div className="ps-preview-nav"><span className="ps-preview-monogram">Y.</span><span>Profile / Selected work / Contact</span></div>
        <div className="ps-preview-profile"><div><p className="ps-preview-kicker">EXPERTISE. EXPERIENCE. IMPACT.</p><p className="ps-preview-title">Your work.<br /><em>Your perspective.</em></p><p className="ps-preview-description">A considered introduction to the expertise and experience that define your career.</p><span className="ps-preview-contact">Explore selected work <ArrowUpRight size={13} /></span></div><div className="ps-preview-portrait"><UserRound size={62} strokeWidth={1} /><span>YOUR PROFILE</span></div></div>
        <div className="ps-preview-skills"><span>Professional skills</span><span>Experience</span><span>Services</span></div>
        <div className="ps-preview-work-heading"><span>Selected work</span><span>01 — 02</span></div>
        <div className="ps-preview-projects"><div><div className="ps-project-art ps-project-art-one"><span /><span /><span /></div><p>Featured project <ArrowUpRight size={12} /></p></div><div><div className="ps-project-art ps-project-art-two"><span /><span /><span /></div><p>Project overview <ArrowUpRight size={12} /></p></div></div>
      </div>
    </div>
    <figcaption><span />Illustrative portfolio preview</figcaption>
  </figure>;
}

function TemplateIllustration({ templateKey }: { templateKey: string }) {
  return <div className={`ps-template-art ps-template-${templateKey}`} aria-hidden="true"><div className="ps-template-bar"><span /><span /><span /></div><div className="ps-template-layout"><div className="ps-template-copy"><i /><i /><i /><b /></div><div className="ps-template-avatar" /></div><div className="ps-template-projects"><span /><span /><span /></div></div>;
}

export default function PortfolioService({ seo, templates }: { seo: SeoType; templates: Template[] }) {
  return <PublicLayout>
    <Seo seo={seo} schema={{ '@context': 'https://schema.org', '@type': 'Service', name: 'Professional Portfolio Service', description: seo.description, provider: { '@type': 'Organization', name: 'iTechOrbit' }, url: seo.canonical }} />
    <div className="portfolio-service-page">
      <section className="ps-hero" aria-labelledby="portfolio-title"><div className="shell">
        <Breadcrumbs items={[{ label: 'Portfolio' }]} />
        <div className="ps-hero-grid"><div className="ps-hero-copy">
          <Eyebrow>Professional portfolio service</Eyebrow>
          <h1 id="portfolio-title"><span>Your expertise.</span>{' '}<span>Professionally presented.</span></h1>
          <p className="ps-hero-description">Bring your experience, skills and projects together in a portfolio that reflects your professional identity.</p>
          <p className="ps-hero-detail">Choose a design, keep your content current and make it easy for clients and employers to connect with you.</p>
          <div className="ps-hero-actions"><Button asChild><Link href={inquiryUrl}>Request a portfolio <ArrowUpRight size={16} aria-hidden="true" /></Link></Button><Button asChild variant="outline"><Link href="/portfolios" target="_blank" rel="noopener noreferrer">View sample portfolio <ArrowUpRight size={16} aria-hidden="true" /><span className="sr-only"> (opens in a new tab)</span></Link></Button></div>
          <div className="ps-hero-notes"><span>Five design options</span><span>Editable content</span><span>Your public portfolio link</span></div>
        </div><PortfolioPreview /></div>
      </div></section>
      <section className="ps-section shell" aria-labelledby="portfolio-features">
        <div className="ps-section-heading ps-features-heading"><div><Eyebrow>Portfolio features</Eyebrow><h2 id="portfolio-features">A complete professional presence.</h2></div><p>Your profile, published work and contact information, organized in one place.</p></div>
        <div className="ps-features">{features.map(({ icon: Icon, title, description }) => <article key={title}><span className="icon-tile"><Icon size={21} aria-hidden="true" /></span><div><h3>{title}</h3><p>{description}</p></div></article>)}</div>
      </section>
      <section className="ps-template-section" aria-labelledby="portfolio-templates"><div className="shell ps-section">
        <div className="ps-section-heading ps-templates-heading"><div><Eyebrow>Design options</Eyebrow><h2 id="portfolio-templates">Your portfolio. Your perspective.</h2></div><p>Five distinct templates to complement your professional identity.</p></div>
        <div className="ps-templates">{templates.map(template => <article key={template.key}><TemplateIllustration templateKey={template.key} /><div className="ps-template-content"><h3>{template.name}</h3><p>{templateDescriptions[template.key] ?? template.description}</p></div></article>)}</div>
      </div></section>
      <section className="ps-section shell" aria-labelledby="portfolio-process"><div className="ps-process-panel">
        <div className="ps-section-heading"><div><Eyebrow>Our process</Eyebrow><h2 id="portfolio-process">A clear path to publication.</h2></div><p>From the initial discussion to a portfolio you can share and maintain.</p></div>
        <ol className="ps-process">{steps.map((step, index) => <li key={step.title}><span>0{index + 1}</span><h3>{step.title}</h3><p>{step.description}</p></li>)}</ol>
      </div></section>
      <section className="shell contact-cta-section"><div className="cta-panel"><div><Eyebrow>Discuss your requirements</Eyebrow><h2 className="mt-5 max-w-xl text-3xl font-semibold tracking-tight md:text-4xl">Create your professional portfolio.</h2><p className="mt-5 max-w-xl leading-7 text-slate-600">Discuss your objectives, content and design preferences with us.</p></div><Button asChild size="lg"><Link href={inquiryUrl}>Request a portfolio <ArrowUpRight size={18} aria-hidden="true" /></Link></Button></div></section>
    </div>
  </PublicLayout>;
}
