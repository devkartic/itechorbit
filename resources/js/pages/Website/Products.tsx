import PublicLayout from '@/layouts/website/PublicLayout';
import { Seo } from '@/components/shared/Seo';
import { PageIntro, ProductCard, ContactCta } from '@/components/website/Content';
import type { Seo as SeoType, ProductSummary } from '@/lib/types';

export default function Products({ seo, products }: { seo: SeoType; products: ProductSummary[] }) {
  return <PublicLayout><Seo seo={seo} /><PageIntro eyebrow="Products" title="Helpful products. Built for your website.">Help visitors find answers, explore your offerings and start a conversation with your business.</PageIntro><section className="section shell"><div className="product-grid">{products.map(product => <ProductCard key={product.slug} product={product} />)}</div>{products.length === 0 && <p className="text-slate-600">We are updating our products. Contact us to discuss your website.</p>}</section><ContactCta /></PublicLayout>;
}
