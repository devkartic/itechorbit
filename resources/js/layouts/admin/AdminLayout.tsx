import '../../../css/admin/admin.css';
import { BrandLogo } from '@/components/shared/BrandLogo';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowUpRight, Bell, BriefcaseBusiness, ChevronDown, ChevronRight, FileText, FolderKanban, Globe, Home, Inbox, LayoutDashboard, ListTree, LogOut, Menu, Mail, Palette, Search, Send, Settings2, Sun, Moon, FolderOpen, ShieldCheck, Tags, UserRound, Users, X } from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { SiteLink } from '@/components/shared/SiteLink';
import { useWorkspaceTheme } from '@/lib/workspace-theme';
import type { Shared } from '@/lib/types';
export type Navigation = { id?: number; label: string; url: string; group?: string; groupId?: number; groupIcon?: string | null; module?: string; moduleId?: number; moduleIcon?: string | null; icon?: string | null }[];
type NavModule = { name: string; icon?: string | null; items: Navigation };
type NavGroup = { name: string; icon?: string | null; modules: Map<string, NavModule> };
const icons: Record<string, typeof FileText> = { posts: FileText, categories: ListTree, tags: Tags, projects: FolderKanban, users: Users, contacts: Inbox, 'workspace/email-notices/compose': Send, 'workspace/email-notices/templates': FileText, 'workspace/email-notices/history': Mail, basics: UserRound, roles: ShieldCheck, permissions: ShieldCheck, experiences: BriefcaseBusiness, menus: Menu, modules: ListTree, links: Globe };
const configuredIcons: Record<string, typeof FileText> = { house: LayoutDashboard, home: LayoutDashboard, palette: Palette, 'paper-plane': Send, 'file-lines': FileText, 'file-text': FileText, envelope: Mail, mail: Mail, users: Users, user: UserRound, lock: ShieldCheck, 'shield-check': ShieldCheck, folder: FolderOpen, 'folder-open': FolderOpen, list: ListTree, tags: Tags, briefcase: BriefcaseBusiness, globe: Globe, cog: Settings2, gears: Settings2, inbox: Inbox, blog: FileText, 'circle-info': FileText };
export default function AdminLayout({ children, title, navigation }: { children: ReactNode; title: string; navigation: Navigation }) {
  const { theme, setTheme } = useWorkspaceTheme();
  const { auth } = usePage<Shared>().props; const { url } = usePage();
  const [open, setOpen] = useState(false); const [search, setSearch] = useState('');
  const [expandedModules, setExpandedModules] = useState<Record<string, boolean>>({});
  const navigationPanel = useRef<HTMLElement>(null);
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
  useEffect(() => {
    const revealActiveLink = () => {
      const panel = navigationPanel.current;
      const active = panel?.querySelector<HTMLElement>('a[aria-current="page"]');
      if (!panel || !active || !active.getClientRects().length || panel.clientHeight === 0) return;
      const bounds = panel.getBoundingClientRect();
      const link = active.getBoundingClientRect();
      // Scroll only the navigation panel; leave the page position and keyboard focus intact.
      if (link.top < bounds.top + 12) panel.scrollTop += link.top - bounds.top - 12;
      else if (link.bottom > bounds.bottom - 12) panel.scrollTop += link.bottom - bounds.bottom + 12;
    };
    const frame = requestAnimationFrame(revealActiveLink);
    window.addEventListener('resize', revealActiveLink);
    return () => { cancelAnimationFrame(frame); window.removeEventListener('resize', revealActiveLink); };
  }, [url, open, search, expandedModules]);
  const groups = new Map<string, NavGroup>();
  for (const item of navigation.filter(item => `${item.label} ${item.group ?? ''} ${item.module ?? ''}`.toLowerCase().includes(search.toLowerCase()))) {
    const groupKey = String(item.groupId ?? item.group ?? 'Workspace');
    const moduleKey = String(item.moduleId ?? item.module ?? 'Manage');
    if (!groups.has(groupKey)) groups.set(groupKey, { name: item.group ?? 'Workspace', icon: item.groupIcon, modules: new Map() });
    const group = groups.get(groupKey)!;
    if (!group.modules.has(moduleKey)) group.modules.set(moduleKey, { name: item.module ?? 'Manage', icon: item.moduleIcon, items: [] });
    group.modules.get(moduleKey)!.items.push(item);
  }
  const activePath = url.split('?')[0];
  const isActive = (item: Navigation[number]) => activePath === item.url || activePath.startsWith(`${item.url}/`);
  const initials = auth.user?.name.trim().split(/\s+/).slice(0, 2).map(word => word[0]).join('').toUpperCase();
  const renderGroup = ([groupId, group]: [string, NavGroup]) => {
    const GroupIcon = group.icon ? configuredIcons[group.icon] : null;
    return <div className="workspace-nav-group" key={groupId}><p>{GroupIcon ? <GroupIcon size={13} aria-hidden="true" /> : <span className="workspace-group-marker" aria-hidden="true" />}<span>{group.name}</span><small>Category</small></p>{[...group.modules].map(([moduleId, module]) => {
      const moduleKey = JSON.stringify([groupId, moduleId, search, activePath]);
      const expanded = expandedModules[moduleKey] ?? (!!search || module.items.some(isActive));
      const ModuleIcon = (module.icon && configuredIcons[module.icon]) || FolderOpen;
      return <details key={moduleId} open={expanded} className="workspace-nav-module"><summary onClick={event => { event.preventDefault(); setExpandedModules(previous => ({ ...previous, [moduleKey]: !expanded })); }}><ModuleIcon size={15} aria-hidden="true" /><span>{module.name}</span><small className="workspace-module-label">Module</small><ChevronDown size={14} /></summary><div>{module.items.map(item => {
        const Icon = (item.icon && configuredIcons[item.icon]) || icons[item.url.slice(1)] || Settings2;
        return <Link key={item.id ?? item.url} href={item.url} onClick={() => setOpen(false)} className={`workspace-nav-link ${isActive(item) ? 'is-current' : ''}`} aria-current={isActive(item) ? 'page' : undefined}><Icon size={17} /><span>{item.label}</span>{isActive(item) && <span className="workspace-active-dot" />}</Link>;
      })}</div></details>;
    })}</div>;
  };
  return <div className="workspace" data-theme={theme}><Head title={`${title} | iTechOrbit workspace`}><meta name="robots" content="noindex,nofollow" /></Head><a className="skip-link" href="#main">Skip to content</a>
    {open && <button className="workspace-overlay" aria-label="Close workspace overlay" onClick={() => { setOpen(false); trigger.current?.focus(); }} />}
    <aside ref={aside} className={`workspace-sidebar ${open ? 'is-open' : ''}`} role={open ? 'dialog' : undefined} aria-modal={open || undefined} aria-label="Workspace sidebar"><div className="workspace-sidebar-brand"><Link href="/home" className="brand workspace-brand" aria-label="iTechOrbit workspace" onClick={() => setOpen(false)}><BrandLogo /></Link><button className="workspace-mobile-close workspace-icon-button" aria-label="Close workspace navigation" onClick={() => { setOpen(false); trigger.current?.focus(); }}><X size={19} /></button></div>
      <div className="workspace-sidebar-context"><span className="workspace-context-icon"><LayoutDashboard size={18} /></span><div><strong>Studio workspace</strong><span>Content & operations</span></div></div>
      <label className="workspace-sidebar-search"><Search size={16} aria-hidden="true" /><input aria-label="Find a module" value={search} onChange={event => setSearch(event.target.value)} placeholder="Find a module…" /></label>
      <nav ref={navigationPanel} className="workspace-navigation" aria-label="Workspace navigation">{[...groups].map(renderGroup)}
        {!groups.size && <p className="workspace-nav-empty">No matching modules.</p>}
      </nav><div className="workspace-sidebar-footer"><SiteLink href="/" className="workspace-nav-link"><Globe size={17} /><span>Visit public website</span><ArrowUpRight size={15} /></SiteLink><div><ShieldCheck size={15} /><span>Engineering with AI</span></div></div>
    </aside>
    <div className="workspace-body"><header className="workspace-topbar"><div className="workspace-topbar-start"><button ref={trigger} className="workspace-mobile-trigger workspace-icon-button" aria-label="Open workspace navigation" aria-expanded={open} onClick={() => setOpen(true)}><Menu size={21} /></button><div className="workspace-breadcrumb"><Link href="/home" aria-label="Workspace overview"><Home size={16} /></Link><ChevronRight size={14} /><span>{title}</span></div></div><div className="workspace-topbar-actions"><div className="workspace-theme-switch" role="group" aria-label="Workspace appearance"><button type="button" aria-label="Light theme" aria-pressed={theme === 'light'} title="Light theme" onClick={() => setTheme('light')}><Sun size={16} /></button><button type="button" aria-label="Dark theme" aria-pressed={theme === 'dark'} title="Dark theme" onClick={() => setTheme('dark')}><Moon size={16} /></button></div>{navigation.some(item => item.url === '/contacts') && <Link href="/contacts" aria-label="Contact inquiries" className="workspace-inbox-link"><Bell size={18} /></Link>}<Link href="/account/settings" className="workspace-user" aria-label="Account settings"><span>{initials || 'U'}</span><div><strong>{auth.user?.name}</strong><small>My account</small></div></Link><Button variant="ghost" size="icon" aria-label="Sign out" onClick={() => router.post('/logout')}><LogOut size={18} /></Button></div></header><main id="main" className="workspace-main">{children}</main><footer className="workspace-footer"><span>iTechOrbit workspace</span><span>Thoughtful engineering. Clear operations.</span></footer></div>
  </div>;
}
