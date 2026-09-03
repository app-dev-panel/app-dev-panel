import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {addLiveDump, addLiveLog} from '@app-dev-panel/sdk/API/Debug/LiveContext';
import {EventTypesEnum} from '@app-dev-panel/sdk/Component/useServerSentEvents';
import type {UnknownAction} from '@reduxjs/toolkit';

export type SseUpdatesDeps = {
    /** Refetch the entry list; resolves with RTK Query's `{data}` / `{error}` shape. */
    getDebugQuery: () => Promise<{data?: DebugEntry[]; error?: unknown}>;
    /** Select an entry in the store. */
    changeEntry: (entry: DebugEntry) => void;
    dispatch: (action: UnknownAction) => unknown;
    /**
     * Read the *current* auto-latest toggle. A getter (not a captured
     * boolean) so the subscription created once by `useServerSentEvents`
     * keeps honouring later toggles without resubscribing.
     */
    isAutoLatest: () => boolean;
};

const parsePayload = (raw: unknown): Record<string, unknown> | null => {
    try {
        const payload = typeof raw === 'string' ? JSON.parse(raw) : raw;
        return payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
    } catch {
        return null;
    }
};

/**
 * Handle one server-sent event from `/debug/api/event-stream`.
 *
 * - `debug-updated` / `entry-created`: always refresh the entry list so
 *   badges and the selector stay current, but only *jump* to the newest entry
 *   when the "auto latest" toggle is on — otherwise every incoming request
 *   would yank the user away from the entry they are inspecting.
 * - `live-log` / `live-dump`: feed the Live Feed regardless of the toggle.
 */
export const createSseUpdatesHandler =
    ({getDebugQuery, changeEntry, dispatch, isAutoLatest}: SseUpdatesDeps) =>
    async (event: MessageEvent): Promise<void> => {
        let data: {type?: string; payload?: unknown};
        try {
            data = JSON.parse(event.data);
        } catch {
            return;
        }
        if (!data || !data.type) return;

        if (data.type === EventTypesEnum.DebugUpdated || data.type === EventTypesEnum.EntryCreated) {
            const result = await getDebugQuery();
            if (!isAutoLatest()) return;
            if (result.data && result.data.length > 0) {
                changeEntry(result.data[0]);
            }
            return;
        }

        if (data.type === EventTypesEnum.LiveLog) {
            const payload = parsePayload(data.payload);
            if (payload) {
                dispatch(
                    addLiveLog({
                        level: String(payload.level ?? 'debug'),
                        message: String(payload.message ?? ''),
                        context: payload.context as Record<string, unknown> | undefined,
                    }),
                );
            }
            return;
        }

        if (data.type === EventTypesEnum.LiveDump) {
            const payload = parsePayload(data.payload);
            if (payload) {
                const line = payload.$__line__$;
                dispatch(addLiveDump({variable: payload, line: line == null ? undefined : String(line)}));
            }
        }
    };
