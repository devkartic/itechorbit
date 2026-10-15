import { useForm, usePage } from '@inertiajs/react';
import { Check, Eye, Monitor, Smartphone, ArrowUpRight, Palette, Save } from 'lucide-react';
import { useState } from 'react';
import AdminLayout, { type Navigation } from '@/layouts/AdminLayout';
import { PortfolioView, type PortfolioData, type PortfolioTemplate } from '@/components/portfolio/PortfolioView';
import { Button } from '@/components/ui/button';
import { Notice } from '@/components/admin/Notice';
import type { Shared } from '@/lib/types';
type Template = { id: PortfolioTemplate; name: string; description: string };
export default function PortfolioTemplates({ profile, projects, template, templates, navigation, publishedUrl }: PortfolioData & { templates: Template[]; navigation: Navigation; publishedUrl: string | null }) {
  const { flash } = usePage<Shared>().props;
  const form = useForm({ template }); const [mobile, setMobile] = useState(false);
  const selected = templates.find(item => item.id === form.data.template)!;
  return <AdminLayout title="Portfolio templates" navigation={navigation}>
    <div className="template-studio-heading"><div><p className="workspace-eyebrow"><Palette size={15} /> YOUR PROFESSIONAL PRESENCE</p><h1>Make your work stand out.</h1><p>Five distinct ways to tell your engineering story. Preview with your own content, then choose your look.</p></div>{publishedUrl && <Button asChild variant="outline"><a href={publishedUrl} target="_blank" rel="noopener noreferrer">View public portfolio <ArrowUpRight size={16} /></a></Button>}</div>
    {flash.success && <Notice message={flash.success} />}
    {!publishedUrl && <Notice message="Your portfolio is currently unpublished. You can preview and save a design here; this does not publish your profile." />}
    <form onSubmit={event => { event.preventDefault(); form.patch('/workspace/portfolio-templates', { preserveScroll: true }); }}>
      <fieldset className="template-choices" disabled={form.processing}><legend className="sr-only">Choose a portfolio template</legend>{templates.map((item, index) => <label key={item.id} className={`template-choice ${form.data.template === item.id ? 'is-selected' : ''}`}><input type="radio" name="template" value={item.id} checked={form.data.template === item.id} onChange={() => form.setData('template', item.id)} /><span className={`template-miniature miniature-${item.id}`} aria-hidden="true"><span className="mini-topline" /><span className="mini-avatar" /><span className="mini-title" /><span className="mini-subtitle" /><span className="mini-button" /><span className="mini-grid"><i /><i /><i /></span></span><span className="template-choice-title"><strong>{item.name}</strong><span>{template === item.id ? 'Current' : `0${index + 1}`}</span>{form.data.template === item.id && <Check size={15} />}</span><span className="template-choice-description">{item.description}</span></label>)}</fieldset>
      {form.errors.template && <Notice error message={form.errors.template} />}
      <div className="template-preview-toolbar"><div><Eye size={19} /><div><h2>{selected.name} preview</h2><p>Your published content · links inactive in preview</p></div></div><div className="template-preview-actions"><div className="template-device-switch" role="group" aria-label="Preview device"><button type="button" aria-label="Desktop preview" aria-pressed={!mobile} onClick={() => setMobile(false)}><Monitor size={17} /></button><button type="button" aria-label="Mobile preview" aria-pressed={mobile} onClick={() => setMobile(true)}><Smartphone size={17} /></button></div><Button type="submit" disabled={form.processing || form.data.template === template}><Save size={15} />{form.processing ? 'Saving…' : 'Save template'}</Button></div></div>
    </form>
    <div className={`template-preview-stage ${mobile ? 'is-mobile' : ''}`}><div className="template-preview-window" role="region" aria-label={`${selected.name} portfolio preview`} tabIndex={0}><div className="template-preview-browser" aria-hidden="true"><i /><i /><i /><span>portfolio / {selected.name.toLowerCase()}</span></div><PortfolioView profile={profile} projects={projects} template={form.data.template} preview /></div></div>
    <p className="template-preview-note">Only active profile content is shown. Add your introduction, experience, skills and projects in the portfolio modules to complete your story. Saving a template changes only your own portfolio.</p>
  </AdminLayout>;
}
