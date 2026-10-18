import { useState, type ComponentProps, type ReactNode } from 'react';

// Check SSR failures when mounting as well as errors received after hydration.
export function MediaImage({ fallback = null, ...props }: ComponentProps<'img'> & { fallback?: ReactNode }) {
  const [failedSource, setFailedSource] = useState<string>();
  if (failedSource === props.src) return <>{fallback}</>;
  return <img {...props} ref={image => { if (image?.complete && image.naturalWidth === 0) setFailedSource(props.src); }} onError={() => setFailedSource(props.src)} />;
}
