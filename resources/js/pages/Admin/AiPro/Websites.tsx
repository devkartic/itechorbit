import { input, useWorkspace, WorkspaceLayout, type Props } from '@/components/ai-pro/admin/workspace';

export default function Websites(props: Props) {
  const workspace = useWorkspace(props, 'websites');
  const { site, editing, form, setForm, busy, saveSite, newSite, editSite } = workspace;
  return <WorkspaceLayout props={props} section="websites" workspace={workspace}><div className="ai-pro-grid"><section className="workspace-card ai-pro-card"><h2>Connected websites</h2><p>Each website has its own knowledge and conversations.</p><button className="ai-pro-button" onClick={newSite}>Add website</button>{site && <><h3>{site.name}</h3><p>{site.url}</p><p>{site.enabled ? 'Enabled' : 'Disabled'} · {site.daily_limit} questions/day</p><button className="ai-pro-button" onClick={() => editSite(site)}>Edit settings</button></>}</section>      <section className="workspace-card ai-pro-card"><h2>{editing ? 'Website settings' : 'Add a website'}</h2><form onSubmit={event => void saveSite(event)} className="ai-pro-form">
        <label className="form-label">Website name<input className={input} required maxLength={120} value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} /></label>
        <label className="form-label">Website URL<input className={input} required type="url" value={form.url} onChange={e => setForm({ ...form, url: e.target.value })} /></label>
        <label className="form-label">Allowed origins, one per line<textarea className={input} required rows={3} placeholder="https://example.com" value={form.origins} onChange={e => setForm({ ...form, origins: e.target.value })} /></label>
        <label className="form-label">Welcome message<input className={input} required maxLength={255} value={form.welcome} onChange={e => setForm({ ...form, welcome: e.target.value })} /></label>
        <label className="form-label">Contact page URL<input className={input} type="url" value={form.contact_url} onChange={e => setForm({ ...form, contact_url: e.target.value })} /></label>
        <div className="ai-pro-row"><label className="form-label">Accent color<input aria-label="Accent color" type="color" value={form.color} onChange={e => setForm({ ...form, color: e.target.value })} /></label><label className="form-label">Questions/minute<input className={input} type="number" min={1} max={60} required value={form.minute_limit} onChange={e => setForm({ ...form, minute_limit: Number(e.target.value) })} /></label><label className="form-label">Questions/day<input className={input} type="number" min={1} max={10000} required value={form.daily_limit} onChange={e => setForm({ ...form, daily_limit: Number(e.target.value) })} /></label></div>
        <label><input type="checkbox" checked={form.enabled} onChange={e => setForm({ ...form, enabled: e.target.checked })} /> Enable this website’s assistant</label><button className="ai-pro-button primary" disabled={busy}>Save website</button>
      </form></section></div></WorkspaceLayout>;
}
