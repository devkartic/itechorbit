import { useEffect, useRef, useState, type ReactNode } from 'react';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronLeft, ChevronRight, FileText, LoaderCircle, Search, SlidersHorizontal, RotateCw, ArrowUpRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Notice } from './Notice';
import { requestJson } from '@/lib/http';
import { dateLabel } from '@/lib/utils';
import type { Paginated } from '@/lib/types';
import type { Row, TableFilters, TableResponse } from './types';
const label = (column: string) => column === 'portfolio_status' ? 'Portfolio' : column === 'created_at' ? 'Created' : column.replace(/_/g, ' ').replace(/^\w/, c => c.toUpperCase());
function PortfolioCell({ row }: { row: Row }) {
  const active = Number(row.status) === 1 && Number(row.portfolio_status) === 1 && (!('public_portfolio' in row) || !!row.public_portfolio);
  return active && row.public_portfolio
    ? <a className="workspace-badge is-active table-portfolio-link" href={String(row.public_portfolio)} target="_blank" rel="noopener noreferrer" aria-label={`View public portfolio for ${row.name}`}><span aria-hidden="true" />Portfolio <ArrowUpRight size={15} aria-hidden="true" /></a>
    : <span className={`workspace-badge ${active ? 'is-active' : 'is-inactive'}`}><span aria-hidden="true" />{active ? 'Active' : 'Inactive'}</span>;
}
export function DataTable({ resource, title, columns, initial, filters, revision, actions }: { resource: string; title: string; columns: string[]; initial: Paginated<Row>; filters: TableFilters; revision: number; actions: (row: Row) => ReactNode }) {
  const [state, setState] = useState<TableFilters>({ ...filters, key: filters.key ?? '', status: filters.status ?? '' });
  const [query, setQuery] = useState(filters.key ?? '');
  const [records, setRecords] = useState(initial);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [retry, setRetry] = useState(0);
  const first = useRef(true);
  useEffect(() => {
    if (query === state.key) return;
    const timeout = window.setTimeout(() => setState(previous => ({ ...previous, key: query, page: 1 })), 350);
    return () => window.clearTimeout(timeout);
  }, [query, state.key]);
  useEffect(() => {
    if (first.current) { first.current = false; return; }
    const controller = new AbortController();
    const search = new URLSearchParams(Object.entries(state).map(([key, value]) => [key, String(value ?? '')]));
    setLoading(true); setError('');
    requestJson<TableResponse>(`/workspace/data/${resource}?${search}`, 'GET', undefined, controller.signal).then(result => {
      if (controller.signal.aborted) return;
      setRecords(result.records);
      if (result.records.current_page > result.records.last_page) setState(previous => ({ ...previous, page: result.records.last_page }));
    }).catch(reason => { if (!controller.signal.aborted) setError(reason instanceof Error ? reason.message : 'Unable to load records.'); }).finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [resource, state, revision, retry]);
  const change = (value: Partial<TableFilters>) => setState(previous => ({ ...previous, page: 1, ...value }));
  return <section className="workspace-table-panel" aria-label={`${title} data table`}>
    <div className="workspace-table-heading"><div><h2>All {title.toLowerCase()} <span>{records.total}</span></h2><p>Search, sort and filter your records in one place.</p></div><span className="table-live-state">{loading ? <><LoaderCircle className="animate-spin" size={14} />Updating</> : error ? 'Needs attention' : <><span />Up to date</>}</span></div>
    <form className="workspace-table-tools" role="search" onSubmit={event => { event.preventDefault(); change({ key: query }); }}>
      <div className="workspace-search"><Search size={17} aria-hidden="true" /><label htmlFor="workspace-search" className="sr-only">Search {title}</label><input id="workspace-search" type="search" maxLength={200} value={query} onChange={event => setQuery(event.target.value)} placeholder={`Search ${title.toLowerCase()}…`} /><button type="submit" aria-label="Search" className="sr-only">Search</button></div>
      <label className="workspace-filter"><SlidersHorizontal size={15} aria-hidden="true" /><span className="sr-only">Filter by status</span><select aria-label="Filter by status" value={state.status} onChange={event => change({ status: event.target.value })}><option value="">All statuses</option><option value="1">{resource === 'contacts' ? 'Read' : 'Active'}</option><option value="0">{resource === 'contacts' ? 'Unread' : 'Inactive'}</option></select></label>
      <button type="button" className="workspace-icon-button" aria-label="Refresh records" disabled={loading} onClick={() => setRetry(value => value + 1)}><RotateCw size={17} /></button>
    </form>
    {(state.key || state.status !== '') && <div className="workspace-filter-summary" aria-label="Applied filters"><span>Filtered view</span>{state.key && <span>Search: {state.key}</span>}{state.status !== '' && <span>Status: {resource === 'contacts' ? state.status === '1' ? 'Read' : 'Unread' : state.status === '1' ? 'Active' : 'Inactive'}</span>}<button type="button" onClick={() => { setQuery(''); change({ key: '', status: '' }); }}>Reset filters</button></div>}
    {error && <div className="workspace-table-error"><Notice message={error} error /><Button variant="outline" size="sm" onClick={() => setRetry(value => value + 1)}>Retry</Button></div>}
    <p className="sr-only" aria-live="polite">{loading ? 'Loading records…' : error ? 'Records could not be refreshed.' : `${records.total} matching records`}</p><div className="workspace-table-scroll" aria-busy={loading}><table className={`admin-table${resource === 'contacts' ? ' contact-inquiries-table' : ''}`}><caption className="sr-only">{title}, sorted and paginated on the server</caption><thead><tr>{columns.map(column => <th key={column} scope="col" aria-sort={state.sort === column ? state.direction === 'asc' ? 'ascending' : 'descending' : 'none'}><button type="button" onClick={() => change({ sort: column, direction: state.sort === column && state.direction === 'asc' ? 'desc' : 'asc' })} aria-label={`Sort by ${label(column)}`}>{label(column)}{state.sort === column ? state.direction === 'asc' ? <ArrowUp size={13} /> : <ArrowDown size={13} /> : <ArrowUpDown size={13} />}</button></th>)}<th scope="col" className="table-actions-heading">Actions</th></tr></thead><tbody>{records.data.map(row => <tr key={String(row.id)} className={resource === 'contacts' && Number(row.read_status) === 0 ? 'is-unread-inquiry' : undefined}>{columns.map((column, index) => <td key={column}>{column === 'portfolio_status' ? <PortfolioCell row={row} /> : resource === 'contacts' && column === 'read_status' ? Number(row.read_status) ? <span className="table-read-status"><span aria-hidden="true" />Read</span> : <span className="workspace-badge is-unread"><span aria-hidden="true" />Unread</span> : column.includes('status') ? <span className={`workspace-badge ${Number(row[column]) ? 'is-active' : resource === 'users' ? 'is-inactive' : 'is-muted'}`}><span />{column === 'read_status' ? Number(row[column]) ? 'Read' : 'Unread' : Number(row[column]) ? 'Active' : 'Inactive'}</span> : column === 'created_at' ? <span className="table-date">{row[column] ? dateLabel(String(row[column])) : '—'}</span> : <span className={index === 0 ? 'table-record-title' : resource === 'contacts' && column === 'subject' ? 'table-cell-copy table-inquiry-subject' : 'table-cell-copy'}>{index === 0 && <span className="table-record-icon"><FileText size={17} /></span>}<span>{String(row[column] ?? '—')}{index === 0 && <small>#{row.id}</small>}</span></span>}</td>)}<td><div className="table-row-actions">{actions(row)}</div></td></tr>)}</tbody></table>{!records.data.length && <div className="workspace-table-empty"><Search size={26} /><h3>No {title.toLowerCase()} found</h3><p>{query || state.status ? 'Try another search or clear your filters.' : 'Your records will appear here when you add them.'}</p>{(query || state.status) && <button type="button" onClick={() => { setQuery(''); change({ key: '', status: '' }); }}>Clear filters</button>}</div>}</div>
    <footer className="workspace-table-footer"><p aria-live="polite">{records.total ? `${(records.current_page - 1) * state.per_page + 1}–${Math.min(records.current_page * state.per_page, records.total)} of ${records.total} records` : '0 records'}</p><div><label>Rows per page <select aria-label="Rows per page" value={state.per_page} onChange={event => change({ per_page: Number(event.target.value) })}>{[10, 20, 25, 50, 100].map(size => <option key={size}>{size}</option>)}</select></label><span>Page {records.current_page} of {records.last_page}</span><button type="button" className="workspace-icon-button" aria-label="Previous page" disabled={loading || records.current_page <= 1} onClick={() => setState(previous => ({ ...previous, page: previous.page - 1 }))}><ChevronLeft size={17} /></button><button type="button" className="workspace-icon-button" aria-label="Next page" disabled={loading || records.current_page >= records.last_page} onClick={() => setState(previous => ({ ...previous, page: previous.page + 1 }))}><ChevronRight size={17} /></button></div></footer>
  </section>;
}
