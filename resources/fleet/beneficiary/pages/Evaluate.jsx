import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router';
import { api } from '../../shared/api';
import { useApi, useGeolocation } from '../../shared/hooks';
import { Alert, Button, Field, LoadError, Spinner, TextareaField, formatDate } from '../../shared/ui';
import LiveRecorder from '../evaluation/LiveRecorder';
import { clearPending, loadPending, savePending, updatePending } from '../evaluation/pending';
import { uploadVideo } from '../evaluation/uploader';

const FUEL_LEVELS = [['empty', 'Empty'], ['quarter', '1/4'], ['half', '1/2'], ['three_quarters', '3/4'], ['full', 'Full']];
const EMPTY_FORM = { mileage_km: '', fuel_level: '', has_damage: null, damage_notes: '', branding_intact: null, branding_notes: '', notes: '' };

/**
 * Record (or choose) the video, then answer the checklist while it uploads in the background.
 */
export default function Evaluate() {
    const navigate = useNavigate();
    const { data, error, reload } = useApi('/evaluations');
    const location = useGeolocation();

    const [step, setStep] = useState('choose'); // choose, record, checklist, done
    const [pending, setPending] = useState(undefined);
    const [challenge, setChallenge] = useState(null);
    const [video, setVideo] = useState(null); // { file, source, duration, challenge }
    const [upload, setUpload] = useState({ status: 'idle', progress: 0, uploadId: null, error: null });
    const [form, setForm] = useState(EMPTY_FORM);
    const [formError, setFormError] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const [startError, setStartError] = useState(null);
    const abortRef = useRef(null);

    const current = data?.current;

    useEffect(() => {
        loadPending().then(setPending);
    }, []);

    // Warn before leaving while a video is uploading or waiting to be submitted
    useEffect(() => {
        if (!video || step === 'done') return;
        const warn = (e) => e.preventDefault();
        window.addEventListener('beforeunload', warn);
        return () => window.removeEventListener('beforeunload', warn);
    }, [video, step]);

    useEffect(() => () => abortRef.current?.abort(), []);

    async function runUpload(file, uploadId = null) {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;
        setUpload({ status: 'uploading', progress: 0, uploadId, error: null });

        // Keep the screen on so the phone doesn't sleep mid-upload
        const wakeLock = await navigator.wakeLock?.request('screen').catch(() => null);
        try {
            const id = await uploadVideo(file, {
                uploadId,
                signal: controller.signal,
                onStarted: (id) => {
                    setUpload((u) => ({ ...u, uploadId: id }));
                    updatePending({ uploadId: id });
                },
                onProgress: (progress) => setUpload((u) => ({ ...u, progress })),
            });
            setUpload({ status: 'done', progress: 1, uploadId: id, error: null });
        } catch (e) {
            if (e.name === 'AbortError') return;
            setUpload((u) => ({ ...u, status: e.status === 422 ? 'failed' : 'paused', error: e.field?.('size') || e.field?.('mime') || e.field?.('period') || e.message }));
        } finally {
            wakeLock?.release().catch(() => {});
        }
    }

    function acceptVideo(chosen) {
        setVideo(chosen);
        setStep('checklist');
        savePending({ ...chosen, periodId: current.id, uploadId: null });
        runUpload(chosen.file);
    }

    async function startRecording() {
        setStartError(null);
        try {
            setChallenge(await api('/evaluations/challenge', { method: 'POST' }));
            setStep('record');
        } catch (e) {
            setStartError(e.field('period') || e.message);
        }
    }

    function chooseFromGallery(event) {
        const file = event.target.files[0];
        event.target.value = '';
        if (!file) return;
        if (!file.type.startsWith('video/')) {
            setStartError('Please choose a video.');
            return;
        }
        if (file.size > data.limits.max_upload_mb * 1024 * 1024) {
            setStartError(`This video is too large (over ${data.limits.max_upload_mb} MB). Please record a shorter one.`);
            return;
        }
        acceptVideo({ file, source: 'gallery', duration: null, challenge: null });
    }

    function resumePending() {
        setVideo(pending);
        setStep('checklist');
        runUpload(pending.file, pending.uploadId);
    }

    async function startOver() {
        abortRef.current?.abort();
        await clearPending();
        setPending(null);
        setVideo(null);
        setChallenge(null);
        setUpload({ status: 'idle', progress: 0, uploadId: null, error: null });
        setStep('choose');
    }

    async function submit(event) {
        event.preventDefault();
        setSubmitting(true);
        setFormError(null);
        try {
            await api('/evaluations', {
                method: 'POST',
                body: {
                    upload_id: upload.uploadId,
                    source: video.source,
                    challenge_id: video.challenge?.id ?? null,
                    client_duration: video.duration,
                    ...(location.coords && {
                        latitude: location.coords.latitude,
                        longitude: location.coords.longitude,
                        accuracy_m: location.coords.accuracy,
                    }),
                    mileage_km: form.mileage_km === '' ? null : Number(form.mileage_km),
                    fuel_level: form.fuel_level,
                    has_damage: form.has_damage,
                    damage_notes: form.damage_notes || null,
                    branding_intact: form.branding_intact,
                    branding_notes: form.branding_notes || null,
                    notes: form.notes || null,
                },
            });
            await clearPending();
            setStep('done');
        } catch (e) {
            setFormError(e);
            // A problem with the video itself means choosing or recording a new one
            if (e.field('upload_id')) await clearPending();
        } finally {
            setSubmitting(false);
        }
    }

    if (error) return <Page><LoadError error={error} onRetry={reload} /></Page>;
    if (!data || pending === undefined) return <Page><div className="flex justify-center py-16"><Spinner className="size-7 text-primary" /></div></Page>;

    if (step === 'done') {
        return (
            <Page title="Thank you!">
                <div className="rounded-xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200">
                    <div className="mx-auto flex size-14 items-center justify-center rounded-full bg-green-100 text-2xl text-green-700">✓</div>
                    <p className="mt-4 font-medium">Your evaluation has been received.</p>
                    <p className="mt-1 text-sm text-slate-600">The next one opens on {formatDate(current.ends_at)}. We'll remind you on WhatsApp.</p>
                    <Button className="mt-6 w-full" onClick={() => navigate('/', { replace: true })}>Done</Button>
                </div>
            </Page>
        );
    }

    if (!current?.accepts_submissions && step === 'choose') {
        return (
            <Page title="Vehicle evaluation">
                <Alert tone="info">
                    {current ? `This period's evaluation is already in. The next one opens on ${formatDate(current.ends_at)}.` : "You don't have a vehicle at the moment."}
                </Alert>
                <Link to="/" className="mt-4 block"><Button variant="secondary" className="w-full">Back</Button></Link>
            </Page>
        );
    }

    if (step === 'record') {
        return (
            <Page title="Record your vehicle">
                <LiveRecorder
                    code={challenge.code}
                    coords={location.coords}
                    minSeconds={data.limits.min_seconds}
                    onCancel={() => setStep('choose')}
                    onDone={({ file, duration }) => acceptVideo({ file, source: 'live', duration, challenge })}
                />
                <LocationNote location={location} />
            </Page>
        );
    }

    if (step === 'checklist') {
        const err = (key) => formError?.field(key);
        const videoError = formError?.field('upload_id') || (upload.status === 'failed' && upload.error);
        const set = (key) => (e) => setForm({ ...form, [key]: e.target.value });

        return (
            <Page title="About the vehicle">
                <form onSubmit={submit} noValidate className="space-y-5">
                    <UploadStatus upload={upload} onRetry={() => runUpload(video.file, upload.uploadId)} />

                    {videoError && (
                        <Alert>
                            {videoError}{' '}
                            <button type="button" className="font-semibold underline" onClick={startOver}>Choose another video</button>
                        </Alert>
                    )}
                    {formError && !videoError && !Object.keys(formError.errors).length && <Alert>{formError.message}</Alert>}

                    <Field
                        id="mileage_km"
                        label="Mileage on the odometer (km)"
                        inputMode="numeric"
                        value={form.mileage_km}
                        onChange={(e) => setForm({ ...form, mileage_km: e.target.value.replace(/\D/g, '') })}
                        error={err('mileage_km')}
                        hint={data.last_mileage_km != null ? `Last reading: ${data.last_mileage_km.toLocaleString()} km` : undefined}
                    />

                    <Choice label="Fuel level" error={err('fuel_level')} options={FUEL_LEVELS} value={form.fuel_level} onChange={(v) => setForm({ ...form, fuel_level: v })} columns={5} />

                    <Choice label="Is there any new damage?" error={err('has_damage')} options={[[false, 'No'], [true, 'Yes']]} value={form.has_damage} onChange={(v) => setForm({ ...form, has_damage: v })} />
                    {form.has_damage && <TextareaField id="damage_notes" label="Describe the damage" value={form.damage_notes} onChange={set('damage_notes')} error={err('damage_notes')} />}

                    <Choice label="Is the campaign branding still intact?" error={err('branding_intact')} options={[[true, 'Yes'], [false, 'No']]} value={form.branding_intact} onChange={(v) => setForm({ ...form, branding_intact: v })} />
                    {form.branding_intact === false && <TextareaField id="branding_notes" label="What's wrong with the branding?" value={form.branding_notes} onChange={set('branding_notes')} error={err('branding_notes')} />}

                    <TextareaField id="notes" label="Anything else? (optional)" value={form.notes} onChange={set('notes')} error={err('notes')} />

                    <LocationNote location={location} />

                    <Button type="submit" loading={submitting} disabled={upload.status !== 'done'} className="w-full">
                        {upload.status === 'done' ? 'Submit evaluation' : 'Waiting for upload…'}
                    </Button>
                    <button type="button" onClick={startOver} className="block w-full text-center text-sm text-slate-500 underline">
                        Start over with a different video
                    </button>
                </form>
            </Page>
        );
    }

    return (
        <Page title="Vehicle evaluation">
            <p className="text-slate-600">Due by <span className="font-medium text-slate-900">{formatDate(current.ends_at)}</span>. It takes about 5 minutes.</p>

            {pending && pending.periodId === current.id && (
                <div className="mt-5 rounded-xl bg-blue-50 p-4 ring-1 ring-blue-200">
                    <p className="font-medium text-blue-900">You have an unfinished evaluation</p>
                    <p className="mt-1 text-sm text-blue-900">Your video is saved on this phone. Continue to finish uploading it.</p>
                    <div className="mt-3 flex gap-2">
                        <Button onClick={resumePending} className="py-2">Continue</Button>
                        <Button variant="secondary" onClick={startOver} className="py-2">Discard</Button>
                    </div>
                </div>
            )}

            {startError && <div className="mt-5"><Alert>{startError}</Alert></div>}

            <div className="mt-5 space-y-3">
                <button type="button" onClick={startRecording} className="block w-full rounded-xl bg-primary p-5 text-left text-white shadow-sm">
                    <span className="text-lg font-semibold">Record now</span>
                    <span className="mt-0.5 block text-sm text-white/80">Recommended. Film the vehicle with your camera in the app.</span>
                </button>
                <label className="block w-full cursor-pointer rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <span className="text-lg font-semibold">Upload from gallery</span>
                    <span className="mt-0.5 block text-sm text-slate-600">Use a video you've just recorded with your phone camera.</span>
                    <input type="file" accept="video/*" className="sr-only" onChange={chooseFromGallery} />
                </label>
            </div>

            <LocationNote location={location} />
            <Link to="/" className="mt-6 block text-center text-sm text-slate-500 underline">Back</Link>
        </Page>
    );
}

function Page({ title, children }) {
    return (
        <main className="mx-auto min-h-dvh max-w-md px-4 py-6">
            {title && <h1 className="mb-5 text-2xl font-semibold">{title}</h1>}
            {children}
        </main>
    );
}

function Choice({ label, options, value, onChange, error, columns = 2 }) {
    return (
        <div>
            <div className="mb-1.5 text-sm font-medium text-slate-700">{label}</div>
            <div className={`grid gap-2 ${columns === 5 ? 'grid-cols-5' : 'grid-cols-2'}`} role="radiogroup" aria-label={label}>
                {options.map(([optionValue, text]) => (
                    <button
                        key={String(optionValue)}
                        type="button"
                        role="radio"
                        aria-checked={value === optionValue}
                        onClick={() => onChange(optionValue)}
                        className={`rounded-lg px-2 py-3 text-sm font-semibold ring-1 ${value === optionValue ? 'bg-primary text-white ring-primary' : 'bg-white text-slate-700 ring-slate-300'}`}
                    >
                        {text}
                    </button>
                ))}
            </div>
            {error && <p className="mt-1.5 text-sm text-red-600">{error}</p>}
        </div>
    );
}

function UploadStatus({ upload, onRetry }) {
    const percent = Math.round(upload.progress * 100);

    if (upload.status === 'failed') return null;

    return (
        <div className="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <div className="flex items-center justify-between text-sm">
                <span className="font-medium">
                    {upload.status === 'done' ? 'Video uploaded' : upload.status === 'paused' ? 'Upload paused' : 'Uploading video…'}
                </span>
                <span className="text-slate-500">{percent}%</span>
            </div>
            <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow={percent} aria-valuemin={0} aria-valuemax={100}>
                <div className={`h-full rounded-full transition-all ${upload.status === 'done' ? 'bg-green-500' : 'bg-primary'}`} style={{ width: `${percent}%` }} />
            </div>
            {upload.status === 'uploading' && <p className="mt-2 text-xs text-slate-500">You can fill in the questions below while this finishes.</p>}
            {upload.status === 'paused' && (
                <p className="mt-2 text-sm text-slate-600">
                    {upload.error} Your video is saved on this phone.{' '}
                    <button type="button" onClick={onRetry} className="font-semibold text-primary underline">Retry</button>
                </p>
            )}
        </div>
    );
}

function LocationNote({ location }) {
    if (location.status === 'ok') return null;
    if (location.status === 'locating') return <p className="mt-4 flex items-center gap-2 text-sm text-slate-500"><Spinner className="size-4" /> Finding your location…</p>;

    return (
        <div className="mt-4">
            <Alert tone="info">
                {location.status === 'denied'
                    ? 'Location is blocked. Please allow location for this site in your browser settings — evaluations without a location need extra checks.'
                    : "We couldn't find your location."}{' '}
                <button type="button" onClick={location.locate} className="font-semibold underline">Try again</button>
            </Alert>
        </div>
    );
}
