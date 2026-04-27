import {Box} from '@mui/material';
import {styled} from '@mui/material/styles';
import {Fragment, ReactNode} from 'react';

type SectionBreadcrumbProps = {
    items: ReactNode[];
};

const Root = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(0.75),
    padding: theme.spacing(0.5, 1.5),
    fontSize: '11px',
    fontWeight: 500,
    lineHeight: 1.4,
    color: theme.palette.text.disabled,
    [theme.breakpoints.up('sm')]: {padding: theme.spacing(0.5, 2.5)},
}));

const Separator = styled('span')({
    opacity: 0.6,
    fontSize: '10px',
});

export const SectionBreadcrumb = ({items}: SectionBreadcrumbProps) => (
    <Root role="navigation" aria-label="Section breadcrumb">
        {items.map((item, i) => (
            <Fragment key={i}>
                {i > 0 && <Separator aria-hidden>→</Separator>}
                <span>{item}</span>
            </Fragment>
        ))}
    </Root>
);
