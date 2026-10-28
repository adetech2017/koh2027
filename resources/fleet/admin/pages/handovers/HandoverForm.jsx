import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router';
import { api } from '../../../shared/api';
import { useApi, useGeolocation } from '../../../shared/hooks';
import { shrinkImage } from '../../../shared/images';
import SignaturePad from '../../../shared/SignaturePad';
import { Alert, Button, Card, Field, LoadError, Loading, PageHeader, Spinner, TextareaField } from '../../../shared/ui';
import { useOptions } from '../../options';
import SearchPicker from './SearchPicker';

const MAX_PHOTOS = 8;
const CONDITIONS = [['ok', 'OK'], ['issue', 'Issue'], ['missing', 'Missing']];

/**
 * The handover form (vehicle goes out) at /handovers/new, and the return form (vehicle comes back)
 * at /assignments/:id/return. Both record condition, photos, location and two signatures.
 */
export default function HandoverForm({ mode }) {
    const isReturn = mode === 'return';
    const { id: assignmentId } = useParams();
    const [params] = useSearchParams();
    const navigate = useNavigate();
    const options = useOptions();
    const location = useGeolocation();
    const topRef = useRef(null);

    const assignment = useApi(isReturn ? `/admin/assignments/${assignmentId}` : null);
    const presetVehicle = useApi(!isReturn && params.get('vehicle') ? `/admin/vehicles/${params.get('vehicle')}` : null);
    const presetBeneficiary = useApi(!isReturn && params.get('beneficiary') ? `/admin/beneficiaries/${params.get('beneficiary')}` : null);

    const [vehicle, setVehicle] = useState(null);
    const [beneficiary, setBeneficiary] = useState(null);
    const [form, setForm] = useState({
        odometer_km: '',
        fuel_level: '',
        checklist: Object.fromEntries(Object.keys(options.handover_checklist).map((key) => [key, { status: 'ok', note: '' }])),
        notes: '',
        signed_by_name: '',
        vehicle_status: 'available',
    });
    const [photos, setPhotos] = useState([]);
    const [signatures, setSignatures] = useState({ signer: null, officer: null });
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);
    const [processingPhotos, setProcessingPhotos] = useState(false);

    useEffect(() => {
        if (presetVehicle.data?.vehicle.status === 'available') setVehicle(presetVehicle.data.vehicle);
    }, [presetVehicle.data]);
    useEffect(() => {
        if (presetBeneficiary.data) setBeneficiary(presetBeneficiary.data.beneficiary);
    }, [presetBeneficiary.data]);

    // The person signing is normally the beneficiary
    const person = isReturn ? assignment.data?.assignment.beneficiary : beneficiary;
    useEffect(() => {
        if (person) setForm((f) => ({ ...f, signed_by_name: person.full_name }));
    }, [person?.id]);

    // Free the photo previews when leaving the page
    const photosRef = useRef(photos);
    photosRef.current = photos;
    useEffect(() => () => photosRef.current.forEach((p) => URL.revokeObjectURL(p.url)), []);

    if (isReturn && assignment.error) return <LoadError error={assignment.error} onRetry={assignment.reload} />;
    if (isReturn && !assignment.data) return <Loading />;

    const current = assignment.data?.assignment;
    if (isReturn && current.status === 'returned') {
        return <Alert>This vehicle has already been returned. <Link to={`/handovers/${current.return_id}`} className="font-semibold underline">View the return form</Link></Alert>;
    }

    const set = (key) => (e) => setForm({ ...form, [key]: e.target.value });
    const setItem = (key, changes) => setForm({ ...form, checklist: { ...form.checklist, [key]: { ...form.checklist[key], ...changes } } });
    const err = (key) => error?.field(key);

    async function addPhotos(event) {
        const files = Array.from(event.target.files).slice(0, MAX_PHOTOS - photos.length);
        event.target.value = '';
        setProcessingPhotos(true);
        const shrunk = await Promise.all(files.map((file) => shrinkImage(file)));
        setPhotos((current) => [...current, ...shrunk.map((file) => ({ file, url: URL.createObjectURL(file) }))]);
        setProcessingPhotos(false);
    }

    function removePhoto(index) {
        URL.revokeObjectURL(photos[index].url);
        setPhotos(photos.filter((_, i) => i !== index));
    }

    async function submit(event) {
        event.preventDefault();
        setError(null);
        setSaving(true);

        const body = new FormData();
        if (isReturn) {
            body.append('vehicle_status', form.vehicle_status);
        } else {
            if (vehicle) body.append('vehicle_id', vehicle.id);
            if (beneficiary) body.append('beneficiary_id', beneficiary.id);
        }
        body.append('odometer_km', form.odometer_km);
        body.append('fuel_level', form.fuel_level);
        body.append('checklist', JSON.stringify(form.checklist));
        body.append('notes', form.notes);
        body.append('signed_by_name', form.signed_by_name);
        body.append('signer_signature', signatures.signer ?? '');
        body.append('officer_signature', signatures.officer ?? '');
        if (location.coords) {
            body.append('latitude', location.coords.latitude);
            body.append('longitude', location.coords.longitude);
            body.append('location_accuracy_m', location.coords.accuracy);
        }
        photos.forEach((photo) => body.append('photos[]', photo.file));

        try {
            const data = await api(isReturn ? `/admin/assignments/${assignmentId}/return` : '/admin/handovers', { method: 'POST', body });
            navigate(`/handovers/${data.handover.id}`, { replace: true });
        } catch (e) {
            setError(e);
            setSaving(false);
            topRef.current?.scrollIntoView({ behavior: 'smooth' });
        }
    }

    const photoError = Object.entries(error?.errors ?? {}).find(([key]) => key.startsWith('photos'))?.[1][0];

    return (
        <form onSubmit={submit} noValidate ref={topRef} className="mx-auto max-w-3xl">
            <PageHeader
                title={isReturn ? 'Record vehicle return' : 'Hand over a vehicle'}
                subtitle={isReturn ? `${current.vehicle.label} from ${current.beneficiary.full_name}` : 'Fill this in with the beneficiary, at the vehicle.'}
            />

            {error && (
                <div className="mb-5">
                    <Alert>{Object.keys(error.errors).length ? 'Some details need attention. Check the highlighted fields below.' : error.message}</Alert>
                </div>
            )}

            <div className="space-y-6">
                {!isReturn && (
                    <Card title="Vehicle and beneficiary">
                        <div className="grid gap-5 sm:grid-cols-2">
                            <SearchPicker
                                id="vehicle"
                                label="Vehicle"
                                endpoint="/admin/vehicles"
                                params={{ status: 'available' }}
                                placeholder="Search available vehicles"
                                value={vehicle}
                                onChange={setVehicle}
                                error={err('vehicle_id')}
                                render={(v) => <><div className="font-medium">{v.plate_number}</div><div className="text-sm text-slate-500">{v.make} {v.model}</div></>}
                            />
                            <SearchPicker
                                id="beneficiary"
                                label="Beneficiary"
                                endpoint="/admin/beneficiaries"
                                params={{ vehicle: 'without', status: 'active' }}
                                placeholder="Search by name or phone"
                                value={beneficiary}
                                onChange={setBeneficiary}
                                error={err('beneficiary_id')}
                                render={(b) => <><div className="font-medium">{b.full_name}</div><div className="text-sm text-slate-500">{b.phone}{b.lga && ` · ${b.lga}`}</div></>}
                            />
                        </div>
                    </Card>
                )}

                <Card title="Readings">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            id="odometer_km"
                            label="Odometer (km)"
                            inputMode="numeric"
                            value={form.odometer_km}
                            onChange={(e) => setForm({ ...form, odometer_km: e.target.value.replace(/\D/g, '') })}
                            error={err('odometer_km')}
                            hint={isReturn && current.handover_odometer_km != null ? `${current.handover_odometer_km.toLocaleString()} km at handover` : undefined}
                            required
                        />
                        <div>
                            <div className="mb-1.5 text-sm font-medium text-slate-700">Fuel level</div>
                            <div className="grid grid-cols-5 gap-1.5" role="radiogroup" aria-label="Fuel level">
                                {Object.entries(options.fuel_levels).map(([value, text]) => (
                                    <button
                                        key={value}
                                        type="button"
                                        role="radio"
                                        aria-checked={form.fuel_level === value}
                                        onClick={() => setForm({ ...form, fuel_level: value })}
                                        className={`rounded-lg px-1 py-2.5 text-sm font-medium ring-1 ${form.fuel_level === value ? 'bg-primary text-white ring-primary' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50'}`}
                                    >
                                        {text}
                                    </button>
                                ))}
                            </div>
                            {err('fuel_level') && <p className="mt-1.5 text-sm text-red-600">Choose the fuel level.</p>}
                        </div>
                    </div>
                </Card>

                <Card title="Condition">
                    <ul className="divide-y divide-slate-100">
                        {Object.entries(options.handover_checklist).map(([key, text]) => {
                            const item = form.checklist[key];
                            const itemError = err(`checklist.${key}.note`) || err(`checklist.${key}.status`);
                            return (
                                <li key={key} className="py-3 first:pt-0 last:pb-0">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <span className="text-sm font-medium">{text}</span>
                                        <div className="flex gap-1" role="radiogroup" aria-label={text}>
                                            {CONDITIONS.map(([value, label]) => (
                                                <button
                                                    key={value}
                                                    type="button"
                                                    role="radio"
                                                    aria-checked={item.status === value}
                                                    onClick={() => setItem(key, { status: value })}
                                                    className={`rounded-md px-3 py-1.5 text-xs font-semibold ring-1 ${
                                                        item.status === value
                                                            ? value === 'ok' ? 'bg-green-600 text-white ring-green-600' : value === 'issue' ? 'bg-amber-500 text-white ring-amber-500' : 'bg-red-600 text-white ring-red-600'
                                                            : 'bg-white text-slate-600 ring-slate-300'
                                                    }`}
                                                >
                                                    {label}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                    {item.status !== 'ok' && (
                                        <input
                                            className={`mt-2 block w-full rounded-lg border px-3 py-2 text-base sm:text-sm ${itemError ? 'border-red-500' : 'border-slate-300'}`}
                                            placeholder="What's wrong?"
                                            value={item.note}
                                            onChange={(e) => setItem(key, { note: e.target.value })}
                                            aria-label={`${text} notes`}
                                        />
                                    )}
                                    {itemError && <p className="mt-1 text-sm text-red-600">{itemError}</p>}
                                </li>
                            );
                        })}
                    </ul>
                    <div className="mt-5">
                        <TextareaField id="notes" label="Other notes" value={form.notes} onChange={set('notes')} error={err('notes')} />
                    </div>
                </Card>

                <Card title={`Photos (${photos.length}/${MAX_PHOTOS})`}>
                    <p className="mb-3 text-sm text-slate-600">Front, back, both sides, dashboard (showing the odometer) and any damage.</p>
                    <div className="grid grid-cols-3 gap-2 sm:grid-cols-4">
                        {photos.map((photo, index) => (
                            <div key={photo.url} className="relative aspect-square overflow-hidden rounded-lg bg-slate-100">
                                <img src={photo.url} alt={`Photo ${index + 1}`} className="size-full object-cover" />
                                <button type="button" onClick={() => removePhoto(index)} className="absolute top-1 right-1 rounded-full bg-black/60 px-2 py-0.5 text-xs text-white" aria-label={`Remove photo ${index + 1}`}>
                                    ✕
                                </button>
                            </div>
                        ))}
                        {photos.length < MAX_PHOTOS && (
                            <label className="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-slate-300 text-sm text-slate-500 hover:bg-slate-50">
                                {processingPhotos ? <Spinner className="size-5 text-primary" /> : <><span className="text-2xl leading-none">+</span>Add photos</>}
                                <input type="file" accept="image/*" multiple className="sr-only" onChange={addPhotos} disabled={processingPhotos} />
                            </label>
                        )}
                    </div>
                    {photoError && <p className="mt-2 text-sm text-red-600">{photoError}</p>}
                </Card>

                {isReturn && (
                    <Card title="After the return">
                        <div className="flex flex-wrap gap-4 text-sm">
                            {[['available', 'Ready to hand out again'], ['maintenance', 'Needs maintenance first']].map(([value, text]) => (
                                <label key={value} className="flex items-center gap-2">
                                    <input type="radio" name="vehicle_status" value={value} checked={form.vehicle_status === value} onChange={set('vehicle_status')} className="size-4" />
                                    {text}
                                </label>
                            ))}
                        </div>
                    </Card>
                )}

                <Card title="Signatures">
                    <div className="space-y-5">
                        <LocationStatus location={location} />
                        <Field
                            id="signed_by_name"
                            label={isReturn ? 'Returned by' : 'Received by'}
                            value={form.signed_by_name}
                            onChange={set('signed_by_name')}
                            error={err('signed_by_name')}
                            hint="Change this if someone is signing on the beneficiary's behalf."
                            required
                        />
                        <SignaturePad label={`${form.signed_by_name || 'Beneficiary'}'s signature`} onChange={(v) => setSignatures((s) => ({ ...s, signer: v }))} error={err('signer_signature')} />
                        <SignaturePad label="Fleet officer's signature" onChange={(v) => setSignatures((s) => ({ ...s, officer: v }))} error={err('officer_signature')} />
                    </div>
                </Card>
            </div>

            <div className="mt-6 flex justify-end gap-3 pb-10">
                <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                <Button type="submit" loading={saving} disabled={processingPhotos}>
                    {isReturn ? 'Complete return' : 'Complete handover'}
                </Button>
            </div>
        </form>
    );
}

function LocationStatus({ location }) {
    if (location.status === 'locating') {
        return <p className="flex items-center gap-2 text-sm text-slate-600"><Spinner className="size-4" /> Getting your location…</p>;
    }
    if (location.status === 'ok') {
        return <p className="text-sm text-green-700">Location recorded (±{Math.round(location.coords.accuracy)} m).</p>;
    }

    return (
        <Alert tone="info">
            {location.status === 'denied' ? 'Location access is blocked in this browser, so' : 'Your location could not be found, so'} this form
            will be saved without it.{' '}
            <button type="button" onClick={location.locate} className="font-semibold underline">Try again</button>
        </Alert>
    );
}
