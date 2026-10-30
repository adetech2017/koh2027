import { useState } from 'react';
import { Link } from 'react-router';
import { qs } from '../../shared/api';
import { useApi } from '../../shared/hooks';
import { Badge, Button, Card, EmptyState, LoadError, Loading, PageHeader, SelectField, formatDate } from '../../shared/ui';
import { useOptions } from '../options';

const STANDING = {
    overdue: ['red', 'Overdue'],
    due_soon: ['amber', 'Due soon'],
    ok: ['green', 'On track'],
};

const WINDOW = {
    open: 'Not sent yet',
    submitted: 'Sent, awaiting review',
    approved: 'Approved',
};

/**
 * Every vehicle that's out and how its beneficiary is keeping up with evaluations, worst first.
 */
export default function Compliance() {
    const options = useOptions();
    const [lga, setLga] = useState('');
    const [standing, setStanding] = useState('');
    const { data, error, reload } = useApi(`/admin/compliance${qs({ lga })}`);

    const rows = data?.data.filter((r) => !standing || r.standing === standing) ?? [];

    return (
        <>
            <PageHeader
                title="Compliance"
                subtitle="Vehicles out with beneficiaries and whether their two-weekly videos are coming in."
                actions={<a href={`/api/v1/admin/exports/compliance${qs({ lga })}`}><Button variant="secondary">Export CSV</Button></a>}
            />

            {error ? <LoadError error={error} onRetry={reload} /> : !data ? <Loading /> : (
                <>
                    <div className="mb-4 grid gap-3 sm:grid-cols-4">
                        {[['', 'Vehicles out', data.summary.vehicles_out], ['overdue', 'Overdue', data.summary.overdue], ['due_soon', 'Due soon', data.summary.due_soon], ['ok', 'On track', data.summary.ok]].map(([value, text, count]) => (
                            <button
                                key={text}
                                type="button"
                                onClick={() => setStanding(value)}
                                aria-pressed={standing === value}
                                className={`rounded-xl bg-white p-4 text-left shadow-sm ring-1 ${standing === value ? 'ring-2 ring-primary' : 'ring-slate-200 hover:ring-slate-300'}`}
                            >
                                <div className="text-sm text-slate-600">{text}</div>
                                <div className="mt-1 text-2xl font-semibold">{count}</div>
                            </button>
                        ))}
                    </div>

                    <div className="mb-4 max-w-xs">
                        <SelectField id="lga" label="LGA" placeholder="All" options={options.lgas.map((l) => [l, l])} value={lga} onChange={(e) => setLga(e.target.value)} />
                    </div>

                    <Card className="overflow-hidden [&>div]:p-0">
                        {rows.length === 0 ? <EmptyState title="No vehicles here" /> : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-slate-50 text-left text-xs font-medium tracking-wide text-slate-500 uppercase">
                                        <tr>
                                            <th className="px-4 py-3">Vehicle</th>
                                            <th className="px-4 py-3">Beneficiary</th>
                                            <th className="px-4 py-3">Standing</th>
                                            <th className="px-4 py-3">This window</th>
                                            <th className="px-4 py-3 text-right">On time</th>
                                            <th className="px-4 py-3 text-right">Missed</th>
                                            <th className="px-4 py-3">Last video</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {rows.map((r) => {
                                            const [tone, text] = STANDING[r.standing];
                                            return (
                                                <tr key={r.assignment_id} className="hover:bg-slate-50">
                                                    <td className="px-4 py-3 font-medium whitespace-nowrap">
                                                        <Link to={`/vehicles/${r.vehicle.id}`} className="text-primary hover:underline">{r.vehicle.plate_number}</Link>
                                                        {r.recalled && <span className="ml-2"><Badge tone="amber">Recalled</Badge></span>}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Link to={`/beneficiaries/${r.beneficiary.id}`} className="hover:underline">{r.beneficiary.full_name}</Link>
                                                        <div className="text-slate-500">{r.beneficiary.phone}{r.beneficiary.lga && ` · ${r.beneficiary.lga}`}</div>
                                                    </td>
                                                    <td className="px-4 py-3"><Badge tone={tone}>{text}</Badge></td>
                                                    <td className="px-4 py-3 whitespace-nowrap">
                                                        {r.current ? (
                                                            <>
                                                                {WINDOW[r.current.status] ?? r.current.status}
                                                                <div className="text-slate-500">due {formatDate(r.current.ends_at)}</div>
                                                            </>
                                                        ) : '—'}
                                                    </td>
                                                    <td className="px-4 py-3 text-right">{r.on_time_rate === null ? '—' : `${Math.round(r.on_time_rate * 100)}%`}</td>
                                                    <td className="px-4 py-3 text-right">{r.missed}</td>
                                                    <td className="px-4 py-3 whitespace-nowrap">
                                                        {r.last_submitted_at ? (
                                                            <Link to={`/evaluations?vehicle_id=${r.vehicle.id}`} className="hover:underline">{formatDate(r.last_submitted_at)}</Link>
                                                        ) : <span className="text-slate-400">None yet</span>}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Card>
                </>
            )}
        </>
    );
}
