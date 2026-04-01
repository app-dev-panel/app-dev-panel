import {DebugEntryList} from '@app-dev-panel/panel/Module/Debug/Component/DebugEntryList';
import {useLazyGetParametersQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {useSelector} from '@app-dev-panel/panel/store';
import {addFavoriteUrl, changeBaseUrl, removeFavoriteUrl} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {StatusCard} from '@app-dev-panel/sdk/Component/StatusCard';
import {Chip, Icon, IconButton, InputBase, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useEffect, useState} from 'react';
import {useDispatch} from 'react-redux';

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const StatusGrid = styled('div')(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(2, 1fr)',
    gap: theme.spacing(2),
    marginBottom: theme.spacing(3),
}));

const UrlForm = styled('form')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(1, 2),
    borderRadius: Number(theme.shape.borderRadius) * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    marginBottom: theme.spacing(3),
    transition: 'border-color 0.2s',
    '&:focus-within': {borderColor: theme.palette.primary.main},
}));

const FavoritesRow = styled('div')(({theme}) => ({
    display: 'flex',
    flexWrap: 'wrap',
    gap: theme.spacing(0.75),
    marginBottom: theme.spacing(3),
}));

const CurrentUrl = styled('div')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1.5, 2),
    borderRadius: Number(theme.shape.borderRadius) * 1.5,
    backgroundColor: theme.palette.action.hover,
    marginBottom: theme.spacing(3),
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '13px',
}));

const SectionLabel = styled(Typography)(({theme}) => ({
    fontSize: '12px',
    fontWeight: 600,
    letterSpacing: '0.6px',
    textTransform: 'uppercase',
    color: theme.palette.text.disabled,
    marginBottom: theme.spacing(1.5),
    paddingBottom: theme.spacing(0.75),
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export function IndexPage() {
    const dispatch = useDispatch();
    const [inspectorQuery] = useLazyGetParametersQuery();
    const baseUrl = useSelector((state) => state.application.baseUrl);
    const [url, setUrl] = useState<string>(baseUrl ?? '');
    const [status, setStatus] = useState<Record<string, 'connected' | 'disconnected' | 'loading'>>({
        debug: 'loading',
        inspector: 'loading',
    });
    const favoriteUrls = useSelector((state) => state.application.favoriteUrls) ?? [];

    // Debug query — used for status card
    const {
        isFetching: debugFetching,
        isSuccess: debugSuccess,
        isError: debugError,
        refetch: debugRefetch,
    } = useGetDebugQuery();

    // Derive debug status from the shared useGetDebugQuery hook (managed by Layout)
    const debugStatus: 'connected' | 'disconnected' | 'loading' = debugFetching
        ? 'loading'
        : debugSuccess
          ? 'connected'
          : debugError
            ? 'disconnected'
            : 'loading';

    async function checkStatus() {
        setStatus((s) => ({...s, inspector: 'loading'}));
        inspectorQuery()
            .then((response) =>
                setStatus((s) => ({...s, inspector: response.isSuccess ? 'connected' : 'disconnected'})),
            )
            .catch(() => setStatus((s) => ({...s, inspector: 'disconnected'})));
    }

    const handleChangeUrl = async (newUrl: string) => {
        setUrl(newUrl);
        dispatch(changeBaseUrl(newUrl));
    };

    const onSubmitHandler = (event: React.FormEvent) => {
        event.preventDefault();
        handleChangeUrl(url);
    };

    useEffect(() => {
        checkStatus();
    }, [baseUrl]);

    return (
        <>
            <PageHeader
                title="Application Development Panel"
                icon="dashboard"
                description="Monitor and manage your application backend services"
            />

            <CurrentUrl>
                <Icon sx={{fontSize: 18, color: 'text.disabled'}}>link</Icon>
                <span style={{flex: 1}}>{baseUrl}</span>
                <IconButton
                    size="small"
                    onClick={() => {
                        checkStatus();
                        debugRefetch();
                    }}
                >
                    <Icon sx={{fontSize: 16}}>refresh</Icon>
                </IconButton>
            </CurrentUrl>

            <SectionLabel>API Status</SectionLabel>
            <StatusGrid>
                <StatusCard title="Debug" icon="bug_report" status={debugStatus} onClick={() => handleChangeUrl(url)} />
                <StatusCard
                    title="Inspector"
                    icon="search"
                    status={status.inspector}
                    onClick={() => handleChangeUrl(url)}
                />
            </StatusGrid>

            <SectionLabel>Backend URL</SectionLabel>
            <UrlForm onSubmit={onSubmitHandler}>
                <Icon sx={{fontSize: 18, color: 'text.disabled'}}>language</Icon>
                <InputBase
                    sx={(theme) => ({flex: 1, fontSize: '13px', fontFamily: theme.adp.fontFamilyMono})}
                    placeholder="http://localhost:8080"
                    value={url}
                    onChange={(event) => setUrl(event.target.value)}
                />
                <IconButton size="small" onClick={() => dispatch(addFavoriteUrl(url))}>
                    <Icon sx={{fontSize: 18}}>star_outline</Icon>
                </IconButton>
                <IconButton size="small" type="submit">
                    <Icon sx={{fontSize: 18, color: 'primary.main'}}>check</Icon>
                </IconButton>
            </UrlForm>

            {favoriteUrls.length > 0 && (
                <>
                    <SectionLabel>Favorites</SectionLabel>
                    <FavoritesRow>
                        {favoriteUrls.map((favUrl) => (
                            <Chip
                                key={favUrl}
                                icon={<Icon sx={{fontSize: '14px !important', color: 'warning.main'}}>star</Icon>}
                                label={favUrl}
                                size="small"
                                variant={favUrl === baseUrl ? 'filled' : 'outlined'}
                                onClick={() => handleChangeUrl(favUrl)}
                                onDelete={() => dispatch(removeFavoriteUrl(favUrl))}
                                deleteIcon={<Icon sx={{fontSize: '14px !important'}}>close</Icon>}
                                sx={(theme) => ({
                                    fontFamily: theme.adp.fontFamilyMono,
                                    fontSize: '12px',
                                    height: 28,
                                    borderRadius: 1,
                                    ...(favUrl === baseUrl && {
                                        backgroundColor: 'primary.light',
                                        borderColor: 'primary.main',
                                        color: 'primary.main',
                                        fontWeight: 600,
                                    }),
                                })}
                            />
                        ))}
                    </FavoritesRow>
                </>
            )}

            <SectionLabel>Debug Entries</SectionLabel>
            <DebugEntryList />
        </>
    );
}
