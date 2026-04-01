import {useGetComposerQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {SwitchDialog} from '@app-dev-panel/panel/Module/Inspector/Component/Composer/SwitchDialog';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import {Code, FolderOpen, OpenInNew, SwapHoriz} from '@mui/icons-material';
import {Box, Chip, IconButton, Tab, Tabs, Tooltip, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import React, {type SyntheticEvent, useCallback, useDeferredValue, useMemo, useState} from 'react';
import {useNavigate} from 'react-router-dom';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type PackageType = 'require' | 'require-dev';

type PackageEntry = {
    name: string;
    constraint: string;
    installedVersion: string | null;
    type: PackageType;
    isPlatform: boolean;
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const isPlatform = (packageName: string) => !packageName.includes('/');

const packagistUrl = (packageName: string) => `https://packagist.org/packages/${packageName}`;

const vendorPath = (packageName: string) => `vendor/${packageName}`;

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const PackageRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(0.75, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    transition: 'background-color 0.1s ease',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const PackageName = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    fontWeight: 500,
    flex: 1,
    wordBreak: 'break-all',
    minWidth: 0,
});

const VersionText = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    color: theme.palette.text.secondary,
    fontFamily: primitives.fontFamilyMono,
    flexShrink: 0,
    whiteSpace: 'nowrap',
}));

const ActionsBox = styled(Box)({display: 'flex', alignItems: 'center', flexShrink: 0, gap: 2});

// ---------------------------------------------------------------------------
// Tab panel
// ---------------------------------------------------------------------------

type TabPanelProps = {children?: React.ReactNode; index: number; value: number};

const TabPanel = ({children, value, index}: TabPanelProps) => (
    <div role="tabpanel" hidden={value !== index}>
        {value === index && <Box sx={{pt: 2}}>{children}</Box>}
    </div>
);

// ---------------------------------------------------------------------------
// Package row component
// ---------------------------------------------------------------------------

type PackageItemProps = {pkg: PackageEntry; onSwitch: (name: string, isDev: boolean) => void};

const PackageItem = React.memo(({pkg, onSwitch}: PackageItemProps) => {
    const getEditorUrl = useEditorUrl();
    const navigate = useNavigate();

    const editorUrl = !pkg.isPlatform ? getEditorUrl(vendorPath(pkg.name)) : null;

    const handleOpenFileExplorer = useCallback(() => {
        navigate(`/inspector/files?path=${vendorPath(pkg.name)}`);
    }, [navigate, pkg.name]);

    const handleSwitch = useCallback(() => {
        onSwitch(pkg.name, pkg.type === 'require-dev');
    }, [onSwitch, pkg.name, pkg.type]);

    return (
        <PackageRow>
            <Chip
                label={pkg.type === 'require' ? 'REQ' : 'DEV'}
                size="small"
                sx={{
                    fontWeight: 700,
                    fontSize: '10px',
                    height: 22,
                    minWidth: 40,
                    backgroundColor: pkg.type === 'require' ? 'primary.main' : 'warning.main',
                    color: 'common.white',
                    borderRadius: 1,
                }}
            />
            <PackageName>{pkg.name}</PackageName>
            <VersionText>{pkg.constraint}</VersionText>
            {pkg.installedVersion && <VersionText sx={{color: 'text.disabled'}}>{pkg.installedVersion}</VersionText>}
            <ActionsBox>
                {!pkg.isPlatform && (
                    <>
                        <Tooltip title="Open on Packagist">
                            <IconButton
                                size="small"
                                component="a"
                                href={packagistUrl(pkg.name)}
                                target="_blank"
                                rel="noopener noreferrer"
                                onClick={(e: React.MouseEvent) => e.stopPropagation()}
                                sx={{p: 0.25}}
                            >
                                <OpenInNew sx={{fontSize: 14}} />
                            </IconButton>
                        </Tooltip>
                        <Tooltip title="Open in File Explorer">
                            <IconButton size="small" onClick={handleOpenFileExplorer} sx={{p: 0.25}}>
                                <FolderOpen sx={{fontSize: 14}} />
                            </IconButton>
                        </Tooltip>
                        {editorUrl && (
                            <Tooltip title="Open in Editor">
                                <IconButton
                                    size="small"
                                    component="a"
                                    href={editorUrl}
                                    onClick={(e: React.MouseEvent) => e.stopPropagation()}
                                    sx={{p: 0.25}}
                                >
                                    <Code sx={{fontSize: 14}} />
                                </IconButton>
                            </Tooltip>
                        )}
                        <Tooltip title="Switch version">
                            <IconButton size="small" onClick={handleSwitch} sx={{p: 0.25}}>
                                <SwapHoriz sx={{fontSize: 14}} />
                            </IconButton>
                        </Tooltip>
                    </>
                )}
            </ActionsBox>
        </PackageRow>
    );
});

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const ComposerPage = () => {
    const theme = useTheme();
    const {data, isLoading} = useGetComposerQuery();
    const [tab, setTab] = useState(0);
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());
    const [showSwitchDialog, setShowSwitchDialog] = useState(false);
    const [selectedPackage, setSelectedPackage] = useState<string | null>(null);
    const [isDev, setIsDev] = useState(false);

    // Build flat package list
    const packages = useMemo<PackageEntry[]>(() => {
        if (!data) return [];

        const installedVersions: Record<string, string> = {};
        (data.lock?.packages ?? []).concat(data.lock?.['packages-dev'] ?? []).forEach((p) => {
            installedVersions[p.name] = p.version;
        });

        const entries: PackageEntry[] = [];
        for (const [name, constraint] of Object.entries(data.json.require ?? {})) {
            entries.push({
                name,
                constraint,
                installedVersion: installedVersions[name] ?? null,
                type: 'require',
                isPlatform: isPlatform(name),
            });
        }
        for (const [name, constraint] of Object.entries(data.json['require-dev'] ?? {})) {
            entries.push({
                name,
                constraint,
                installedVersion: installedVersions[name] ?? null,
                type: 'require-dev',
                isPlatform: isPlatform(name),
            });
        }
        return entries;
    }, [data]);

    // Badge counts
    const badgeCounts = useMemo(() => {
        const counts: [string, number][] = [
            ['require', packages.filter((p) => p.type === 'require').length],
            ['require-dev', packages.filter((p) => p.type === 'require-dev').length],
            ['platform', packages.filter((p) => p.isPlatform).length],
        ];
        return counts.filter(([, count]) => count > 0);
    }, [packages]);

    // Filter
    const filtered = useMemo(() => {
        let result = packages;
        if (activeFilters.size > 0) {
            result = result.filter((p) => {
                if (activeFilters.has('platform')) return p.isPlatform;
                return activeFilters.has(p.type);
            });
        }
        if (deferredFilter) {
            const lower = deferredFilter.toLowerCase();
            result = result.filter(
                (p) =>
                    p.name.toLowerCase().includes(lower) ||
                    p.constraint.toLowerCase().includes(lower) ||
                    (p.installedVersion?.toLowerCase().includes(lower) ?? false),
            );
        }
        return result;
    }, [packages, deferredFilter, activeFilters]);

    const toggleFilter = useCallback((name: string) => {
        setActiveFilters((prev) => {
            const next = new Set(prev);
            if (next.has(name)) {
                next.delete(name);
            } else {
                next.clear();
                next.add(name);
            }
            return next;
        });
    }, []);

    const handleSwitch = useCallback((name: string, dev: boolean) => {
        setSelectedPackage(name);
        setIsDev(dev);
        setShowSwitchDialog(true);
    }, []);

    const installedVersions = useMemo(() => {
        const map: Record<string, string> = {};
        for (const p of packages) {
            if (p.installedVersion) map[p.name] = p.installedVersion;
        }
        return map;
    }, [packages]);

    const badgeColor = (key: string): string => {
        switch (key) {
            case 'require':
                return theme.palette.primary.main;
            case 'require-dev':
                return theme.palette.warning.main;
            case 'platform':
                return theme.palette.text.disabled;
            default:
                return theme.palette.text.disabled;
        }
    };

    const badgeLabel = (key: string): string => {
        switch (key) {
            case 'require':
                return 'Require';
            case 'require-dev':
                return 'Require Dev';
            case 'platform':
                return 'Platform';
            default:
                return key;
        }
    };

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    return (
        <>
            <PageHeader title="Composer" icon="inventory_2" description="Manage project dependencies and packages" />

            <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                <Tabs value={tab} onChange={(_: SyntheticEvent, v: number) => setTab(v)}>
                    <Tab label="Packages" />
                    <Tab label="composer.json" />
                    <Tab label="composer.lock" />
                </Tabs>
            </Box>

            <TabPanel value={tab} index={0}>
                <SectionTitle
                    action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter packages..." />}
                >{`${filtered.length} packages`}</SectionTitle>

                {badgeCounts.length > 1 && (
                    <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 2}}>
                        {badgeCounts.map(([key, count]) => {
                            const isActive = activeFilters.has(key);
                            const color = badgeColor(key);
                            return (
                                <Chip
                                    key={key}
                                    label={`${badgeLabel(key)} (${count})`}
                                    size="small"
                                    onClick={() => toggleFilter(key)}
                                    sx={{
                                        fontSize: '11px',
                                        height: 24,
                                        borderRadius: 1,
                                        fontWeight: 600,
                                        cursor: 'pointer',
                                        backgroundColor: isActive ? color : 'transparent',
                                        color: isActive ? 'common.white' : color,
                                        border: `1px solid ${color}`,
                                    }}
                                />
                            );
                        })}
                        {activeFilters.size > 0 && (
                            <Chip
                                label="Clear"
                                size="small"
                                onClick={() => setActiveFilters(new Set())}
                                variant="outlined"
                                sx={{fontSize: '11px', height: 24, borderRadius: 1}}
                            />
                        )}
                    </Box>
                )}

                {filtered.length === 0 ? (
                    <EmptyState icon="inventory_2" title="No packages found" description="Try adjusting your filter" />
                ) : (
                    <Box>
                        {filtered.map((pkg) => (
                            <PackageItem key={`${pkg.type}-${pkg.name}`} pkg={pkg} onSwitch={handleSwitch} />
                        ))}
                    </Box>
                )}
            </TabPanel>

            <TabPanel value={tab} index={1}>
                {data && <JsonRenderer value={data.json} />}
            </TabPanel>

            <TabPanel value={tab} index={2}>
                {data && <JsonRenderer value={data.lock} />}
            </TabPanel>

            {showSwitchDialog && selectedPackage && (
                <SwitchDialog
                    packageName={selectedPackage}
                    installedVersion={installedVersions[selectedPackage] ?? null}
                    open={true}
                    isDev={isDev}
                    onClose={() => setShowSwitchDialog(false)}
                    onSwitch={() => setShowSwitchDialog(false)}
                />
            )}
        </>
    );
};
