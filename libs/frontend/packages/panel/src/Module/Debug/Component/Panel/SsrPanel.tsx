import {Box} from '@mui/material';

type SsrPanelProps = {html: string};

/**
 * Renders a server-rendered HTML fragment produced by a backend collector that
 * implements `HtmlViewProviderInterface`. The data shape is `{__html: "<...>"}`
 * and the API has already escaped/encoded everything inside, so the panel embeds
 * the markup verbatim.
 */
export const SsrPanel = ({html}: SsrPanelProps) => <Box sx={{p: 2}} dangerouslySetInnerHTML={{__html: html}} />;
