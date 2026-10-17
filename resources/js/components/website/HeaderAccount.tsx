import { useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import { ChevronDown, ExternalLink, LayoutDashboard, LogIn, LogOut, Settings2, UserRound } from 'lucide-react';
import { SiteLink } from '../shared/SiteLink';
import { Button } from '../ui/button';
import type { Shared } from '@/lib/types';
export function HeaderAccount() {
  const { auth } = usePage<Shared>().props;
  const details = useRef<HTMLDetailsElement>(null);
  useEffect(() => {
    const outside = (event: PointerEvent) => { if (event.target instanceof Node && !details.current?.contains(event.target) && details.current) details.current.open = false; };
    document.addEventListener('pointerdown', outside);
    return () => document.removeEventListener('pointerdown', outside);
  }, []);
  if (!auth.user) return <Button asChild variant="outline" size="sm" className="header-sign-in"><SiteLink href="/login"><LogIn size={16} />Sign in</SiteLink></Button>;
  const initials = auth.user.name.trim().split(/\s+/).slice(0, 2).map(word => [...word][0]).join('').toUpperCase();
  return <details ref={details} className="account-menu" onToggle={event => { if (event.currentTarget.open) document.querySelector('details.mobile-menu')?.removeAttribute('open'); }} onKeyDown={event => {
    if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); if (details.current) details.current.open = false; details.current?.querySelector('summary')?.focus(); return; }
    if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
    event.preventDefault(); if (details.current) details.current.open = true;
    const items = [...(details.current?.querySelectorAll<HTMLElement>('nav a, nav button') ?? [])];
    const current = items.indexOf(document.activeElement as HTMLElement);
    const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1 : event.key === 'ArrowDown' ? (current + 1) % items.length : (current - 1 + items.length) % items.length;
    items[next]?.focus();
  }}>
    <summary role="button" aria-label="Open account menu" aria-controls="account-dropdown" className="account-trigger"><span className="account-avatar" aria-hidden="true">{initials || 'U'}</span><span className="account-trigger-name">{auth.user.name}</span><ChevronDown size={15} aria-hidden="true" /></summary>
    <div id="account-dropdown" className="account-dropdown"><div className="account-dropdown-heading"><span className="account-avatar" aria-hidden="true">{initials || 'U'}</span><div><strong>{auth.user.name}</strong><span>Your iTechOrbit account</span></div></div>
      <nav aria-label="Account navigation"><SiteLink href="/account/profile"><UserRound size={17} /><span>My profile</span></SiteLink><SiteLink href="/account/settings"><Settings2 size={17} /><span>Account settings</span></SiteLink><SiteLink href="/home"><LayoutDashboard size={17} /><span>Workspace</span></SiteLink>{auth.user.publicProfileUrl && <SiteLink href={auth.user.publicProfileUrl}><ExternalLink size={17} /><span>Public portfolio</span></SiteLink>}
        <form method="post" action="/logout" className="account-logout"><input type="hidden" name="_token" value={auth.csrfToken} /><button type="submit"><LogOut size={17} /><span>Sign out</span></button></form>
      </nav>
    </div>
  </details>;
}
