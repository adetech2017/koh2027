import { useState } from 'react';
import { useLocation, useNavigate } from 'react-router';
import { api } from '../../shared/api';
import { Alert, Button, Field, Logo } from '../../shared/ui';
import { useAuth } from '../auth';

export default function Login() {
    const { setUser } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();

    const [form, setForm] = useState({ email: '', password: '', remember: false });
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    async function submit(event) {
        event.preventDefault();
        setError(null);
        setLoading(true);
        try {
            const data = await api('/admin/auth/login', { method: 'POST', body: form });
            setUser(data.admin);
            navigate(location.state?.from || '/', { replace: true });
        } catch (e) {
            setError(e);
            setLoading(false);
        }
    }

    const set = (key) => (e) => setForm({ ...form, [key]: e.target.type === 'checkbox' ? e.target.checked : e.target.value });

    return (
        <main className="flex min-h-dvh items-center justify-center px-4 py-10">
            <form onSubmit={submit} className="w-full max-w-sm space-y-5 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200" noValidate>
                <Logo subtitle="Admin" />
                <h1 className="text-xl font-semibold">Sign in</h1>

                {error && !error.field('email') && !error.field('password') && <Alert>{error.message}</Alert>}

                <Field id="email" label="Email" type="email" autoComplete="username" value={form.email} onChange={set('email')} error={error?.field('email')} required />
                <Field id="password" label="Password" type="password" autoComplete="current-password" value={form.password} onChange={set('password')} error={error?.field('password')} required />

                <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" checked={form.remember} onChange={set('remember')} className="size-4 rounded border-slate-300" />
                    Keep me signed in
                </label>

                <Button type="submit" loading={loading} className="w-full">
                    Sign in
                </Button>
            </form>
        </main>
    );
}
