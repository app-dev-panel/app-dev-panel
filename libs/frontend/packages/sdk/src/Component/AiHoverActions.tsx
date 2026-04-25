import {ContentCopy} from '@mui/icons-material';
import AutoAwesomeIcon from '@mui/icons-material/AutoAwesome';
import {IconButton, Tooltip} from '@mui/material';
import {styled} from '@mui/material/styles';
import React, {useCallback, useState} from 'react';

export type AiAction = {label: string; icon?: React.ReactNode; onClick: (content: string) => void};

export type AiHoverActionsProps = {
    children: React.ReactNode;
    actions?: AiAction[];
    getText?: () => string;
    variant?: 'card' | 'row';
};

const CardRoot = styled('div')(({theme}) => ({
    position: 'relative',
    '& > .ai-hover-actions': {opacity: 0, transition: 'opacity 0.15s ease'},
    '&:hover > .ai-hover-actions': {opacity: 1},
    '&:hover': {boxShadow: `0 0 0 2px ${theme.palette.primary.light}`, borderRadius: theme.shape.borderRadius},
}));

const RowRoot = styled('div')(({theme}) => ({
    position: 'relative',
    display: 'flex',
    alignItems: 'center',
    '& > .ai-hover-actions': {opacity: 0, transition: 'opacity 0.1s ease'},
    '&:hover > .ai-hover-actions': {opacity: 1},
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const CardActions = styled('div')(({theme}) => ({
    position: 'absolute',
    top: theme.spacing(1),
    right: theme.spacing(1),
    display: 'flex',
    gap: 2,
    zIndex: 1,
}));

const RowActions = styled('div')({display: 'flex', gap: 2, marginLeft: 'auto', flexShrink: 0});

const ActionButton = styled(IconButton)(({theme}) => ({
    width: 28,
    height: 28,
    borderRadius: theme.shape.borderRadius,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    color: theme.palette.text.secondary,
    transition: 'all 0.15s ease',
    '&:hover': {
        backgroundColor: theme.palette.primary.main,
        color: theme.palette.common.white,
        borderColor: theme.palette.primary.main,
    },
}));

const SmallActionButton = styled(IconButton)(({theme}) => ({
    width: 24,
    height: 24,
    borderRadius: Number(theme.shape.borderRadius) / 2,
    color: theme.palette.text.disabled,
    '&:hover': {backgroundColor: theme.palette.action.selected, color: theme.palette.text.primary},
}));

const defaultGetText = (): string => '';

const defaultActions: AiAction[] = [
    {
        label: 'Copy',
        icon: <ContentCopy sx={{fontSize: 14}} />,
        onClick: (content) => navigator.clipboard.writeText(content),
    },
    {label: 'Explain', icon: <AutoAwesomeIcon sx={{fontSize: 14}} />, onClick: () => {}},
];

export const AiHoverActions = ({
    children,
    actions = defaultActions,
    getText = defaultGetText,
    variant = 'card',
}: AiHoverActionsProps) => {
    const [copiedIndex, setCopiedIndex] = useState<number | null>(null);

    const handleAction = useCallback(
        (action: AiAction, index: number) => {
            const content = getText();
            action.onClick(content);

            if (action.label === 'Copy') {
                setCopiedIndex(index);
                setTimeout(() => setCopiedIndex(null), 1500);
            }
        },
        [getText],
    );

    const Root = variant === 'card' ? CardRoot : RowRoot;
    const ActionsContainer = variant === 'card' ? CardActions : RowActions;
    const Button = variant === 'card' ? ActionButton : SmallActionButton;

    return (
        <Root>
            {children}
            <ActionsContainer className="ai-hover-actions">
                {actions.map((action, index) => (
                    <Tooltip key={index} title={copiedIndex === index ? 'Copied!' : action.label} placement="top">
                        <Button onClick={() => handleAction(action, index)}>{action.icon}</Button>
                    </Tooltip>
                ))}
            </ActionsContainer>
        </Root>
    );
};
