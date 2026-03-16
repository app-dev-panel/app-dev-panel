import {Divider, Paper} from '@mui/material';
import {styled} from '@mui/material/styles';
import {NavItem} from '@yiisoft/yii-dev-panel-sdk/Component/Layout/NavItem';
import {componentTokens} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';
import React from 'react';

type CollectorNavItem = {
    key: string;
    icon: string;
    label: string;
    badge?: number | string;
    badgeVariant?: 'default' | 'error';
};

type CollectorSidebarProps = {
    items: CollectorNavItem[];
    activeKey?: string;
    onItemClick: (key: string) => void;
    onOverviewClick?: () => void;
};

const SidebarRoot = styled(Paper)(({theme}) => ({
    width: componentTokens.sidebar.width,
    borderRadius: componentTokens.sidebar.borderRadius,
    display: 'flex',
    flexDirection: 'column',
    padding: theme.spacing(1.5, 1),
    gap: 2,
    flexShrink: 0,
    alignSelf: 'flex-start',
    overflowY: 'auto',
}));

export const CollectorSidebar = React.memo(
    ({items, activeKey, onItemClick, onOverviewClick}: CollectorSidebarProps) => {
        return (
            <SidebarRoot variant="outlined">
                {onOverviewClick && (
                    <>
                        <NavItem icon="grid_view" label="Overview" active={!activeKey} onClick={onOverviewClick} />
                        <Divider sx={{mx: 1.25, my: 0.75}} />
                    </>
                )}
                {items.map((item) => (
                    <NavItem
                        key={item.key}
                        icon={item.icon}
                        label={item.label}
                        badge={item.badge}
                        badgeVariant={item.badgeVariant}
                        active={activeKey === item.key}
                        onClick={() => onItemClick(item.key)}
                    />
                ))}
            </SidebarRoot>
        );
    },
);
