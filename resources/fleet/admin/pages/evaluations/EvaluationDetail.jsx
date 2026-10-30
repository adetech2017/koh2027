import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { api } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Alert, Badge, Button, Card, Details, LoadError, Loading, PageHeader, TextareaField, formatDate } from '../../../shared/ui';
import { useOptions } from '../../options';
import { FLAGS, TRUST, TrustBadge } from './labels';

export default function EvaluationDetail() {
    const { id } = useParams();
    const options = useOptions();
    const { data, error, reload } = useApi(`/admin/evaluations/${id}`);

    if (error) return <LoadError error={error} onRetry={reload} />;
    if (!data) return <Loading />;

    const e = data.evaluation;
    const mapLink = (loc) => (
        <a href={`https://www.google.com/maps?q=${loc.latitude},${loc.longitude}`} target="_blank" rel="noopener" className="text-primary hover:underline">
            {loc.latitude.toFixed(5)}, {loc.longitude.toFixed(5)}{loc.accuracy_m != null && ` (±${loc.accuracy_m} m)`}
        </a>
    );

    return (
        <>
            <PageHeader
                title={`${e.vehicle.plate_number} · period ${e.period.sequence}`}
                subtitle={`${e.beneficiary.full_name} · submitted ${formatDate(e.submitted_at, true)}`}
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card className="overflow-hidden [&>div]:p-0">
                        {e.processing_status === 'ready' ? (
                            <video src={e.video_url} poster={e.thumbnail_url ?? undefined} controls playsInline preload="metadata" className="aspect-video w-full bg-black" />
                        ) : e.processing_status === 'failed' ? (
                            <div className="p-5"><Alert>The video couldn't be processed: {e.processing_error}</Alert></div>
                        ) : (
                            <div className="flex aspect-video items-center justify-center bg-slate-900 text-sm text-slate-300">
                                Processing the video. Refresh in a minute.
                            </div>
                        )}
                    </Card>

                    <Card title="Beneficiary's answers">
                        <Details items={[
                            ['Mileage', `${e.mileage_km.toLocaleString()} km`],
                            ['Fuel', options.fuel_levels[e.fuel_level] ?? e.fuel_level],
                            ['New damage', e.has_damage ? <span key="d" className="text-red-700">Yes{e.damage_notes && ` — ${e.damage_notes}`}</span> : 'No'],
                            ['Branding intact', e.branding_intact ? 'Yes' : <span key="b" className="text-red-700">No{e.branding_notes && ` — ${e.branding_notes}`}</span>],
                            ['Other notes', e.notes],
                        ]} />
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card title="Is it a new video?">
                        <TrustBadge level={e.trust_level} />
                        <p className="mt-2 text-sm text-slate-600">{TRUST[e.trust_level]?.[2]}</p>
                        {e.challenge_code && (
                            <div className="mt-4 rounded-lg bg-gold/15 p-3 text-sm">
                                Check that the code <span className="font-mono text-base font-semibold tracking-widest">{e.challenge_code}</span> is
                                shown in the video's bottom strip and/or said aloud.
                            </div>
                        )}
                        {e.trust_flags.length > 0 && (
                            <ul className="mt-4 space-y-2 text-sm">
                                {e.trust_flags.map((flag) => (
                                    <li key={flag} className="flex gap-2 text-amber-800"><span aria-hidden="true">⚠</span>{FLAGS[flag] ?? flag}</li>
                                ))}
                            </ul>
                        )}
                    </Card>

                    <Card title="Evidence">
                        <Details columns={1} items={[
                            ['Captured', e.source === 'live' ? 'Recorded in the app' : `Uploaded from gallery (${e.original_filename})`],
                            ['Recorded at (from the video)', e.recorded_at ? formatDate(e.recorded_at, true) : 'Not in the file'],
                            ['Phone location at upload', e.device_location ? mapLink(e.device_location) : 'Not available'],
                            ['Location in the video', e.metadata_location ? mapLink(e.metadata_location) : 'Not in the file'],
                            ['Recording device', e.device],
                            ['Length', e.duration_seconds ? `${Math.round(e.duration_seconds)} seconds` : null],
                            ['Period', `${formatDate(e.period.starts_at)} – ${formatDate(e.period.ends_at)}`],
                        ]} />
                    </Card>

                    <ReviewPanel key={e.id} evaluation={e} onReviewed={reload} />

                    <Card title="Links">
                        <div className="space-y-2 text-sm">
                            <Link to={`/vehicles/${e.vehicle.id}`} className="block text-primary hover:underline">Vehicle {e.vehicle.plate_number}</Link>
                            <Link to={`/beneficiaries/${e.beneficiary.id}`} className="block text-primary hover:underline">{e.beneficiary.full_name}</Link>
                            <Link to={`/evaluations?vehicle_id=${e.vehicle.id}`} className="block text-primary hover:underline">All evaluations for this vehicle</Link>
                        </div>
                    </Card>
                </div>
            </div>
        </>
    );
}

const REVIEW = { approved: ['green', 'Approved'], rejected: ['red', 'Rejected'] };

/**
 * Approve or reject. Rejecting asks for a reason, which the beneficiary gets on WhatsApp and in
 * the app; if the two-week window is still running they can send a new video.
 */
function ReviewPanel({ evaluation: e, onReviewed }) {
    const navigate = useNavigate();
    const [rejecting, setRejecting] = useState(false);
    const [notes, setNotes] = useState('');
    const [saving, setSaving] = useState(null);
    const [error, setError] = useState(null);

    if (e.review_status !== 'pending') {
        const [tone, text] = REVIEW[e.review_status];
        return (
            <Card title="Review">
                <Badge tone={tone}>{text}</Badge>
                <p className="mt-2 text-sm text-slate-600">By {e.reviewed_by ?? 'a deleted admin'} on {formatDate(e.reviewed_at, true)}</p>
                {e.review_notes && <p className="mt-2 text-sm">{e.review_notes}</p>}
            </Card>
        );
    }

    const windowOpen = new Date(e.period.ends_at) > new Date();

    async function decide(decision) {
        setSaving(decision);
        setError(null);
        try {
            const data = await api(`/admin/evaluations/${e.id}/review`, { method: 'POST', body: { decision, notes: notes || null } });
            if (data.next_id) navigate(`/evaluations/${data.next_id}`);
            else onReviewed();
        } catch (err) {
            setError(err);
        } finally {
            setSaving(null);
        }
    }

    return (
        <Card title="Review">
            {e.processing_status !== 'ready' && <p className="mb-3 text-sm text-slate-600">You can review once the video has finished processing.</p>}
            {error && !error.field('notes') && <div className="mb-3"><Alert>{error.field('decision') || error.message}</Alert></div>}
            {rejecting ? (
                <div className="space-y-3">
                    <TextareaField
                        id="notes"
                        label="Why isn't it acceptable?"
                        placeholder="e.g. The odometer isn't shown"
                        value={notes}
                        onChange={(ev) => setNotes(ev.target.value)}
                        error={error?.field('notes')}
                        maxLength={300}
                        hint={windowOpen
                            ? 'Sent to the beneficiary on WhatsApp. Their window is still open, so they can send a new video.'
                            : "Sent to the beneficiary on WhatsApp. Their window has ended, so this period will be recorded as rejected."}
                    />
                    <div className="flex gap-2">
                        <Button variant="secondary" onClick={() => setRejecting(false)} className="flex-1">Cancel</Button>
                        <Button variant="danger" loading={saving === 'reject'} onClick={() => decide('reject')} className="flex-1">Reject</Button>
                    </div>
                </div>
            ) : (
                <div className="flex gap-2">
                    <Button variant="danger" onClick={() => setRejecting(true)} disabled={e.processing_status !== 'ready'} className="flex-1">Reject…</Button>
                    <Button loading={saving === 'approve'} onClick={() => decide('approve')} disabled={e.processing_status !== 'ready'} className="flex-1">Approve</Button>
                </div>
            )}
            <p className="mt-3 text-xs text-slate-500">After deciding, the next video waiting for review opens automatically.</p>
        </Card>
    );
}
