import { useState } from 'react';
import { Link, useNavigate } from 'react-router';
import { qs } from '../../shared/api';
import { useApi, useDebounced } from '../../shared/hooks';
import { Button, Card, EmptyState, Field, LoadError, Loading, PageHeader, Pagination, SelectField, StatusBadge, formatDate } from '../../shared/ui';
import { useCanManage, useOptions } from '../options';

const TABS = [['out', 'All out'], ['active', 'Active'], ['recalled', 'Recalled'], ['returned', 'Returned']];

/**
 * Who has which vehicle. Vehicles that are out can be selected and recalled together.
 */
export default function Assignments() {
    const options = useOptions();
    const canManage = useCanManage();
    const navigate = useNavigate();
    const [filters, setFilters] = useState({ status: 'out', search: '', lga: '', page: 1 });
    const [selected, setSelected] = useState([]);
    const search = useDebounced(filters.search);
    const { data, error, loading, reload } = useApi(`/admin/assignments${qs({ ...filters, search })}`);

    const setFilter = (changes) => {
        setFilters({ ...filters, page: 1, ...changes });
        setSelected([]);
    };
    const selectable = (a) => canManage && a.status === 'active';
    const toggle = (id) => setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
    const pageSelectable = data?.data.filter(selectable).map((a) => a.id) ?? [];
    const allSelected = pageSelectable.length > 0 && pageSelectable.every((id) => selected.includes(id));

    return (
        <>
            <PageHeader
                title="Assignments"
                subtitle="Vehicles out with beneficiaries, and past handovers."
                actions={(
                    <>
                        <a href="/api/v1/admin/exports/assignments"><Button variant="secondary">Export CSV</Button></a>
                        {canManage && (
                            <>
                                {selected.length > 0 && (
                                    <Button onClick={() => navigate('/recalls/new', { state: { assignmentIds: selected } })}>
                                        Recall {selected.length} selected
                                    </Button>
                                )}
                                <Link to="/handovers/new"><Button variant={selected.length ? 'secondary' : 'primary'}>Hand over a vehicle</Button></Link>
                            </>
                        )}
                    </>
                )}
            />

            <div className="mb-4 flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1 text-sm">
                {TABS.map(([value, text]) => (
                    <button
                        key={value}
                        type="button"
                        onClick={() => setFilter({ status: value })}
                        className={`rounded-md px-3 py-1.5 font-medium ${filters.status === value ? 'bg-white shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
                    >
                        {text}
                    </button>
                ))}
            </div>

            <div className="mb-4 grid gap-3 sm:grid-cols-2">
                <Field id="search" label="Search" placeholder="Plate number or beneficiary name" value={filters.search} onChange={(e) => setFilter({ search: e.target.value })} />
                <SelectField id="lga" label="LGA" placeholder="All" options={options.lgas.map((l) => [l, l])} value={filters.lga} onChange={(e) => setFilter({ lga: e.target.value })} />
            </div>

            {error ? <LoadError error={error} onRetry={reload} /> : (
                <Card className="overflow-hidden [&>div]:p-0">
                    {loading && !data ? <Loading /> : data.data.length === 0 ? (
                        <EmptyState title="Nothing here" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-medium tracking-wide text-slate-500 uppercase">
                                    <tr>
                                        {canManage && (
                                            <th className="w-10 px-4 py-3">
                                                <input
                                                    type="checkbox"
                                                    className="size-4"
                                                    aria-label="Select all on this page"
                                                    checked={allSelected}
                                                    disabled={pageSelectable.length === 0}
                                                    onChange={() => setSelected(allSelected ? selected.filter((id) => !pageSelectable.includes(id)) : [...new Set([...selected, ...pageSelectable])])}
                                                />
                                            </th>
                                        )}
                                        <th className="px-4 py-3">Vehicle</th>
                                        <th className="px-4 py-3">Beneficiary</th>
                                        <th className="px-4 py-3">Handed over</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3"><span className="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {data.data.map((a) => (
                                        <tr key={a.id} className="hover:bg-slate-50">
                                            {canManage && (
                                                <td className="px-4 py-3">
                                                    {selectable(a) && <input type="checkbox" className="size-4" checked={selected.includes(a.id)} onChange={() => toggle(a.id)} aria-label={`Select ${a.vehicle.plate_number}`} />}
                                                </td>
                                            )}
                                            <td className="px-4 py-3 font-medium whitespace-nowrap">
                                                <Link to={`/vehicles/${a.vehicle.id}`} className="text-primary hover:underline">{a.vehicle.plate_number}</Link>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link to={`/beneficiaries/${a.beneficiary.id}`} className="hover:underline">{a.beneficiary.full_name}</Link>
                                                {a.beneficiary.lga && <span className="text-slate-500"> · {a.beneficiary.lga}</span>}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap">{formatDate(a.handed_over_at)}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <StatusBadge status={a.status} />
                                                {a.recall && a.status === 'recalled' && <div className="mt-1 text-xs text-slate-500">by {formatDate(a.recall.return_by)}</div>}
                                            </td>
                                            <td className="px-4 py-3 text-right whitespace-nowrap">
                                                {canManage && a.status !== 'returned' ? (
                                                    <Link to={`/assignments/${a.id}/return`} className="font-medium text-primary hover:underline">Record return</Link>
                                                ) : a.return_id ? (
                                                    <Link to={`/handovers/${a.return_id}`} className="text-primary hover:underline">Return form</Link>
                                                ) : null}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination meta={data} onPage={(page) => setFilters({ ...filters, page })} />
                </Card>
            )}
        </>
    );
}
