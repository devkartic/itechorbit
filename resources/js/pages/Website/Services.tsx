import PublicLayout from '@/layouts/website/PublicLayout';
import { Seo } from '@/components/shared/Seo';
import { PageIntro, ServiceCard, ContactCta } from '@/components/website/Content';
import type { Seo as SeoType, Service } from '@/lib/types';

export default function Services({ seo, services }: { seo: SeoType; services: Service[] }) {
  return <PublicLayout><Seo seo={seo} /><PageIntro eyebrow="Services" title="Practical engineering. Lasting value.">From a new application to a careful modernization, start with the problem and build the right solution.</PageIntro><section className="section shell space-y-14">
    <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">{services.map(service => <ServiceCard key={service.slug} service={service} />)}</div>
    {services.length === 0 && <p className="text-slate-600">We are updating our service offerings. Contact us to discuss your project.</p>}
  </section><ContactCta /></PublicLayout>;
}
