import { Button } from '@/components/ui/button';

export type SocialProvider = { id: string; name: string; url: string; connected?: boolean };

export function SocialSignIn({ providers }: { providers: SocialProvider[] }) {
  if (!providers.length) return null;
  return <div className="mt-6" aria-label="Social sign-in"><div className="mb-4 flex items-center gap-3 text-xs text-slate-500"><span className="h-px flex-1 bg-slate-200" />Or continue with<span className="h-px flex-1 bg-slate-200" /></div><div className="grid gap-3">{providers.map(provider => <Button key={provider.id} asChild variant="outline" size="sm" className="w-full rounded-lg"><a href={provider.url}>Continue with {provider.name}</a></Button>)}</div></div>;
}
