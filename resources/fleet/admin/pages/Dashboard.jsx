import { Link } from 'react-router';
import { useApi } from '../../shared/hooks';
import { Badge, Button, Card, EmptyState, LoadError, Loading, PageHeader, formatDate } from '../../shared/ui';
import { useAuth } from '../auth';
import { useCanManage } from '../options';
import { WeeklyChart } from '../charts';

export default function Dashboard() {
    const { user } = useAuth();
    const canManage = useCanManage();
    const { data, error, reload } = useApi('/admin/dashboard');

    if (error) return <LoadError error={error} onRetry={reload} />;
    if (!data) return <Loading />;

    const v = data.vehicles;
    const totalVehicles = Object.values(v).reduce((a, b) => a + b, 0);

    return (
        <>
            <PageHeader
                title={`Welcome, ${user.name.split(' ')[0]}`}
                actions={canManage && <Link to="/handovers/new"><Button>Hand over a vehicle</Button></Link>}
            />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Stat label="Vehicles" value={totalVehicles} to="/vehicles" />
                <Stat label="With beneficiaries" value={v.assigned} to="/assignments" />
                <Stat label="Available" value={v.available} to="/vehicles?status=available" note={v.maintenance ? `${v.maintenance} in maintenance` : null} />
                <Stat
                    label="Awaiting return (recalled)"
                    value={data.recalls.vehicles_outstanding}
                    to="/recalls"
                    note={data.recalls.open ? `${data.recalls.open} open recall${data.recalls.open === 1 ? '' : 's'}` : null}
                />
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Stat label="Waiting for review" value={data.evaluations.pending_review} to="/evaluations?review_status=pending" />
                <Stat
                    label="Videos in, this window"
                    value={`${data.evaluations.this_window.sent} / ${data.evaluations.this_window.vehicles}`}
                    to="/compliance"
                />
                <Stat label="Missed (last 30 days)" value={data.evaluations.missed_30_days} to="/compliance" />
                <Stat
                    label="WhatsApp failures (7 days)"
                    value={data.evaluations.notifications_failed_7_days}
                    to="/beneficiaries"
                    note={data.evaluations.notifications_failed_7_days ? 'Check the template names and access token' : null}
                />
            </div>

            <Card title="Evaluations due each week" className="mt-6">
                <WeeklyChart weeks={data.evaluations.weekly} />
            </Card>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card title="Recent handovers and returns" className="lg:col-span-2 [&>div:last-child]:p-0">
                    {data.recent_handovers.length === 0 ? <EmptyState title="Nothing yet" /> : (
                        <ul className="divide-y divide-slate-100 text-sm">
                            {data.recent_handovers.map((h) => (
                                <li key={h.id}>
                                    <Link to={`/handovers/${h.id}`} className="flex flex-wrap items-center justify-between gap-2 px-5 py-3 hover:bg-slate-50">
                                        <span>
                                            <span className="font-medium">{h.vehicle}</span>
                                            <span className="text-slate-600"> {h.type === 'return' ? 'returned by' : 'handed to'} {h.beneficiary}</span>
                                        </span>
                                        <span className="flex items-center gap-2 text-slate-500">
                                            <Badge tone={h.type === 'return' ? 'slate' : 'blue'}>{h.reference}</Badge>
                                            {formatDate(h.occurred_at, true)}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
                <Card title="Beneficiaries">
                    <p className="text-3xl font-semibold">{data.beneficiaries.total}</p>
                    <p className="mt-1 text-sm text-slate-600">{data.beneficiaries.with_vehicle} currently have a vehicle.</p>
                    <Link to="/beneficiaries" className="mt-4 inline-block text-sm font-medium text-primary hover:underline">View all</Link>
                </Card>
            </div>
        </>
    );
}

function Stat({ label, value, to, note }) {
    return (
        <Link to={to} className="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 hover:ring-primary/40">
            <div className="text-sm text-slate-600">{label}</div>
            <div className="mt-1 text-3xl font-semibold">{value}</div>
            {note && <div className="mt-1 text-xs text-slate-500">{note}</div>}
        </Link>
    );
}
