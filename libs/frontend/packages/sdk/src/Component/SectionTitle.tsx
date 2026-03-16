import {Typography} from '@mui/material';
import {styled} from '@mui/material/styles';

type SectionTitleProps = {children: string};

const StyledTitle = styled(Typography)(({theme}) => ({
    fontSize: theme.typography.overline.fontSize,
    fontWeight: theme.typography.overline.fontWeight,
    letterSpacing: theme.typography.overline.letterSpacing,
    textTransform: 'uppercase',
    color: theme.palette.text.disabled,
    marginTop: theme.spacing(3.5),
    marginBottom: theme.spacing(1.25),
    paddingBottom: theme.spacing(0.75),
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:first-of-type': {marginTop: 0},
}));

export const SectionTitle = ({children}: SectionTitleProps) => {
    return <StyledTitle>{children}</StyledTitle>;
};
