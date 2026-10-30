import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { api } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Alert, Button, Card, Field, LoadError, Loading, PageHeader, SelectField, TextareaField } from '../../../shared/ui';
import { useAuth } from '../../auth';
import { entries, useOptions } from '../../options';

const EMPTY = { plate_number: '', vehicle_type: 'car', make: '', model: '', year: '', colour: '', chassis_number: '', engine_number: '', branding: '', notes: '' };

export default function VehicleForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const options = useOptions();
    const { user } = useAuth();
    const existing = useApi(id ? `/admin/vehicles/${id}` : null);
    // Fetched on each visit, so makes a super admin has just added show up straight away
    const catalog = useApi('/admin/vehicle-catalog');
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

    // Type → Make → Model: each list only offers what fits the choice before it
    const makes = catalog.data?.makes ?? [];
    const modelsOf = (makeName, type) => makes.find((m) => m.name === makeName)?.models.filter((m) => m.vehicle_type === type) ?? [];
    const makesForType = makes.filter((make) => make.models.some((m) => m.vehicle_type === form.vehicle_type));
    const models = modelsOf(form.make, form.vehicle_type);

    const setType = (e) => {
        const type = e.target.value;
        const keepMake = modelsOf(form.make, type).length > 0;
        const keepModel = keepMake && modelsOf(form.make, type).some((m) => m.name === form.model);
        setForm({ ...form, vehicle_type: type, make: keepMake ? form.make : '', model: keepModel ? form.model : '' });
    };
    const setMake = (e) => setForm({ ...form, make: e.target.value, model: '' });

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
    if (catalog.error) return <LoadError error={catalog.error} onRetry={catalog.reload} />;
    if ((id && !existing.data) || !catalog.data) return <Loading />;

    const err = (key) => error?.field(key);
    const thisYear = new Date().getFullYear();
    const years = Array.from({ length: thisYear + 1 - 1980 + 1 }, (_, i) => String(thisYear + 1 - i));
    // A colour recorded before the list existed stays selectable on that vehicle
    const colours = form.colour && !options.colours.includes(form.colour) ? [...options.colours, form.colour] : options.colours;
    const missingHint = user.role === 'super_admin'
        ? <>Not listed? Add it under <Link to="/vehicle-catalog" className="underline">Makes &amp; models</Link>.</>
        : 'Not listed? Ask a super admin to add it.';

    return (
        <>
            <PageHeader title={id ? `Edit ${existing.data.vehicle.plate_number}` : 'Add vehicle'} />
            <form onSubmit={submit} noValidate>
                <Card>
                    {error && Object.keys(error.errors).length === 0 && <div className="mb-5"><Alert>{error.message}</Alert></div>}
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field id="plate_number" label="Plate number" placeholder="KJA 123 XY" value={form.plate_number} onChange={set('plate_number')} error={err('plate_number')} required autoCapitalize="characters" />
                        <SelectField id="vehicle_type" label="Type" options={entries(options.vehicle_types)} value={form.vehicle_type} onChange={setType} error={err('vehicle_type')} />
                        <SelectField id="make" label="Make" placeholder="Choose…" options={makesForType.map((m) => [m.name, m.name])} value={form.make} onChange={setMake} error={err('make')} hint={missingHint} required />
                        <SelectField id="model" label="Model" placeholder={form.make ? 'Choose…' : 'Choose a make first'} options={models.map((m) => [m.name, m.name])} value={form.model} onChange={set('model')} error={err('model')} disabled={!form.make} required />
                        <SelectField id="year" label="Year" placeholder="Choose…" options={years.map((y) => [y, y])} value={String(form.year)} onChange={set('year')} error={err('year')} />
                        <SelectField id="colour" label="Colour" placeholder="Choose…" options={colours.map((c) => [c, c])} value={form.colour} onChange={set('colour')} error={err('colour')} />
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
