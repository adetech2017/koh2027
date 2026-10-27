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
                className={`block w-full rounded-lg border bg-white px-3.5 py-3 text-base shadow-sm outline-none transition focus:ring-2 focus:ring-primary/30 ${
                    error ? 'border-red-500' : 'border-slate-300 focus:border-primary'
                } ${className}`}
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
