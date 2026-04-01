import {CommandResponseType} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';

type MutationResult = {data: CommandResponseType} | {error: unknown};

type CommandError = {errors: string[]};

export function extractCommandError(result: MutationResult): CommandError | null {
    if ('error' in result) {
        const error = result.error;
        if (typeof error === 'object' && error !== null && 'status' in error) {
            const fetchError = error as {status: string; error?: string; data?: unknown};
            if (fetchError.status === 'FETCH_ERROR') {
                return {errors: [fetchError.error ?? 'Unable to reach the server. Check that the backend is running.']};
            }
            if (typeof fetchError.data === 'object' && fetchError.data !== null) {
                const data = fetchError.data as Record<string, unknown>;
                if (typeof data.message === 'string') {
                    return {errors: [data.message]};
                }
                if (typeof data.error === 'string') {
                    return {errors: [data.error]};
                }
            }
            return {errors: [`Request failed with status ${fetchError.status}`]};
        }
        return {errors: ['An unexpected error occurred']};
    }

    if ('data' in result && result.data) {
        const {status, errors} = result.data;
        if (status === 'error' || status === 'fail') {
            return {errors: errors?.length ? errors : [`Command finished with status "${status}"`]};
        }
    }

    return null;
}
