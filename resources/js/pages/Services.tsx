import PublicLayout from '@/layouts/PublicLayout';
import { Seo } from '@/components/Seo';
import { PageIntro, ServiceCard, ContactCta } from '@/components/Content';
import type { Seo as SeoType, Service } from '@/lib/types';
export default function Services({ seo, services, existingContent }: { seo: SeoType; services: Service[]; existingContent: string }) { return <PublicLayout><Seo seo={seo} /><PageIntro eyebrow="Services" title="Practical engineering. Lasting value.">From a new application to a careful modernization, start with the problem and build the right solution.</PageIntro><section className="section shell"><div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">{services.map(service => <ServiceCard key={service.slug} service={service} />)}</div>{existingContent && <div className="prose-content mt-14" dangerouslySetInnerHTML={{ __html: existingContent }} />}</section><ContactCta /></PublicLayout>; }
