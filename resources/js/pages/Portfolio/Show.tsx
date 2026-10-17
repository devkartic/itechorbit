import { Seo } from '@/components/shared/Seo';
import { PortfolioView, type PortfolioData } from '@/components/portfolio/PortfolioView';
import type { Seo as SeoType } from '@/lib/types';
export default function Portfolio({ seo, adminPreview = false, ...portfolio }: PortfolioData & { seo: SeoType; adminPreview?: boolean }) {
  return <><Seo seo={seo} /><a className="skip-link" href="#main">Skip to portfolio</a><main id="main">{adminPreview && <div className="border-b border-amber-200 bg-amber-50"><p role="status" className="shell py-3 text-sm text-amber-900">Admin preview — this portfolio is not publicly visible.</p></div>}<PortfolioView {...portfolio} /></main></>;
}
