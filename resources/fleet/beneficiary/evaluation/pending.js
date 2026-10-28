/**
 * Keeps a recorded video (and how far its upload got) in the browser's IndexedDB until it's
 * submitted, so closing the app or losing signal doesn't lose the recording. Every call fails
 * quietly: if storage isn't available, the app works as before without it.
 */

const DB = 'koh-fleet';
const STORE = 'pending';
const KEY = 'evaluation';

function open() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB, 1);
        request.onupgradeneeded = () => request.result.createObjectStore(STORE);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function run(mode, action) {
    const db = await open();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, mode);
        const request = action(tx.objectStore(STORE));
        tx.oncomplete = () => resolve(request?.result);
        tx.onerror = () => reject(tx.error);
    });
}

/**
 * @returns {Promise<{file: File, source: string, challenge: object|null, periodId: number, uploadId: string|null, duration: number|null}|null>}
 */
export async function loadPending() {
    try {
        return (await run('readonly', (store) => store.get(KEY))) ?? null;
    } catch {
        return null;
    }
}

export async function savePending(value) {
    try {
        await run('readwrite', (store) => store.put(value, KEY));
    } catch {
        // Private browsing or storage full: carry on without it
    }
}

export async function updatePending(changes) {
    const current = await loadPending();
    if (current) await savePending({ ...current, ...changes });
}

export async function clearPending() {
    try {
        await run('readwrite', (store) => store.delete(KEY));
    } catch {
        // ignore
    }
}
