import { StatusBadge, EmptyState, base, useWorkspace, WorkspaceLayout, Pagination, type Props } from '@/components/ai-pro/admin/workspace';
import { requestJson } from '@/lib/http';

export default function Synchronization(props: Props) {
  const workspace = useWorkspace(props, 'synchronization');
  const { site, data, busy, perform, setPage } = workspace;
  return <WorkspaceLayout props={props} section="synchronization" workspace={workspace}>{site ? <><section className="workspace-card ai-pro-card"><h2>Synchronization</h2><p>Index published content and review source errors. Active runs refresh automatically.</p><button className="ai-pro-button primary" disabled={busy || !props.providerReady || !!data.runs?.data.some(run => ['queued', 'running'].includes(run.status))} onClick={() => void perform(() => requestJson(`${base}/sites/${site.id}/sync`, 'POST', {}), 'Synchronization queued.')}>Sync knowledge</button><ul className="ai-pro-list">{data.runs?.data.map(run => <li key={run.id}><strong><StatusBadge status={run.status} /> · {run.processed} sources</strong><span>{new Date(run.created_at).toLocaleString()}</span>{run.errors?.map((error, i) => <p key={i}>{error}</p>)}</li>)}</ul>{!data.runs?.total && <EmptyState>No synchronization runs yet.</EmptyState>}<Pagination page={data.runs} change={setPage} label="runs" /></section>
</> : <p>Add a website before synchronizing content.</p>}</WorkspaceLayout>;
}
