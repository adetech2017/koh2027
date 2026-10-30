import { useState } from 'react';
import { Link, useParams } from 'react-router';
import { api } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Alert, Button, Card, Details, LoadError, Loading, PageHeader, StatusBadge, formatDate } from '../../../shared/ui';
import { useCanManage, useOptions } from '../../options';
import AssignmentHistory from '../AssignmentHistory';
import { MileageChart } from '../../charts';

export default function VehicleDetail() {
    const { id } = useParams();
    const options = useOptions();
    const canManage = useCanManage();
    const { data, error, reload } = useApi(`/admin/vehicles/${id}`);
    const [statusError, setStatusError] = useState(null);
    const [saving, setSaving] = useState(null);

    if (error) return <LoadError error={error} onRetry={reload} />;
    if (!data) return <Loading />;

    const { vehicle, assignments } = data;
    const current = assignments.find((a) => a.status !== 'returned');

    async function setStatus(status) {
        setSaving(status);
        setStatusError(null);
        try {
            await api(`/admin/vehicles/${id}`, { method: 'PATCH', body: { status } });
            reload();
        } catch (e) {
            setStatusError(e.field('status') || e.message);
        } finally {
            setSaving(null);
        }
    }

    return (
        <>
            <PageHeader
                title={vehicle.plate_number}
                subtitle={`${vehicle.make} ${vehicle.model}${vehicle.year ? ` (${vehicle.year})` : ''}`}
                actions={(
                    <>
                        <Link to={`/evaluations?vehicle_id=${id}`}><Button variant="secondary">Evaluations</Button></Link>
                        {canManage && (
                            <>
                                <Link to={`/vehicles/${id}/edit`}><Button variant="secondary">Edit</Button></Link>
                                {vehicle.status === 'available' && <Link to={`/handovers/new?vehicle=${id}`}><Button>Hand over</Button></Link>}
                                {current && <Link to={`/assignments/${current.id}/return`}><Button>Record return</Button></Link>}
                            </>
                        )}
                    </>
                )}
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card title="Details">
                        <Details items={[
                            ['Type', options.vehicle_types[vehicle.vehicle_type] ?? vehicle.vehicle_type],
                            ['Colour', vehicle.colour],
                            ['Chassis number', vehicle.chassis_number],
                            ['Engine number', vehicle.engine_number],
                            ['Branding', vehicle.branding],
                            ['Notes', vehicle.notes],
                        ]} />
                    </Card>
                    <Mileage vehicleId={id} />
                    <AssignmentHistory assignments={assignments} with="beneficiary" />
                </div>

                <div className="space-y-6">
                    <Card title="Status">
                        <StatusBadge status={vehicle.status} />
                        {current && (
                            <p className="mt-3 text-sm text-slate-600">
                                With <Link to={`/beneficiaries/${current.beneficiary.id}`} className="font-medium text-primary hover:underline">{current.beneficiary.full_name}</Link>
                                {current.status === 'recalled' && ' — recalled, awaiting return'}
                            </p>
                        )}
                        {canManage && vehicle.status !== 'assigned' && (
                            <div className="mt-4 space-y-2">
                                {statusError && <Alert>{statusError}</Alert>}
                                <div className="flex flex-wrap gap-2">
                                    {['available', 'maintenance', 'retired'].filter((s) => s !== vehicle.status).map((status) => (
                                        <Button key={status} variant="secondary" className="py-1.5" loading={saving === status} onClick={() => setStatus(status)}>
                                            Mark {status === 'available' ? 'available' : status === 'maintenance' ? 'in maintenance' : 'retired'}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </>
    );
}

const SOURCES = { handover: 'Handover', return: 'Return', evaluation: 'Evaluation' };

function Mileage({ vehicleId }) {
    const { data, error, reload } = useApi(`/admin/vehicles/${vehicleId}/mileage`);
    const [showTable, setShowTable] = useState(false);

    if (error) return <LoadError error={error} onRetry={reload} />;
    if (!data || data.readings.length === 0) return null;

    const r = data.readings;
    const driven = r[r.length - 1].km - r[0].km;

    return (
        <Card
            title="Mileage"
            actions={<button type="button" onClick={() => setShowTable(!showTable)} className="text-sm font-medium text-primary hover:underline">{showTable ? 'Show chart' : 'Show table'}</button>}
        >
            <p className="mb-3 text-sm text-slate-600">
                {driven.toLocaleString()} km driven since {formatDate(r[0].date)}, across {r.length} reading{r.length === 1 ? '' : 's'}.
            </p>
            {showTable ? (
                <div className="overflow-x-auto">
                    <table className="min-w-full text-sm">
                        <thead className="text-left text-xs font-medium tracking-wide text-slate-500 uppercase">
                            <tr><th className="py-2 pr-4">Date</th><th className="py-2 pr-4">Reading</th><th className="py-2 pr-4 text-right">Odometer</th><th className="py-2 text-right">Since previous</th></tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {r.map((reading, i) => (
                                <tr key={i}>
                                    <td className="py-2 pr-4 whitespace-nowrap">{formatDate(reading.date)}</td>
                                    <td className="py-2 pr-4"><Link to={reading.link} className="text-primary hover:underline">{SOURCES[reading.source]}</Link> · {reading.beneficiary}</td>
                                    <td className="py-2 pr-4 text-right">{reading.km.toLocaleString()} km</td>
                                    <td className={`py-2 text-right ${i && reading.km < r[i - 1].km ? 'text-red-700' : ''}`}>{i ? `${(reading.km - r[i - 1].km).toLocaleString()} km` : '—'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <MileageChart readings={r} labelFor={(p) => `${SOURCES[p.source]} · ${p.beneficiary}`} />
            )}
        </Card>
    );
}
