import { router } from '@inertiajs/react';
import { LockKeyhole, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import AdminLayout, { type Navigation } from '@/layouts/admin/AdminLayout';
import { Notice } from '@/components/admin/Notice';
import { requestJson, RequestError } from '@/lib/http';
type PermissionLink = { id: number; name: string; index: boolean; create: boolean; edit: boolean; destroy: boolean };
export default function Permissions({ navigation, roles, roleId, links }: { navigation: Navigation; roles: { id: number; name: string }[]; roleId: number; links: PermissionLink[] }) {
  const [busy, setBusy] = useState(false); const [notice, setNotice] = useState(''); const [failed, setFailed] = useState(false);
  async function toggle(link: number, action: string, checked: boolean) {
    setBusy(true); setNotice('');
    try { await requestJson('/cr-permission', 'POST', { role_id: roleId, link_id: link, route_name: action, is_checked: checked }); router.reload({ only: ['links'], onSuccess: () => { setNotice('Permission updated successfully.'); setFailed(false); }, onFinish: () => setBusy(false) }); }
    catch (error) { setFailed(true); setNotice(error instanceof RequestError ? Object.values(error.errors).flat()[0] ?? error.message : 'Unable to update this permission.'); setBusy(false); }
  }
  return <AdminLayout title="Roles & permissions" navigation={navigation}><div className="workspace-page-heading"><div><p className="workspace-eyebrow">Administration</p><h1>Roles & permissions</h1><p>Give each role the access it needs. Changes are saved automatically.</p></div><span className="workspace-overview-label"><ShieldCheck size={17} />Access control</span></div><Notice message={notice} error={failed} onDismiss={() => setNotice('')} />
    <section className="workspace-table-panel"><div className="workspace-table-heading"><div><h2>Permission matrix <span>{links.length} resources</span></h2><p>Choose a role to review its access.</p></div></div><div className="workspace-permission-tools"><div><label htmlFor="permission-role" className="form-label">Select role</label><select className="form-control" id="permission-role" disabled={busy} value={roleId} onChange={event => { setNotice(''); router.get('/permissions', { role_id: event.target.value }, { onStart: () => setBusy(true), onFinish: () => setBusy(false) }); }}>{roles.map(role => <option key={role.id} value={role.id}>{role.name}</option>)}</select></div>{roleId === 1 && <p><LockKeyhole size={15} />Super-admin access is protected.</p>}</div>
      <div className="workspace-table-scroll" aria-busy={busy}><table className="admin-table"><thead><tr><th scope="col">Resource</th>{['View', 'Create', 'Edit', 'Delete'].map(label => <th scope="col" key={label}>{label}</th>)}</tr></thead><tbody>{links.map(link => <tr key={link.id}><th scope="row" className="permission-resource-name">{link.name}</th>{(['index', 'create', 'edit', 'destroy'] as const).map(action => <td key={action}><input type="checkbox" className="workspace-permission-checkbox" checked={link[action]} disabled={busy || roleId === 1} aria-label={`${action} ${link.name}`} onChange={event => void toggle(link.id, action, event.target.checked)} /></td>)}</tr>)}</tbody></table></div></section>
  </AdminLayout>;
}
