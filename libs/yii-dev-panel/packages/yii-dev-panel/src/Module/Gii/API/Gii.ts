import {createApi} from '@reduxjs/toolkit/query/react';
import {createBaseQuery} from '@yiisoft/yii-dev-panel-sdk/API/createBaseQuery';
import {GiiGeneratorAttribute} from '@yiisoft/yii-dev-panel-sdk/Types/Gii';

export type GiiGenerator = {
    id: string;
    description: string;
    name: string;
    attributes: Record<string, GiiGeneratorAttribute>;
    [name: string]: any;
};
type SummaryResponseType = {
    generators: GiiGenerator[];
};
type PreviewResponseType = {
    files: any[];
    operations: any[];
    errors: {[name: string]: any} | undefined;
};

type GiiPreviewType = {
    generator: string;
    parameters: any;
};
type GiiGenerateType = {
    generator: string;
    parameters: any;
    answers: any;
};
type GiiDiffType = {
    generator: string;
    parameters: any;
    fileId: string;
};
export const giiApi = createApi({
    reducerPath: 'api.gii',
    baseQuery: createBaseQuery('/gii/api'),
    endpoints: (builder) => ({
        getGenerators: builder.query<GiiGenerator[], void>({
            query: () => `/generator`,
            transformResponse: (result: SummaryResponseType) => (result.generators as GiiGenerator[]) || [],
        }),
        postPreview: builder.mutation<PreviewResponseType, GiiPreviewType>({
            query: ({generator, parameters}) => ({
                url: `/generator/${generator}/preview`,
                method: 'POST',
                body: {parameters},
            }),
        }),
        postGenerate: builder.mutation<PreviewResponseType, GiiGenerateType>({
            query: ({generator, parameters, answers}) => ({
                url: `/generator/${generator}/generate`,
                method: 'POST',
                body: {parameters, answers},
            }),
        }),
        postDiff: builder.mutation<PreviewResponseType, GiiDiffType>({
            query: ({generator, parameters, fileId}) => ({
                url: `/generator/${generator}/diff?file=${fileId}`,
                method: 'POST',
                body: {parameters},
            }),
        }),
    }),
});

export const {
    useGetGeneratorsQuery,
    useLazyGetGeneratorsQuery,
    usePostPreviewMutation,
    usePostGenerateMutation,
    usePostDiffMutation,
} = giiApi;
