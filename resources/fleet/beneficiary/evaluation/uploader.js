import { api } from '../../shared/api';

const RETRY_DELAYS = [1, 2, 4, 8, 15, 30]; // seconds

const wait = (seconds, signal) =>
    new Promise((resolve, reject) => {
        const timer = setTimeout(resolve, seconds * 1000);
        signal?.addEventListener('abort', () => {
            clearTimeout(timer);
            reject(new DOMException('Aborted', 'AbortError'));
        });
    });

/**
 * Retries network drops, server hiccups and rate limits with growing pauses; anything else
 * (e.g. a validation error) fails straight away.
 */
async function withRetry(request, signal) {
    for (let attempt = 0; ; attempt++) {
        try {
            return await request();
        } catch (e) {
            const retryable = e.status === 0 || e.status === 429 || e.status >= 500;
            if (!retryable || attempt >= RETRY_DELAYS.length) throw e;
            await wait(RETRY_DELAYS[attempt], signal);
        }
    }
}

/**
 * Uploads a video in pieces and returns its upload id. Pass the id from an earlier attempt to
 * carry on where it stopped (only the missing pieces are sent).
 *
 * onStarted(uploadId) is called as soon as the server has an id, so it can be saved for resuming.
 * onProgress(fraction) is called after each piece.
 */
export async function uploadVideo(file, { uploadId, onStarted, onProgress, signal } = {}) {
    let state = null;

    if (uploadId) {
        try {
            state = await withRetry(() => api(`/evaluations/uploads/${uploadId}`, { signal }), signal);
        } catch (e) {
            if (e.status !== 404) throw e; // 404: it expired, start a new one
        }
    }

    if (!state) {
        state = await withRetry(
            () => api('/evaluations/uploads', { method: 'POST', body: { filename: file.name, mime: file.type || 'video/mp4', size: file.size }, signal }),
            signal,
        );
    }
    onStarted?.(state.upload_id);

    const received = new Set(state.received);
    onProgress?.(received.size / state.total_chunks);

    for (let index = 0; index < state.total_chunks; index++) {
        if (received.has(index)) continue;
        const piece = file.slice(index * state.chunk_size, Math.min(file.size, (index + 1) * state.chunk_size));
        await withRetry(() => api(`/evaluations/uploads/${state.upload_id}/chunks/${index}`, { method: 'PUT', body: piece, signal }), signal);
        received.add(index);
        onProgress?.(received.size / state.total_chunks);
    }

    return state.upload_id;
}
