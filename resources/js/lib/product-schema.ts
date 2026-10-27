import type { Product, Seo } from './types';

type Pricing = Pick<Product, 'price' | 'currency'>;

export function publishedOffer({ price, currency }: Pricing, url: string) {
  // Number('') and Number(null) are zero; neither represents a published price.
  if (typeof price !== 'string' || price.trim() === '' || !Number.isFinite(Number(price)) || Number(price) < 0 || !/^[A-Z]{3}$/.test(currency ?? '')) return null;
  return { '@type': 'Offer', price, priceCurrency: currency!, url };
}

export function productSchema(product: Pick<Product, 'title' | 'summary'>, seo: Seo, offer: ReturnType<typeof publishedOffer>) {
  const common = { '@context': 'https://schema.org', name: product.title, description: product.summary, url: seo.canonical, ...(seo.image ? { image: seo.image } : {}) };
  // Quote-based pages do not have the offer or review data Product snippets require.
  return offer
    ? { ...common, '@type': 'Product', brand: { '@type': 'Brand', name: 'iTechOrbit' }, offers: offer }
    : { ...common, '@type': 'WebPage' };
}
