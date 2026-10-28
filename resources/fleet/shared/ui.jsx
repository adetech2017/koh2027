/**
 * Small shared building blocks for both fleet apps.
 */

export function Spinner({ className = 'size-5' }) {
    return (
        <svg className={`animate-spin ${className}`} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" className="opacity-25" />
            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
        </svg>
    );
}

export function Button({ loading = false, variant = 'primary', className = '', children, ...props }) {
    const variants = {
        primary: 'bg-primary text-white hover:bg-primary-dark',
        secondary: 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50',
        link: 'text-primary underline-offset-2 hover:underline',
        danger: 'bg-white text-red-700 ring-1 ring-red-300 hover:bg-red-50',
    };

    return (
        <button
            disabled={loading || props.disabled}
            className={`inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60 ${variants[variant]} ${className}`}
            {...props}
        >
            {loading && <Spinner className="size-4" />}
            {children}
        </button>
    );
}

export function Field({ label, error, hint, id, className = '', ...props }) {
    return (
        <div>
            <label htmlFor={id} className="mb-1.5 block text-sm font-medium text-slate-700">
                {label}
            </label>
            <input
                id={id}
                aria-invalid={error ? 'true' : undefined}
                aria-describedby={error ? `${id}-error` : hint ? `${id}-hint` : undefined}
                className={`${inputClass(error)} ${className}`}
                {...props}
            />
            {error ? (
                <p id={`${id}-error`} className="mt-1.5 text-sm text-red-600">{error}</p>
            ) : hint ? (
                <p id={`${id}-hint`} className="mt-1.5 text-sm text-slate-500">{hint}</p>
            ) : null}
        </div>
    );
}

export function Alert({ tone = 'error', children }) {
    const tones = {
        error: 'bg-red-50 text-red-700 ring-red-200',
        success: 'bg-green-50 text-green-800 ring-green-200',
        info: 'bg-blue-50 text-blue-800 ring-blue-200',
    };

    return (
        <div role={tone === 'error' ? 'alert' : 'status'} className={`rounded-lg px-4 py-3 text-sm ring-1 ${tones[tone]}`}>
            {children}
        </div>
    );
}

export function Logo({ subtitle }) {
    return (
        <div className="flex items-center gap-3">
            <div className="flex size-10 items-center justify-center rounded-lg bg-primary text-sm font-bold text-gold">KOH</div>
            <div className="leading-tight">
                <div className="font-semibold text-slate-900">KOH Fleet</div>
                {subtitle && <div className="text-xs text-slate-500">{subtitle}</div>}
            </div>
        </div>
    );
}

const inputClass = (error) =>
    `block w-full rounded-lg border bg-white px-3.5 py-2.5 text-base shadow-sm outline-none transition focus:ring-2 focus:ring-primary/30 sm:text-sm ${
        error ? 'border-red-500' : 'border-slate-300 focus:border-primary'
    }`;

function FieldShell({ label, id, error, hint, children }) {
    return (
        <div>
            {label && (
                <label htmlFor={id} className="mb-1.5 block text-sm font-medium text-slate-700">
                    {label}
                </label>
            )}
            {children}
            {error ? (
                <p id={`${id}-error`} className="mt-1.5 text-sm text-red-600">{error}</p>
            ) : hint ? (
                <p id={`${id}-hint`} className="mt-1.5 text-sm text-slate-500">{hint}</p>
            ) : null}
        </div>
    );
}

/**
 * options: [[value, label], ...]
 */
export function SelectField({ label, id, error, hint, options, placeholder, ...props }) {
    return (
        <FieldShell label={label} id={id} error={error} hint={hint}>
            <select id={id} aria-invalid={error ? 'true' : undefined} className={inputClass(error)} {...props}>
                {placeholder !== undefined && <option value="">{placeholder}</option>}
                {options.map(([value, text]) => (
                    <option key={value} value={value}>{text}</option>
                ))}
            </select>
        </FieldShell>
    );
}

export function TextareaField({ label, id, error, hint, ...props }) {
    return (
        <FieldShell label={label} id={id} error={error} hint={hint}>
            <textarea id={id} rows={3} aria-invalid={error ? 'true' : undefined} className={inputClass(error)} {...props} />
        </FieldShell>
    );
}

const BADGE_TONES = {
    green: 'bg-green-50 text-green-700 ring-green-600/20',
    blue: 'bg-blue-50 text-blue-700 ring-blue-600/20',
    amber: 'bg-amber-50 text-amber-800 ring-amber-600/20',
    red: 'bg-red-50 text-red-700 ring-red-600/20',
    slate: 'bg-slate-100 text-slate-700 ring-slate-500/20',
};

export function Badge({ tone = 'slate', children }) {
    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset ${BADGE_TONES[tone]}`}>
            {children}
        </span>
    );
}

const STATUSES = {
    available: ['green', 'Available'],
    assigned: ['blue', 'With beneficiary'],
    maintenance: ['amber', 'Maintenance'],
    retired: ['slate', 'Retired'],
    active: ['blue', 'Out'],
    recalled: ['amber', 'Recalled'],
    returned: ['slate', 'Returned'],
    suspended: ['red', 'Suspended'],
};

export function StatusBadge({ status }) {
    const [tone, text] = STATUSES[status] ?? ['slate', status];
    return <Badge tone={tone}>{text}</Badge>;
}

export function Card({ title, actions, children, className = '' }) {
    return (
        <section className={`rounded-xl bg-white shadow-sm ring-1 ring-slate-200 ${className}`}>
            {(title || actions) && (
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                    {title && <h2 className="font-semibold">{title}</h2>}
                    {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
                </div>
            )}
            <div className="p-5">{children}</div>
        </section>
    );
}

export function PageHeader({ title, subtitle, actions, back }) {
    return (
        <div className="mb-6">
            {back}
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="min-w-0">
                    <h1 className="text-2xl font-semibold break-words">{title}</h1>
                    {subtitle && <p className="mt-1 text-slate-600">{subtitle}</p>}
                </div>
                {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
            </div>
        </div>
    );
}

export function EmptyState({ title, children }) {
    return (
        <div className="px-4 py-12 text-center">
            <p className="font-medium text-slate-900">{title}</p>
            {children && <div className="mt-1 text-sm text-slate-500">{children}</div>}
        </div>
    );
}

export function Loading() {
    return (
        <div className="flex justify-center py-16">
            <Spinner className="size-7 text-primary" />
        </div>
    );
}

export function LoadError({ error, onRetry }) {
    return (
        <Alert>
            {error?.status === 404 ? 'This record was not found.' : error?.message || 'Could not load this page.'}{' '}
            {onRetry && error?.status !== 404 && (
                <button type="button" onClick={onRetry} className="font-semibold underline">
                    Try again
                </button>
            )}
        </Alert>
    );
}

/**
 * Laravel paginator JSON → "Showing 1–25 of 140" with previous/next.
 */
export function Pagination({ meta, onPage }) {
    if (!meta || meta.total === 0) return null;

    return (
        <div className="flex items-center justify-between gap-4 border-t border-slate-100 px-4 py-3 text-sm text-slate-600">
            <span>
                {meta.from}–{meta.to} of {meta.total}
            </span>
            {meta.last_page > 1 && (
                <div className="flex gap-2">
                    <Button variant="secondary" className="py-1.5" disabled={meta.current_page <= 1} onClick={() => onPage(meta.current_page - 1)}>
                        Previous
                    </Button>
                    <Button variant="secondary" className="py-1.5" disabled={meta.current_page >= meta.last_page} onClick={() => onPage(meta.current_page + 1)}>
                        Next
                    </Button>
                </div>
            )}
        </div>
    );
}

/**
 * items: [[label, value], ...]; empty values show as a dash.
 */
export function Details({ items, columns = 2 }) {
    return (
        <dl className={`grid gap-x-6 gap-y-4 ${columns === 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-2'}`}>
            {items.map(([label, value]) => (
                <div key={label} className="min-w-0">
                    <dt className="text-xs font-medium tracking-wide text-slate-500 uppercase">{label}</dt>
                    <dd className="mt-0.5 break-words text-slate-900">{value === null || value === undefined || value === '' ? '—' : value}</dd>
                </div>
            ))}
        </dl>
    );
}

export function formatDate(iso, withTime = false) {
    if (!iso) return '—';
    const date = new Date(iso.length === 10 ? `${iso}T00:00:00` : iso);
    return date.toLocaleString('en-NG', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        ...(withTime && { hour: 'numeric', minute: '2-digit' }),
    });
}
