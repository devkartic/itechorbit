import type { ComponentProps } from 'react';

// Public navigation remains usable before hydration and when client scripts fail.
export function SiteLink(props: ComponentProps<'a'>) {
  return <a {...props} />;
}
