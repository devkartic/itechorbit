import { SiteLink as Link } from '@/components/shared/SiteLink';
import PublicLayout from '@/layouts/website/PublicLayout';
import { Seo } from '@/components/shared/Seo';
import { Button } from '@/components/ui/button';
import type { Seo as SeoType } from '@/lib/types';
export default function ErrorPage({ status, seo }: { status: number; seo: SeoType }) { return <PublicLayout><Seo seo={seo} /><section className="shell py-24 text-center"><p className="font-mono text-sm text-blue-600">{status}</p><h1 className="mt-5 text-4xl font-semibold tracking-tight">{status === 404 ? 'This page couldn’t be found.' : status === 403 ? 'This page isn’t available.' : 'Something went wrong.'}</h1><p className="mt-6 text-slate-600">{status === 404 ? 'The link may be outdated, or the content is no longer published.' : 'Please try again or return to the homepage.'}</p><Button asChild className="mt-8"><Link href="/">Back to home</Link></Button></section></PublicLayout>; }
