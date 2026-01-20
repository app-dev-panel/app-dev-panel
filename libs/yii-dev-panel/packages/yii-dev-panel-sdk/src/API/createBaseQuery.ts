import {BaseQueryFn, FetchArgs, FetchBaseQueryError} from '@reduxjs/toolkit/query';
import {fetchBaseQuery} from '@reduxjs/toolkit/query/react';

export const createBaseQuery = (
    baseUrlAdditional: string,
): BaseQueryFn<string | FetchArgs, unknown, FetchBaseQueryError> => {
    return async (args, WebApi, extraOptions) => {
        const baseUrl = (WebApi.getState() as any).application?.baseUrl || '';

        const rawBaseQuery = fetchBaseQuery({
            baseUrl: baseUrl.replace(/\/$/, '') + baseUrlAdditional,
            referrerPolicy: 'no-referrer',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
        });
        return rawBaseQuery(args, WebApi, extraOptions);
    };
};
