import { createContext, useContext, useEffect, useState } from 'react';
import { Navigate, useLocation } from 'react-router';
import { api } from './api';
import { Spinner } from './ui';

/**
 * Who is signed in, loaded once from the API. `user` is undefined while loading and null for a guest.
 * Each app makes its own: createAuth('/auth/me', 'beneficiary') or createAuth('/admin/auth/me', 'admin').
 */
export function createAuth(meEndpoint, key) {
    const AuthContext = createContext(null);

    function AuthProvider({ children }) {
        const [user, setUser] = useState(undefined);

        useEffect(() => {
            api(meEndpoint)
                .then((data) => setUser(data[key]))
                .catch(() => setUser(null));
        }, []);

        return <AuthContext.Provider value={{ user, setUser }}>{children}</AuthContext.Provider>;
    }

    const useAuth = () => useContext(AuthContext);

    function RequireAuth({ children }) {
        const { user } = useAuth();
        const location = useLocation();

        if (user === undefined) return <FullPageSpinner />;
        if (user === null) return <Navigate to="/login" replace state={{ from: location.pathname }} />;
        return children;
    }

    function GuestOnly({ children }) {
        const { user } = useAuth();

        if (user === undefined) return <FullPageSpinner />;
        if (user) return <Navigate to="/" replace />;
        return children;
    }

    return { AuthProvider, useAuth, RequireAuth, GuestOnly };
}

function FullPageSpinner() {
    return (
        <div className="flex min-h-dvh items-center justify-center">
            <Spinner className="size-8 text-primary" />
        </div>
    );
}
