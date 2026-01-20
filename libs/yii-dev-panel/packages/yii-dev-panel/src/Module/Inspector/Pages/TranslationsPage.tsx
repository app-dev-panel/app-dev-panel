import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {FilterInput} from '@yiisoft/yii-dev-panel-sdk/Component/Form/FilterInput';
import {FullScreenCircularProgress} from '@yiisoft/yii-dev-panel-sdk/Component/FullScreenCircularProgress';
import {DataTable} from '@yiisoft/yii-dev-panel-sdk/Component/Grid';
import {JsonRenderer} from '@yiisoft/yii-dev-panel-sdk/Component/JsonRenderer';
import {regexpQuote} from '@yiisoft/yii-dev-panel-sdk/Helper/regexpQuote';
import {
    useGetTranslationsQuery,
    usePutTranslationsMutation,
} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import {
    TranslationUpdaterContext,
    TranslationUpdaterContextProvider,
} from '@yiisoft/yii-dev-panel/Module/Inspector/Context/TranslationUpdaterContext';
import {useCallback, useContext, useMemo, useState} from 'react';
import {useSearchParams} from 'react-router-dom';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

const TempComponent = (params: GridRenderCellParams) => {
    const {updater} = useContext(TranslationUpdaterContext);
    return (
        <JsonRenderer
            editable
            onChange={(path, oldValue, newValue) => {
                updater(params.row[0], String(path[0]), String(path[1]), String(newValue));
            }}
            value={params.value}
        />
    );
};
const columns: GridColDef[] = [
    {
        field: '0',
        headerName: 'Name',
        width: 200,
        renderCell: (params: GridRenderCellParams) => <span style={{wordBreak: 'break-all'}}>{params.value}</span>,
    },
    {
        field: '1',
        headerName: 'Value',
        flex: 1,
        renderCell: (params: GridRenderCellParams) => <TempComponent {...params} />,
    },
];

export const TranslationsPage = () => {
    const {data, isLoading} = useGetTranslationsQuery();
    const [putTranslationsMutation] = usePutTranslationsMutation();
    // const [lazyLoadObject] = useLazyGetObjectQuery();
    const [objects, setObject] = useState<Record<string, any>>({});
    const [searchParams, setSearchParams] = useSearchParams();
    const searchString = searchParams.get('filter') || '';

    const handleLoadObject = async (id: string) => {
        // const result = await lazyLoadObject(id);
        // setObject((prev) => ({...prev, [id]: result.data}));
    };
    const rows = useMemo(() => {
        const isArray = Array.isArray(data);
        const rows = Object.entries(data || ([] as any));
        return rows.map((el) => ({0: el[0], 1: isArray ? Object.assign({}, el[1]) : el[1]}));
    }, [data]);

    const filteredRows = useMemo(() => {
        const regExp = new RegExp(regexpQuote(searchString || ''), 'i');
        return rows.filter((object) => object[0].match(regExp));
    }, [rows, searchString]);

    const onChangeHandler = useCallback(async (value: string) => {
        setSearchParams({filter: value});
    }, []);

    const updateTranslationHandler = useCallback(
        (category: string, locale: string, translation: string, message: string) => {
            const object = {
                category,
                locale,
                translation,
                message,
            };
            console.log(object);
            putTranslationsMutation(object);
        },
        [],
    );

    useBreadcrumbs(() => ['Inspector', 'Translations']);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    return (
        <>
            <FilterInput value={searchString} onChange={onChangeHandler} />
            <TranslationUpdaterContextProvider updater={updateTranslationHandler}>
                <DataTable rows={filteredRows as GridValidRowModel[]} getRowId={(row) => row[0]} columns={columns} />
            </TranslationUpdaterContextProvider>
        </>
    );
};
