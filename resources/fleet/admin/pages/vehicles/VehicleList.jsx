import { useState } from 'react';
import { Link, useSearchParams } from 'react-router';
import { qs } from '../../../shared/api';
import { useApi, useDebounced } from '../../../shared/hooks';
import { Button, Card, EmptyState, Field, LoadError, Loading, PageHeader, Pagination, SelectField, StatusBadge } from '../../../shared/ui';
import { entries, useCanManage, useOptions } from '../../options';

const STATUS_OPTIONS = [['available', 'Available'], ['assigned', 'With beneficiary'], ['maintenance', 'Maintenance'], ['retired', 'Retired']];

export default function VehicleList() {
    const options = useOptions();
    const canManage = useCanManage();
    const [params] = useSearchParams();
    const [filters, setFilters] = useState({ search: '', status: params.get('status') ?? '', vehicle_type: '', page: 1 });
    const search = useDebounced(filters.search);
    const { data, error, loading, reload } = useApi(`/admin/vehicles${qs({ ...filters, search })}`);

    const set = (key) => (e) => setFilters({ ...filters, [key]: e.target.value, page: 1 });

    return (
        <>
            <PageHeader
                title="Vehicles"
                actions={(
                    <>
                        <a href="/api/v1/admin/exports/vehicles"><Button variant="secondary">Export CSV</Button></a>
                        {canManage && (
                            <>
                                <Link to="/import?type=vehicles"><Button variant="secondary">Import CSV</Button></Link>
                                <Link to="/vehicles/new"><Button>Add vehicle</Button></Link>
                            </>
                        )}
                    </>
                )}
            />

            <div className="mb-4 grid gap-3 sm:grid-cols-3">
                <Field id="search" label="Search" placeholder="Plate, make or model" value={filters.search} onChange={set('search')} />
                <SelectField id="status" label="Status" placeholder="All" options={STATUS_OPTIONS} value={filters.status} onChange={set('status')} />
                <SelectField id="type" label="Type" placeholder="All" options={entries(options.vehicle_types)} value={filters.vehicle_type} onChange={set('vehicle_type')} />
            </div>

            {error ? <LoadError error={error} onRetry={reload} /> : (
                <Card className="overflow-hidden [&>div]:p-0">
                    {loading && !data ? <Loading /> : data.data.length === 0 ? (
                        <EmptyState title="No vehicles found">{canManage && 'Add a vehicle or import a CSV to get started.'}</EmptyState>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-medium tracking-wide text-slate-500 uppercase">
                                    <tr>
                                        <th className="px-4 py-3">Plate</th>
                                        <th className="px-4 py-3">Vehicle</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Beneficiary</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {data.data.map((vehicle) => (
                                        <tr key={vehicle.id} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 font-medium whitespace-nowrap">
                                                <Link to={`/vehicles/${vehicle.id}`} className="text-primary hover:underline">{vehicle.plate_number}</Link>
                                            </td>
                                            <td className="px-4 py-3">
                                                {vehicle.make} {vehicle.model}
                                                <span className="text-slate-500"> · {options.vehicle_types[vehicle.vehicle_type] ?? vehicle.vehicle_type}</span>
                                            </td>
                                            <td className="px-4 py-3"><StatusBadge status={vehicle.status} /></td>
                                            <td className="px-4 py-3">
                                                {vehicle.beneficiary ? (
                                                    <Link to={`/beneficiaries/${vehicle.beneficiary.id}`} className="hover:underline">{vehicle.beneficiary.full_name}</Link>
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
