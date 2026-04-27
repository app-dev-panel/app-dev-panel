import {ClassName} from '@app-dev-panel/panel/Application/Component/ClassName';
import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import type {ComponentType} from 'react';

/**
 * Slot registry for `SsrPanel`.
 *
 * Backend templates (via `AppDevPanel\Api\Debug\Slot\Slot`) emit elements with
 * a `data-adp-slot="<name>"` attribute and a payload that lives either in a
 * child `<script type="application/json" data-adp-payload>`, in `data-*`
 * attributes, or in the element's text content. After the SSR HTML is mounted
 * the panel parses each slot, then renders the matching React component into
 * the slot element via `createPortal` so theme, Redux, and router context flow
 * naturally from the SPA tree.
 *
 * Adding a new slot is one entry below; bump the matching `Slot::*` helper on
 * the backend if a new payload shape is needed.
 */
export type SlotData = {
    /** Parsed `<script type="application/json">` payload, if any. */
    payload: unknown;
    /** All `data-*` attributes from the slot element except `data-adp-slot`, with the `data-` prefix stripped. */
    attrs: Record<string, string>;
    /** Pre-hydration text content of the slot, suitable as a fallback label. */
    label: string;
};

export type SlotComponent = ComponentType<SlotData>;

/** Names of registered slots; backend `Slot::*` calls must use one of these. */
export type SlotName = keyof typeof ssrSlots;

export const ssrSlots = {
    json: ({payload}) => <JsonRenderer value={payload} />,
    'file-link': ({attrs, label}) => {
        const path = attrs.path ?? '';
        if (!path) return null;
        const lineRaw = attrs.line ? Number.parseInt(attrs.line, 10) : NaN;
        const line = Number.isFinite(lineRaw) ? lineRaw : undefined;
        return (
            <FileLink path={path} line={line}>
                {label || path}
            </FileLink>
        );
    },
    'class-name': ({attrs, label}) => {
        const fqcn = attrs.fqcn ?? '';
        if (!fqcn) return null;
        return (
            <ClassName value={fqcn} methodName={attrs.method}>
                {label || undefined}
            </ClassName>
        );
    },
} satisfies Record<string, SlotComponent>;

/** Reads a slot's payload from a `<script type="application/json" data-adp-payload>` child, if any. */
export const readSlotPayload = (el: Element): unknown => {
    const script = el.querySelector(':scope > script[type="application/json"][data-adp-payload]');
    const text = script?.textContent ?? '';
    if (text === '') return undefined;
    try {
        return JSON.parse(text);
    } catch (e) {
        console.error('[SsrPanel] failed to parse slot payload', e, text);
        return undefined;
    }
};

/** Reads all `data-*` attributes from the slot element except `data-adp-slot`. */
export const readSlotAttrs = (el: Element): Record<string, string> => {
    const out: Record<string, string> = {};
    for (const attr of Array.from(el.attributes)) {
        if (!attr.name.startsWith('data-')) continue;
        if (attr.name === 'data-adp-slot') continue;
        out[attr.name.slice('data-'.length)] = attr.value;
    }
    return out;
};

/**
 * Parses a slot element into a {@link SlotData} value object. The label is read
 * from the visible text content (so for payload slots, where the only child is
 * the `<script>` tag, the label is empty).
 */
export const parseSlotEntry = (el: Element): SlotData => {
    const payloadScript = el.querySelector(':scope > script[type="application/json"][data-adp-payload]');
    const label = payloadScript ? '' : (el.textContent ?? '').trim();
    return {payload: readSlotPayload(el), attrs: readSlotAttrs(el), label};
};

export const SLOT_SELECTOR = '[data-adp-slot]';
