import { useState } from 'react';
import { NavLink, Outlet } from 'react-router';
import { api } from '../shared/api';
import { Button, Logo } from '../shared/ui';
import { useAuth } from './auth';
import { OptionsProvider } from './options';
import { ROLE_LABELS } from './pages/Admins';

const NAV = [
    { to: '/', label: 'Dashboard', end: true },
    { to: '/vehicles', label: 'Vehicles' },
    { to: '/beneficiaries', label: 'Beneficiaries' },
    { to: '/assignments', label: 'Assignments' },
    { to: '/evaluations', label: 'Evaluations' },
    { to: '/recalls', label: 'Recalls' },
    { to: '/import', label: 'Import', roles: ['super_admin', 'fleet_officer'] },
    { to: '/admins', label: 'Admins', roles: ['super_admin'] },
];

export default function Layout() {
    const { user, setUser } = useAuth();
    const [menuOpen, setMenuOpen] = useState(false);
    const [signingOut, setSigningOut] = useState(false);

    async function logout() {
        setSigningOut(true);
        await api('/admin/auth/logout', { method: 'POST' }).catch(() => {});
        setUser(null);
    }

    return (
        <div className="min-h-dvh lg:flex">
            <aside
                className={`${menuOpen ? 'block' : 'hidden'} border-b border-slate-200 bg-white lg:block lg:w-64 lg:shrink-0 lg:border-r lg:border-b-0`}
            >
                <div className="hidden p-5 lg:block">
                    <Logo subtitle="Admin" />
                </div>
                <nav className="space-y-1 p-3">
                    {NAV.filter((item) => !item.roles || item.roles.includes(user.role)).map((item) => (
                        <NavLink
                            key={item.to}
                            to={item.to}
                            end={item.end}
                            onClick={() => setMenuOpen(false)}
                            className={({ isActive }) =>
                                `block rounded-lg px-3 py-2 text-sm font-medium ${isActive ? 'bg-primary text-white' : 'text-slate-700 hover:bg-slate-100'}`
                            }
                        >
                            {item.label}
                        </NavLink>
                    ))}
                </nav>
            </aside>

            <div className="min-w-0 flex-1">
                <header className="flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 py-3 lg:px-8">
                    <div className="flex items-center gap-3 lg:hidden">
                        <button
                            type="button"
                            onClick={() => setMenuOpen((open) => !open)}
                            className="rounded-lg p-2 text-slate-700 hover:bg-slate-100"
                            aria-label="Toggle menu"
                            aria-expanded={menuOpen}
                        >
                            <svg className="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M4 6h16M4 12h16M4 18h16" strokeLinecap="round" />
                            </svg>
                        </button>
                        <Logo />
                    </div>
                    <div className="ml-auto flex items-center gap-4">
                        <div className="hidden text-right text-sm sm:block">
                            <div className="font-medium">{user.name}</div>
                            <div className="text-slate-500">{ROLE_LABELS[user.role] ?? user.role}</div>
                        </div>
                        <Button variant="secondary" loading={signingOut} onClick={logout} className="py-2">
                            Sign out
                        </Button>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-4 py-6 lg:px-8">
                    <OptionsProvider>
                        <Outlet />
                    </OptionsProvider>
                </main>
            </div>
        </div>
    );
}
