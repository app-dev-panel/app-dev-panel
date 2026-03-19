import {Box, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import React from 'react';

type SectionTitleProps = {children: string | string[]; action?: React.ReactNode};

const StyledTitle = styled(Typography)(({theme}) => ({
    fontSize: theme.typography.overline.fontSize,
    fontWeight: theme.typography.overline.fontWeight,
    letterSpacing: '0.05em',
    textTransform: 'uppercase',
    color: theme.palette.text.disabled,
    paddingBottom: theme.spacing(0.75),
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

const Container = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(2),
    marginTop: theme.spacing(3.5),
    marginBottom: theme.spacing(1.25),
    '&:first-of-type': {marginTop: 0},
}));

export const SectionTitle = ({children, action}: SectionTitleProps) => {
    const labels = Array.isArray(children) ? children : [children];
    return (
        <Container>
            {labels.map((label) => (
                <StyledTitle key={label}>{label}</StyledTitle>
            ))}
            {action && <Box sx={{ml: 'auto', display: 'flex', alignItems: 'center'}}>{action}</Box>}
        </Container>
    );
};
