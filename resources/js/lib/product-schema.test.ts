import { describe, expect, it } from 'vitest';
import { productSchema, publishedOffer } from './product-schema';

const seo = { title: 'AI Assistant | iTechOrbit', description: 'Answers from your content.', canonical: 'https://www.itechorbit.com/products/assistant', image: 'https://www.itechorbit.com/assistant.jpg' };
const product = { title: 'AI Assistant', summary: 'Answers from your content.' };

describe('product search markup', () => {
  it.each([
    [null, null], [null, 'USD'], ['', 'USD'], ['   ', 'USD'], ['NaN', 'USD'], ['Infinity', 'USD'], ['-1', 'USD'],
    ['25.00', null], ['25.00', ''], ['25.00', 'usd'], ['25.00', 'US'], ['25.00', ' USD'],
  ])('uses ordinary WebPage markup for incomplete pricing (%s, %s)', (price, currency) => {
    const offer = publishedOffer({ price, currency }, seo.canonical);
    expect(offer).toBeNull();
    const schema = productSchema(product, seo, offer);
    expect(schema).toMatchObject({ '@type': 'WebPage', name: product.title, description: product.summary, url: seo.canonical, image: seo.image });
    for (const key of ['offers', 'review', 'aggregateRating', 'brand']) expect(schema).not.toHaveProperty(key);
  });

  it.each(['0', '0.00', '49.99'])('includes an Offer for a genuine published price of %s', price => {
    const offer = publishedOffer({ price, currency: 'USD' }, seo.canonical);
    expect(offer).toEqual({ '@type': 'Offer', price, priceCurrency: 'USD', url: seo.canonical });
    expect(productSchema(product, seo, offer)).toMatchObject({ '@type': 'Product', offers: offer, brand: { '@type': 'Brand', name: 'iTechOrbit' } });
  });

  it('does not add a placeholder image when none is published', () => {
    expect(productSchema(product, { ...seo, image: null }, null)).not.toHaveProperty('image');
  });
});
