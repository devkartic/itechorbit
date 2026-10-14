import { usePage } from '@inertiajs/react';
import { SiteLink as Link } from '@/components/SiteLink';
import { ArrowUpRight, Code2, Menu, X } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { HeaderAccount } from '@/components/HeaderAccount';
import { Button } from '@/components/ui/button';
import type { Shared } from '@/lib/types';
const navigation = [['Home', '/'], ['Services', '/service/info'], ['AI solutions', '/services/ai-workflow-integration'], ['Work', '/work'], ['Insights', '/blogs'], ['About', '/about/info']];
export default function PublicLayout({ children }: { children: ReactNode }) {
  const [scrolled, setScrolled] = useState(false);
  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 16);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);
  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent) => { if (event.key === 'Escape') document.querySelector('details.mobile-menu')?.removeAttribute('open'); };
    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, []);
  const { url } = usePage<Shared>();
  return <><a className="skip-link" href="#main">Skip to content</a>
    <header className={`site-header ${scrolled ? 'is-scrolled' : ''}`}><div className="shell header-inner">
      <Link href="/" className="brand" aria-label="iTechOrbit home"><span className="brand-mark"><Code2 size={22} /></span><span className="header-brand-copy"><span>iTech<span className="text-blue-600">Orbit</span></span><small>ENGINEERING &amp; AI</small></span></Link>
      <nav className="header-navigation hidden items-center gap-1 lg:flex" aria-label="Main navigation">{navigation.map(([label, path]) => <Link key={path} href={path} className={`nav-link ${path === '/services/ai-workflow-integration' ? 'nav-ai' : ''}`} aria-current={(path === '/' ? url === '/' : url.startsWith(path)) ? 'page' : undefined}>{label}</Link>)}</nav>
      <div className="header-actions"><Button asChild className="header-project-cta hidden xl:inline-flex"><Link href="/contact/info">Let’s talk <ArrowUpRight size={16} /></Link></Button><HeaderAccount />
      <details className="mobile-menu lg:hidden">
        <summary role="button" aria-label="Open navigation" className="flex size-11 cursor-pointer list-none items-center justify-center rounded-full border border-slate-200 text-navy"><Menu className="menu-open-icon" size={21} /><X className="menu-close-icon" size={21} /></summary>
        <nav id="mobile-navigation" aria-label="Mobile navigation" className="shell grid gap-1 border-t border-slate-200 py-4">{[...navigation, ['Let’s talk', '/contact/info']].map(([label, path]) => <Link key={path} href={path} className="rounded-xl p-3 font-medium" aria-current={(path === '/' ? url === '/' : url.startsWith(path)) ? 'page' : undefined}>{label}</Link>)}</nav>
      </details>
    </div></div></header>
    <main id="main">{children}</main>
    <footer className="site-footer"><div className="shell grid gap-10 py-14 md:grid-cols-[2fr_1fr_1fr]">
      <div><Link href="/" className="brand"><span className="brand-mark"><Code2 size={22} /></span>iTechOrbit</Link><p className="mt-4 footer-copy max-w-sm text-sm leading-7">Thoughtful software engineering.<br />Built for your business. Ready for what’s next.</p></div>
      <div><p className="mb-4 text-sm font-semibold">Explore</p><nav aria-label="Footer navigation" className="footer-links grid gap-3 text-sm">{navigation.map(([label, path]) => <Link key={path} href={path}>{label}</Link>)}</nav></div>
      <div><p className="mb-4 text-sm font-semibold">Start a conversation</p><div className="footer-links grid gap-3 text-sm"><Link href="/contact/info">Project inquiries <ArrowUpRight className="inline" size={14} /></Link><Link href="/contact/info?subject=Engineering%20opportunity">Engineering opportunities</Link><Link href="/privacy-n-policy">Privacy policy</Link><Link href="/login">Workspace sign in</Link></div></div>
    </div><div className="footer-bottom shell flex flex-wrap justify-between gap-3 py-6 text-xs"><span>© {new Date().getUTCFullYear()} iTechOrbit. All rights reserved.</span><span>Clarity in code. Confidence in delivery.</span></div></footer>
  </>;
}
