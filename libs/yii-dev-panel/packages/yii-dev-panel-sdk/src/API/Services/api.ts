import {createApi} from '@reduxjs/toolkit/query/react';
import {createBaseQuery} from '@yiisoft/yii-dev-panel-sdk/API/createBaseQuery';

export type ServiceDescriptor = {
    service: string;
    language: string;
    inspectorUrl: string | null;
    capabilities: string[];
    registeredAt: number;
    lastSeenAt: number;
    online: boolean;
};

type Response<T = unknown> = {data: T};

export const servicesApi = createApi({
    reducerPath: 'api.services',
    tagTypes: ['services/list'],
    baseQuery: createBaseQuery('/debug/api/services/'),
    endpoints: (builder) => ({
        getServices: builder.query<ServiceDescriptor[], void>({
            query: () => '',
            transformResponse: (result: Response<ServiceDescriptor[]>) => result.data ?? [],
            providesTags: ['services/list'],
        }),
    }),
});

export const {useGetServicesQuery} = servicesApi;
