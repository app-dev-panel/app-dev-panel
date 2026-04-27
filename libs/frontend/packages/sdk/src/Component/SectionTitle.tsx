import {Box} from '@mui/material';
import {styled} from '@mui/material/styles';
import React from 'react';

type SectionTitleProps = {children: React.ReactNode; action?: React.ReactNode};

const StyledTitle = styled('div')(({theme}) => ({
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
    paddingLeft: theme.spacing(1.5),
    paddingRight: theme.spacing(1.5),
    [theme.breakpoints.up('sm')]: {paddingLeft: theme.spacing(2.5), paddingRight: theme.spacing(2.5)},
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
