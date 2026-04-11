export const extractErrorMessage = (err: unknown): string | null => {
    if (typeof err === 'object' && err !== null && 'data' in err) {
        const data = (err as {data: unknown}).data;
        if (typeof data === 'object' && data !== null) {
            const obj = data as Record<string, unknown>;
            if (typeof obj.error === 'string') return obj.error;
            if (typeof obj.data === 'object' && obj.data !== null) {
                const inner = obj.data as Record<string, unknown>;
                if (typeof inner.error === 'string') return inner.error;
            }
        }
    }
    return null;
};

/**
 * Translates an RTK Query error into a user-facing message.
 *
 * Handles both `FetchBaseQueryError` shapes ({data, status}) and the
 * `SerializedError` shape ({message, name, code}) that RTK Query produces
 * when a thrown error escapes from baseQuery / transformResponse.
 *
 * Falls back to a generic message tailored for connection errors when the
 * underlying transport failed (FETCH_ERROR, TIMEOUT_ERROR, etc.).
 */
export const formatQueryError = (err: unknown, fallback = 'Failed to load data.'): string => {
    const extracted = extractErrorMessage(err);
    if (extracted) return extracted;
    if (typeof err === 'object' && err !== null) {
        if ('status' in err) {
            const status = (err as {status?: unknown}).status;
            if (status === 'FETCH_ERROR' || status === 'TIMEOUT_ERROR') {
                return 'Unable to connect to the server. Make sure the application is running.';
            }
            if (typeof status === 'number') {
                return `${fallback} (HTTP ${status})`;
            }
        }
        // SerializedError shape: prefer .message, fall back to .name
        if ('message' in err && typeof (err as {message?: unknown}).message === 'string') {
            const message = (err as {message: string}).message;
            if (message.length > 0) return message;
        }
        if ('name' in err && typeof (err as {name?: unknown}).name === 'string') {
            const name = (err as {name: string}).name;
            if (name.length > 0) return name;
        }
    }
    return fallback;
};
