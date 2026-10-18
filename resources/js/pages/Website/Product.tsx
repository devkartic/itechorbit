import { MediaImage } from '@/components/shared/MediaImage';
import { SiteLink as Link } from '@/components/shared/SiteLink';
import { ArrowUpRight, Check } from 'lucide-react';
import PublicLayout from '@/layouts/website/PublicLayout';
import { Seo } from '@/components/shared/Seo';
import { PageIntro, Breadcrumbs, ContactCta } from '@/components/website/Content';
import { Button } from '@/components/ui/button';
import type { Product as ProductType, Seo as SeoType } from '@/lib/types';

export default function Product({ seo, product }: { seo: SeoType; product: ProductType }) {
  return <PublicLayout><Seo seo={seo} schema={{ '@context': 'https://schema.org', '@type': 'Product', name: product.title, description: product.summary, provider: { '@type': 'Organization', name: 'iTechOrbit' }, url: seo.canonical, ...(product.price !== null && product.currency ? { offers: { '@type': 'Offer', price: product.price, priceCurrency: product.currency } } : {}) }} /><PageIntro eyebrow={'Product'} title={product.title}>{product.summary}</PageIntro>
    <section className="section shell"><Breadcrumbs items={[{ label: 'Products', url: '/products' }, { label: product.short }]} /><div className="grid gap-14 lg:grid-cols-[1.5fr_1fr]"><div>
      {product.image && <MediaImage src={product.image} alt={product.title} className="mb-10 h-auto w-full rounded-2xl" />}
      <div className="prose-content" dangerouslySetInnerHTML={{ __html: product.description }} />
      {product.problem && <><h2 className="mt-10 text-2xl font-semibold">The challenge</h2><p className="mt-5 text-lg leading-8 text-slate-600">{product.problem}</p></>}
      {product.solution && <><h2 className="mt-12 text-2xl font-semibold">A considered solution</h2><p className="mt-5 leading-8 text-slate-600">{product.solution}</p></>}
      {!!product.approach?.length && <><h2 className="mt-12 text-2xl font-semibold">How we work</h2><ol className="mt-6 grid gap-5">{product.approach.map((text, i) => <li key={i} className="flex gap-4 text-sm leading-7 text-slate-600"><span className="font-mono text-blue-600">{String(i + 1).padStart(2, '0')}</span>{text}</li>)}</ol></>}
      {!!product.useCases?.length && <section className="mt-12"><h2 className="text-2xl font-semibold">{product.icon === 'sparkles' ? 'How website owners can use it' : 'Use cases'}</h2><div className="mt-6 grid gap-4">{product.useCases.map((useCase, i) => <article key={i} className="rounded-xl border border-blue-100 bg-blue-50/50 p-6"><h3 className="text-lg font-semibold">{useCase.title}</h3><p className="mt-3 text-sm leading-7 text-slate-600">{useCase.description}</p></article>)}</div></section>}
    </div><aside className="h-fit rounded-2xl border border-slate-200 bg-slate-50 p-8">
      <h2 className="text-lg font-semibold">{'Product details'}</h2>
      {product.price !== null && <p className="mt-5 text-2xl font-semibold">{product.currency} {product.price}</p>}
      {product.deliveryTime && <p className="mt-4 text-sm text-slate-600">Estimated delivery: {product.deliveryTime}</p>}
      {product.deliverables.length > 0 && <><h3 className="mt-6 text-sm font-semibold">What is included</h3><ul className="mt-6 grid gap-5">{product.deliverables.map((item, i) => <li key={i} className="flex gap-3 text-sm leading-6"><Check size={17} className="mt-1 shrink-0 text-blue-600" />{item}</li>)}</ul></>}
      {product.technologies.length > 0 && <><h3 className="mt-9 text-sm font-semibold">Relevant technologies</h3><div className="mt-4 flex flex-wrap gap-2">{product.technologies.map((tech, i) => <span key={i} className="tag">{tech}</span>)}</div></>}
      <Button asChild className="mt-9 w-full"><Link href={`/contact/info?subject=${encodeURIComponent(product.title)}`}>{'Request a demo or quote'} <ArrowUpRight size={16} /></Link></Button>
    </aside></div></section><ContactCta /></PublicLayout>;
}
