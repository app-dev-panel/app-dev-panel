import {Box} from '@mui/material';
import {ssrUiKitSx} from './SsrPanel.uiKit';

type SsrPanelProps = {html: string};

/**
 * Generic host for server-rendered HTML fragments produced by collectors that
 * implement `HtmlViewProviderInterface` (data shape `{__html: "<...>"}`).
 *
 * The host is intentionally agnostic of any specific collector: all it does is
 * mount the backend HTML and apply the shared `adp-ui-*` UI kit (see
 * `SsrPanel.uiKit.ts`). Backend templates ship structure + class names only;
 * colors, dark mode, and theme-driven spacing live here.
 */
export const SsrPanel = ({html}: SsrPanelProps) => (
    <Box className="adp-ui" sx={ssrUiKitSx} dangerouslySetInnerHTML={{__html: html}} />
);
