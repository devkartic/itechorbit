import { Head } from '@inertiajs/react';
import type { Seo as SeoType } from '@/lib/types';
import { jsonLd } from '@/lib/utils';
export function Seo({ seo, schema }: { seo: SeoType; schema?: unknown }) {
  return <Head title={seo.title}><meta head-key="description" name="description" content={seo.description} /><link head-key="canonical" rel="canonical" href={seo.canonical} />
    <meta head-key="robots" name="robots" content={seo.noindex ? 'noindex,follow' : 'index,follow'} />
    <meta head-key="og:title" property="og:title" content={seo.title} /><meta head-key="og:description" property="og:description" content={seo.description} /><meta head-key="og:url" property="og:url" content={seo.canonical} /><meta head-key="og:type" property="og:type" content={seo.type ?? 'website'} /><meta head-key="og:site_name" property="og:site_name" content="iTechOrbit" />
    <meta head-key="twitter:card" name="twitter:card" content={seo.image ? 'summary_large_image' : 'summary'} /><meta head-key="twitter:title" name="twitter:title" content={seo.title} /><meta head-key="twitter:description" name="twitter:description" content={seo.description} />
    {seo.image && <><meta head-key="og:image" property="og:image" content={seo.image} /><meta head-key="og:image:alt" property="og:image:alt" content={seo.imageAlt ?? seo.title} /><meta head-key="twitter:image" name="twitter:image" content={seo.image} /><meta head-key="twitter:image:alt" name="twitter:image:alt" content={seo.imageAlt ?? seo.title} /></>}
    {schema !== undefined && <script head-key="page-schema" type="application/ld+json" dangerouslySetInnerHTML={{ __html: jsonLd(schema) }} />}
  </Head>;
}
