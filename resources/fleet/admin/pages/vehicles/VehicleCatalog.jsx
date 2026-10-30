import { useState } from 'react';
import { api } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Alert, Badge, Button, Card, EmptyState, Field, LoadError, Loading, PageHeader, SelectField } from '../../../shared/ui';
import { entries, useOptions } from '../../options';

const plural = (n) => `${n} vehicle${n === 1 ? '' : 's'}`;

/**
 * The makes and models offered in the vehicle form (super admins only).
 * Renames carry onto the vehicles that use them; entries in use can't be deleted.
 */
export default function VehicleCatalog() {
    const options = useOptions();
    const { data, error, reload } = useApi('/admin/vehicle-catalog');
    const [search, setSearch] = useState('');
    const [type, setType] = useState('');
    const [notice, setNotice] = useState(null); // { tone, text }

    // Runs a change, then reloads; failures show at the top of the page
    async function run(request, success) {
        setNotice(null);
        try {
            await request();
            setNotice({ tone: 'success', text: success });
            reload();
            return true;
        } catch (e) {
            setNotice({ tone: 'error', text: Object.values(e.errors)[0]?.[0] ?? e.message });
            return false;
        }
    }

    if (error) return <LoadError error={error} onRetry={reload} />;
    if (!data) return <Loading />;

    const term = search.trim().toLowerCase();
    const makes = data.makes
        .map((make) => ({
            ...make,
            shown: make.models.filter((m) => (!type || m.vehicle_type === type) && (!term || `${make.name} ${m.name}`.toLowerCase().includes(term))),
        }))
        .filter((make) => make.shown.length > 0 || (!type && (!term || make.name.toLowerCase().includes(term))));

    return (
        <>
            <PageHeader
                title="Makes & models"
                subtitle="What the vehicle form offers. Each model is tagged with its type, so a keke can't be recorded as a Corolla. Renaming updates the vehicles that use it."
            />

            {notice && <div className="mb-5"><Alert tone={notice.tone}>{notice.text}</Alert></div>}

            <div className="mb-5 grid gap-4 sm:grid-cols-3">
                <Field id="search" label="Search" placeholder="Make or model" value={search} onChange={(e) => setSearch(e.target.value)} />
                <SelectField id="type" label="Type" placeholder="All types" options={entries(options.vehicle_types)} value={type} onChange={(e) => setType(e.target.value)} />
                <AddMake onAdd={(name) => run(() => api('/admin/vehicle-makes', { method: 'POST', body: { name } }), `${name} added.`)} />
            </div>

            {makes.length === 0 ? <EmptyState title="Nothing matches">Try another search or type.</EmptyState> : (
                <div className="space-y-5">
                    {makes.map((make) => <MakeCard key={make.id} make={make} run={run} />)}
                </div>
            )}
        </>
    );
}

function AddMake({ onAdd }) {
    const [name, setName] = useState('');
    const [saving, setSaving] = useState(false);

    async function submit(event) {
        event.preventDefault();
        setSaving(true);
        if (await onAdd(name.trim())) setName('');
        setSaving(false);
    }

    return (
        <form onSubmit={submit} className="flex items-end gap-2">
            <div className="flex-1">
                <Field id="new_make" label="Add a make" placeholder="e.g. Haojue" value={name} onChange={(e) => setName(e.target.value)} />
            </div>
            <Button type="submit" loading={saving} disabled={!name.trim()}>Add</Button>
        </form>
    );
}

function MakeCard({ make, run }) {
    const options = useOptions();
    const [renaming, setRenaming] = useState(false);

    const actions = renaming ? null : (
        <>
            <Button variant="link" className="px-0 py-0" onClick={() => setRenaming(true)}>Rename</Button>
            <Button
                variant="link"
                className="px-0 py-0 text-red-700"
                onClick={() => window.confirm(`Delete ${make.name} and all its models?`)
                    && run(() => api(`/admin/vehicle-makes/${make.id}`, { method: 'DELETE' }), `${make.name} deleted.`)}
            >
                Delete
            </Button>
        </>
    );

    const title = renaming ? (
        <InlineEdit
            initial={make.name}
            label={`New name for ${make.name}`}
            onSave={(name) => run(() => api(`/admin/vehicle-makes/${make.id}`, { method: 'PATCH', body: { name } }), `Renamed to ${name}.`)}
            onDone={() => setRenaming(false)}
        />
    ) : (
        <>{make.name} <span className="text-sm font-normal text-slate-500">· {plural(make.vehicle_count)}</span></>
    );

    return (
        <Card title={title} actions={actions} className="overflow-hidden [&>div:last-child]:p-0">
            <table className="min-w-full text-sm">
                <thead className="bg-slate-50 text-left text-xs font-medium tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th className="px-4 py-2.5">Model</th>
                        <th className="px-4 py-2.5">Type</th>
                        <th className="px-4 py-2.5">In use</th>
                        <th className="px-4 py-2.5"><span className="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {make.shown.map((model) => <ModelRow key={model.id} make={make} model={model} run={run} />)}
                </tbody>
            </table>
            <AddModel
                make={make}
                types={entries(options.vehicle_types)}
                onAdd={(body) => run(() => api(`/admin/vehicle-makes/${make.id}/models`, { method: 'POST', body }), `${make.name} ${body.name} added.`)}
            />
        </Card>
    );
}

function ModelRow({ make, model, run }) {
    const options = useOptions();
    const [editing, setEditing] = useState(false);
    const [form, setForm] = useState({ name: model.name, vehicle_type: model.vehicle_type });
    const [saving, setSaving] = useState(false);

    async function save() {
        setSaving(true);
        const body = { name: form.name.trim(), ...(form.vehicle_type !== model.vehicle_type ? { vehicle_type: form.vehicle_type } : {}) };
        if (await run(() => api(`/admin/vehicle-models/${model.id}`, { method: 'PATCH', body }), `${make.name} ${body.name} saved.`)) setEditing(false);
        setSaving(false);
    }

    if (editing) {
        return (
            <tr className="bg-slate-50">
                <td className="px-4 py-2"><Field id={`model-${model.id}`} label="Model" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></td>
                <td className="px-4 py-2">
                    <SelectField
                        id={`type-${model.id}`}
                        label="Type"
                        options={entries(options.vehicle_types)}
                        value={form.vehicle_type}
                        onChange={(e) => setForm({ ...form, vehicle_type: e.target.value })}
                        hint={model.vehicle_count > 0 ? "Can't change while vehicles use it." : undefined}
                        disabled={model.vehicle_count > 0}
                    />
                </td>
                <td className="px-4 py-2">{plural(model.vehicle_count)}</td>
                <td className="px-4 py-2 text-right whitespace-nowrap">
                    <Button variant="link" className="px-2 py-0" onClick={() => setEditing(false)}>Cancel</Button>
                    <Button className="px-3 py-2" loading={saving} disabled={!form.name.trim()} onClick={save}>Save</Button>
                </td>
            </tr>
        );
    }

    return (
        <tr>
            <td className="px-4 py-2.5 font-medium">{model.name}</td>
            <td className="px-4 py-2.5">{options.vehicle_types[model.vehicle_type] ?? model.vehicle_type}</td>
            <td className="px-4 py-2.5">{model.vehicle_count > 0 ? <Badge tone="blue">{plural(model.vehicle_count)}</Badge> : <span className="text-slate-400">—</span>}</td>
            <td className="px-4 py-2.5 text-right whitespace-nowrap">
                <Button variant="link" className="px-2 py-0" onClick={() => setEditing(true)}>Edit</Button>
                <Button
                    variant="link"
                    className="px-2 py-0 text-red-700"
                    onClick={() => window.confirm(`Delete ${make.name} ${model.name}?`)
                        && run(() => api(`/admin/vehicle-models/${model.id}`, { method: 'DELETE' }), `${make.name} ${model.name} deleted.`)}
                >
                    Delete
                </Button>
            </td>
        </tr>
    );
}

function AddModel({ make, types, onAdd }) {
    const [form, setForm] = useState({ name: '', vehicle_type: 'car' });
    const [saving, setSaving] = useState(false);

    async function submit(event) {
        event.preventDefault();
        setSaving(true);
        if (await onAdd({ ...form, name: form.name.trim() })) setForm({ ...form, name: '' });
        setSaving(false);
    }

    return (
        <form onSubmit={submit} className="flex flex-wrap items-end gap-3 border-t border-slate-100 px-4 py-3">
            <div className="min-w-40 flex-1">
                <Field id={`new-model-${make.id}`} label={`Add a ${make.name} model`} value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            </div>
            <SelectField id={`new-type-${make.id}`} label="Type" options={types} value={form.vehicle_type} onChange={(e) => setForm({ ...form, vehicle_type: e.target.value })} />
            <Button type="submit" variant="secondary" loading={saving} disabled={!form.name.trim()}>Add model</Button>
        </form>
    );
}

function InlineEdit({ initial, label, onSave, onDone }) {
    const [value, setValue] = useState(initial);
    const [saving, setSaving] = useState(false);

    async function submit(event) {
        event.preventDefault();
        setSaving(true);
        if (await onSave(value.trim())) onDone();
        setSaving(false);
    }

    return (
        <form onSubmit={submit} className="flex items-end gap-2 font-normal">
            <Field id={`rename-${initial}`} label={label} value={value} onChange={(e) => setValue(e.target.value)} />
            <Button type="button" variant="link" className="px-2 py-3" onClick={onDone}>Cancel</Button>
            <Button type="submit" className="px-3" loading={saving} disabled={!value.trim() || value.trim() === initial}>Save</Button>
        </form>
    );
}
