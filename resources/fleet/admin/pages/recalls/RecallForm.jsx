import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router';
import { api } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Alert, Button, Card, Field, PageHeader, TextareaField } from '../../../shared/ui';

/**
 * Recall the vehicles picked on the Assignments page, or every vehicle that's out.
 */
export default function RecallForm() {
    const navigate = useNavigate();
    const picked = useLocation().state?.assignmentIds ?? [];
    const outCount = useApi('/admin/assignments?status=active&per_page=1');
    const [form, setForm] = useState({ title: '', reason: '', return_by: '', scope: picked.length ? 'selected' : 'all' });
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);

    const set = (key) => (e) => setForm({ ...form, [key]: e.target.value });
    const err = (key) => error?.field(key);
    const total = outCount.data?.total;

    async function submit(event) {
        event.preventDefault();
        setSaving(true);
        setError(null);
        try {
            const body = {
                title: form.title,
                reason: form.reason || null,
                return_by: form.return_by,
                ...(form.scope === 'all' ? { all_active: true } : { assignment_ids: picked }),
            };
            const data = await api('/admin/recalls', { method: 'POST', body });
            navigate(`/recalls/${data.recall.id}`, { replace: true });
        } catch (e) {
            setError(e);
            setSaving(false);
        }
    }

    return (
        <form onSubmit={submit} noValidate className="mx-auto max-w-2xl">
            <PageHeader title="New recall" subtitle="Call vehicles back from beneficiaries by a date." />
            <Card>
                <div className="space-y-5">
                    {error && (err('assignment_ids') || !Object.keys(error.errors).length) && <Alert>{err('assignment_ids') || error.message}</Alert>}
                    <Field id="title" label="Title" placeholder="e.g. End of campaign — Ikeja" value={form.title} onChange={set('title')} error={err('title')} required />
                    <TextareaField id="reason" label="Reason (optional)" value={form.reason} onChange={set('reason')} error={err('reason')} hint="Beneficiaries see this in the app." />
                    <Field id="return_by" label="Return by" type="date" min={new Date().toISOString().slice(0, 10)} value={form.return_by} onChange={set('return_by')} error={err('return_by')} required />

                    <fieldset>
                        <legend className="mb-2 text-sm font-medium text-slate-700">Vehicles</legend>
                        <div className="space-y-2 text-sm">
                            {picked.length > 0 && (
                                <label className="flex items-center gap-2">
                                    <input type="radio" name="scope" value="selected" checked={form.scope === 'selected'} onChange={set('scope')} className="size-4" />
                                    The {picked.length} vehicle{picked.length === 1 ? '' : 's'} selected on the Assignments page
                                </label>
                            )}
                            <label className="flex items-center gap-2">
                                <input type="radio" name="scope" value="all" checked={form.scope === 'all'} onChange={set('scope')} className="size-4" />
                                Every vehicle that's out and not already recalled{total != null && ` (${total})`}
                            </label>
                        </div>
                        {picked.length === 0 && (
                            <p className="mt-2 text-sm text-slate-500">
                                To recall only some vehicles, select them on the <Link to="/assignments" className="text-primary hover:underline">Assignments</Link> page first.
                            </p>
                        )}
                    </fieldset>
                </div>
            </Card>
            <div className="mt-5 flex justify-end gap-3">
                <Link to="/recalls"><Button type="button" variant="secondary">Cancel</Button></Link>
                <Button type="submit" loading={saving}>Create recall</Button>
            </div>
        </form>
    );
}
