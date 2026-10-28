import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { api } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Alert, Button, Card, Field, LoadError, Loading, PageHeader, SelectField, TextareaField } from '../../../shared/ui';
import { entries, useOptions } from '../../options';

const EMPTY = { plate_number: '', vehicle_type: 'car', make: '', model: '', year: '', colour: '', chassis_number: '', engine_number: '', branding: '', notes: '' };

export default function VehicleForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const options = useOptions();
    const existing = useApi(id ? `/admin/vehicles/${id}` : null);
    const [form, setForm] = useState(EMPTY);
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (existing.data) {
            const v = existing.data.vehicle;
            setForm(Object.fromEntries(Object.keys(EMPTY).map((key) => [key, v[key] ?? ''])));
        }
    }, [existing.data]);

    const set = (key) => (e) => setForm({ ...form, [key]: e.target.value });

    async function submit(event) {
        event.preventDefault();
        setSaving(true);
        setError(null);
        try {
            const body = { ...form, year: form.year === '' ? null : Number(form.year) };
            const data = await api(id ? `/admin/vehicles/${id}` : '/admin/vehicles', { method: id ? 'PATCH' : 'POST', body });
            navigate(`/vehicles/${data.vehicle.id}`);
        } catch (e) {
            setError(e);
            setSaving(false);
        }
    }

    if (id && existing.error) return <LoadError error={existing.error} onRetry={existing.reload} />;
    if (id && !existing.data) return <Loading />;

    const err = (key) => error?.field(key);

    return (
        <>
            <PageHeader title={id ? `Edit ${existing.data.vehicle.plate_number}` : 'Add vehicle'} />
            <form onSubmit={submit} noValidate>
                <Card>
                    {error && Object.keys(error.errors).length === 0 && <div className="mb-5"><Alert>{error.message}</Alert></div>}
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field id="plate_number" label="Plate number" placeholder="KJA 123 XY" value={form.plate_number} onChange={set('plate_number')} error={err('plate_number')} required autoCapitalize="characters" />
                        <SelectField id="vehicle_type" label="Type" options={entries(options.vehicle_types)} value={form.vehicle_type} onChange={set('vehicle_type')} error={err('vehicle_type')} />
                        <Field id="make" label="Make" placeholder="Toyota" value={form.make} onChange={set('make')} error={err('make')} required />
                        <Field id="model" label="Model" placeholder="Corolla" value={form.model} onChange={set('model')} error={err('model')} required />
                        <Field id="year" label="Year" inputMode="numeric" value={form.year} onChange={set('year')} error={err('year')} />
                        <Field id="colour" label="Colour" value={form.colour} onChange={set('colour')} error={err('colour')} />
                        <Field id="chassis_number" label="Chassis number (VIN)" value={form.chassis_number} onChange={set('chassis_number')} error={err('chassis_number')} autoCapitalize="characters" />
                        <Field id="engine_number" label="Engine number" value={form.engine_number} onChange={set('engine_number')} error={err('engine_number')} />
                        <div className="sm:col-span-2">
                            <Field id="branding" label="Branding" placeholder="e.g. Full wrap, KOH 2027 livery" value={form.branding} onChange={set('branding')} error={err('branding')} />
                        </div>
                        <div className="sm:col-span-2">
                            <TextareaField id="notes" label="Notes" value={form.notes} onChange={set('notes')} error={err('notes')} />
                        </div>
                    </div>
                </Card>
                <div className="mt-5 flex justify-end gap-3">
                    <Link to={id ? `/vehicles/${id}` : '/vehicles'}><Button type="button" variant="secondary">Cancel</Button></Link>
                    <Button type="submit" loading={saving}>{id ? 'Save changes' : 'Add vehicle'}</Button>
                </div>
            </form>
        </>
    );
}
