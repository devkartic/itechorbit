import PublicLayout from '@/layouts/PublicLayout';
import { Seo } from '@/components/Seo';
import { PageIntro } from '@/components/Content';
import type { Seo as SeoType } from '@/lib/types';
export default function Content({ seo, title, content }: { seo: SeoType; title: string; content: string }) { return <PublicLayout><Seo seo={seo} /><PageIntro eyebrow="iTechOrbit" title={title} /><section className="section shell"><div className="prose-content mx-auto max-w-3xl" dangerouslySetInnerHTML={{ __html: content }} />{!content && <p className="text-slate-600">This page is awaiting published content. Contact iTechOrbit with any questions.</p>}</section></PublicLayout>; }
