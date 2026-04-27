<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Slot;

use AppDevPanel\Kernel\Helper\Json;

/**
 * Markup helper for SSR collector templates.
 *
 * Emits the slot HTML contract that the panel's `SsrPanel` component understands
 * (see `libs/frontend/packages/panel/src/Module/Debug/Component/Panel/SsrPanel.slots.tsx`).
 * After the backend HTML is mounted, the panel scans for `data-adp-slot` markers,
 * parses the payload, and renders the matching React component into the slot
 * element via a portal — so the rendered fragment shares theme, Redux store,
 * and router context with the rest of the SPA.
 *
 * Three primitives:
 *   - `json($name, $payload)`  — complex value travels in `<script type="application/json">`.
 *   - `attrs($name, $attrs, $label)` — scalar props as `data-*`, `$label` is fallback text.
 *   - `text($name, $content)`   — payload is plain text content.
 *
 * Plus bespoke helpers for the standard interactive/display slots:
 *   - `tooltip`, `emptyState` (display)
 *   - `filter`, `chips`, `tabs` (interactive — render MUI controls that mutate
 *     the visibility of host descendants by data-search / data-tag /
 *     data-adp-tab-panel markers).
 */
final class Slot
{
    public const ATTR_NAME = 'data-adp-slot';
    public const PAYLOAD_ATTR = 'data-adp-payload';

    /**
     * Slot whose payload is a structured value rendered as JSON inside an
     * inert `<script type="application/json">` child.
     */
    public static function json(string $name, mixed $payload, string $tag = 'div'): string
    {
        return self::compound($name, $payload, [], '', $tag);
    }

    /**
     * Slot whose props travel in `data-*` attributes; `$label` is the
     * pre-hydration fallback that's visible when JS is disabled.
     *
     * @param array<string, scalar|null> $attrs
     */
    public static function attrs(string $name, array $attrs, string $label = '', string $tag = 'span'): string
    {
        return self::compound($name, null, $attrs, $label, $tag);
    }

    /**
     * Slot whose payload is the literal text content (SQL, code, raw output).
     */
    public static function text(string $name, string $content, string $tag = 'pre'): string
    {
        return sprintf(
            '<%1$s %2$s="%3$s">%4$s</%1$s>',
            self::escapeTag($tag),
            self::ATTR_NAME,
            self::escapeAttr($name),
            self::escapeText($content),
        );
    }

    /**
     * Wraps `$label` in a tooltip; the panel hydrates this into a MUI `<Tooltip>`.
     */
    public static function tooltip(string $text, string $label): string
    {
        return self::attrs('tooltip', ['text' => $text], $label);
    }

    /**
     * Empty-state placeholder rendered as `<EmptyState icon="…" title="…" />`.
     */
    public static function emptyState(string $icon, string $title, ?string $description = null): string
    {
        return self::json('empty-state', [
            'icon' => $icon,
            'title' => $title,
            'description' => $description,
        ]);
    }

    /**
     * Free-text filter that hides target rows whose `data-search` attribute does
     * not contain the typed query. Items keep their original positioning; the
     * filter only toggles `display: none`.
     */
    public static function filter(string $target, string $placeholder = 'Filter…'): string
    {
        return self::attrs('filter', ['target' => $target, 'placeholder' => $placeholder]);
    }

    /**
     * Multi-select chip set. Each chip toggles inclusion; rows whose
     * `$attr` (e.g. `data-tag`) does not match any active chip are hidden.
     *
     * @param list<array{value: string, label?: string, count?: int|float}> $items
     */
    public static function chips(string $target, string $attr, array $items): string
    {
        return self::compound('chips', $items, ['target' => $target, 'attr' => $attr]);
    }

    /**
     * Sticky page header that hydrates to the SDK `<PageToolbar>` so SSR
     * collectors share the exact toolbar styling (label uppercase, border-bottom,
     * sticky position) of the React panels (DatabasePanel, LogPanel, …).
     *
     * Pass `$filterTarget` to render a filter input inside the toolbar's
     * actions area; rows whose `data-search` does not contain the typed query
     * are hidden, just like {@see filter()}.
     */
    public static function pageToolbar(
        string $label,
        ?string $filterTarget = null,
        ?string $filterPlaceholder = null,
    ): string {
        $attrs = ['label' => $label];
        if ($filterTarget !== null) {
            $attrs['filter-target'] = $filterTarget;
            $attrs['filter-placeholder'] = $filterPlaceholder ?? 'Filter…';
        }
        return self::attrs('page-toolbar', $attrs, '', 'div');
    }

    /**
     * Tab strip — clicking a tab shows the matching `<section data-adp-tab-panel="$value">`
     * and hides the rest. Useful for Summary/All views over the same data.
     *
     * @param list<array{value: string, label: string}> $items
     */
    public static function tabs(array $items, ?string $default = null): string
    {
        $attrs = $default !== null ? ['default' => $default] : [];
        return self::compound('tabs', $items, $attrs);
    }

    /**
     * @param array<string, scalar|null> $attrs
     */
    private static function compound(
        string $name,
        mixed $payload,
        array $attrs,
        string $label = '',
        string $tag = 'div',
    ): string {
        $attrHtml = '';
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $attrHtml .= sprintf(' data-%s="%s"', self::escapeAttr((string) $key), self::escapeAttr((string) $value));
        }

        $body = '';
        if ($payload !== null) {
            $json = Json::encode($payload);
            // Neutralise stray `</script>` inside string data.
            $body = sprintf(
                '<script type="application/json" %s>%s</script>',
                self::PAYLOAD_ATTR,
                str_replace('</', '<\/', $json),
            );
        }
        if ($label !== '') {
            $body .= self::escapeText($label);
        }

        return sprintf(
            '<%1$s %2$s="%3$s"%4$s>%5$s</%1$s>',
            self::escapeTag($tag),
            self::ATTR_NAME,
            self::escapeAttr($name),
            $attrHtml,
            $body,
        );
    }

    private static function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escapeText(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escapeTag(string $tag): string
    {
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9-]*$/', $tag) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid HTML tag name: %s', $tag));
        }
        return $tag;
    }
}
