import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { requestJson } from '@/lib/http';
import { dateLabel } from '@/lib/utils';
import { base, emptyPage, noticeLayout, Pagination, CountSummary, statuses, label, useNoticeWorkspace, type NoticeProps, type Page, type Campaign, type Detail } from '@/components/admin/mail-notices/workspace';

export default function History({ installed, sendingReady, selectedCampaignId: campaignId }: NoticeProps & { selectedCampaignId: number | null }) {
  const { actions: { busy, perform, setNotice, setFailed } } = useNoticeWorkspace();
  const [campaigns, setCampaigns] = useState<Page<Campaign>>(emptyPage); const [campaignPage, setCampaignPage] = useState(1);
  const [detail, setDetail] = useState<Detail | null>(null); const [recipientPage, setRecipientPage] = useState(1); const [statusFilter, setStatusFilter] = useState('');
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    if (!installed) return;
    const controller = new AbortController();
    const refresh = () => {
      requestJson<Page<Campaign>>(`${base}/campaigns?page=${campaignPage}`, 'GET', undefined, controller.signal).then(setCampaigns).catch(error => { if (!controller.signal.aborted) { setNotice(error.message); setFailed(true); } });
      if (campaignId) requestJson<Detail>(`${base}/campaigns/${campaignId}?page=${recipientPage}&status=${statusFilter}`, 'GET', undefined, controller.signal).then(setDetail).catch(error => { if (!controller.signal.aborted) { setNotice(error.message); setFailed(true); } });
    };
    refresh(); const interval = setInterval(refresh, 5000);
    return () => { controller.abort(); clearInterval(interval); };
  }, [installed, campaignPage, campaignId, recipientPage, statusFilter, revision, setNotice, setFailed]);

  return (
    <section aria-label="Notice delivery history" className="notice-stack"><section className="workspace-card notice-card"><div className="notice-heading"><h2>Send history</h2><Button type="button" size="sm" variant="outline" onClick={() => setRevision(value => value + 1)}><RefreshCw size={14} />Refresh progress</Button></div><p className="notice-help">Individual email addresses are visible only to Admin and Super Admin. Progress updates every five seconds.</p><ul className="notice-history-list">{campaigns.data.map(campaign => <li key={campaign.id}><button type="button" aria-pressed={campaignId === campaign.id} onClick={() => { router.get(`${base}/history`, { campaign: campaign.id }, { preserveState: true, preserveScroll: true }); setDetail(null); setRecipientPage(1); setStatusFilter(''); }}><strong>{campaign.subject}</strong><span>{dateLabel(campaign.created_at)} · {campaign.counts.total} recipients · {campaign.counts.sent} sent · {campaign.counts.failed} failed</span></button></li>)}</ul>{!campaigns.total && <p className="notice-help">No notices have been sent yet.</p>}<Pagination page={campaigns} change={setCampaignPage} /></section>
      {detail && detail.campaign.id === campaignId && <section className="workspace-card notice-card"><div className="notice-heading"><h2>{detail.campaign.subject}</h2><Button type="button" size="sm" variant="outline" disabled={busy || !sendingReady || !detail.counts.failed} onClick={async () => { const result = await perform(() => requestJson<{ success: string }>(`${base}/campaigns/${detail.campaign.id}/retry`, 'POST', {})); if (result) { setNotice(result.success); setRevision(value => value + 1); } }}>Retry failed recipients</Button></div><CountSummary counts={detail.counts} /><p className="notice-help">Sent = accepted by the mail transport. Unconfirmed sends require a provider-log review; they are never automatically resent.</p><label className="form-label" htmlFor="notice-status-filter">Filter recipients by send status</label><select id="notice-status-filter" className="form-control" value={statusFilter} onChange={event => { setStatusFilter(event.target.value); setRecipientPage(1); }}><option value="">All statuses</option>{statuses.map(status => <option key={status} value={status}>{label(status)}</option>)}</select><div className="workspace-table-scroll"><table className="admin-table notice-results"><caption className="sr-only">Recipient send results</caption><thead><tr><th scope="col">Recipient</th><th scope="col">Status</th><th scope="col">Attempts</th><th scope="col">Details</th></tr></thead><tbody>{detail.recipients.data.map(recipient => <tr key={recipient.id}><td>{recipient.name}<small>{recipient.email}</small></td><td><span className={`notice-delivery-status is-${recipient.status}`}>{label(recipient.status)}</span></td><td>{recipient.attempts}</td><td>{recipient.last_error || (recipient.sent_at ? `Accepted ${dateLabel(recipient.sent_at)}` : 'Awaiting worker')}</td></tr>)}</tbody></table></div><Pagination page={detail.recipients} change={setRecipientPage} /></section>}</section>
  );
}
History.layout = noticeLayout;
