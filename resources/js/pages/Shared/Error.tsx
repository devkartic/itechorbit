import { SiteLink as Link } from '@/components/shared/SiteLink';
import PublicLayout from '@/layouts/website/PublicLayout';
import { Seo } from '@/components/shared/Seo';
import { Button } from '@/components/ui/button';
import type { Seo as SeoType } from '@/lib/types';
import { ArrowRight, ArrowUpRight, Compass, Home, Route } from 'lucide-react';
import '../../../css/error.css';

export default function ErrorPage({ status, seo }: { status: number; seo: SeoType }) {
  if (status !== 404 && status !== 403) {
    return <PublicLayout><Seo seo={seo} /><section className="shell py-24 text-center"><p className="font-mono text-sm text-blue-600">{status}</p><h1 className="mt-5 text-4xl font-semibold tracking-tight">Something went wrong.</h1><p className="mt-6 text-slate-600">Please try again or return to the homepage.</p><Button asChild className="mt-8"><Link href="/">Back to home</Link></Button></section></PublicLayout>;
  }

  const forbidden = status === 403;
  return <PublicLayout><Seo seo={seo} />
    <section className="error-page" aria-labelledby="error-title">
      <div className="shell error-page-grid">
        <div className="error-page-copy">
          <p className="eyebrow"><span />{forbidden ? 'A protected space' : 'A little off the map'}</p>
          <h1 id="error-title">{forbidden ? 'Access forbidden.' : 'Page not found.'}<br /><span>{forbidden ? 'Explore what’s ahead.' : 'Possibilities ahead.'}</span></h1>
          <p className="error-page-description">{forbidden ? 'This area isn’t available for public access. Head back to the homepage to explore our services, work, and insights.' : 'The page or file you’re looking for may have moved, or no longer exists. Let’s get you somewhere useful.'}</p>
          <div className="error-page-actions">
            <Button asChild size="lg"><Link href="/"><Home size={17} aria-hidden="true" />Back to home<ArrowRight size={17} aria-hidden="true" /></Link></Button>
            <Button asChild size="lg" variant="outline"><Link href="/contact/info">Contact us<ArrowUpRight size={17} aria-hidden="true" /></Link></Button>
          </div>
          <p className="error-page-note">{forbidden ? 'There’s still plenty to discover.' : 'A wrong turn doesn’t have to be a dead end.'}</p>
        </div>
        <div className="error-page-art" aria-hidden="true">
          <div className="error-page-orbit" /><div className="error-page-orbit error-page-orbit-inner" />
          <div className="error-page-coordinate"><span />ITECHORBIT / {forbidden ? 'ACCESS FORBIDDEN' : 'ROUTE NOT FOUND'}</div>
          <div className="error-page-number">4<span><Compass /></span>{forbidden ? 3 : 4}</div>
          <div className="error-page-route"><Route size={17} /><span>New direction, same possibilities.</span><ArrowUpRight size={17} /></div>
          <span className="error-page-star">✦</span><span className="error-page-dot" />
        </div>
      </div>
    </section>
    <section className="shell error-page-explore" aria-labelledby="error-explore">
      <div><p className="eyebrow"><span />Keep exploring</p><h2 id="error-explore">There’s more to discover.</h2></div>
      <nav aria-label="Suggested destinations">
        <Link href="/service/info"><span><small>WHAT WE DO</small>Explore services</span><ArrowUpRight size={20} aria-hidden="true" /></Link>
        <Link href="/work"><span><small>WHAT WE BUILD</small>See our work</span><ArrowUpRight size={20} aria-hidden="true" /></Link>
        <Link href="/blogs"><span><small>WHAT WE SHARE</small>Read insights</span><ArrowUpRight size={20} aria-hidden="true" /></Link>
      </nav>
    </section>
  </PublicLayout>;
}
