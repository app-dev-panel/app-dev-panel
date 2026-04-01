/**
 * Markdown-it plugin for inline class references with tooltips.
 *
 * Usage in markdown:
 *   <class>\AppDevPanel\Kernel\Collector\LogCollector</class>
 *
 * Renders as:
 *   <a href="https://github.com/..." class="class-ref">
 *     LogCollector
 *     <span class="class-ref-tooltip">
 *       <code class="class-ref-tooltip-fqcn">AppDevPanel\Kernel\Collector\LogCollector</code>
 *       <span class="class-ref-tooltip-desc">Collects Log data during application lifecycle.</span>
 *       <span class="class-ref-tooltip-meta">Kernel · class · implements SummaryCollectorInterface</span>
 *     </span>
 *   </a>
 */
import type MarkdownIt from 'markdown-it';
import type StateInline from 'markdown-it/lib/rules_inline/state_inline.mjs';
import type StateBlock from 'markdown-it/lib/rules_block/state_block.mjs';
import { readFileSync } from 'fs';
import { resolve } from 'path';

interface ClassEntry {
    fqcn: string;
    short: string;
    type: string;
    library: string;
    description: string;
    github: string;
    path: string;
    modifier?: string;
    extends?: string;
    implements?: string[];
}

let registry: Record<string, ClassEntry> = {};

function loadRegistry(): void {
    if (Object.keys(registry).length > 0) return;
    try {
        const jsonPath = resolve(__dirname, 'class-registry.json');
        registry = JSON.parse(readFileSync(jsonPath, 'utf-8'));
    } catch {
        console.warn('class-link: class-registry.json not found, run: php generate-class-registry.php');
        registry = {};
    }
}

function escapeHtml(str: string): string {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function stripNamespace(fqcn: string): string {
    const idx = fqcn.lastIndexOf('\\');
    return idx !== -1 ? fqcn.slice(idx + 1) : fqcn;
}

function normalizeFqcn(raw: string): string {
    let fqcn = raw.trim();
    if (!fqcn.startsWith('\\')) fqcn = '\\' + fqcn;
    // Remove leading backslash for registry lookup (registry uses AppDevPanel\... not \AppDevPanel\...)
    return fqcn.startsWith('\\') ? fqcn.slice(1) : fqcn;
}

function renderClassRefHtml(fqcn: string): string {
    const normalized = normalizeFqcn(fqcn);
    const short = stripNamespace(normalized);
    const entry = registry[normalized];

    if (entry) {
        let meta = entry.library + ' · ' + entry.type;
        if (entry.modifier) meta = entry.modifier + ' ' + meta;
        if (entry.extends) meta += ' · extends ' + entry.extends;
        if (entry.implements && entry.implements.length > 0) {
            meta += ' · implements ' + entry.implements.join(', ');
        }

        const tooltip =
            `<span class="class-ref-tooltip">` +
            `<code class="class-ref-tooltip-fqcn">${escapeHtml(entry.fqcn)}</code>` +
            `<span class="class-ref-tooltip-desc">${escapeHtml(entry.description)}</span>` +
            `<span class="class-ref-tooltip-meta">${escapeHtml(meta)}</span>` +
            `</span>`;

        return `<a href="${escapeHtml(entry.github)}" target="_blank" class="class-ref">${escapeHtml(short)}${tooltip}</a>`;
    }

    // Fallback: no entry found — show short name with FQN tooltip
    const tooltip =
        `<span class="class-ref-tooltip">` +
        `<code class="class-ref-tooltip-fqcn">${escapeHtml(normalized)}</code>` +
        `</span>`;
    return `<span class="class-ref class-ref-unlinked">${escapeHtml(short)}${tooltip}</span>`;
}

export function classLinkPlugin(md: MarkdownIt): void {
    loadRegistry();

    const openTag = '<class>';
    const closeTag = '</class>';

    // Block rule: <class>...</class> at line start (followed by more text)
    md.block.ruler.before('html_block', 'class_block', (state: StateBlock, startLine: number, _endLine: number, silent: boolean) => {
        const pos = state.bMarks[startLine] + state.tShift[startLine];
        const max = state.eMarks[startLine];
        const line = state.src.slice(pos, max);

        if (!line.startsWith(openTag)) return false;

        const closeIdx = line.indexOf(closeTag);
        if (closeIdx === -1) return false;

        if (silent) return true;

        const content = line.slice(openTag.length, closeIdx).trim();
        const afterTag = line.slice(closeIdx + closeTag.length);
        const renderedHtml = renderClassRefHtml(content);

        const openToken = state.push('paragraph_open', 'p', 1);
        openToken.map = [startLine, startLine + 1];

        const inlineToken = state.push('inline', '', 0);
        inlineToken.content = renderedHtml + afterTag;
        inlineToken.map = [startLine, startLine + 1];
        inlineToken.children = [];

        state.push('paragraph_close', 'p', -1);

        state.line = startLine + 1;
        return true;
    });

    // Inline rule: <class>...</class> within text
    md.inline.ruler.before('html_inline', 'class_ref', (state: StateInline, silent: boolean) => {
        if (state.src.slice(state.pos, state.pos + openTag.length) !== openTag) {
            return false;
        }

        const closeIdx = state.src.indexOf(closeTag, state.pos + openTag.length);
        if (closeIdx === -1) return false;

        if (silent) return true;

        const token = state.push('class_ref', '', 0);
        token.content = state.src.slice(state.pos + openTag.length, closeIdx).trim();

        state.pos = closeIdx + closeTag.length;
        return true;
    });

    // Inline renderer
    md.renderer.rules['class_ref'] = (tokens, idx) => {
        return renderClassRefHtml(tokens[idx].content);
    };
}
