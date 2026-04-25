import {Box, Icon, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {isValidElement, type ReactNode} from 'react';

export type EmptyStateProps = {
    /**
     * Visual glyph rendered above the title. Pass a string to use a MUI icon ligature
     * (rendered via `<Icon />`), or pass any React element (e.g. a custom SVG icon) to
     * render it verbatim. Custom elements are placed inside a decorative halo and sized
     * to `iconSize`.
     */
    icon?: ReactNode;
    iconSize?: number;
    title: string;
    description?: ReactNode;
    action?: ReactNode;
    severity?: 'default' | 'error';
    /**
     * When true, the component stretches to fill its parent vertically so the content
     * sits centered in the available space. Use for full-viewport empty states.
     */
    fillHeight?: boolean;
};

const Root = styled(Box, {shouldForwardProp: (prop) => prop !== 'fillHeight'})<{fillHeight?: boolean}>(
    ({theme, fillHeight}) => ({
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        padding: theme.spacing(6, 3),
        textAlign: 'center',
        ...(fillHeight && {minHeight: '100%', flex: 1}),
    }),
);

const IconHalo = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: theme.spacing(3),
    borderRadius: '50%',
    background: `radial-gradient(circle, ${theme.palette.action.hover} 0%, transparent 70%)`,
    filter: `drop-shadow(0 4px 16px ${theme.palette.action.hover})`,
}));

const isStringIcon = (icon: ReactNode): icon is string => typeof icon === 'string';

export const EmptyState = ({
    icon = 'inbox',
    iconSize,
    title,
    description,
    action,
    severity = 'default',
    fillHeight = false,
}: EmptyStateProps) => {
    const isError = severity === 'error';
    const iconColor = isError ? 'error.main' : 'text.disabled';
    const titleColor = isError ? 'error.main' : 'text.secondary';
    const isCustomIcon = isValidElement(icon);
    const resolvedIconSize = iconSize ?? (isCustomIcon ? 96 : 48);

    return (
        <Root role={isError ? 'alert' : undefined} aria-live={isError ? 'polite' : undefined} fillHeight={fillHeight}>
            {isCustomIcon ? (
                <IconHalo sx={{width: resolvedIconSize * 1.6, height: resolvedIconSize * 1.6}}>
                    <Box
                        sx={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            fontSize: resolvedIconSize,
                            // `&&` doubles the wrapper class specificity so we override
                            // the default 1.5rem font-size that MUI SvgIcon applies via
                            // its own MuiSvgIcon-fontSize* class.
                            '&& > *': {fontSize: resolvedIconSize, width: resolvedIconSize, height: resolvedIconSize},
                        }}
                    >
                        {icon}
                    </Box>
                </IconHalo>
            ) : isStringIcon(icon) ? (
                <Icon sx={{fontSize: resolvedIconSize, color: iconColor, mb: 2}}>{icon}</Icon>
            ) : null}
            <Typography
                component="h2"
                sx={{
                    fontSize: isCustomIcon ? '20px' : '14px',
                    fontWeight: isCustomIcon ? 700 : 600,
                    color: isCustomIcon && !isError ? 'text.primary' : titleColor,
                    mb: isCustomIcon ? 1 : 0.5,
                }}
            >
                {title}
            </Typography>
            {description && (
                <Typography
                    component="div"
                    sx={{fontSize: isCustomIcon ? '14px' : '13px', color: 'text.secondary', maxWidth: 480}}
                >
                    {description}
                </Typography>
            )}
            {action && <Box sx={{mt: 3}}>{action}</Box>}
        </Root>
    );
};
