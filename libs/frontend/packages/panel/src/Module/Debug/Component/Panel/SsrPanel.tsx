import {Box} from '@mui/material';
import {useLayoutEffect, useRef, useState} from 'react';
import {createPortal} from 'react-dom';
import {parseSlotEntry, SLOT_SELECTOR, ssrSlots, type SlotData} from './SsrPanel.slots';
import {ssrUiKitSx} from './SsrPanel.uiKit';

type SsrPanelProps = {html: string};

type Mount = {el: Element; name: string; data: SlotData};

/**
 * Generic host for server-rendered HTML fragments produced by collectors that
 * implement `HtmlViewProviderInterface` (data shape `{__html: "<...>"}`).
 *
 * The host:
 *   1. Imperatively writes the backend HTML into a ref'd container — React
 *      owns the container element, we own its content. We avoid
 *      `dangerouslySetInnerHTML` so a `setMounts` re-render can't roll the
 *      children back to the raw payload mid-flight.
 *   2. Applies the shared `adp-ui-*` UI kit (see `SsrPanel.uiKit.ts`).
 *   3. Scans for slot markers (`[data-adp-slot]`) and replaces each with a
 *      real React component via `createPortal`, so primitives like
 *      `<JsonRenderer>`, `<FileLink>`, `<ClassName>` keep theme/Redux/router
 *      context from the SPA.
 *
 * Backend templates ship structure + class names + slot markers only; colors,
 * dark mode, and React behaviour live here.
 */
export const SsrPanel = ({html}: SsrPanelProps) => {
    const ref = useRef<HTMLDivElement | null>(null);
    const [mounts, setMounts] = useState<Mount[]>([]);

    useLayoutEffect(() => {
        const root = ref.current;
        if (!root) {
            setMounts([]);
            return;
        }
        // Imperative write — React doesn't manage the child tree, so a re-render
        // triggered by setMounts won't restore the original payload.
        root.innerHTML = html;

        const elements = Array.from(root.querySelectorAll(SLOT_SELECTOR));
        const next: Mount[] = [];
        for (const el of elements) {
            const name = el.getAttribute('data-adp-slot') ?? '';
            if (!(name in ssrSlots)) {
                console.warn(`[SsrPanel] unknown slot "${name}"`, el);
                continue;
            }
            const data = parseSlotEntry(el, root);
            // Wipe pre-hydration content (the JSON <script> + fallback label) so
            // the portal owns the slot exclusively and there's no flash of raw
            // payload between commit and paint.
            el.replaceChildren();
            next.push({el, name, data});
        }
        setMounts(next);
    }, [html]);

    return (
        <>
            <Box ref={ref} className="adp-ui" sx={ssrUiKitSx} />
            {mounts.map(({el, name, data}, i) => {
                const Component = ssrSlots[name as keyof typeof ssrSlots];
                return createPortal(<Component {...data} />, el, `${name}-${i}`);
            })}
        </>
    );
};
