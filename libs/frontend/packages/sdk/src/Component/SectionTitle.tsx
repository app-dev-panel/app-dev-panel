import {Box, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import React from 'react';

type SectionTitleProps = {children: string; action?: React.ReactNode};

const StyledTitle = styled(Typography)(({theme}) => ({
    fontSize: theme.typography.overline.fontSize,
    fontWeight: theme.typography.overline.fontWeight,
    letterSpacing: theme.typography.overline.letterSpacing,
    textTransform: 'uppercase',
    color: theme.palette.text.disabled,
}));

const Container = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    marginTop: theme.spacing(3.5),
    marginBottom: theme.spacing(1.25),
    paddingBottom: theme.spacing(0.75),
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:first-of-type': {marginTop: 0},
}));

export const SectionTitle = ({children, action}: SectionTitleProps) => {
    return (
        <Container>
            <StyledTitle>{children}</StyledTitle>
            {action && <Box sx={{ml: 'auto', display: 'flex', alignItems: 'center'}}>{action}</Box>}
        </Container>
    );
};
