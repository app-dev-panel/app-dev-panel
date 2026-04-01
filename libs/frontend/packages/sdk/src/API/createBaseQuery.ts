import {BaseQueryFn, FetchArgs, FetchBaseQueryError, retry} from '@reduxjs/toolkit/query';
import {fetchBaseQuery} from '@reduxjs/toolkit/query/react';

type ApplicationState = {application?: {baseUrl?: string; selectedService?: string}};

const INSPECTOR_PREFIX = '/inspect/api/';

export const createBaseQuery = (
    baseUrlAdditional: string,
): BaseQueryFn<string | FetchArgs, unknown, FetchBaseQueryError> => {
    const baseQueryWithRetry = retry(
        async (args, WebApi, extraOptions) => {
            const state = WebApi.getState() as ApplicationState;
            const baseUrl = state.application?.baseUrl ?? '';
            const selectedService = state.application?.selectedService ?? 'local';

            const rawBaseQuery = fetchBaseQuery({
                baseUrl: baseUrl.replace(/\/$/, '') + baseUrlAdditional,
                referrerPolicy: 'no-referrer',
                headers: {Accept: 'application/json', 'Content-Type': 'application/json'},
            });

            // Inject ?service= param for inspector API calls when a non-local service is selected
            if (
                baseUrlAdditional.startsWith(INSPECTOR_PREFIX) &&
                selectedService !== 'local' &&
                selectedService !== ''
            ) {
                if (typeof args === 'string') {
                    const separator = args.includes('?') ? '&' : '?';
                    args = args + separator + 'service=' + encodeURIComponent(selectedService);
                } else {
                    const url = args.url;
                    const separator = url.includes('?') ? '&' : '?';
                    args = {...args, url: url + separator + 'service=' + encodeURIComponent(selectedService)};
                }
            }

            const result = await rawBaseQuery(args, WebApi, extraOptions);

            // Don't retry network errors (connection refused, timeout, etc.)
            if (result.error && result.error.status === 'FETCH_ERROR') {
                retry.fail(result.error);
            }

            return result;
        },
        {maxRetries: 0},
    );

    return baseQueryWithRetry;
};
