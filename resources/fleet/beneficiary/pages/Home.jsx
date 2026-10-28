import { useState } from 'react';
import { Link } from 'react-router';
import { api } from '../../shared/api';
import { useApi } from '../../shared/hooks';
import { Badge, Button, LoadError, Logo, Spinner, formatDate } from '../../shared/ui';
import { useAuth } from '../auth';

export default function Home() {
    const { user, setUser } = useAuth();
    const { data, error, reload } = useApi('/vehicle');
    const evaluations = useApi('/evaluations');
    const [signingOut, setSigningOut] = useState(false);

    async function logout() {
        setSigningOut(true);
        await api('/auth/logout', { method: 'POST' }).catch(() => {});
        setUser(null);
    }

    const assignment = data?.assignment;

    return (
        <main className="mx-auto min-h-dvh max-w-md px-4 py-6">
            <header className="flex items-center justify-between">
                <Logo />
                <Button variant="secondary" loading={signingOut} onClick={logout} className="py-2">
                    Sign out
                </Button>
            </header>

            <h1 className="mt-8 text-2xl font-semibold">Hello, {user.full_name.split(' ')[0]}</h1>

            {error ? (
                <div className="mt-6"><LoadError error={error} onRetry={reload} /></div>
            ) : !data ? (
                <div className="flex justify-center py-12"><Spinner className="size-7 text-primary" /></div>
            ) : assignment ? (
                <>
                    {assignment.recall && (
                        <section className="mt-6 rounded-xl bg-amber-50 p-5 ring-1 ring-amber-200" role="alert">
                            <h2 className="font-semibold text-amber-900">Please return your vehicle by {formatDate(assignment.recall.return_by)}</h2>
                            <p className="mt-1 text-sm text-amber-900">{assignment.recall.reason || assignment.recall.title}</p>
                            <p className="mt-2 text-sm text-amber-800">Contact your fleet coordinator to arrange the return.</p>
                        </section>
                    )}

                    <section className="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <h2 className="text-sm font-medium text-slate-500">Your vehicle</h2>
                        <p className="mt-1 text-2xl font-semibold tracking-wide">{assignment.vehicle.plate_number}</p>
                        <p className="text-slate-700">
                            {[assignment.vehicle.colour, assignment.vehicle.make, assignment.vehicle.model].filter(Boolean).join(' ')}
                        </p>
                        <p className="mt-3 text-sm text-slate-500">Received {formatDate(assignment.handed_over_at)}</p>
                    </section>

                    <EvaluationCard evaluations={evaluations} />
                </>
            ) : (
                <section className="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 className="font-semibold">No vehicle yet</h2>
                    <p className="mt-1 text-sm text-slate-600">
                        Your vehicle details will appear here once a vehicle has been handed over to you.
                    </p>
                </section>
            )}
        </main>
    );
}

const PERIOD_STATUS = {
    open: ['blue', 'Due'],
    submitted: ['green', 'Received'],
    approved: ['green', 'Approved'],
    rejected: ['red', 'Not accepted'],
    missed: ['red', 'Missed'],
    closed: ['slate', 'Closed'],
};

function EvaluationCard({ evaluations }) {
    const { data, error, reload } = evaluations;

    if (error) return <div className="mt-4"><LoadError error={error} onRetry={reload} /></div>;
    if (!data) return null;

    const current = data.current;
    const daysLeft = current ? Math.max(0, Math.ceil((new Date(current.ends_at) - Date.now()) / 86400000)) : null;
    const past = data.periods.filter((p) => p.id !== current?.id);

    return (
        <>
            <section className="mt-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 className="font-semibold">Two-weekly video check</h2>
                {current?.accepts_submissions ? (
                    <>
                        <p className="mt-1 text-sm text-slate-600">
                            Due by <span className="font-medium text-slate-900">{formatDate(current.ends_at)}</span>
                            {' '}({daysLeft === 0 ? 'today' : `${daysLeft} day${daysLeft === 1 ? '' : 's'} left`}).
                        </p>
                        {current.evaluation?.review_status === 'rejected' && (
                            <p className="mt-2 rounded-lg bg-red-50 p-3 text-sm text-red-800">
                                Your last video wasn't accepted{current.evaluation.review_notes ? `: ${current.evaluation.review_notes}` : '.'} Please record a new one.
                            </p>
                        )}
                        <Link to="/evaluate" className="mt-4 block"><Button className="w-full">Record evaluation</Button></Link>
                    </>
                ) : current ? (
                    <p className="mt-1 text-sm text-slate-600">
                        Thank you — this period's video is in. The next one opens on <span className="font-medium text-slate-900">{formatDate(current.ends_at)}</span>.
                    </p>
                ) : (
                    <p className="mt-1 text-sm text-slate-600">Every two weeks you'll record a short video of the vehicle here.</p>
                )}
            </section>

            {past.length > 0 && (
                <section className="mt-4 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    <h2 className="px-5 pt-4 font-semibold">Past evaluations</h2>
                    <ul className="mt-2 divide-y divide-slate-100 text-sm">
                        {past.map((period) => {
                            const [tone, text] = PERIOD_STATUS[period.status] ?? ['slate', period.status];
                            return (
                                <li key={period.id} className="flex items-center justify-between px-5 py-3">
                                    <span>{formatDate(period.starts_at)} – {formatDate(period.ends_at)}</span>
                                    <Badge tone={tone}>{text}</Badge>
                                </li>
                            );
                        })}
                    </ul>
                </section>
            )}
        </>
    );
}
