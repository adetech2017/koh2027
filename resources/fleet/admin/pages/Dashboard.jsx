import { useAuth } from '../auth';

export default function Dashboard() {
    const { user } = useAuth();

    return (
        <div>
            <h1 className="text-2xl font-semibold">Welcome, {user.name.split(' ')[0]}</h1>
            <p className="mt-1 text-slate-600">Vehicle, handover and evaluation tools will appear here as they're added.</p>
        </div>
    );
}
