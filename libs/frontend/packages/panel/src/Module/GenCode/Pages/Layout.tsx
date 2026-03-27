import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {GenCodeGenerator, useGetGeneratorsQuery} from '@app-dev-panel/panel/Module/GenCode/API/GenCode';
import {GeneratorStepper} from '@app-dev-panel/panel/Module/GenCode/Component/GeneratorSteps/GeneratorStepper';
import {ContextProvider} from '@app-dev-panel/panel/Module/GenCode/Context/Context';
import {DuckIcon} from '@app-dev-panel/sdk/Component/DuckIcon';
import {ErrorFallback} from '@app-dev-panel/sdk/Component/ErrorFallback';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {InfoBox} from '@app-dev-panel/sdk/Component/InfoBox';
import {LinkProps, MenuPanel} from '@app-dev-panel/sdk/Component/MenuPanel';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import InboxIcon from '@mui/icons-material/Inbox';
import MailIcon from '@mui/icons-material/Mail';
import {Link, Typography} from '@mui/material';
import {useEffect, useMemo, useState} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {useSearchParams} from 'react-router-dom';

const Layout = () => {
    const [selectedGenerator, setSelectedGenerator] = useState<GenCodeGenerator | null>(null);
    const [searchParams] = useSearchParams();

    const {data, isLoading} = useGetGeneratorsQuery();

    useEffect(() => {
        const selectedGeneratorId = searchParams.get('generator') || '';
        const found = (data || []).find((v) => v.id === selectedGeneratorId) || null;
        setSelectedGenerator(found);
    }, [searchParams, data]);

    const links: LinkProps[] = useMemo(
        () =>
            (data || []).map((generator, index) => ({
                name: generator.id,
                text: generator.name,
                href: '/gen-code?generator=' + generator.id,
                icon: index % 2 === 0 ? <InboxIcon /> : <MailIcon />,
            })),
        [data],
    );

    useBreadcrumbs(() => ['GenCode', !!selectedGenerator ? selectedGenerator.name : null]);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    return (
        <>
            <PageHeader title="Code Generator" icon="build_circle" description="Generate code from templates" />
            {links.length === 0 ? (
                <InfoBox
                    title="Code generators are empty"
                    text={
                        <>
                            <Typography>
                                Code generator is not configured or it does not have any generators.
                            </Typography>
                            <Typography>
                                Make sure the code generator is active and its configuration has at least one active
                                generator.&nbsp;
                                <Link href="/inspector/config/parameters?filter=app-dev-panel/gen-code">
                                    Open parameters.
                                </Link>
                            </Typography>
                        </>
                    }
                    severity="info"
                    icon={<DuckIcon />}
                />
            ) : (
                <MenuPanel links={links} open={!selectedGenerator} activeLink={selectedGenerator?.id}>
                    {selectedGenerator ? (
                        <ErrorBoundary FallbackComponent={ErrorFallback} resetKeys={[window.location.pathname]}>
                            <ContextProvider>
                                <GeneratorStepper generator={selectedGenerator} />
                            </ContextProvider>
                        </ErrorBoundary>
                    ) : (
                        <InfoBox
                            title="No one generator is chosen"
                            text="Select a generator from the left side panel to see more options"
                            severity="info"
                            icon={<DuckIcon />}
                        />
                    )}
                </MenuPanel>
            )}
        </>
    );
};

export {Layout};
