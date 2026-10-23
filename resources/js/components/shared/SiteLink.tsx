import type { ComponentProps } from 'react';
import { Link } from '@inertiajs/react';

// Internal links use Inertia after hydration and remain ordinary anchors without JavaScript.
type SiteLinkProps = Omit<ComponentProps<'a'>, 'onClick' | 'onProgress' | 'onError'> & Pick<ComponentProps<typeof Link>, 'onClick'>;

export function SiteLink(props: SiteLinkProps) {
  const { href, target, download } = props;
  if (href?.startsWith('/') && !href.startsWith('//') && !download && download !== '' && (!target || target === '_self')) {
    return <Link {...props} href={href} />;
  }
  return <a {...props} />;
}
