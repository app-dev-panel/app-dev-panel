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
 * Three flavours of slots:
 *
 *   - `json($name, $payload)`  — complex object/array; payload travels in an inert
 *                                `<script type="application/json" data-adp-payload>`
 *                                child so neither HTML escaping nor double encoding
 *                                applies.
 *   - `attrs($name, $attrs, $label)` — simple scalar props (path, fqcn, line, …)
 *                                travel in `data-*` attributes; `$label` becomes the
 *                                no-JS / hydration-fallback text content.
 *   - `text($name, $content)`   — payload is plain text (e.g. SQL, code) and lives
 *                                in the element's text content.
 */
final class Slot
{
    public const ATTR_NAME = 'data-adp-slot';
    public const PAYLOAD_ATTR = 'data-adp-payload';

    /**
     * Slot whose payload is a structured value rendered as JSON inside an
     * inert `<script type="application/json">` child.
     *
     * Example:
     * ```
     * <?= Slot::json('json', $context) ?>
     * ```
     * produces (panel renders <JsonRenderer value=... />):
     * ```
     * <div data-adp-slot="json">
     *   <script type="application/json" data-adp-payload>{"foo":"bar"}</script>
     * </div>
     * ```
     */
    public static function json(string $name, mixed $payload, string $tag = 'div'): string
    {
        $json = Json::encode($payload);
        // The only sequence that is unsafe inside a <script> body is "</" — escape
        // it so a stray "</script>" inside string data can't terminate the block.
        $safe = str_replace('</', '<\/', $json);

        return sprintf(
            '<%1$s %2$s="%3$s"><script type="application/json" %4$s>%5$s</script></%1$s>',
            self::escapeTag($tag),
            self::ATTR_NAME,
            self::escapeAttr($name),
            self::PAYLOAD_ATTR,
            $safe,
        );
    }

    /**
     * Slot whose props travel in `data-*` attributes; `$label` is the
     * pre-hydration fallback that's visible when JS is disabled.
     *
     * Example:
     * ```
     * <?= Slot::attrs('file-link', ['path' => $line], $line) ?>
     * <?= Slot::attrs('class-name', ['fqcn' => $event['class'], 'method' => 'handle'], shortName($event['class'])) ?>
     * ```
     *
     * @param array<string, scalar|null> $attrs
     */
    public static function attrs(string $name, array $attrs, string $label = '', string $tag = 'span'): string
    {
        $attrHtml = '';
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $attrHtml .= sprintf(' data-%s="%s"', self::escapeAttr((string) $key), self::escapeAttr((string) $value));
        }

        return sprintf(
            '<%1$s %2$s="%3$s"%4$s>%5$s</%1$s>',
            self::escapeTag($tag),
            self::ATTR_NAME,
            self::escapeAttr($name),
            $attrHtml,
            self::escapeText($label),
        );
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
        // Tags are always developer-supplied and must be a simple identifier.
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9-]*$/', $tag) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid HTML tag name: %s', $tag));
        }
        return $tag;
    }
}
