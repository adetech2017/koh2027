import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { api } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Alert, Button, Card, Field, LoadError, Loading, PageHeader, SelectField, TextareaField } from '../../../shared/ui';
import { entries, useOptions } from '../../options';

const EMPTY = { full_name: '', phone: '', alt_phone: '', email: '', address: '', lga: '', ward: '', directorate: '', sub_directorate: '', id_type: '', id_number: '', notes: '' };

export default function BeneficiaryForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const options = useOptions();
    const existing = useApi(id ? `/admin/beneficiaries/${id}` : null);
    const [form, setForm] = useState(EMPTY);
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (existing.data) {
            const b = existing.data.beneficiary;
            setForm(Object.fromEntries(Object.keys(EMPTY).map((key) => [key, b[key] ?? ''])));
        }
    }, [existing.data]);

    const set = (key) => (e) => setForm({ ...form, [key]: e.target.value });
    // Sub-directorates belong to one directorate, so picking another directorate clears it
    const setDirectorate = (e) => setForm({ ...form, directorate: e.target.value, sub_directorate: '' });
    const subDirectorates = options.directorates[form.directorate] ?? [];

    async function submit(event) {
        event.preventDefault();
        setSaving(true);
        setError(null);
        try {
            // Empty optional fields are saved as blank, not as ""
            const body = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v === '' ? null : v]));
            const data = await api(id ? `/admin/beneficiaries/${id}` : '/admin/beneficiaries', { method: id ? 'PATCH' : 'POST', body });
            navigate(`/beneficiaries/${data.beneficiary.id}`);
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
            <PageHeader title={id ? `Edit ${existing.data.beneficiary.full_name}` : 'Add beneficiary'} />
            <form onSubmit={submit} noValidate>
                <Card>
                    {error && Object.keys(error.errors).length === 0 && <div className="mb-5"><Alert>{error.message}</Alert></div>}
                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <Field id="full_name" label="Full name" value={form.full_name} onChange={set('full_name')} error={err('full_name')} required />
                        </div>
                        <Field id="phone" label="WhatsApp number" type="tel" placeholder="0803 123 4567" value={form.phone} onChange={set('phone')} error={err('phone')} hint="Login codes and reminders go to this number." required />
                        <Field id="alt_phone" label="Other phone" type="tel" value={form.alt_phone} onChange={set('alt_phone')} error={err('alt_phone')} />
                        <Field id="email" label="Email" type="email" value={form.email} onChange={set('email')} error={err('email')} />
                        <Field id="address" label="Address" value={form.address} onChange={set('address')} error={err('address')} />
                        <SelectField id="lga" label="LGA" placeholder="Choose…" options={options.lgas.map((l) => [l, l])} value={form.lga} onChange={set('lga')} error={err('lga')} />
                        <Field id="ward" label="Ward" value={form.ward} onChange={set('ward')} error={err('ward')} />
                        <SelectField id="directorate" label="Directorate" placeholder="Choose…" options={Object.keys(options.directorates).map((d) => [d, d])} value={form.directorate} onChange={setDirectorate} error={err('directorate')} />
                        {subDirectorates.length > 0 ? (
                            <SelectField id="sub_directorate" label="Sub-directorate" placeholder="Choose…" options={subDirectorates.map((s) => [s, s])} value={form.sub_directorate} onChange={set('sub_directorate')} error={err('sub_directorate')} />
                        ) : (
                            // Keeps the grid aligned while the directorate has no sub-directorates
                            <div className="hidden sm:block" />
                        )}
                        <SelectField id="id_type" label="ID type" placeholder="Choose…" options={entries(options.id_types)} value={form.id_type} onChange={set('id_type')} error={err('id_type')} />
                        <Field id="id_number" label="ID number" value={form.id_number} onChange={set('id_number')} error={err('id_number')} />
                        <div className="sm:col-span-2">
                            <TextareaField id="notes" label="Notes" value={form.notes} onChange={set('notes')} error={err('notes')} />
                        </div>
                    </div>
                </Card>
                <div className="mt-5 flex justify-end gap-3">
                    <Link to={id ? `/beneficiaries/${id}` : '/beneficiaries'}><Button type="button" variant="secondary">Cancel</Button></Link>
                    <Button type="submit" loading={saving}>{id ? 'Save changes' : 'Add beneficiary'}</Button>
                </div>
            </form>
        </>
    );
}
