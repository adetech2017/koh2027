import { useState } from 'react';
import { Link, useParams } from 'react-router';
import { qs } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Card, Details, EmptyState, LoadError, Loading, PageHeader, Pagination, StatusBadge, formatDate } from '../../../shared/ui';
import { useCanManage } from '../../options';
import { RecallProgress } from './RecallList';

export default function RecallDetail() {
    const { id } = useParams();
    const canManage = useCanManage();
    const [page, setPage] = useState(1);
    const recall = useApi(`/admin/recalls/${id}`);
    const vehicles = useApi(`/admin/assignments${qs({ recall_id: id, page, per_page: 50 })}`);

    if (recall.error) return <LoadError error={recall.error} onRetry={recall.reload} />;
    if (!recall.data) return <Loading />;

    const r = recall.data.recall;

    return (
        <>
            <PageHeader title={r.title} subtitle={`Return by ${formatDate(r.return_by)}`} />
            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6">
                    <Card title="Progress"><RecallProgress recall={r} /></Card>
                    <Card title="Details">
                        <Details items={[['Reason', r.reason], ['Created by', r.created_by], ['Created', formatDate(r.created_at, true)]]} />
                    </Card>
                </div>
                <Card title="Vehicles" className="lg:col-span-2 [&>div:last-child]:p-0">
                    {vehicles.error ? <div className="p-5"><LoadError error={vehicles.error} onRetry={vehicles.reload} /></div> : !vehicles.data ? <Loading /> : vehicles.data.data.length === 0 ? <EmptyState title="No vehicles" /> : (
                        <>
                            <ul className="divide-y divide-slate-100 text-sm">
                                {vehicles.data.data.map((a) => (
                                    <li key={a.id} className="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                                        <div>
                                            <Link to={`/vehicles/${a.vehicle.id}`} className="font-medium text-primary hover:underline">{a.vehicle.plate_number}</Link>
                                            <span className="text-slate-600"> · {a.beneficiary.full_name}</span>
                                            <div className="text-slate-500">{a.beneficiary.phone}</div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <StatusBadge status={a.status} />
                                            {a.status === 'returned'
                                                ? <Link to={`/handovers/${a.return_id}`} className="text-primary hover:underline">Return form</Link>
                                                : canManage && <Link to={`/assignments/${a.id}/return`} className="font-medium text-primary hover:underline">Record return</Link>}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                            <Pagination meta={vehicles.data} onPage={setPage} />
                        </>
                    )}
                </Card>
            </div>
        </>
    );
}
