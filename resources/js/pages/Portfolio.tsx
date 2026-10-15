import PublicLayout from '@/layouts/PublicLayout';
import { Seo } from '@/components/Seo';
import { PortfolioView, type PortfolioData } from '@/components/portfolio/PortfolioView';
import type { Seo as SeoType } from '@/lib/types';
export default function Portfolio({ seo, ...portfolio }: PortfolioData & { seo: SeoType }) {
  return <PublicLayout><Seo seo={seo} /><PortfolioView {...portfolio} /></PublicLayout>;
}
