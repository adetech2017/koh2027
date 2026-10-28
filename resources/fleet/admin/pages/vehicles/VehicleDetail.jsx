import { useState } from 'react';
import { Link, useParams } from 'react-router';
import { api } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Alert, Button, Card, Details, LoadError, Loading, PageHeader, StatusBadge } from '../../../shared/ui';
import { useCanManage, useOptions } from '../../options';
import AssignmentHistory from '../AssignmentHistory';

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
