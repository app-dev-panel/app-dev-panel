import {CheckCircleRounded, ErrorRounded, PlayArrowRounded} from '@mui/icons-material';
import {Box, ButtonBase, CircularProgress, Icon, Tooltip, Typography, alpha, styled} from '@mui/material';

export type CommandRunStatus = 'idle' | 'loading' | 'success' | 'error';

type CommandButtonProps = {
    title: string;
    description?: string;
    group?: string;
    status?: CommandRunStatus;
    disabled?: boolean;
    onClick: () => void;
    fullWidth?: boolean;
};

const groupIcon: Record<string, string> = {
    test: 'science',
    tests: 'science',
    analyse: 'insights',
    analyze: 'insights',
    analysis: 'insights',
    composer: 'inventory_2',
    coverage: 'pie_chart',
    build: 'construction',
    deploy: 'rocket_launch',
    db: 'storage',
    database: 'storage',
    cache: 'memory',
    server: 'dns',
};

const resolveGroupIcon = (group?: string): string => {
    if (!group) return 'play_arrow';
    return groupIcon[group.toLowerCase()] ?? 'terminal';
};

const Root = styled(ButtonBase, {shouldForwardProp: (prop) => prop !== 'status' && prop !== 'fullWidth'})<{
    status: CommandRunStatus;
    fullWidth: boolean;
}>(({theme, status, fullWidth}) => {
    const palette = theme.palette;
    const accentColor =
        status === 'success' ? palette.success.main : status === 'error' ? palette.error.main : palette.primary.main;
    const accentLight =
        status === 'success' ? palette.success.light : status === 'error' ? palette.error.light : palette.primary.light;

    return {
        position: 'relative',
        display: 'inline-flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        textAlign: 'left',
        padding: theme.spacing(1.25, 1.75),
        paddingRight: theme.spacing(2),
        minHeight: 56,
        minWidth: 220,
        width: fullWidth ? '100%' : undefined,
        borderRadius: theme.shape.borderRadius * 1.5,
        border: `1px solid ${status === 'idle' || status === 'loading' ? palette.divider : alpha(accentColor, 0.4)}`,
        backgroundColor: status === 'idle' ? palette.background.paper : alpha(accentLight, 0.35),
        color: palette.text.primary,
        transition: theme.transitions.create(['background-color', 'border-color', 'box-shadow', 'transform'], {
            duration: theme.transitions.duration.shortest,
        }),
        '&:hover': {
            borderColor: alpha(accentColor, 0.55),
            backgroundColor: status === 'idle' ? alpha(palette.primary.main, 0.04) : alpha(accentLight, 0.55),
            boxShadow: theme.shadows[1],
        },
        '&:focus-visible': {outline: `2px solid ${alpha(accentColor, 0.6)}`, outlineOffset: 2},
        '&:active:not(:disabled)': {transform: 'translateY(1px)'},
        '&.Mui-disabled': {opacity: 0.55, cursor: 'not-allowed'},
    };
});

const IconBubble = styled('span', {shouldForwardProp: (prop) => prop !== 'status'})<{status: CommandRunStatus}>(({
    theme,
    status,
}) => {
    const palette = theme.palette;
    const accentColor =
        status === 'success' ? palette.success.main : status === 'error' ? palette.error.main : palette.primary.main;
    const accentLight =
        status === 'success' ? palette.success.light : status === 'error' ? palette.error.light : palette.primary.light;

    return {
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        width: 36,
        height: 36,
        flexShrink: 0,
        borderRadius: theme.shape.borderRadius * 1.25,
        backgroundColor: alpha(accentLight, 0.7),
        color: accentColor,
        '& > .material-icons, & > svg': {fontSize: 20},
    };
});

const TextColumn = styled('span')(({theme}) => ({
    display: 'inline-flex',
    flexDirection: 'column',
    alignItems: 'flex-start',
    minWidth: 0,
    gap: theme.spacing(0.25),
}));

const TitleLine = styled(Typography)(({theme}) => ({
    fontSize: '14px',
    fontWeight: 600,
    lineHeight: 1.25,
    display: 'inline-flex',
    alignItems: 'center',
    gap: theme.spacing(0.75),
    color: 'inherit',
}));

const DescriptionLine = styled(Typography)(({theme}) => ({
    fontSize: '12px',
    color: theme.palette.text.secondary,
    lineHeight: 1.35,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
    maxWidth: 280,
}));

const RunHint = styled('span')(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    letterSpacing: '0.4px',
    textTransform: 'uppercase',
    color: theme.palette.text.secondary,
    marginRight: theme.spacing(0.25),
}));

const StatusSlot = styled('span')(({theme}) => ({
    marginLeft: 'auto',
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    width: 28,
    height: 28,
    flexShrink: 0,
    color: theme.palette.text.secondary,
}));

const StatusIcon = ({status}: {status: CommandRunStatus}) => {
    if (status === 'loading') {
        return <CircularProgress size={18} thickness={5} color="primary" />;
    }
    if (status === 'success') {
        return <CheckCircleRounded sx={{fontSize: 22, color: 'success.main'}} />;
    }
    if (status === 'error') {
        return <ErrorRounded sx={{fontSize: 22, color: 'error.main'}} />;
    }
    return <PlayArrowRounded sx={{fontSize: 22, color: 'primary.main', opacity: 0.6}} />;
};

export const CommandButton = ({
    title,
    description,
    group,
    status = 'idle',
    disabled = false,
    onClick,
    fullWidth = false,
}: CommandButtonProps) => {
    const iconName = resolveGroupIcon(group);
    const statusLabel =
        status === 'loading'
            ? 'Running…'
            : status === 'success'
              ? 'Last run: success'
              : status === 'error'
                ? 'Last run: failed'
                : 'Click to run';

    const button = (
        <Root
            status={status}
            fullWidth={fullWidth}
            disabled={disabled || status === 'loading'}
            onClick={onClick}
            aria-label={`Run ${title}`}
            aria-busy={status === 'loading'}
        >
            <IconBubble status={status} aria-hidden="true">
                <Icon className="material-icons">{iconName}</Icon>
            </IconBubble>
            <TextColumn>
                <TitleLine>
                    <RunHint>Run</RunHint>
                    <Box component="span" sx={{minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis'}}>
                        {title}
                    </Box>
                </TitleLine>
                {description ? (
                    <DescriptionLine>{description}</DescriptionLine>
                ) : (
                    <DescriptionLine sx={{color: 'text.disabled'}}>{statusLabel}</DescriptionLine>
                )}
            </TextColumn>
            <StatusSlot aria-hidden="true">
                <StatusIcon status={status} />
            </StatusSlot>
        </Root>
    );

    if (description) {
        return (
            <Tooltip title={description} placement="top" arrow disableInteractive enterDelay={400}>
                {button}
            </Tooltip>
        );
    }

    return button;
};
