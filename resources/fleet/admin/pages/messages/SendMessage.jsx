import { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router';
import { api, qs } from '../../../shared/api';
import { useApi } from '../../../shared/hooks';
import { Alert, Button, Card, LoadError, Loading, PageHeader, SelectField, TextareaField } from '../../../shared/ui';
import { useOptions } from '../../options';
import { MessageBubble } from './labels';

const AUDIENCES = [['with_vehicle', 'Those with a vehicle'], ['all', 'All beneficiaries']];

/**
 * An admin's own message, to one beneficiary (?beneficiary=ID) or a group. Only custom templates
 * Meta has approved can be used; the admin fills in their blanks.
 */
export default function SendMessage() {
    const options = useOptions();
    const navigate = useNavigate();
    const [params] = useSearchParams();
    const beneficiaryId = params.get('beneficiary');
    const templates = useApi('/admin/messages/templates');
    const beneficiary = useApi(beneficiaryId ? `/admin/beneficiaries/${beneficiaryId}` : null);

    const [form, setForm] = useState({ template_id: '', values: [], audience: 'with_vehicle', lga: '' });
    const audienceQuery = beneficiaryId ? null : `/admin/messages/audience${qs({ audience: form.audience, lga: form.lga })}`;
    const audience = useApi(audienceQuery);
    const [error, setError] = useState(null);
    const [sending, setSending] = useState(false);

    if (templates.error || beneficiary.error) return <LoadError error={templates.error ?? beneficiary.error} />;
    if (!templates.data || (beneficiaryId && !beneficiary.data)) return <Loading />;

    const usable = templates.data.data.filter((t) => !t.automatic && t.can_send);
    const template = usable.find((t) => String(t.id) === String(form.template_id)) ?? usable[0];
    const blanks = template?.variables.filter((v) => v.number > 1) ?? [];
    const person = beneficiary.data?.beneficiary;
    const count = person ? 1 : audience.data?.count;
    const firstName = person?.full_name.trim().split(' ')[0];

    const setValue = (i) => (e) => {
        const values = [...form.values];
        values[i] = e.target.value;
        setForm({ ...form, values });
    };
    const err = (key) => error?.field(key);

    async function submit(event) {
        event.preventDefault();
        if (!person && !confirm(`Send this WhatsApp message to ${count} beneficiar${count === 1 ? 'y' : 'ies'}?`)) return;
        setSending(true);
        setError(null);
        try {
            const result = await api('/admin/messages/send', {
                method: 'POST',
                body: {
                    template_id: template.id,
                    values: blanks.map((_, i) => form.values[i] ?? ''),
                    ...(person ? { audience: 'beneficiary', beneficiary_id: person.id } : { audience: form.audience, lga: form.lga || null }),
                },
            });
            navigate(person ? `/messages?beneficiary_id=${person.id}` : '/messages', { state: { queued: result.queued } });
        } catch (e) {
            setError(e);
            setSending(false);
        }
    }

    const back = <Link to={person ? `/beneficiaries/${person.id}` : '/messages'} className="mb-2 inline-block text-sm text-primary hover:underline">← Back</Link>;

    if (usable.length === 0) {
        return (
            <>
                <PageHeader title="Send a message" back={back} />
                <Alert tone="info">
                    There's no custom message Meta has approved yet. A super admin can add one, or check the status of the General message, under{' '}
                    <Link to="/messages/templates" className="font-semibold underline">Templates</Link>.
                </Alert>
            </>
        );
    }

    const otherErrors = error && (error.field('template_id') || error.field('audience') || error.field('values') || (!Object.keys(error.errors).length && error.message));

    return (
        <>
            <PageHeader title="Send a message" subtitle={person ? `To ${person.full_name} (${person.phone})` : 'To a group of beneficiaries on WhatsApp.'} back={back} />
            <form onSubmit={submit} noValidate className="grid gap-6 lg:grid-cols-2">
                <Card title="Message">
                    <div className="space-y-5">
                        {otherErrors && <Alert>{otherErrors}</Alert>}
                        <SelectField
                            id="template"
                            label="Choose a message"
                            options={usable.map((t) => [t.id, t.title])}
                            value={template.id}
                            onChange={(e) => setForm({ ...form, template_id: e.target.value, values: [] })}
                        />
                        {blanks.map((v, i) => (
                            <TextareaField
                                key={`${template.id}-${v.number}`}
                                id={`value-${i}`}
                                label={v.label}
                                rows={v.label.toLowerCase().includes('message') ? 4 : 2}
                                maxLength={500}
                                placeholder={`e.g. ${v.example}`}
                                value={form.values[i] ?? ''}
                                onChange={setValue(i)}
                                error={err(`values.${i}`)}
                                hint="Line breaks are turned into spaces: WhatsApp doesn't allow them here."
                            />
                        ))}
                        {!person && (
                            <div className="grid gap-5 sm:grid-cols-2">
                                <SelectField id="audience" label="Send to" options={AUDIENCES} value={form.audience} onChange={(e) => setForm({ ...form, audience: e.target.value })} />
                                <SelectField id="lga" label="LGA" placeholder="All LGAs" options={options.lgas.map((l) => [l, l])} value={form.lga} onChange={(e) => setForm({ ...form, lga: e.target.value })} />
                            </div>
                        )}
                    </div>
                </Card>

                <div className="space-y-4">
                    <Card title="Preview">
                        <MessageBubble
                            body={template.body}
                            variables={template.variables}
                            values={[firstName ?? '', ...blanks.map((_, i) => form.values[i] ?? '')]}
                        />
                        {!person && <p className="mt-3 text-sm text-slate-500">Each beneficiary sees their own first name.</p>}
                    </Card>
                    <div className="flex flex-wrap items-center justify-end gap-4">
                        {!person && (
                            <span className="text-sm text-slate-600">
                                {count === undefined ? 'Counting…' : `${count} beneficiar${count === 1 ? 'y' : 'ies'} (suspended ones are left out)`}
                            </span>
                        )}
                        <Button type="submit" loading={sending} disabled={count === 0}>Send on WhatsApp</Button>
                    </div>
                </div>
            </form>
        </>
    );
}
