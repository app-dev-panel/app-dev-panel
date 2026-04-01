/**
 * Markdown-it plugin for packagist package badges.
 *
 * Usage in markdown:
 *   <pkg>app-dev-panel/kernel</pkg>
 *
 * Renders as:
 *   <a href="https://packagist.org/packages/app-dev-panel/kernel" target="_blank" class="pkg-ref">
 *     app-dev-panel/kernel
 *     <img class="pkg-badge" src="https://img.shields.io/packagist/v/app-dev-panel/kernel?style=flat-square&label=" alt="version" loading="lazy" />
 *     <img class="pkg-badge" src="https://img.shields.io/packagist/dt/app-dev-panel/kernel?style=flat-square&label=&color=blue" alt="downloads" loading="lazy" />
 *     <span class="pkg-ref-tooltip">
 *       <code class="pkg-ref-tooltip-name">app-dev-panel/kernel</code>
 *       <span class="pkg-ref-tooltip-desc">View on Packagist</span>
 *       <span class="pkg-ref-tooltip-badges">
 *         <img src="https://img.shields.io/packagist/v/app-dev-panel/kernel?style=flat-square" alt="version" loading="lazy" />
 *         <img src="https://img.shields.io/packagist/dt/app-dev-panel/kernel?style=flat-square" alt="downloads" loading="lazy" />
 *         <img src="https://img.shields.io/packagist/l/app-dev-panel/kernel?style=flat-square" alt="license" loading="lazy" />
 *         <img src="https://img.shields.io/packagist/php-v/app-dev-panel/kernel?style=flat-square" alt="php version" loading="lazy" />
 *       </span>
 *     </span>
 *   </a>
 */
import type MarkdownIt from 'markdown-it';
import type StateInline from 'markdown-it/lib/rules_inline/state_inline.mjs';
import type StateBlock from 'markdown-it/lib/rules_block/state_block.mjs';

function escapeHtml(str: string): string {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function renderPkgRefHtml(pkg: string): string {
    const escaped = escapeHtml(pkg);
    const packagistUrl = `https://packagist.org/packages/${escaped}`;
    const shieldsBase = `https://img.shields.io/packagist`;

    const inlineBadges =
        `<img class="pkg-badge" src="${shieldsBase}/v/${escaped}?style=flat-square&amp;label=" alt="version" loading="lazy" />` +
        `<img class="pkg-badge" src="${shieldsBase}/dt/${escaped}?style=flat-square&amp;label=&amp;color=blue" alt="downloads" loading="lazy" />`;

    const tooltipBadges =
        `<span class="pkg-ref-tooltip-badges">` +
        `<img src="${shieldsBase}/v/${escaped}?style=flat-square" alt="version" loading="lazy" />` +
        `<img src="${shieldsBase}/dt/${escaped}?style=flat-square" alt="downloads" loading="lazy" />` +
        `<img src="${shieldsBase}/l/${escaped}?style=flat-square" alt="license" loading="lazy" />` +
        `<img src="${shieldsBase}/php-v/${escaped}?style=flat-square" alt="php version" loading="lazy" />` +
        `</span>`;

    const tooltip =
        `<span class="pkg-ref-tooltip">` +
        `<code class="pkg-ref-tooltip-name">${escaped}</code>` +
        `<span class="pkg-ref-tooltip-desc">View on Packagist</span>` +
        tooltipBadges +
        `</span>`;

    return `<a href="${packagistUrl}" target="_blank" class="pkg-ref">${escaped}${inlineBadges}${tooltip}</a>`;
}

export function pkgLinkPlugin(md: MarkdownIt): void {
    const openTag = '<pkg>';
    const closeTag = '</pkg>';

    // Block rule: <pkg>...</pkg> at line start
    md.block.ruler.before('html_block', 'pkg_block', (state: StateBlock, startLine: number, _endLine: number, silent: boolean) => {
        const pos = state.bMarks[startLine] + state.tShift[startLine];
        const max = state.eMarks[startLine];
        const line = state.src.slice(pos, max);

        if (!line.startsWith(openTag)) return false;

        const closeIdx = line.indexOf(closeTag);
        if (closeIdx === -1) return false;

        if (silent) return true;

        const content = line.slice(openTag.length, closeIdx).trim();
        const afterTag = line.slice(closeIdx + closeTag.length);
        const renderedHtml = renderPkgRefHtml(content);

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

    // Inline rule: <pkg>...</pkg> within text
    md.inline.ruler.before('html_inline', 'pkg_ref', (state: StateInline, silent: boolean) => {
        if (state.src.slice(state.pos, state.pos + openTag.length) !== openTag) {
            return false;
        }

        const closeIdx = state.src.indexOf(closeTag, state.pos + openTag.length);
        if (closeIdx === -1) return false;

        if (silent) return true;

        const token = state.push('pkg_ref', '', 0);
        token.content = state.src.slice(state.pos + openTag.length, closeIdx).trim();

        state.pos = closeIdx + closeTag.length;
        return true;
    });

    // Inline renderer
    md.renderer.rules['pkg_ref'] = (tokens, idx) => {
        return renderPkgRefHtml(tokens[idx].content);
    };
}
