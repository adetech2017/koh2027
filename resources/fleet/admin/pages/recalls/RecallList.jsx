import { Link } from 'react-router';
import { useApi } from '../../../shared/hooks';
import { Badge, Button, Card, EmptyState, LoadError, Loading, PageHeader, formatDate } from '../../../shared/ui';
import { useCanManage } from '../../options';

export default function RecallList() {
    const canManage = useCanManage();
    const { data, error, reload } = useApi('/admin/recalls');

    return (
        <>
            <PageHeader
                title="Recalls"
                subtitle="Committee decisions to call vehicles back."
                actions={canManage && <Link to="/recalls/new"><Button>New recall</Button></Link>}
            />
            {error ? <LoadError error={error} onRetry={reload} /> : !data ? <Loading /> : (
                <Card className="[&>div]:p-0">
                    {data.data.length === 0 ? <EmptyState title="No recalls yet" /> : (
                        <ul className="divide-y divide-slate-100">
                            {data.data.map((r) => <RecallRow key={r.id} recall={r} />)}
                        </ul>
                    )}
                </Card>
            )}
        </>
    );
}

export function RecallProgress({ recall }) {
    const percent = recall.vehicles_total ? Math.round((recall.vehicles_returned / recall.vehicles_total) * 100) : 0;
    const done = recall.vehicles_returned === recall.vehicles_total;

    return (
        <div>
            <div className="flex items-center justify-between text-sm">
                <span>{recall.vehicles_returned} of {recall.vehicles_total} returned</span>
                {done ? <Badge tone="green">Complete</Badge> : recall.overdue ? <Badge tone="red">Overdue</Badge> : <Badge tone="amber">Open</Badge>}
            </div>
            <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow={percent} aria-valuemin={0} aria-valuemax={100}>
                <div className={`h-full rounded-full ${done ? 'bg-green-500' : 'bg-primary'}`} style={{ width: `${percent}%` }} />
            </div>
        </div>
    );
}

function RecallRow({ recall }) {
    return (
        <li>
            <Link to={`/recalls/${recall.id}`} className="grid gap-3 px-5 py-4 hover:bg-slate-50 sm:grid-cols-2 sm:items-center">
                <div>
                    <div className="font-medium text-primary">{recall.title}</div>
                    <div className="text-sm text-slate-500">Return by {formatDate(recall.return_by)} · created {formatDate(recall.created_at)}</div>
                </div>
                <RecallProgress recall={recall} />
            </Link>
        </li>
    );
}
