import { useState } from 'react';
import { api } from '../../shared/api';
import { useApi } from '../../shared/hooks';
import { Alert, Badge, Button, Card, Field, LoadError, Loading, PageHeader, SelectField, formatDate } from '../../shared/ui';
import { useAuth } from '../auth';

export const ROLE_LABELS = { super_admin: 'Super admin', fleet_officer: 'Fleet officer', reviewer: 'Reviewer' };
const ROLE_OPTIONS = Object.entries(ROLE_LABELS);
const ROLE_HELP = 'Super admins manage admins. Fleet officers manage vehicles, beneficiaries, handovers and recalls. Reviewers can view everything but change nothing.';

/**
 * Fleet admin accounts (super admins only).
 */
export default function Admins() {
    const { user } = useAuth();
    const { data, error, reload } = useApi('/admin/admins');
    const [editing, setEditing] = useState(null); // null, 'new' or an admin

    return (
        <>
            <PageHeader title="Admins" subtitle={ROLE_HELP} actions={!editing && <Button onClick={() => setEditing('new')}>Add admin</Button>} />

            {editing && (
                <AdminForm
                    key={editing === 'new' ? 'new' : editing.id}
                    admin={editing === 'new' ? null : editing}
                    isSelf={editing !== 'new' && editing.id === user.id}
                    onDone={() => { setEditing(null); reload(); }}
                    onCancel={() => setEditing(null)}
                />
            )}

            {error ? <LoadError error={error} onRetry={reload} /> : !data ? <Loading /> : (
                <Card className="overflow-hidden [&>div]:p-0">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs font-medium tracking-wide text-slate-500 uppercase">
                                <tr>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3">Role</th>
                                    <th className="px-4 py-3">Last signed in</th>
                                    <th className="px-4 py-3"><span className="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {data.data.map((admin) => (
                                    <tr key={admin.id}>
                                        <td className="px-4 py-3">
                                            <div className="font-medium">{admin.name}{admin.id === user.id && <span className="text-slate-500"> (you)</span>}</div>
                                            <div className="text-slate-500">{admin.email}</div>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {ROLE_LABELS[admin.role]} {!admin.active && <Badge tone="red">Suspended</Badge>}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">{admin.last_login_at ? formatDate(admin.last_login_at, true) : 'Never'}</td>
                                        <td className="px-4 py-3 text-right">
                                            <Button variant="link" className="px-0 py-0" onClick={() => setEditing(admin)}>Edit</Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}
        </>
    );
}

function AdminForm({ admin, isSelf, onDone, onCancel }) {
    const [form, setForm] = useState({
        name: admin?.name ?? '',
        email: admin?.email ?? '',
        role: admin?.role ?? 'fleet_officer',
        password: '',
        active: admin?.active ?? true,
    });
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);

    const set = (key) => (e) => setForm({ ...form, [key]: e.target.type === 'checkbox' ? e.target.checked : e.target.value });
    const err = (key) => error?.field(key);

    async function submit(event) {
        event.preventDefault();
        setSaving(true);
        setError(null);
        try {
            const body = admin
                ? { name: form.name, email: form.email, ...(!isSelf && { role: form.role, active: form.active }), ...(form.password && { password: form.password }) }
                : { name: form.name, email: form.email, role: form.role, password: form.password };
            await api(admin ? `/admin/admins/${admin.id}` : '/admin/admins', { method: admin ? 'PATCH' : 'POST', body });
            onDone();
        } catch (e) {
            setError(e);
            setSaving(false);
        }
    }

    return (
        <form onSubmit={submit} noValidate className="mb-6">
            <Card title={admin ? `Edit ${admin.name}` : 'Add admin'}>
                {error && !Object.keys(error.errors).length && <div className="mb-5"><Alert>{error.message}</Alert></div>}
                <div className="grid gap-5 sm:grid-cols-2">
                    <Field id="name" label="Name" value={form.name} onChange={set('name')} error={err('name')} required />
                    <Field id="email" label="Email" type="email" value={form.email} onChange={set('email')} error={err('email')} required />
                    <SelectField id="role" label="Role" options={ROLE_OPTIONS} value={form.role} onChange={set('role')} error={err('role')} disabled={isSelf} hint={isSelf ? "You can't change your own role." : undefined} />
                    <Field
                        id="password"
                        label={admin ? 'New password' : 'Password'}
                        type="password"
                        autoComplete="new-password"
                        value={form.password}
                        onChange={set('password')}
                        error={err('password')}
                        hint={admin ? 'Leave blank to keep the current password.' : 'At least 10 characters. Share it with them securely.'}
                    />
                    {admin && !isSelf && (
                        <label className="flex items-center gap-2 text-sm sm:col-span-2">
                            <input type="checkbox" checked={form.active} onChange={set('active')} className="size-4" />
                            Account active (untick to suspend; they're signed out on their next request)
                        </label>
                    )}
                </div>
                <div className="mt-5 flex justify-end gap-3">
                    <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                    <Button type="submit" loading={saving}>{admin ? 'Save' : 'Add admin'}</Button>
                </div>
            </Card>
        </form>
    );
}
