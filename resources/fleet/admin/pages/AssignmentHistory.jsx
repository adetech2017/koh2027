import { Link } from 'react-router';
import { Card, EmptyState, StatusBadge, formatDate } from '../../shared/ui';

/**
 * Past and current assignments, shown on a vehicle (with: 'beneficiary') or a beneficiary (with: 'vehicle').
 */
export default function AssignmentHistory({ assignments, with: other }) {
    return (
        <Card title="History" className="[&>div:last-child]:p-0">
            {assignments.length === 0 ? (
                <EmptyState title="No handovers yet" />
            ) : (
                <ul className="divide-y divide-slate-100">
                    {assignments.map((a) => (
                        <li key={a.id} className="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 text-sm">
                            <div className="min-w-0">
                                <div className="font-medium">
                                    {other === 'vehicle' ? (
                                        <Link to={`/vehicles/${a.vehicle.id}`} className="text-primary hover:underline">{a.vehicle.label}</Link>
                                    ) : (
                                        <Link to={`/beneficiaries/${a.beneficiary.id}`} className="text-primary hover:underline">{a.beneficiary.full_name}</Link>
                                    )}
                                </div>
                                <div className="text-slate-500">
                                    {formatDate(a.handed_over_at)} – {a.returned_at ? formatDate(a.returned_at) : 'now'}
                                    {a.recall && ` · Recall: return by ${formatDate(a.recall.return_by)}`}
                                </div>
                            </div>
                            <div className="flex flex-wrap items-center gap-3">
                                <StatusBadge status={a.status} />
                                {a.handover_id && <Link to={`/handovers/${a.handover_id}`} className="text-primary hover:underline">Handover form</Link>}
                                {a.return_id && <Link to={`/handovers/${a.return_id}`} className="text-primary hover:underline">Return form</Link>}
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}
