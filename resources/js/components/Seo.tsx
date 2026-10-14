import { Head } from '@inertiajs/react';
import type { Seo as SeoType } from '@/lib/types';
import { jsonLd } from '@/lib/utils';
export function Seo({ seo, schema }: { seo: SeoType; schema?: unknown }) {
  return <Head title={seo.title}><meta head-key="description" name="description" content={seo.description} /><link head-key="canonical" rel="canonical" href={seo.canonical} />
    <meta head-key="robots" name="robots" content={seo.noindex ? 'noindex,follow' : 'index,follow'} />
    <meta property="og:title" content={seo.title} /><meta property="og:description" content={seo.description} /><meta property="og:url" content={seo.canonical} /><meta property="og:type" content={seo.type ?? 'website'} /><meta property="og:site_name" content="iTechOrbit" />
    <meta name="twitter:card" content="summary" /><meta name="twitter:title" content={seo.title} /><meta name="twitter:description" content={seo.description} />
    {seo.verification && <meta name="google-site-verification" content={seo.verification} />}
    {schema !== undefined && <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: jsonLd(schema) }} />}
  </Head>;
}
