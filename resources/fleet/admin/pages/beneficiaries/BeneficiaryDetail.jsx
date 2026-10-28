import { useState } from 'react';
import { Link, useParams } from 'react-router';
import { api } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Alert, Button, Card, Details, LoadError, Loading, PageHeader, StatusBadge, formatDate } from '../../../shared/ui';
import { useCanManage, useOptions } from '../../options';
import AssignmentHistory from '../AssignmentHistory';

export default function BeneficiaryDetail() {
    const { id } = useParams();
    const options = useOptions();
    const canManage = useCanManage();
    const { data, error, reload } = useApi(`/admin/beneficiaries/${id}`);
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState(null);

    if (error) return <LoadError error={error} onRetry={reload} />;
    if (!data) return <Loading />;

    const { beneficiary: b, assignments } = data;
    const current = assignments.find((a) => a.status !== 'returned');

    async function setActive(active) {
        if (!active && !confirm(`Suspend ${b.full_name}? They won't be able to sign in until reinstated.`)) return;
        setSaving(true);
        setActionError(null);
        try {
            await api(`/admin/beneficiaries/${id}`, { method: 'PATCH', body: { active } });
            reload();
        } catch (e) {
            setActionError(e.message);
        } finally {
            setSaving(false);
        }
    }

    return (
        <>
            <PageHeader
                title={b.full_name}
                subtitle={b.phone}
                actions={(
                    <>
                        <Link to={`/evaluations?beneficiary_id=${id}`}><Button variant="secondary">Evaluations</Button></Link>
                        {canManage && (
                            <>
                                <Link to={`/beneficiaries/${id}/edit`}><Button variant="secondary">Edit</Button></Link>
                                {b.active
                                    ? <Button variant="danger" loading={saving} onClick={() => setActive(false)}>Suspend</Button>
                                    : <Button variant="secondary" loading={saving} onClick={() => setActive(true)}>Reinstate</Button>}
                                {!current && b.active && <Link to={`/handovers/new?beneficiary=${id}`}><Button>Hand over a vehicle</Button></Link>}
                            </>
                        )}
                    </>
                )}
            />
            {actionError && <div className="mb-4"><Alert>{actionError}</Alert></div>}
            {!b.active && <div className="mb-4"><Alert>This beneficiary is suspended and can't sign in.</Alert></div>}

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card title="Details">
                        <Details items={[
                            ['Other phone', b.alt_phone],
                            ['Email', b.email],
                            ['Address', b.address],
                            ['LGA / Ward', [b.lga, b.ward].filter(Boolean).join(' / ')],
                            ['ID', b.id_number && `${options.id_types[b.id_type] ?? b.id_type ?? ''} ${b.id_number}`],
                            ['Last signed in', b.last_login_at && formatDate(b.last_login_at, true)],
                            ['Notes', b.notes],
                        ]} />
                    </Card>
                    <AssignmentHistory assignments={assignments} with="vehicle" />
                </div>
                <div>
                    <Card title="Current vehicle">
                        {current ? (
                            <div className="space-y-3 text-sm">
                                <Link to={`/vehicles/${current.vehicle.id}`} className="block font-medium text-primary hover:underline">{current.vehicle.label}</Link>
                                <StatusBadge status={current.status} />
                                <p className="text-slate-600">Since {formatDate(current.handed_over_at)}</p>
                                {canManage && <Link to={`/assignments/${current.id}/return`}><Button variant="secondary" className="py-1.5">Record return</Button></Link>}
                            </div>
                        ) : (
                            <p className="text-sm text-slate-500">No vehicle at the moment.</p>
                        )}
                    </Card>
                </div>
            </div>
        </>
    );
}
