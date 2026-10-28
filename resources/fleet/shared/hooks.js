import { useCallback, useEffect, useState } from 'react';
import { api } from './api';

/**
 * Loads a GET endpoint and reloads when `path` changes. Pass null to skip loading.
 */
export function useApi(path) {
    const [state, setState] = useState({ data: null, error: null, loading: path !== null });
    const [version, setVersion] = useState(0);

    useEffect(() => {
        if (path === null) return;
        let cancelled = false;
        setState((s) => ({ ...s, loading: true, error: null }));
        api(path)
            .then((data) => !cancelled && setState({ data, error: null, loading: false }))
            .catch((error) => !cancelled && setState({ data: null, error, loading: false }));
        return () => {
            cancelled = true;
        };
    }, [path, version]);

    const reload = useCallback(() => setVersion((v) => v + 1), []);

    return { ...state, reload };
}

export function useDebounced(value, delay = 300) {
    const [debounced, setDebounced] = useState(value);

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(value), delay);
        return () => clearTimeout(timer);
    }, [value, delay]);

    return debounced;
}

/**
 * The device's position, asked for once on mount. status: locating | ok | denied | unavailable
 */
export function useGeolocation() {
    const [state, setState] = useState({ status: 'locating', coords: null });

    const locate = useCallback(() => {
        if (!navigator.geolocation) {
            setState({ status: 'unavailable', coords: null });
            return;
        }
        setState((s) => ({ ...s, status: 'locating' }));
        navigator.geolocation.getCurrentPosition(
            (position) => setState({ status: 'ok', coords: position.coords }),
            (error) => setState({ status: error.code === error.PERMISSION_DENIED ? 'denied' : 'unavailable', coords: null }),
            { enableHighAccuracy: true, timeout: 20000, maximumAge: 60000 },
        );
    }, []);

    useEffect(locate, [locate]);

    return { ...state, locate };
}
