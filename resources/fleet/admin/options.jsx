import { createContext, useContext } from 'react';
import { useApi } from '../shared/hooks';
import { LoadError, Loading } from '../shared/ui';
import { useAuth } from './auth';

const OptionsContext = createContext(null);

/**
 * Loads the option lists (LGAs, vehicle types, checklist items...) once for all admin pages.
 */
export function OptionsProvider({ children }) {
    const { data, error, reload } = useApi('/admin/options');

    if (error) return <div className="p-6"><LoadError error={error} onRetry={reload} /></div>;
    if (!data) return <Loading />;

    return <OptionsContext.Provider value={data}>{children}</OptionsContext.Provider>;
}

export const useOptions = () => useContext(OptionsContext);

/**
 * { key: label } → [[key, label], ...] for SelectField
 */
export const entries = (map) => Object.entries(map);

/**
 * Super admins and fleet officers can change records; reviewers can only look.
 */
export function useCanManage() {
    const { user } = useAuth();
    return user.role === 'super_admin' || user.role === 'fleet_officer';
}
