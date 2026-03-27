import {useGetTranslationsQuery, usePutTranslationsMutation} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {
    TranslationUpdaterContext,
    TranslationUpdaterContextProvider,
} from '@app-dev-panel/panel/Module/Inspector/Context/TranslationUpdaterContext';
import {FilterInput} from '@app-dev-panel/sdk/Component/Form/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {DataTable} from '@app-dev-panel/sdk/Component/Grid';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {regexpQuote} from '@app-dev-panel/sdk/Helper/regexpQuote';
import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {useCallback, useContext, useMemo, useState} from 'react';
import {useSearchParams} from 'react-router-dom';

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
        const patterns = searchVariants(searchString || '').map((v) => new RegExp(regexpQuote(v), 'i'));
        return rows.filter((object) => patterns.some((re) => object[0].match(re)));
    }, [rows, searchString]);

    const onChangeHandler = useCallback(async (value: string) => {
        setSearchParams({filter: value});
    }, []);

    const updateTranslationHandler = useCallback(
        (category: string, locale: string, translation: string, message: string) => {
            const object = {category, locale, translation, message};
            putTranslationsMutation(object);
        },
        [],
    );

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
