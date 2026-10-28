import { useState } from 'react';
import { Link, useSearchParams } from 'react-router';
import { api } from '../../shared/api';
import { Alert, Button, Card, PageHeader } from '../../shared/ui';

const TYPES = {
    beneficiaries: {
        label: 'Beneficiaries',
        required: 'full_name, phone',
        optional: 'alt_phone, email, address, lga, ward, id_type, id_number, notes',
    },
    vehicles: {
        label: 'Vehicles',
        required: 'plate_number, vehicle_type, make, model',
        optional: 'year, colour, chassis_number, engine_number, branding, notes',
    },
};

export default function Import() {
    const [params, setParams] = useSearchParams();
    const type = TYPES[params.get('type')] ? params.get('type') : 'beneficiaries';
    const [file, setFile] = useState(null);
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);
    const [uploading, setUploading] = useState(false);

    function choose(newType) {
        setParams({ type: newType });
        setFile(null);
        setResult(null);
        setError(null);
    }

    async function upload(event) {
        event.preventDefault();
        if (!file) return;
        setUploading(true);
        setError(null);
        setResult(null);
        const body = new FormData();
        body.append('file', file);
        try {
            setResult(await api(`/admin/import/${type}`, { method: 'POST', body }));
            setFile(null);
            event.target.reset();
        } catch (e) {
            setError(e);
        } finally {
            setUploading(false);
        }
    }

    const rowErrors = error?.status === 422 ? (error.rowErrors ?? []) : [];

    return (
        <div className="mx-auto max-w-3xl">
            <PageHeader title="Import from CSV" subtitle="Add many records at once from a spreadsheet." />

            <div className="mb-5 flex gap-1 rounded-lg bg-slate-100 p-1 text-sm">
                {Object.entries(TYPES).map(([key, t]) => (
                    <button key={key} type="button" onClick={() => choose(key)} className={`flex-1 rounded-md px-3 py-1.5 font-medium ${type === key ? 'bg-white shadow-sm' : 'text-slate-600'}`}>
                        {t.label}
                    </button>
                ))}
            </div>

            <Card>
                <ol className="list-decimal space-y-2 pl-5 text-sm text-slate-700">
                    <li>
                        <a href={`/api/v1/admin/import/${type}/template`} className="font-medium text-primary hover:underline">Download the template</a>{' '}
                        and fill it in (in Excel or Google Sheets), one row per record.
                    </li>
                    <li>
                        Required columns: <code className="text-xs">{TYPES[type].required}</code>. Optional: <code className="text-xs">{TYPES[type].optional}</code>.
                    </li>
                    <li>Save it as CSV and upload it below. If any row has a problem, nothing is imported and every problem is listed.</li>
                </ol>

                <form onSubmit={upload} className="mt-5 flex flex-wrap items-center gap-3">
                    <input type="file" accept=".csv,text/csv" onChange={(e) => setFile(e.target.files[0] ?? null)} className="min-w-0 flex-1 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-medium" />
                    <Button type="submit" loading={uploading} disabled={!file}>Import {TYPES[type].label.toLowerCase()}</Button>
                </form>
            </Card>

            <div className="mt-5 space-y-4">
                {result && (
                    <Alert tone="success">
                        {result.message} <Link to={`/${type}`} className="font-semibold underline">View {TYPES[type].label.toLowerCase()}</Link>
                    </Alert>
                )}
                {error && <Alert>{error.field('file') || error.message}</Alert>}
                {rowErrors.length > 0 && (
                    <Card title={`${rowErrors.length} row${rowErrors.length === 1 ? '' : 's'} to fix`} className="[&>div:last-child]:p-0">
                        <ul className="max-h-96 divide-y divide-slate-100 overflow-y-auto text-sm">
                            {rowErrors.map((row) => (
                                <li key={row.line} className="flex gap-4 px-5 py-2.5">
                                    <span className="w-16 shrink-0 font-medium text-slate-500">Line {row.line}</span>
                                    <ul className="text-red-700">{row.errors.map((message) => <li key={message}>{message}</li>)}</ul>
                                </li>
                            ))}
                        </ul>
                    </Card>
                )}
            </div>
        </div>
    );
}
