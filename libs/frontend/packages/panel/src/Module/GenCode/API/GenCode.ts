import {createBaseQuery} from '@app-dev-panel/sdk/API/createBaseQuery';
import {GenCodeGeneratorAttribute} from '@app-dev-panel/sdk/Types/GenCode';
import {createApi} from '@reduxjs/toolkit/query/react';

export type GenCodeGenerator = {
    id: string;
    description: string;
    name: string;
    attributes: Record<string, GenCodeGeneratorAttribute>;
    [name: string]: any;
};
type SummaryResponseType = {generators: GenCodeGenerator[]};
type PreviewResponseType = {files: any[]; operations: any[]; errors: {[name: string]: any} | undefined};

type GenCodePreviewType = {generator: string; parameters: any};
type GenCodeGenerateType = {generator: string; parameters: any; answers: any};
type GenCodeDiffType = {generator: string; parameters: any; fileId: string};
export const genCodeApi = createApi({
    reducerPath: 'api.genCode',
    baseQuery: createBaseQuery('/gen-code/api'),
    endpoints: (builder) => ({
        getGenerators: builder.query<GenCodeGenerator[], void>({
            query: () => `/generator`,
            transformResponse: (result: SummaryResponseType) => (result.generators as GenCodeGenerator[]) || [],
        }),
        postPreview: builder.mutation<PreviewResponseType, GenCodePreviewType>({
            query: ({generator, parameters}) => ({
                url: `/generator/${generator}/preview`,
                method: 'POST',
                body: {parameters},
            }),
        }),
        postGenerate: builder.mutation<PreviewResponseType, GenCodeGenerateType>({
            query: ({generator, parameters, answers}) => ({
                url: `/generator/${generator}/generate`,
                method: 'POST',
                body: {parameters, answers},
            }),
        }),
        postDiff: builder.mutation<PreviewResponseType, GenCodeDiffType>({
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
} = genCodeApi;
