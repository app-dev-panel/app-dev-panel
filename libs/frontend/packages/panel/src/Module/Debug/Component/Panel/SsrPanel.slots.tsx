import {ClassName} from '@app-dev-panel/panel/Application/Component/ClassName';
import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {Chip, Tab, Tabs, Tooltip} from '@mui/material';
import {useEffect, useMemo, useState, type ComponentType} from 'react';

/**
 * Slot registry for `SsrPanel`.
 *
 * Backend templates emit elements with a `data-adp-slot="<name>"` attribute
 * and a payload that lives either in a child `<script type="application/json"
 * data-adp-payload>`, in `data-*` attributes, or in the element's text
 * content. After the SSR HTML is mounted, the panel parses each slot and
 * renders the matching React component into the slot element via `createPortal`
 * so theme, Redux, and router context flow naturally from the SPA tree.
 *
 * Slots come in two flavours:
 *   - **Display slots** (`json`, `file-link`, `class-name`, `tooltip`,
 *     `empty-state`) — render a single React component in place of the slot
 *     marker.
 *   - **Interactive slots** (`filter`, `chips`, `tabs`) — render a control
 *     that mutates the visibility of sibling DOM nodes inside the SsrPanel
 *     host, using `data-search` / `data-tag` / `data-adp-tab-panel` markers
 *     written by the backend template.
 */
export type SlotData = {
    /** Parsed `<script type="application/json">` payload, if any. */
    payload: unknown;
    /** All `data-*` attributes from the slot element except `data-adp-slot`, with the `data-` prefix stripped. */
    attrs: Record<string, string>;
    /** Pre-hydration text content of the slot, suitable as a fallback label. */
    label: string;
    /** The SsrPanel host element, used by interactive slots to find their targets. */
    host: Element;
};

export type SlotComponent = ComponentType<SlotData>;

/** Names of registered slots; backend `Slot::*` calls must use one of these. */
export type SlotName = keyof typeof ssrSlots;

// -----------------------------------------------------------------------------
// Helpers shared by interactive slots
// -----------------------------------------------------------------------------

/** Toggle `display: none` on every host descendant matching `selector`. */
const setVisibility = (host: Element, selector: string, isVisible: (el: Element) => boolean): void => {
    const items = host.querySelectorAll(selector);
    items.forEach((el) => {
        (el as HTMLElement).style.display = isVisible(el) ? '' : 'none';
    });
};

// -----------------------------------------------------------------------------
// Interactive slot components
// -----------------------------------------------------------------------------

type FilterControlProps = {host: Element; target: string; placeholder?: string};

const FilterControl = ({host, target, placeholder}: FilterControlProps) => {
    const [q, setQ] = useState('');
    useEffect(() => {
        if (!target) return;
        const lower = q.trim().toLowerCase();
        setVisibility(host, target, (el) => {
            if (lower === '') return true;
            const haystack = (el.getAttribute('data-search') ?? el.textContent ?? '').toLowerCase();
            return haystack.includes(lower);
        });
    }, [q, target, host]);
    return <FilterInput value={q} onChange={setQ} placeholder={placeholder ?? 'Filter…'} />;
};

type ChipItem = {value: string; label?: string; count?: number};
type ChipsControlProps = {host: Element; target: string; attr: string; items: ChipItem[]};

const ChipsControl = ({host, target, attr, items}: ChipsControlProps) => {
    const [active, setActive] = useState<Set<string>>(new Set());
    useEffect(() => {
        if (!target || !attr) return;
        setVisibility(host, target, (el) => {
            if (active.size === 0) return true;
            const tag = el.getAttribute(attr) ?? '';
            return active.has(tag);
        });
    }, [active, target, attr, host]);

    const toggle = (value: string) => {
        setActive((prev) => {
            const next = new Set(prev);
            if (next.has(value)) next.delete(value);
            else next.add(value);
            return next;
        });
    };

    return (
        <>
            {items.map((item) => {
                const isActive = active.has(item.value);
                const label =
                    item.count !== undefined
                        ? `${item.label ?? item.value} (${item.count})`
                        : (item.label ?? item.value);
                return (
                    <Chip
                        key={item.value}
                        label={label}
                        size="small"
                        variant={isActive ? 'filled' : 'outlined'}
                        onClick={() => toggle(item.value)}
                        sx={{fontSize: 11, height: 24, borderRadius: 1, cursor: 'pointer'}}
                    />
                );
            })}
            {active.size > 0 && (
                <Chip
                    label="Clear"
                    size="small"
                    variant="outlined"
                    onClick={() => setActive(new Set())}
                    sx={{fontSize: 11, height: 24, borderRadius: 1}}
                />
            )}
        </>
    );
};

type TabItem = {value: string; label: string};
type TabsControlProps = {host: Element; items: TabItem[]; defaultValue?: string};

const TabsControl = ({host, items, defaultValue}: TabsControlProps) => {
    const initial = defaultValue ?? items[0]?.value ?? '';
    const [active, setActive] = useState<string>(initial);
    useEffect(() => {
        const panels = host.querySelectorAll('[data-adp-tab-panel]');
        panels.forEach((el) => {
            const v = el.getAttribute('data-adp-tab-panel');
            (el as HTMLElement).style.display = v === active ? '' : 'none';
        });
    }, [active, host]);
    return (
        <Tabs
            value={active}
            onChange={(_, v) => setActive(v as string)}
            sx={{
                minHeight: 36,
                '& .MuiTab-root': {minHeight: 36, fontSize: 12, fontWeight: 600, textTransform: 'none'},
            }}
        >
            {items.map((item) => (
                <Tab key={item.value} label={item.label} value={item.value} />
            ))}
        </Tabs>
    );
};

// -----------------------------------------------------------------------------
// Slot registry
// -----------------------------------------------------------------------------

export const ssrSlots = {
    // Display
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
    tooltip: ({attrs, label}) => (
        <Tooltip title={attrs.text ?? ''}>
            <span>{label}</span>
        </Tooltip>
    ),
    'empty-state': ({payload}) => {
        const data = (payload && typeof payload === 'object' ? payload : {}) as {
            icon?: string;
            title?: string;
            description?: string;
        };
        return <EmptyState icon={data.icon ?? 'inbox'} title={data.title ?? ''} description={data.description} />;
    },

    // Interactive
    filter: ({host, attrs}) => (
        <FilterControl host={host} target={attrs.target ?? ''} placeholder={attrs.placeholder} />
    ),
    chips: ({host, attrs, payload}) => {
        const items = useMemo(() => {
            if (Array.isArray(payload)) return payload as ChipItem[];
            return [];
        }, [payload]);
        if (items.length === 0) return null;
        return <ChipsControl host={host} target={attrs.target ?? ''} attr={attrs.attr ?? 'data-tag'} items={items} />;
    },
    tabs: ({host, attrs, payload}) => {
        const items = Array.isArray(payload) ? (payload as TabItem[]) : [];
        if (items.length === 0) return null;
        return <TabsControl host={host} items={items} defaultValue={attrs.default} />;
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
export const parseSlotEntry = (el: Element, host: Element): SlotData => {
    const payloadScript = el.querySelector(':scope > script[type="application/json"][data-adp-payload]');
    const label = payloadScript ? '' : (el.textContent ?? '').trim();
    return {payload: readSlotPayload(el), attrs: readSlotAttrs(el), label, host};
};

export const SLOT_SELECTOR = '[data-adp-slot]';
