import { ArrowRight, ArrowUpRight, FileText, Layers3, Search, Sparkles } from 'lucide-react';
import { SiteLink } from './SiteLink';
import { Button } from './ui/button';
export function AiHighlight() {
  return <section className="section shell ai-section" aria-labelledby="ai-highlight-title"><div className="ai-highlight">
    <div className="ai-highlight-copy"><span className="ai-label"><Sparkles size={15} />AI, WITH PURPOSE</span>
      <h2 id="ai-highlight-title">Make intelligence<br /><span>part of your workflow.</span></h2>
      <p>Connect AI to the software your business already uses. Explore knowledge assistants, document processing and workflows that keep your team in control.</p>
      <div className="ai-highlight-actions"><Button asChild size="lg"><SiteLink href="/services/ai-workflow-integration">Explore AI solutions <ArrowUpRight size={18} /></SiteLink></Button><SiteLink href="/contact/info?subject=AI%20integration%20discovery" className="text-link">Discuss an AI idea <ArrowRight size={17} /></SiteLink></div>
      <span className="ai-pilot-note">Start with one useful problem. Validate a focused pilot. Scale what works.</span>
    </div>
    <div className="ai-use-cases"><p className="ai-use-cases-label">POSSIBILITIES TO EXPLORE</p>{[
      { icon: Search, title: 'Find the knowledge you need', text: 'Search approved information with context and source references.' },
      { icon: FileText, title: 'Turn documents into useful data', text: 'Explore extraction, summaries and classification with review built in.' },
      { icon: Layers3, title: 'Connect the next step', text: 'Bring AI-assisted tasks into your Laravel apps, APIs and team workflows.' },
    ].map(({ icon: Icon, title, text }) => <div key={title} className="ai-use-case"><span><Icon size={21} /></span><div><h3>{title}</h3><p>{text}</p></div></div>)}</div>
  </div></section>;
}
