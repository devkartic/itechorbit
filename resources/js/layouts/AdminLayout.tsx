import '../../css/admin.css';
import { BrandLogo } from '@/components/BrandLogo';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowUpRight, Bell, BriefcaseBusiness, ChevronDown, ChevronRight, FileText, FolderKanban, Globe, Home, Inbox, LayoutDashboard, ListTree, LogOut, Menu, Palette, Search, Settings2, ShieldCheck, Tags, UserRound, Users, X } from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { SiteLink } from '@/components/SiteLink';
import type { Shared } from '@/lib/types';
export type Navigation = { label: string; url: string; group?: string; module?: string }[];
const icons: Record<string, typeof FileText> = { posts: FileText, categories: ListTree, tags: Tags, projects: FolderKanban, users: Users, contacts: Inbox, basics: UserRound, roles: ShieldCheck, permissions: ShieldCheck, experiences: BriefcaseBusiness, menus: Menu, modules: ListTree, links: Globe };
export default function AdminLayout({ children, title, navigation }: { children: ReactNode; title: string; navigation: Navigation }) {
  const { auth } = usePage<Shared>().props; const { url } = usePage();
  const [open, setOpen] = useState(false); const [search, setSearch] = useState('');
  const aside = useRef<HTMLElement>(null); const trigger = useRef<HTMLButtonElement>(null);
  useEffect(() => {
    if (!open) return;
    const previous = document.body.style.overflow; document.body.style.overflow = 'hidden';
    aside.current?.querySelector<HTMLButtonElement>('[aria-label="Close workspace navigation"]')?.focus();
    const keys = (event: KeyboardEvent) => {
      if (event.key === 'Escape') { setOpen(false); trigger.current?.focus(); }
      if (event.key !== 'Tab') return;
      const focusable = [...(aside.current?.querySelectorAll<HTMLElement>('a, button, input, summary') ?? [])].filter(node => node.getClientRects().length);
      const first = focusable[0]; const last = focusable.at(-1);
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    };
    const desktop = window.matchMedia('(min-width: 1024px)');
    const resize = () => { if (desktop.matches) setOpen(false); };
    document.addEventListener('keydown', keys); desktop.addEventListener('change', resize);
    return () => { document.body.style.overflow = previous; document.removeEventListener('keydown', keys); desktop.removeEventListener('change', resize); };
  }, [open]);
  const groups = new Map<string, Map<string, Navigation>>();
  for (const item of navigation.filter(item => `${item.label} ${item.group ?? ''} ${item.module ?? ''}`.toLowerCase().includes(search.toLowerCase()))) {
    const group = item.group ?? 'Workspace'; const module = item.module ?? 'Manage';
    if (!groups.has(group)) groups.set(group, new Map());
    const modules = groups.get(group)!;
    if (!modules.has(module)) modules.set(module, []);
    modules.get(module)!.push(item);
  }
  const current = url.split('?')[0].split('/')[1];
  const initials = auth.user?.name.trim().split(/\s+/).slice(0, 2).map(word => word[0]).join('').toUpperCase();
  return <div className="workspace"><Head title={`${title} | iTechOrbit workspace`}><meta name="robots" content="noindex,nofollow" /></Head><a className="skip-link" href="#main">Skip to content</a>
    {open && <button className="workspace-overlay" aria-label="Close workspace overlay" onClick={() => { setOpen(false); trigger.current?.focus(); }} />}
    <aside ref={aside} className={`workspace-sidebar ${open ? 'is-open' : ''}`} role={open ? 'dialog' : undefined} aria-modal={open || undefined} aria-label="Workspace sidebar"><div className="workspace-sidebar-brand"><Link href="/home" className="brand workspace-brand" aria-label="iTechOrbit workspace" onClick={() => setOpen(false)}><BrandLogo /></Link><button className="workspace-mobile-close workspace-icon-button" aria-label="Close workspace navigation" onClick={() => { setOpen(false); trigger.current?.focus(); }}><X size={19} /></button></div>
      <div className="workspace-sidebar-context"><span className="workspace-context-icon"><LayoutDashboard size={18} /></span><div><strong>Studio workspace</strong><span>Content & operations</span></div></div>
      <label className="workspace-sidebar-search"><Search size={16} aria-hidden="true" /><input aria-label="Find a module" value={search} onChange={event => setSearch(event.target.value)} placeholder="Find a module…" /></label>
      <nav className="workspace-navigation" aria-label="Workspace navigation"><Link href="/home" onClick={() => setOpen(false)} className={`workspace-nav-link ${current === 'home' ? 'is-current' : ''}`} aria-current={current === 'home' ? 'page' : undefined}><LayoutDashboard size={18} /><span>Overview</span></Link><Link href="/workspace/portfolio-templates" onClick={() => setOpen(false)} className={`workspace-nav-link ${url.startsWith('/workspace/portfolio-templates') ? 'is-current' : ''}`} aria-current={url.startsWith('/workspace/portfolio-templates') ? 'page' : undefined}><Palette size={18} /><span>Portfolio templates</span></Link>
        {[...groups].map(([group, modules]) => <div className="workspace-nav-group" key={group}><p>{group}</p>{[...modules].map(([module, items]) => <details key={module} open={!!search || items.some(item => item.url.split('/')[1] === current) || current === 'home'} className="workspace-nav-module"><summary><span>{module}</span><ChevronDown size={14} /></summary><div>{items.map(item => { const slug = item.url.slice(1); const Icon = icons[slug] ?? Settings2; return <Link key={item.url} href={item.url} onClick={() => setOpen(false)} className={`workspace-nav-link ${current === slug ? 'is-current' : ''}`} aria-current={current === slug ? 'page' : undefined}><Icon size={17} /><span>{item.label}</span>{current === slug && <span className="workspace-active-dot" />}</Link>; })}</div></details>)}</div>)}
        {!groups.size && <p className="workspace-nav-empty">No matching modules.</p>}
      </nav><div className="workspace-sidebar-footer"><SiteLink href="/" className="workspace-nav-link"><Globe size={17} /><span>Visit public website</span><ArrowUpRight size={15} /></SiteLink><div><ShieldCheck size={15} /><span>Engineering with AI</span></div></div>
    </aside>
    <div className="workspace-body"><header className="workspace-topbar"><div className="workspace-topbar-start"><button ref={trigger} className="workspace-mobile-trigger workspace-icon-button" aria-label="Open workspace navigation" aria-expanded={open} onClick={() => setOpen(true)}><Menu size={21} /></button><div className="workspace-breadcrumb"><Link href="/home" aria-label="Workspace overview"><Home size={16} /></Link><ChevronRight size={14} /><span>{title}</span></div></div><div className="workspace-topbar-actions">{navigation.some(item => item.url === '/contacts') && <Link href="/contacts" aria-label="Contact inquiries" className="workspace-inbox-link"><Bell size={18} /></Link>}<Link href="/account/settings" className="workspace-user" aria-label="Account settings"><span>{initials || 'U'}</span><div><strong>{auth.user?.name}</strong><small>My account</small></div></Link><Button variant="ghost" size="icon" aria-label="Sign out" onClick={() => router.post('/logout')}><LogOut size={18} /></Button></div></header><main id="main" className="workspace-main">{children}</main><footer className="workspace-footer"><span>iTechOrbit workspace</span><span>Thoughtful engineering. Clear operations.</span></footer></div>
  </div>;
}
