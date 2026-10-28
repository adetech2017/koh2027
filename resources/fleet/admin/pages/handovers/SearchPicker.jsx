import { useState } from 'react';
import { qs } from '../../../shared/api';
import { useApi, useDebounced } from '../../../shared/hooks';
import { Button, Field, Spinner } from '../../../shared/ui';

/**
 * Search-as-you-type chooser for a vehicle or beneficiary. Shows the chosen record with a
 * "Change" button once picked.
 */
export default function SearchPicker({ id, label, endpoint, params, placeholder, value, onChange, render, error }) {
    const [search, setSearch] = useState('');
    const debounced = useDebounced(search);
    const { data, loading } = useApi(value ? null : `${endpoint}${qs({ ...params, search: debounced, per_page: 8 })}`);

    if (value) {
        return (
            <div>
                <div className="mb-1.5 text-sm font-medium text-slate-700">{label}</div>
                <div className="flex items-center justify-between gap-3 rounded-lg border border-slate-300 bg-slate-50 px-3.5 py-2.5">
                    <div className="min-w-0">{render(value)}</div>
                    <Button type="button" variant="link" className="shrink-0 px-0 py-0" onClick={() => onChange(null)}>Change</Button>
                </div>
                {error && <p className="mt-1.5 text-sm text-red-600">{error}</p>}
            </div>
        );
    }

    return (
        <div>
            <Field id={id} label={label} placeholder={placeholder} value={search} onChange={(e) => setSearch(e.target.value)} error={error} autoComplete="off" />
            <div className="mt-2 max-h-64 overflow-y-auto rounded-lg border border-slate-200 bg-white">
                {loading && !data ? (
                    <div className="flex justify-center py-4"><Spinner className="size-5 text-primary" /></div>
                ) : data?.data.length ? (
                    <ul className="divide-y divide-slate-100">
                        {data.data.map((item) => (
                            <li key={item.id}>
                                <button type="button" onClick={() => onChange(item)} className="block w-full px-3.5 py-2.5 text-left text-sm hover:bg-slate-50">
                                    {render(item)}
                                </button>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="px-3.5 py-3 text-sm text-slate-500">No matches.</p>
                )}
            </div>
        </div>
    );
}
