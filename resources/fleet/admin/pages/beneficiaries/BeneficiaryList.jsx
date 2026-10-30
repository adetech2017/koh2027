import { useState } from 'react';
import { Link } from 'react-router';
import { qs } from '../../../shared/api';
import { useApi, useDebounced } from '../../../shared/hooks';
import { Button, Card, EmptyState, Field, LoadError, Loading, PageHeader, Pagination, SelectField, StatusBadge } from '../../../shared/ui';
import { useCanManage, useOptions } from '../../options';

export default function BeneficiaryList() {
    const options = useOptions();
    const canManage = useCanManage();
    const [filters, setFilters] = useState({ search: '', lga: '', vehicle: '', status: '', page: 1 });
    const search = useDebounced(filters.search);
    const { data, error, loading, reload } = useApi(`/admin/beneficiaries${qs({ ...filters, search })}`);

    const set = (key) => (e) => setFilters({ ...filters, [key]: e.target.value, page: 1 });

    return (
        <>
            <PageHeader
                title="Beneficiaries"
                actions={(
                    <>
                        <a href="/api/v1/admin/exports/beneficiaries"><Button variant="secondary">Export CSV</Button></a>
                        {canManage && (
                            <>
                                <Link to="/import?type=beneficiaries"><Button variant="secondary">Import CSV</Button></Link>
                                <Link to="/beneficiaries/new"><Button>Add beneficiary</Button></Link>
                            </>
                        )}
                    </>
                )}
            />

            <div className="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Field id="search" label="Search" placeholder="Name, phone or ID number" value={filters.search} onChange={set('search')} />
                <SelectField id="lga" label="LGA" placeholder="All" options={options.lgas.map((l) => [l, l])} value={filters.lga} onChange={set('lga')} />
                <SelectField id="vehicle" label="Vehicle" placeholder="All" options={[['with', 'Has a vehicle'], ['without', 'No vehicle']]} value={filters.vehicle} onChange={set('vehicle')} />
                <SelectField id="status" label="Account" placeholder="All" options={[['active', 'Active'], ['suspended', 'Suspended']]} value={filters.status} onChange={set('status')} />
            </div>

            {error ? <LoadError error={error} onRetry={reload} /> : (
                <Card className="overflow-hidden [&>div]:p-0">
                    {loading && !data ? <Loading /> : data.data.length === 0 ? (
                        <EmptyState title="No beneficiaries found">{canManage && 'Add a beneficiary or import a CSV to get started.'}</EmptyState>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-medium tracking-wide text-slate-500 uppercase">
                                    <tr>
                                        <th className="px-4 py-3">Name</th>
                                        <th className="px-4 py-3">Phone</th>
                                        <th className="px-4 py-3">LGA</th>
                                        <th className="px-4 py-3">Vehicle</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {data.data.map((b) => (
                                        <tr key={b.id} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 font-medium">
                                                <Link to={`/beneficiaries/${b.id}`} className="text-primary hover:underline">{b.full_name}</Link>
                                                {!b.active && <span className="ml-2"><StatusBadge status="suspended" /></span>}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap">{b.phone}</td>
                                            <td className="px-4 py-3">{b.lga ?? <span className="text-slate-400">—</span>}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                {b.vehicle ? (
                                                    <Link to={`/vehicles/${b.vehicle.id}`} className="hover:underline">{b.vehicle.plate_number}</Link>
                                                ) : <span className="text-slate-400">—</span>}
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
