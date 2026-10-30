import { useState } from 'react';
import { Link, NavLink, useLocation, useSearchParams } from 'react-router';
import { qs } from '../../../shared/api';
import { useApi, useDebounced } from '../../../shared/hooks';
import { Alert, Button, Card, EmptyState, Field, LoadError, Loading, PageHeader, Pagination, SelectField, formatDate } from '../../../shared/ui';
import { useCanManage } from '../../options';
import { MESSAGE_STATUS, MESSAGE_TYPES, MessageStatus } from './labels';

export function MessageTabs() {
    const tab = ({ isActive }) => `rounded-md px-3 py-1.5 font-medium ${isActive ? 'bg-white shadow-sm' : 'text-slate-600 hover:text-slate-900'}`;

    return (
        <div className="mb-4 flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1 text-sm">
            <NavLink to="/messages" end className={tab}>Sent messages</NavLink>
            <NavLink to="/messages/templates" className={tab}>Templates</NavLink>
        </div>
    );
}

/**
 * Every WhatsApp message to beneficiaries, automatic or sent by an admin, newest first.
 */
export default function MessageLog() {
    const canManage = useCanManage();
    const location = useLocation();
    const [params] = useSearchParams();
    const [filters, setFilters] = useState({ search: '', type: '', status: '', beneficiary_id: params.get('beneficiary_id') ?? '', page: 1 });
    const search = useDebounced(filters.search);
    const { data, error, loading, reload } = useApi(`/admin/messages${qs({ ...filters, search })}`);

    const set = (key) => (e) => setFilters({ ...filters, [key]: e.target.value, page: 1 });

    return (
        <>
            <PageHeader
                title="Messages"
                subtitle="WhatsApp messages to beneficiaries, newest first."
                actions={canManage && <Link to="/messages/send"><Button>Send a message</Button></Link>}
            />
            <MessageTabs />

            {location.state?.queued && (
                <div className="mb-4">
                    <Alert tone="success">
                        {location.state.queued === 1 ? 'The message is on its way.' : `${location.state.queued} messages are on their way.`} Their status updates below.
                    </Alert>
                </div>
            )}

            <div className="mb-4 grid gap-3 sm:grid-cols-3">
                <Field id="search" label="Search" placeholder="Beneficiary name or phone" value={filters.search} onChange={set('search')} />
                <SelectField id="type" label="Message" placeholder="All" options={Object.entries(MESSAGE_TYPES)} value={filters.type} onChange={set('type')} />
                <SelectField id="status" label="Status" placeholder="All" options={Object.entries(MESSAGE_STATUS).map(([k, [, label]]) => [k, label])} value={filters.status} onChange={set('status')} />
            </div>
            {filters.beneficiary_id && (
                <p className="mb-4 text-sm text-slate-600">
                    Showing one beneficiary.{' '}
                    <button type="button" className="text-primary underline" onClick={() => setFilters({ ...filters, beneficiary_id: '', page: 1 })}>Show all</button>
                </p>
            )}

            {error ? <LoadError error={error} onRetry={reload} /> : (
                <Card className="overflow-hidden [&>div]:p-0">
                    {loading && !data ? <Loading /> : data.data.length === 0 ? (
                        <EmptyState title="No messages yet">Reminders, rejections, recalls and messages admins send appear here.</EmptyState>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {data.data.map((m) => (
                                <li key={m.id} className="px-4 py-3 text-sm">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Link to={`/beneficiaries/${m.beneficiary.id}`} className="font-medium text-primary hover:underline">{m.beneficiary.full_name}</Link>
                                            <span className="text-slate-500">{m.title ?? MESSAGE_TYPES[m.type] ?? m.type}</span>
                                        </div>
                                        <MessageStatus status={m.status} />
                                    </div>
                                    {m.body && <p className="mt-1 max-w-3xl text-slate-700">{m.body}</p>}
                                    <div className="mt-1 text-xs text-slate-500">
                                        {formatDate(m.sent_at ?? m.created_at, true)}
                                        {m.sent_by && ` · sent by ${m.sent_by}`}
                                    </div>
                                    {m.error && <div className="mt-1 text-xs break-words text-red-700">{m.error}</div>}
                                </li>
                            ))}
                        </ul>
                    )}
                    <Pagination meta={data} onPage={(page) => setFilters({ ...filters, page })} />
                </Card>
            )}
        </>
    );
}
