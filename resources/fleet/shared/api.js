/**
 * JSON client for the fleet API. The API is on the same origin as the page, so the session
 * cookie goes along automatically; Laravel's XSRF-TOKEN cookie is echoed back as a header.
 */

export class ApiError extends Error {
    constructor(status, data) {
        super(data?.message || 'Something went wrong. Please try again.');
        this.status = status;
        // Laravel validation errors: { field: ['message', ...] }
        this.errors = data?.errors || {};
        // CSV import problems: [{ line, errors: [...] }]
        this.rowErrors = data?.row_errors;
    }

    field(name) {
        return this.errors[name]?.[0];
    }
}

function xsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : null;
}

/**
 * Builds a query string, leaving out empty values: qs({ search: 'kja', page: 2, lga: '' }) → '?search=kja&page=2'
 */
export function qs(params) {
    const query = new URLSearchParams(Object.entries(params).filter(([, value]) => value !== '' && value != null)).toString();
    return query ? `?${query}` : '';
}

/**
 * Pass a plain object as `body` for JSON, a FormData for form uploads, or a Blob to send raw bytes.
 */
export async function api(path, { method = 'GET', body, signal } = {}) {
    const token = xsrfToken();
    const isForm = body instanceof FormData;
    const isBlob = body instanceof Blob;
    let response;

    try {
        response = await fetch(`/api/v1${path}`, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                ...(body !== undefined && !isForm && { 'Content-Type': isBlob ? 'application/octet-stream' : 'application/json' }),
                ...(token && { 'X-XSRF-TOKEN': token }),
            },
            body: body === undefined || isForm || isBlob ? body : JSON.stringify(body),
            signal,
        });
    } catch (e) {
        if (e.name === 'AbortError') throw e;
        throw new ApiError(0, { message: 'No connection. Check your internet and try again.' });
    }

    const data = await response.json().catch(() => null);

    if (response.status === 413) {
        throw new ApiError(413, { message: 'The upload is too large. Try fewer or smaller photos.' });
    }
    if (response.status === 419) {
        throw new ApiError(419, { message: 'Your session expired. Refresh the page and try again.' });
    }
    if (!response.ok) {
        throw new ApiError(response.status, data);
    }

    return data;
}
