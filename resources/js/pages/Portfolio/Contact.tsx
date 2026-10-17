import '../../../css/portfolio/portfolio.css';
import { ArrowLeft, ArrowUpRight } from 'lucide-react';
import { Seo } from '@/components/shared/Seo';
import { ContactForm } from '@/components/shared/ContactForm';
import type { PortfolioTemplate } from '@/components/portfolio/PortfolioView';
import type { Seo as SeoType } from '@/lib/types';
export default function PortfolioContact({ seo, recipient, template }: { seo: SeoType; recipient: { name: string; portfolio_id: string }; template: PortfolioTemplate }) {
  const portfolio = `/portfolios/${encodeURIComponent(recipient.portfolio_id)}`;
  return <main id="main" className={`portfolio-view pf-${template} pf-contact-page`}><Seo seo={seo} /><div className="shell pf-shell"><header className="pf-local-header"><a className="pf-personal-brand" href={portfolio}><ArrowLeft size={17} />{recipient.name}</a><a href={portfolio} className="pf-text-link">View portfolio <ArrowUpRight size={15} /></a></header><section className="pf-personal-contact pf-contact"><div className="pf-contact-copy"><p className="pf-kicker">Personal portfolio inquiry</p><h1>Contact<br />{recipient.name}.</h1><p>A role, a project, or an idea worth exploring. Your message goes directly to {recipient.name}.</p></div><div className="pf-contact-form"><ContactForm recipient={recipient} /></div></section><footer className="pf-signature"><span>{recipient.name} · Personal portfolio</span><a href={portfolio}>Back to portfolio <ArrowUpRight size={13} /></a></footer></div></main>;
}
