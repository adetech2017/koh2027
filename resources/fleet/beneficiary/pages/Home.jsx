import { useState } from 'react';
import { api } from '../../shared/api';
import { Button, Logo } from '../../shared/ui';
import { useAuth } from '../auth';

export default function Home() {
    const { user, setUser } = useAuth();
    const [signingOut, setSigningOut] = useState(false);

    async function logout() {
        setSigningOut(true);
        await api('/auth/logout', { method: 'POST' }).catch(() => {});
        setUser(null);
    }

    return (
        <main className="mx-auto min-h-dvh max-w-md px-4 py-6">
            <header className="flex items-center justify-between">
                <Logo />
                <Button variant="secondary" loading={signingOut} onClick={logout} className="py-2">
                    Sign out
                </Button>
            </header>

            <h1 className="mt-8 text-2xl font-semibold">Hello, {user.full_name.split(' ')[0]}</h1>

            <section className="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 className="font-semibold">Your vehicle</h2>
                <p className="mt-1 text-sm text-slate-600">
                    Your vehicle details and evaluation schedule will appear here once a vehicle has been handed over to you.
                </p>
            </section>
        </main>
    );
}
