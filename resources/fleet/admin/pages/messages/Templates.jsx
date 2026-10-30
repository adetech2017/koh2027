import { useRef, useState } from 'react';
import { api } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Alert, Badge, Button, Card, Field, LoadError, Loading, PageHeader, SelectField, TextareaField, formatDate } from '../../../shared/ui';
import { useAuth } from '../../auth';
import { MessageTabs } from './MessageLog';
import { MessageBubble, TemplateStatus } from './labels';

const CATEGORIES = [['UTILITY', 'Utility (about their vehicle or account)'], ['MARKETING', 'Marketing (campaign news, events)']];

/**
 * The wording of every WhatsApp message. Meta has to approve wording before WhatsApp delivers it,
 * so changes go to Meta first and the approved wording keeps being used until they're approved.
 */
export default function Templates() {
    const { user } = useAuth();
    const canEdit = user.role === 'super_admin';
    const { data, error, reload } = useApi('/admin/messages/templates');
    const [creating, setCreating] = useState(false);

    if (error) return <LoadError error={error} onRetry={reload} />;
    if (!data) return <Loading />;

    const automatic = data.data.filter((t) => t.automatic);
    const custom = data.data.filter((t) => !t.automatic);
    const card = (template) => (
        <TemplateCard key={template.id} template={template} canEdit={canEdit} canSubmit={data.can_manage_templates} testMode={data.test_mode} onChange={reload} />
    );

    return (
        <>
            <PageHeader
                title="Messages"
                subtitle="WhatsApp only delivers wording Meta has approved. Changes are sent to Meta first; the approved wording keeps being used until the new one is approved."
            />
            <MessageTabs />

            <div className="mb-6 space-y-3">
                {data.test_mode && (
                    <Alert tone="info">
                        Test mode: nothing is sent to WhatsApp or Meta. Messages are written to the server log and new wording is approved straight away.
                    </Alert>
                )}
                {canEdit && !data.can_manage_templates && (
                    <Alert tone="info">
                        To send new wording to Meta from here, add FLEET_WHATSAPP_BUSINESS_ACCOUNT_ID to the server settings. Until then you can switch messages on and off,
                        and point them at templates you have made in WhatsApp Manager (Settings).
                    </Alert>
                )}
            </div>

            <h2 className="mb-3 text-lg font-semibold">Automatic messages</h2>
            <div className="mb-8 grid gap-4 xl:grid-cols-2">{automatic.map(card)}</div>

            <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 className="text-lg font-semibold">Custom messages</h2>
                    <p className="text-sm text-slate-600">Messages admins send themselves, to one beneficiary or a group.</p>
                </div>
                {canEdit && data.can_manage_templates && !creating && <Button onClick={() => setCreating(true)}>New message</Button>}
            </div>
            {creating && (
                <div className="mb-4">
                    <WordingForm
                        isNew
                        testMode={data.test_mode}
                        onDone={() => { setCreating(false); reload(); }}
                        onCancel={() => setCreating(false)}
                    />
                </div>
            )}
            <div className="grid gap-4 xl:grid-cols-2">{custom.map(card)}</div>
        </>
    );
}

function TemplateCard({ template: t, canEdit, canSubmit, testMode, onChange }) {
    const [mode, setMode] = useState(null); // null, 'wording', 'settings'
    const [busy, setBusy] = useState(null);
    const [error, setError] = useState(null);

    async function run(name, path, options) {
        setBusy(name);
        setError(null);
        try {
            await api(path, options);
            onChange();
        } catch (e) {
            setError(e.field('meta') ?? e.message);
        } finally {
            setBusy(null);
        }
    }

    const toggle = () => run('toggle', `/admin/messages/templates/${t.id}`, { method: 'PATCH', body: { enabled: !t.enabled } });
    const check = () => run('check', `/admin/messages/templates/${t.id}/refresh`, { method: 'POST' });
    const discard = () => confirm('Discard the new wording? The current wording stays as it is.') && run('discard', `/admin/messages/templates/${t.id}/draft`, { method: 'DELETE' });

    if (mode === 'wording') {
        return <WordingForm template={t} testMode={testMode} onDone={() => { setMode(null); onChange(); }} onCancel={() => setMode(null)} />;
    }
    if (mode === 'settings') {
        return <SettingsForm template={t} onDone={() => { setMode(null); onChange(); }} onCancel={() => setMode(null)} />;
    }

    return (
        <Card
            title={(
                <span className="flex flex-wrap items-center gap-2">
                    {t.title}
                    {!t.enabled ? <Badge>Switched off</Badge> : !(testMode && t.status === 'not_checked') && <TemplateStatus status={t.status} />}
                </span>
            )}
            actions={canEdit && (
                <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" checked={t.enabled} onChange={toggle} disabled={busy === 'toggle'} className="size-4" />
                    On
                </label>
            )}
        >
            <div className="space-y-4">
                {t.description && <p className="text-sm text-slate-600">{t.description}</p>}
                {t.key === 'evaluation_due' && <p className="text-sm text-slate-600">Sent <strong>{t.settings.days_before} days</strong> before the due date.</p>}
                {error && <Alert>{error}</Alert>}
                {t.status_reason && <Alert>{t.status_reason}</Alert>}

                <MessageBubble body={t.body ?? t.draft?.body} variables={t.body ? t.variables : t.draft?.variables} />
                <Blanks variables={t.body ? t.variables : t.draft?.variables ?? t.variables} />

                {t.draft && (
                    <div className={`space-y-3 rounded-lg p-4 ring-1 ${t.draft.status === 'rejected' ? 'bg-red-50 ring-red-200' : 'bg-amber-50 ring-amber-200'}`}>
                        <p className="text-sm font-medium">
                            {t.draft.status === 'rejected'
                                ? `Meta rejected the new wording. ${t.draft.reason ?? ''}`
                                : `New wording sent to Meta ${formatDate(t.draft.submitted_at, true)}, waiting for approval. ${t.body ? 'The wording above is used until then.' : "This message can't be sent until then."}`}
                        </p>
                        {t.body && <MessageBubble body={t.draft.body} variables={t.draft.variables} />}
                        {canEdit && (
                            <div className="flex flex-wrap gap-2">
                                {t.draft.status === 'pending' && <Button variant="secondary" className="py-1.5" loading={busy === 'check'} onClick={check}>Check now</Button>}
                                {t.draft.status === 'rejected' && canSubmit && <Button variant="secondary" className="py-1.5" onClick={() => setMode('wording')}>Edit again</Button>}
                                <Button variant="danger" className="py-1.5" loading={busy === 'discard'} onClick={discard}>Discard</Button>
                            </div>
                        )}
                    </div>
                )}

                <p className="text-xs break-words text-slate-500">
                    Template <span className="font-mono">{t.name}</span> · {t.language} · {t.category.toLowerCase()}
                    {t.checked_at && ` · checked ${formatDate(t.checked_at, true)}`}
                    {t.updated_by && ` · last changed by ${t.updated_by}`}
                </p>

                {canEdit && (
                    <div className="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                        {canSubmit && !t.draft && <Button variant="secondary" className="py-1.5" onClick={() => setMode('wording')}>Edit wording</Button>}
                        <Button variant="secondary" className="py-1.5" onClick={() => setMode('settings')}>Settings</Button>
                        {!testMode && <Button variant="secondary" className="py-1.5" loading={busy === 'check'} onClick={check}>Check with Meta</Button>}
                    </div>
                )}
            </div>
        </Card>
    );
}

function Blanks({ variables }) {
    if (!variables?.length) return null;

    return (
        <p className="text-xs text-slate-500">
            {variables.map((v) => (
                <span key={v.number} className="mr-3 inline-block">
                    <span className="font-mono text-slate-700">{`{{${v.number}}}`}</span> {v.label}
                </span>
            ))}
        </p>
    );
}

/**
 * New wording for a template (or a new custom template). Custom templates also choose their blanks:
 * {{1}} is always the first name, and each extra blank is typed in by whoever sends the message.
 */
function WordingForm({ template, isNew = false, testMode, onDone, onCancel }) {
    const custom = isNew || !template.automatic;
    const source = template?.draft?.status === 'rejected' ? template.draft : template;
    const [form, setForm] = useState({
        title: '',
        name: '',
        language: 'en',
        category: 'UTILITY',
        body: source?.body ?? 'Hello {{1}}, ',
        variables: (source?.variables ?? []).filter((v) => v.number > 1).map(({ label, example }) => ({ label, example })),
    });
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);
    const bodyRef = useRef(null);

    const variables = custom
        ? [{ number: 1, label: 'First name', example: 'Adebayo' }, ...form.variables.map((v, i) => ({ ...v, number: i + 2 }))]
        : template.variables;
    const set = (key) => (e) => setForm({ ...form, [key]: e.target.value });
    const err = (key) => error?.field(key);

    function insert(number) {
        const el = bodyRef.current;
        const token = `{{${number}}}`;
        const start = el?.selectionStart ?? form.body.length;
        const end = el?.selectionEnd ?? form.body.length;
        setForm({ ...form, body: form.body.slice(0, start) + token + form.body.slice(end) });
        requestAnimationFrame(() => {
            el?.focus();
            el?.setSelectionRange(start + token.length, start + token.length);
        });
    }

    const setVariable = (i, key) => (e) => setForm({ ...form, variables: form.variables.map((v, j) => (j === i ? { ...v, [key]: e.target.value } : v)) });
    const addVariable = () => setForm({ ...form, variables: [...form.variables, { label: '', example: '' }] });
    const removeVariable = (i) => setForm({ ...form, variables: form.variables.filter((_, j) => j !== i) });

    async function submit(event) {
        event.preventDefault();
        setSaving(true);
        setError(null);
        try {
            if (isNew) {
                await api('/admin/messages/templates', { method: 'POST', body: form });
            } else {
                await api(`/admin/messages/templates/${template.id}/wording`, { method: 'POST', body: custom ? { body: form.body, variables: form.variables } : { body: form.body } });
            }
            onDone();
        } catch (e) {
            setError(e);
            setSaving(false);
        }
    }

    const otherErrors = error && (error.field('meta') || (!Object.keys(error.errors).length && error.message));

    return (
        <form onSubmit={submit} noValidate>
            <Card title={isNew ? 'New custom message' : `Edit wording: ${template.title}`}>
                <div className="space-y-5">
                    {otherErrors && <Alert>{otherErrors}</Alert>}
                    {isNew && (
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field id="title" label="Title" hint="For admins, e.g. Meeting notice." value={form.title} onChange={set('title')} error={err('title')} />
                            <Field id="name" label="Template name in Meta" hint="Lowercase and underscores, e.g. fleet_meeting_notice." value={form.name} onChange={set('name')} error={err('name')} />
                            <SelectField id="category" label="Category" options={CATEGORIES} value={form.category} onChange={set('category')} error={err('category')} hint="Marketing messages cost more and people can opt out of them." />
                            <Field id="language" label="Language code" value={form.language} onChange={set('language')} error={err('language')} hint="en, en_GB, en_US..." />
                        </div>
                    )}

                    <div>
                        <TextareaField
                            id="body"
                            label="Wording"
                            rows={5}
                            maxLength={1024}
                            ref={bodyRef}
                            value={form.body}
                            onChange={set('body')}
                            error={err('body')}
                            hint="The blanks are filled in for each beneficiary. Use each one once, and don't start or end the message with one."
                        />
                        <div className="mt-2 flex flex-wrap gap-2">
                            {variables.map((v) => (
                                <button
                                    key={v.number}
                                    type="button"
                                    onClick={() => insert(v.number)}
                                    className="rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-700 hover:bg-slate-200"
                                >
                                    Insert <span className="font-mono">{`{{${v.number}}}`}</span> {v.label || 'blank'}
                                </button>
                            ))}
                        </div>
                    </div>

                    {custom && (
                        <div>
                            <h3 className="mb-1 text-sm font-medium text-slate-700">Blanks</h3>
                            <p className="mb-3 text-sm text-slate-500">
                                <span className="font-mono">{'{{1}}'}</span> is always the beneficiary's first name. Add a blank for anything typed in when the message is sent.
                                Meta needs a realistic example of each.
                            </p>
                            <div className="space-y-3">
                                {form.variables.map((v, i) => (
                                    <div key={i} className="grid items-start gap-3 sm:grid-cols-[auto_1fr_2fr_auto]">
                                        <span className="pt-2.5 font-mono text-sm">{`{{${i + 2}}}`}</span>
                                        <Field id={`var-${i}-label`} label="Name" placeholder="e.g. Date" value={v.label} onChange={setVariable(i, 'label')} error={err(`variables.${i}.label`)} />
                                        <Field id={`var-${i}-example`} label="Example" placeholder="e.g. Saturday 5 December" value={v.example} onChange={setVariable(i, 'example')} error={err(`variables.${i}.example`)} />
                                        <Button type="button" variant="link" className="px-0 sm:mt-7" onClick={() => removeVariable(i)}>Remove</Button>
                                    </div>
                                ))}
                            </div>
                            {form.variables.length < 5 && <Button type="button" variant="secondary" className="mt-3 py-1.5" onClick={addVariable}>Add a blank</Button>}
                        </div>
                    )}

                    <div>
                        <h3 className="mb-2 text-sm font-medium text-slate-700">Preview</h3>
                        <MessageBubble body={form.body} variables={variables} />
                    </div>

                    {!testMode && (
                        <p className="text-sm text-slate-600">
                            Meta usually reviews new wording within minutes, sometimes up to a day.
                            {!isNew && ' The current wording keeps being sent until then.'}
                        </p>
                    )}
                </div>
                <div className="mt-5 flex justify-end gap-3">
                    <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                    <Button type="submit" loading={saving}>{testMode ? 'Save' : 'Send to Meta for approval'}</Button>
                </div>
            </Card>
        </form>
    );
}

function SettingsForm({ template: t, onDone, onCancel }) {
    const [form, setForm] = useState({ title: t.title, name: t.name, language: t.language, days_before: t.settings.days_before ?? '' });
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);
    const set = (key) => (e) => setForm({ ...form, [key]: e.target.value });
    const err = (key) => error?.field(key);

    async function submit(event) {
        event.preventDefault();
        setSaving(true);
        setError(null);
        try {
            await api(`/admin/messages/templates/${t.id}`, {
                method: 'PATCH',
                body: {
                    name: form.name.trim(),
                    language: form.language.trim(),
                    ...(!t.automatic && { title: form.title }),
                    ...(t.key === 'evaluation_due' && { days_before: Number(form.days_before) }),
                },
            });
            onDone();
        } catch (e) {
            setError(e);
            setSaving(false);
        }
    }

    async function remove() {
        if (!confirm(`Delete "${t.title}"? Messages already sent stay in the log. The template stays in WhatsApp Manager until you delete it there.`)) return;
        setSaving(true);
        try {
            await api(`/admin/messages/templates/${t.id}`, { method: 'DELETE' });
            onDone();
        } catch (e) {
            setError(e);
            setSaving(false);
        }
    }

    const otherErrors = error && (error.field('meta') || (!Object.keys(error.errors).length && error.message));

    return (
        <form onSubmit={submit} noValidate>
            <Card title={`Settings: ${t.title}`}>
                <div className="space-y-5">
                    {otherErrors && <Alert>{otherErrors}</Alert>}
                    <div className="grid gap-5 sm:grid-cols-2">
                        {!t.automatic && <Field id="title" label="Title" value={form.title} onChange={set('title')} error={err('title')} />}
                        {t.key === 'evaluation_due' && (
                            <Field id="days_before" label="Days before the due date" type="number" min={1} max={13} value={form.days_before} onChange={set('days_before')} error={err('days_before')} />
                        )}
                    </div>
                    <div>
                        <h3 className="text-sm font-medium text-slate-700">Template in WhatsApp Manager</h3>
                        <p className="mb-3 text-sm text-slate-500">
                            Only change these if the template was approved in WhatsApp Manager under a different name or language. The wording is then read from Meta.
                        </p>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field id="name" label="Template name" value={form.name} onChange={set('name')} error={err('name')} />
                            <Field id="language" label="Language code" value={form.language} onChange={set('language')} error={err('language')} />
                        </div>
                    </div>
                </div>
                <div className="mt-5 flex flex-wrap justify-between gap-3">
                    {!t.automatic ? <Button type="button" variant="danger" onClick={remove} disabled={saving}>Delete message</Button> : <span />}
                    <div className="flex gap-3">
                        <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                        <Button type="submit" loading={saving}>Save</Button>
                    </div>
                </div>
            </Card>
        </form>
    );
}
