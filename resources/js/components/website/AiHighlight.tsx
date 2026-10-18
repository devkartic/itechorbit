import { ArrowRight, ArrowUpRight, FileText, Layers3, Search, Sparkles } from 'lucide-react';
import { SiteLink } from '../shared/SiteLink';
import { Button } from '../ui/button';
import type { Service } from '@/lib/types';
export function AiHighlight({ service }: { service?: Service }) {
  if (!service) return null;
  return <section className="section shell ai-section" aria-labelledby="ai-highlight-title"><div className="ai-highlight">
    <div className="ai-highlight-copy"><span className="ai-label"><Sparkles size={15} />AI, WITH PURPOSE</span>
      <h2 id="ai-highlight-title">Make intelligence<br /><span>part of your workflow.</span></h2>
      <p>{service.summary}</p>
      <div className="ai-highlight-actions"><Button asChild size="lg"><SiteLink href={service.url}>Explore AI solutions <ArrowUpRight size={18} /></SiteLink></Button><SiteLink href={`/contact/info?subject=${encodeURIComponent(service.title)}`} className="text-link">Discuss an AI idea <ArrowRight size={17} /></SiteLink></div>
      <span className="ai-pilot-note">Start with one useful problem. Validate a focused pilot. Scale what works.</span>
    </div>
    <div className="ai-use-cases"><p className="ai-use-cases-label">POSSIBILITIES TO EXPLORE</p>{service.useCases?.map(({ title, description }, index) => { const Icon = [Search, FileText, Layers3][index % 3]; return <div key={index} className="ai-use-case"><span><Icon size={21} /></span><div><h3>{title}</h3><p>{description}</p></div></div>; })}</div>
  </div></section>;
}
