import {useLazyGetGeneratorsQuery} from '@app-dev-panel/panel/Module/Gii/API/Gii';
import {useLazyGetParametersQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {useSelector} from '@app-dev-panel/panel/store';
import {addFavoriteUrl, changeBaseUrl, removeFavoriteUrl} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {useLazyGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {StatusCard} from '@app-dev-panel/sdk/Component/StatusCard';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Icon, IconButton, InputBase, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useEffect, useState} from 'react';
import {useDispatch} from 'react-redux';

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const StatusGrid = styled('div')(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(3, 1fr)',
    gap: theme.spacing(2),
    marginBottom: theme.spacing(3),
}));

const UrlForm = styled('form')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(1, 2),
    borderRadius: theme.shape.borderRadius * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    marginBottom: theme.spacing(3),
    transition: 'border-color 0.2s',
    '&:focus-within': {borderColor: theme.palette.primary.main},
}));

const FavoritesList = styled('div')(({theme}) => ({display: 'flex', flexDirection: 'column', gap: theme.spacing(1)}));

const FavoriteItem = styled('div')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 2),
    borderRadius: theme.shape.borderRadius,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    cursor: 'pointer',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const CurrentUrl = styled('div')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1.5, 2),
    borderRadius: theme.shape.borderRadius * 1.5,
    backgroundColor: theme.palette.action.hover,
    marginBottom: theme.spacing(3),
    fontFamily: primitives.fontFamilyMono,
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
    const defaultBackendUrl = useSelector((state) => state.application.baseUrl) as string;
    const dispatch = useDispatch();
    const [debugQuery] = useLazyGetDebugQuery();
    const [inspectorQuery] = useLazyGetParametersQuery();
    const [giiQuery] = useLazyGetGeneratorsQuery();
    const baseUrl = useSelector((state) => state.application.baseUrl);
    const [url, setUrl] = useState<string>(String(baseUrl));
    const initialStatus = {debug: 'loading' as const, inspector: 'loading' as const, gii: 'loading' as const};
    const [status, setStatus] = useState<Record<string, 'connected' | 'disconnected' | 'loading'>>(initialStatus);
    const favoriteUrls = useSelector((state) => state.application.favoriteUrls) as string[];

    async function checkStatus() {
        setStatus({debug: 'loading', inspector: 'loading', gii: 'loading'});
        debugQuery()
            .then((response) => setStatus((s) => ({...s, debug: response.isSuccess ? 'connected' : 'disconnected'})))
            .catch(() => setStatus((s) => ({...s, debug: 'disconnected'})));
        inspectorQuery()
            .then((response) =>
                setStatus((s) => ({...s, inspector: response.isSuccess ? 'connected' : 'disconnected'})),
            )
            .catch(() => setStatus((s) => ({...s, inspector: 'disconnected'})));
        giiQuery()
            .then((response) => setStatus((s) => ({...s, gii: response.isSuccess ? 'connected' : 'disconnected'})))
            .catch(() => setStatus((s) => ({...s, gii: 'disconnected'})));
    }

    const handleChangeUrl = async (newUrl: string) => {
        setUrl(newUrl);
        dispatch(changeBaseUrl(newUrl));
        await checkStatus();
    };

    const onSubmitHandler = async (event: {preventDefault: () => void}) => {
        event.preventDefault();
        await handleChangeUrl(url);
    };

    useEffect(() => {
        checkStatus();
    }, []);

    return (
        <>
            <PageHeader
                title="Application Development Panel"
                icon="dashboard"
                description="Monitor and manage your application backend services"
            />

            <CurrentUrl>
                <Icon sx={{fontSize: 18, color: 'text.disabled'}}>link</Icon>
                <span style={{flex: 1}}>{String(defaultBackendUrl)}</span>
                <IconButton size="small" onClick={() => checkStatus()}>
                    <Icon sx={{fontSize: 16}}>refresh</Icon>
                </IconButton>
            </CurrentUrl>

            <SectionLabel>API Status</SectionLabel>
            <StatusGrid>
                <StatusCard
                    title="Debug"
                    icon="bug_report"
                    status={status.debug}
                    onClick={() => handleChangeUrl(url)}
                />
                <StatusCard
                    title="Inspector"
                    icon="search"
                    status={status.inspector}
                    onClick={() => handleChangeUrl(url)}
                />
                <StatusCard title="Gii" icon="build_circle" status={status.gii} onClick={() => handleChangeUrl(url)} />
            </StatusGrid>

            <SectionLabel>Backend URL</SectionLabel>
            <UrlForm onSubmit={onSubmitHandler}>
                <Icon sx={{fontSize: 18, color: 'text.disabled'}}>language</Icon>
                <InputBase
                    sx={{flex: 1, fontSize: '13px', fontFamily: primitives.fontFamilyMono}}
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
                    <FavoritesList>
                        {favoriteUrls.map((favUrl, index) => (
                            <FavoriteItem key={index} onClick={() => handleChangeUrl(favUrl)}>
                                <Icon sx={{fontSize: 16, color: 'warning.main'}}>star</Icon>
                                <Typography sx={{flex: 1, fontFamily: primitives.fontFamilyMono, fontSize: '13px'}}>
                                    {favUrl}
                                </Typography>
                                <IconButton size="small" href={favUrl} onClick={(e) => e.stopPropagation()}>
                                    <Icon sx={{fontSize: 16}}>open_in_new</Icon>
                                </IconButton>
                                <IconButton
                                    size="small"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        dispatch(removeFavoriteUrl(favUrl));
                                    }}
                                >
                                    <Icon sx={{fontSize: 16}}>delete_outline</Icon>
                                </IconButton>
                            </FavoriteItem>
                        ))}
                    </FavoritesList>
                </>
            )}
        </>
    );
}
