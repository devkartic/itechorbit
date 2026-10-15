import { Seo } from '@/components/Seo';
import { PortfolioView, type PortfolioData } from '@/components/portfolio/PortfolioView';
import type { Seo as SeoType } from '@/lib/types';
export default function Portfolio({ seo, ...portfolio }: PortfolioData & { seo: SeoType }) {
  return <><Seo seo={seo} /><a className="skip-link" href="#main">Skip to portfolio</a><main id="main"><PortfolioView {...portfolio} /></main></>;
}
