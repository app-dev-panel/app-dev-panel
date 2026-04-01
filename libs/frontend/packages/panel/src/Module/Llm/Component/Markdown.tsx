import {Box, Link, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';

const MarkdownRoot = styled(Box)(({theme}) => ({
    '& > *:first-of-type': {marginTop: 0},
    '& > *:last-child': {marginBottom: 0},
    '& p': {margin: theme.spacing(1, 0), fontSize: '13px', lineHeight: 1.6},
    '& h1, & h2, & h3, & h4, & h5, & h6': {
        marginTop: theme.spacing(2),
        marginBottom: theme.spacing(1),
        fontWeight: 600,
    },
    '& h1': {fontSize: '18px'},
    '& h2': {fontSize: '16px'},
    '& h3': {fontSize: '14px'},
    '& ul, & ol': {margin: theme.spacing(1, 0), paddingLeft: theme.spacing(3), fontSize: '13px', lineHeight: 1.6},
    '& li': {marginBottom: theme.spacing(0.5)},
    '& code': {
        fontFamily: "'JetBrains Mono', monospace",
        fontSize: '12px',
        backgroundColor: theme.palette.action.hover,
        padding: theme.spacing(0.25, 0.5),
        borderRadius: Number(theme.shape.borderRadius) / 2,
    },
    '& pre': {
        margin: theme.spacing(1.5, 0),
        padding: theme.spacing(1.5),
        backgroundColor: theme.palette.mode === 'dark' ? 'rgba(0,0,0,0.3)' : 'rgba(0,0,0,0.04)',
        borderRadius: theme.shape.borderRadius,
        overflow: 'auto',
        '& code': {backgroundColor: 'transparent', padding: 0, fontSize: '12px'},
    },
    '& blockquote': {
        margin: theme.spacing(1.5, 0),
        paddingLeft: theme.spacing(2),
        borderLeft: `3px solid ${theme.palette.divider}`,
        color: theme.palette.text.secondary,
    },
    '& table': {borderCollapse: 'collapse', width: '100%', margin: theme.spacing(1.5, 0), fontSize: '13px'},
    '& th, & td': {border: `1px solid ${theme.palette.divider}`, padding: theme.spacing(0.75, 1.5), textAlign: 'left'},
    '& th': {fontWeight: 600, backgroundColor: theme.palette.action.hover},
    '& hr': {border: 'none', borderTop: `1px solid ${theme.palette.divider}`, margin: theme.spacing(2, 0)},
    '& strong': {fontWeight: 600},
}));

const components = {
    p: ({children}: {children?: React.ReactNode}) => (
        <Typography variant="body2" component="p" sx={{my: 1, lineHeight: 1.6}}>
            {children}
        </Typography>
    ),
    a: ({href, children}: {href?: string; children?: React.ReactNode}) => (
        <Link href={href} target="_blank" rel="noopener noreferrer">
            {children}
        </Link>
    ),
};

export const Markdown = ({content}: {content: string}) => (
    <MarkdownRoot>
        <ReactMarkdown remarkPlugins={[remarkGfm]} components={components}>
            {content}
        </ReactMarkdown>
    </MarkdownRoot>
);
