import { Link, useParams } from 'react-router';
import { useApi } from '../../../shared/hooks';
import { Badge, Button, Card, Details, LoadError, Loading, PageHeader, formatDate } from '../../../shared/ui';
import { useOptions } from '../../options';

const CONDITION_TONES = { ok: ['green', 'OK'], issue: ['amber', 'Issue'], missing: ['red', 'Missing'] };

export default function HandoverDetail() {
    const { id } = useParams();
    const options = useOptions();
    const { data, error, reload } = useApi(`/admin/handovers/${id}`);

    if (error) return <LoadError error={error} onRetry={reload} />;
    if (!data) return <Loading />;

    const h = data.handover;
    const isReturn = h.type === 'return';

    return (
        <>
            <PageHeader
                title={`${isReturn ? 'Return' : 'Handover'} ${h.reference}`}
                subtitle={formatDate(h.occurred_at, true)}
                actions={<a href={h.receipt_url} target="_blank" rel="noopener"><Button>Download receipt (PDF)</Button></a>}
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card title="Summary">
                        <Details items={[
                            ['Vehicle', <Link key="v" to={`/vehicles/${h.vehicle.id}`} className="text-primary hover:underline">{h.vehicle.label}</Link>],
                            ['Beneficiary', <Link key="b" to={`/beneficiaries/${h.beneficiary.id}`} className="text-primary hover:underline">{h.beneficiary.full_name}</Link>],
                            ['Odometer', `${h.odometer_km.toLocaleString()} km`],
                            ['Fuel', options.fuel_levels[h.fuel_level] ?? h.fuel_level],
                            ['Fleet officer', h.officer],
                            [isReturn ? 'Returned by' : 'Received by', h.signed_by_name],
                            ['Location', h.latitude != null ? (
                                <a key="l" href={`https://www.google.com/maps?q=${h.latitude},${h.longitude}`} target="_blank" rel="noopener" className="text-primary hover:underline">
                                    {h.latitude.toFixed(5)}, {h.longitude.toFixed(5)}{h.location_accuracy_m != null && ` (±${h.location_accuracy_m} m)`}
                                </a>
                            ) : 'Not recorded'],
                            ['Notes', h.notes],
                        ]} />
                    </Card>

                    <Card title="Condition">
                        <ul className="divide-y divide-slate-100 text-sm">
                            {Object.entries(options.handover_checklist).map(([key, text]) => {
                                const item = h.checklist[key];
                                const [tone, label] = CONDITION_TONES[item?.status] ?? ['slate', '—'];
                                return (
                                    <li key={key} className="flex flex-wrap items-start justify-between gap-2 py-2.5 first:pt-0 last:pb-0">
                                        <div>
                                            <div>{text}</div>
                                            {item?.note && <div className="text-slate-500">{item.note}</div>}
                                        </div>
                                        <Badge tone={tone}>{label}</Badge>
                                    </li>
                                );
                            })}
                        </ul>
                    </Card>

                    {h.photos.length > 0 && (
                        <Card title="Photos">
                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                {h.photos.map((photo, i) => (
                                    <a key={photo.id} href={photo.url} target="_blank" rel="noopener" className="block aspect-[4/3] overflow-hidden rounded-lg bg-slate-100">
                                        <img src={photo.url} alt={`Photo ${i + 1}`} loading="lazy" className="size-full object-cover" />
                                    </a>
                                ))}
                            </div>
                        </Card>
                    )}
                </div>

                <Card title="Signatures">
                    <div className="space-y-5">
                        <figure>
                            <img src={h.signer_signature_url} alt={`${h.signed_by_name}'s signature`} className="h-24 w-full rounded-lg bg-slate-50 object-contain" />
                            <figcaption className="mt-1 text-sm text-slate-600">{h.signed_by_name}</figcaption>
                        </figure>
                        <figure>
                            <img src={h.officer_signature_url} alt="Fleet officer's signature" className="h-24 w-full rounded-lg bg-slate-50 object-contain" />
                            <figcaption className="mt-1 text-sm text-slate-600">{h.officer ?? 'Fleet officer'}</figcaption>
                        </figure>
                    </div>
                </Card>
            </div>
        </>
    );
}
