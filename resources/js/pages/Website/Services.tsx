import { SiteLink as Link } from '@/components/shared/SiteLink';
import PublicLayout from '@/layouts/website/PublicLayout';
import { Seo } from '@/components/shared/Seo';
import { PageIntro, ServiceCard, ContactCta } from '@/components/website/Content';
import type { Seo as SeoType, ServiceSummary, Technology } from '@/lib/types';

export default function Services({ seo, services, technologies, selectedTechnology }: { seo: SeoType; services: ServiceSummary[]; technologies: Technology[]; selectedTechnology: Technology | null }) {
  return <PublicLayout><Seo seo={seo} /><PageIntro eyebrow="Services" title="A stronger website. A clearer path forward.">Improve speed, search visibility, measurement and security with practical expertise. From focused website improvements to custom software, start with a clear scope and results you can review.</PageIntro><section className="section shell space-y-14">
    <div><nav aria-label="Filter services by technology" className="flex flex-wrap gap-2"><Link href="/service/info" aria-current={!selectedTechnology ? 'page' : undefined} className={`tag ${!selectedTechnology ? 'border-blue-600 bg-blue-50 text-blue-700' : ''}`}>All services</Link>{technologies.map(technology => <Link key={technology.id} href={technology.url} aria-current={selectedTechnology?.id === technology.id ? 'page' : undefined} className={`tag ${selectedTechnology?.id === technology.id ? 'border-blue-600 bg-blue-50 text-blue-700' : ''}`}>{technology.name}</Link>)}</nav><p role="status" className="mt-5 text-sm text-slate-600">{services.length} {selectedTechnology ? `${selectedTechnology.name} ` : ''}{services.length === 1 ? 'service' : 'services'}</p></div>
    <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">{services.map(service => <ServiceCard key={service.slug} service={service} />)}</div>
    {services.length === 0 && <p className="text-slate-600">No matching services are currently published. Choose another technology or contact us to discuss your project.</p>}
  </section><ContactCta /></PublicLayout>;
}
