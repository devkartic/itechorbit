import { SiteLink as Link } from '@/components/shared/SiteLink';
import { ArrowUpRight, Check } from 'lucide-react';
import PublicLayout from '@/layouts/website/PublicLayout';
import { Seo } from '@/components/shared/Seo';
import { PageIntro, Breadcrumbs, ContactCta } from '@/components/website/Content';
import { Button } from '@/components/ui/button';
import type { Service as ServiceType, Seo as SeoType } from '@/lib/types';

export default function Service({ seo, service }: { seo: SeoType; service: ServiceType }) {
  return <PublicLayout><Seo seo={seo} schema={{ '@context': 'https://schema.org', '@type': 'Service', name: service.title, description: service.summary, provider: { '@type': 'Organization', name: 'iTechOrbit' }, url: seo.canonical,  }} /><PageIntro eyebrow={'Service'} title={service.title}>{service.summary}</PageIntro>
    <section className="section shell"><Breadcrumbs items={[{ label: 'Services', url: '/service/info' }, { label: service.short }]} /><div className="grid gap-14 lg:grid-cols-[1.5fr_1fr]"><div>
      {service.image && <img src={service.image} alt={service.title} className="mb-10 h-auto w-full rounded-2xl" />}
      <div className="prose-content" dangerouslySetInnerHTML={{ __html: service.description }} />
      {service.problem && <><h2 className="mt-10 text-2xl font-semibold">The challenge</h2><p className="mt-5 text-lg leading-8 text-slate-600">{service.problem}</p></>}
      {service.solution && <><h2 className="mt-12 text-2xl font-semibold">A considered solution</h2><p className="mt-5 leading-8 text-slate-600">{service.solution}</p></>}
      {!!service.approach?.length && <><h2 className="mt-12 text-2xl font-semibold">How we work</h2><ol className="mt-6 grid gap-5">{service.approach.map((text, i) => <li key={i} className="flex gap-4 text-sm leading-7 text-slate-600"><span className="font-mono text-blue-600">{String(i + 1).padStart(2, '0')}</span>{text}</li>)}</ol></>}
      {!!service.useCases?.length && <section className="mt-12"><h2 className="text-2xl font-semibold">{service.icon === 'sparkles' ? 'Where AI can fit' : 'Use cases'}</h2><div className="mt-6 grid gap-4">{service.useCases.map((useCase, i) => <article key={i} className="rounded-xl border border-blue-100 bg-blue-50/50 p-6"><h3 className="text-lg font-semibold">{useCase.title}</h3><p className="mt-3 text-sm leading-7 text-slate-600">{useCase.description}</p></article>)}</div></section>}
    </div><aside className="h-fit rounded-2xl border border-slate-200 bg-slate-50 p-8">
      <h2 className="text-lg font-semibold">{'Your engagement'}</h2>
      {service.deliverables.length > 0 && <><h3 className="mt-6 text-sm font-semibold">What is included</h3><ul className="mt-6 grid gap-5">{service.deliverables.map((item, i) => <li key={i} className="flex gap-3 text-sm leading-6"><Check size={17} className="mt-1 shrink-0 text-blue-600" />{item}</li>)}</ul></>}
      {service.technologies.length > 0 && <><h3 className="mt-9 text-sm font-semibold">Relevant technologies</h3><div className="mt-4 flex flex-wrap gap-2">{service.technologies.map((tech, i) => <span key={i} className="tag">{tech}</span>)}</div></>}
      <Button asChild className="mt-9 w-full"><Link href={`/contact/info?subject=${encodeURIComponent(service.title)}`}>{'Discuss this service'} <ArrowUpRight size={16} /></Link></Button>
    </aside></div></section><ContactCta /></PublicLayout>;
}
