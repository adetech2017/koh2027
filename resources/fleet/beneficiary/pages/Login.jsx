import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { api } from '../../shared/api';
import { Alert, Button, Field, Logo } from '../../shared/ui';
import { useAuth } from '../auth';

const RESEND_SECONDS = 60;

export default function Login() {
    const { setUser } = useAuth();
    const navigate = useNavigate();

    const [step, setStep] = useState('phone');
    const [phone, setPhone] = useState('');
    const [code, setCode] = useState('');
    const [sentTo, setSentTo] = useState('');
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);
    const [resendIn, setResendIn] = useState(0);

    useEffect(() => {
        if (resendIn <= 0) return;
        const timer = setTimeout(() => setResendIn((s) => s - 1), 1000);
        return () => clearTimeout(timer);
    }, [resendIn]);

    async function requestCode(event) {
        event?.preventDefault();
        setError(null);
        setLoading(true);
        try {
            const data = await api('/auth/request-code', { method: 'POST', body: { phone } });
            setSentTo(data.phone);
            setStep('code');
            setCode('');
            setResendIn(RESEND_SECONDS);
        } catch (e) {
            setError(e);
        } finally {
            setLoading(false);
        }
    }

    async function verify(event) {
        event.preventDefault();
        setError(null);
        setLoading(true);
        try {
            const data = await api('/auth/verify', { method: 'POST', body: { phone, code } });
            setUser(data.beneficiary);
            navigate('/', { replace: true });
        } catch (e) {
            setError(e);
            setLoading(false);
        }
    }

    return (
        <main className="mx-auto flex min-h-dvh max-w-md flex-col justify-center px-4 py-10">
            <Logo subtitle="Campaign vehicle programme" />

            {step === 'phone' ? (
                <form onSubmit={requestCode} className="mt-8 space-y-5" noValidate>
                    <div>
                        <h1 className="text-2xl font-semibold">Sign in</h1>
                        <p className="mt-1 text-slate-600">We'll send a login code to your WhatsApp.</p>
                    </div>

                    {error && !error.field('phone') && <Alert>{error.message}</Alert>}

                    <Field
                        id="phone"
                        label="WhatsApp number"
                        type="tel"
                        inputMode="tel"
                        autoComplete="tel"
                        placeholder="0803 123 4567"
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        error={error?.field('phone')}
                        required
                    />

                    <Button type="submit" loading={loading} className="w-full">
                        Send code
                    </Button>
                </form>
            ) : (
                <form onSubmit={verify} className="mt-8 space-y-5" noValidate>
                    <div>
                        <h1 className="text-2xl font-semibold">Enter your code</h1>
                        <p className="mt-1 text-slate-600">
                            If <span className="font-medium text-slate-900">{sentTo}</span> is registered, a 6-digit
                            code has been sent to it on WhatsApp. Ask your coordinator if it doesn't arrive.
                        </p>
                    </div>

                    {error && !error.field('code') && <Alert>{error.message}</Alert>}

                    <Field
                        id="code"
                        label="Login code"
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        maxLength={6}
                        className="text-center text-2xl tracking-[0.5em]"
                        value={code}
                        onChange={(e) => setCode(e.target.value.replace(/\D/g, ''))}
                        error={error?.field('code')}
                        autoFocus
                        required
                    />

                    <Button type="submit" loading={loading} disabled={code.length !== 6} className="w-full">
                        Sign in
                    </Button>

                    <div className="flex items-center justify-between text-sm">
                        <Button type="button" variant="link" className="px-0" onClick={() => { setStep('phone'); setError(null); }}>
                            Change number
                        </Button>
                        {resendIn > 0 ? (
                            <span className="text-slate-500">Resend in {resendIn}s</span>
                        ) : (
                            <Button type="button" variant="link" className="px-0" onClick={requestCode} disabled={loading}>
                                Resend code
                            </Button>
                        )}
                    </div>
                </form>
            )}
        </main>
    );
}
