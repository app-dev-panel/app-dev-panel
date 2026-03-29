import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { resolve, relative } from 'node:path';
import type { SiteConfig } from 'vitepress';
import matter from 'gray-matter';

interface SidebarItem {
    text?: string;
    link?: string;
    items?: SidebarItem[];
    collapsed?: boolean;
}

/**
 * Strip frontmatter, Vue <script setup> blocks, and custom VitePress containers
 * to produce clean markdown for LLM consumption.
 */
function cleanMarkdown(raw: string): string {
    const { content } = matter(raw);
    return content
        .replace(/<script\b[^>]*>[\s\S]*?<\/script>/g, '')
        .replace(/<[A-Z][A-Za-z]*\b[^/>]*\/>/g, '')
        .replace(/<[A-Z][A-Za-z]*\b[^>]*>[\s\S]*?<\/[A-Z][A-Za-z]*>/g, '')
        .replace(/^:::\s*\w+.*$/gm, '')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

/**
 * Recursively collect all links from sidebar items.
 */
function collectLinks(items: SidebarItem[]): { text: string; link: string }[] {
    const links: { text: string; link: string }[] = [];
    for (const item of items) {
        if (item.link && item.text) {
            links.push({ text: item.text, link: item.link });
        }
        if (item.items) {
            links.push(...collectLinks(item.items));
        }
    }
    return links;
}

/**
 * Recursively collect sidebar sections preserving group structure.
 */
function collectSections(items: SidebarItem[]): { title: string; links: { text: string; link: string }[] }[] {
    const sections: { title: string; links: { text: string; link: string }[] }[] = [];
    for (const item of items) {
        if (item.items && item.text) {
            const links = collectLinks(item.items);
            if (links.length > 0) {
                sections.push({ title: item.text, links });
            }
        }
    }
    return sections;
}

/**
 * Resolve a VitePress link to its source markdown file path.
 */
function resolveMarkdownPath(srcDir: string, link: string): string | null {
    const normalized = link.replace(/^\//, '').replace(/\/$/, '');
    const candidates = [
        resolve(srcDir, `${normalized}.md`),
        resolve(srcDir, normalized, 'index.md'),
    ];
    for (const candidate of candidates) {
        if (existsSync(candidate)) {
            return candidate;
        }
    }
    return null;
}

/**
 * Build the base URL for the deployed site.
 */
function getSiteUrl(siteConfig: SiteConfig): string {
    const hostname = siteConfig.sitemap?.hostname?.replace(/\/$/, '')
        || `https://app-dev-panel.github.io${siteConfig.site.base || '/'}`.replace(/\/$/, '');
    return hostname;
}

/**
 * Generate llms.txt and llms-full.txt in the output directory.
 *
 * Called from VitePress `buildEnd` hook.
 */
export async function generateLlmsTxt(siteConfig: SiteConfig): Promise<void> {
    const { outDir, srcDir } = siteConfig;
    const siteUrl = getSiteUrl(siteConfig);

    // Use English sidebar only (root locale)
    const themeConfig = siteConfig.site.locales?.root?.themeConfig
        ?? siteConfig.site.themeConfig
        ?? {};

    const sidebar = (themeConfig as Record<string, unknown>).sidebar as Record<string, SidebarItem[]> | undefined;
    if (!sidebar) {
        console.warn('[llms-txt] No sidebar config found, skipping generation');
        return;
    }

    // --- llms.txt (concise TOC) ---

    const tocLines: string[] = [
        '# Application Development Panel (ADP)',
        '',
        '> ADP is a universal, framework-agnostic PHP debugging and development panel. It collects runtime data (logs, events, requests, exceptions, database queries) from applications via 28 collectors and provides a web UI with 20+ inspector pages, 40+ API endpoints, and an MCP server for AI assistant integration. Adapters exist for Symfony, Laravel, Yii 3, and Yii 2.',
        '',
    ];

    const allSections: { title: string; links: { text: string; link: string }[] }[] = [];

    for (const [prefix, items] of Object.entries(sidebar)) {
        const sections = collectSections(items);
        allSections.push(...sections);
    }

    for (const section of allSections) {
        tocLines.push(`## ${section.title}`);
        tocLines.push('');
        for (const { text, link } of section.links) {
            const href = `${siteUrl}${link}`;
            tocLines.push(`- [${text}](${href})`);
        }
        tocLines.push('');
    }

    writeFileSync(resolve(outDir, 'llms.txt'), tocLines.join('\n'));
    console.log('[llms-txt] Generated llms.txt');

    // --- llms-full.txt (concatenated docs) ---

    const fullLines: string[] = [
        '# Application Development Panel (ADP)',
        '',
        '> ADP is a universal, framework-agnostic PHP debugging and development panel. It collects runtime data (logs, events, requests, exceptions, database queries) from applications via 28 collectors and provides a web UI with 20+ inspector pages, 40+ API endpoints, and an MCP server for AI assistant integration. Adapters exist for Symfony, Laravel, Yii 3, and Yii 2.',
        '',
    ];

    const seen = new Set<string>();

    for (const section of allSections) {
        for (const { link } of section.links) {
            if (seen.has(link)) continue;
            seen.add(link);

            const mdPath = resolveMarkdownPath(srcDir, link);
            if (!mdPath) continue;

            const raw = readFileSync(mdPath, 'utf-8');
            const cleaned = cleanMarkdown(raw);
            if (cleaned) {
                fullLines.push(cleaned);
                fullLines.push('');
                fullLines.push('---');
                fullLines.push('');
            }
        }
    }

    writeFileSync(resolve(outDir, 'llms-full.txt'), fullLines.join('\n'));
    console.log('[llms-txt] Generated llms-full.txt');
}
