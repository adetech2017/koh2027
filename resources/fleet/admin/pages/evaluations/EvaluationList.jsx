import { useState } from 'react';
import { Link, useSearchParams } from 'react-router';
import { qs } from '../../../shared/api';
import { useApi, useDebounced } from '../../../shared/hooks';
import { Badge, Card, EmptyState, Field, LoadError, Loading, PageHeader, Pagination, SelectField, formatDate } from '../../../shared/ui';
import { useOptions } from '../../options';
import { FLAGS, PROCESSING, TRUST, TrustBadge } from './labels';

export default function EvaluationList() {
    const options = useOptions();
    const [params] = useSearchParams();
    const [filters, setFilters] = useState({
        search: '',
        trust_level: '',
        lga: '',
        vehicle_id: params.get('vehicle_id') ?? '',
        beneficiary_id: params.get('beneficiary_id') ?? '',
        page: 1,
    });
    const search = useDebounced(filters.search);
    const { data, error, loading, reload } = useApi(`/admin/evaluations${qs({ ...filters, search })}`);

    const set = (key) => (e) => setFilters({ ...filters, [key]: e.target.value, page: 1 });

    return (
        <>
            <PageHeader title="Evaluations" subtitle="Two-weekly videos from beneficiaries, newest first." />

            <div className="mb-4 grid gap-3 sm:grid-cols-3">
                <Field id="search" label="Search" placeholder="Plate number or beneficiary" value={filters.search} onChange={set('search')} />
                <SelectField id="trust" label="Trust" placeholder="All" options={Object.entries(TRUST).map(([k, [, label]]) => [k, label])} value={filters.trust_level} onChange={set('trust_level')} />
                <SelectField id="lga" label="LGA" placeholder="All" options={options.lgas.map((l) => [l, l])} value={filters.lga} onChange={set('lga')} />
            </div>
            {(filters.vehicle_id || filters.beneficiary_id) && (
                <p className="mb-4 text-sm text-slate-600">
                    Showing one {filters.vehicle_id ? 'vehicle' : 'beneficiary'}.{' '}
                    <button type="button" className="text-primary underline" onClick={() => setFilters({ ...filters, vehicle_id: '', beneficiary_id: '', page: 1 })}>Show all</button>
                </p>
            )}

            {error ? <LoadError error={error} onRetry={reload} /> : (
                <Card className="overflow-hidden [&>div]:p-0">
                    {loading && !data ? <Loading /> : data.data.length === 0 ? (
                        <EmptyState title="No evaluations yet">They appear here as beneficiaries submit them.</EmptyState>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {data.data.map((e) => (
                                <li key={e.id}>
                                    <Link to={`/evaluations/${e.id}`} className="flex gap-4 px-4 py-3 hover:bg-slate-50">
                                        <div className="aspect-video w-28 shrink-0 overflow-hidden rounded-md bg-slate-200">
                                            {e.thumbnail_url && <img src={e.thumbnail_url} alt="" loading="lazy" className="size-full object-cover" />}
                                        </div>
                                        <div className="min-w-0 flex-1 text-sm">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="font-medium text-primary">{e.vehicle.plate_number}</span>
                                                <span className="text-slate-600">{e.beneficiary.full_name}</span>
                                            </div>
                                            <div className="mt-1 flex flex-wrap items-center gap-2">
                                                <TrustBadge level={e.trust_level} />
                                                {PROCESSING[e.processing_status] && <Badge tone={PROCESSING[e.processing_status][0]}>{PROCESSING[e.processing_status][1]}</Badge>}
                                                {e.trust_flags.length > 0 && (
                                                    <span className="text-xs text-amber-700" title={e.trust_flags.map((f) => FLAGS[f] ?? f).join('\n')}>
                                                        {e.trust_flags.length} flag{e.trust_flags.length === 1 ? '' : 's'}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="mt-1 text-slate-500">
                                                Submitted {formatDate(e.submitted_at, true)} · {e.mileage_km.toLocaleString()} km
                                                {e.has_damage && <span className="text-red-700"> · damage reported</span>}
                                                {!e.branding_intact && <span className="text-red-700"> · branding issue</span>}
                                            </div>
                                        </div>
                                    </Link>
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
