import { Feed } from 'feed';
import matter from 'gray-matter';
import fs from 'node:fs';
import path from 'node:path';
import type { SiteConfig } from 'vitepress';

const SITE_URL = 'https://app-dev-panel.github.io/app-dev-panel';
const BLOG_DIR = 'blog';

interface BlogPost {
    title: string;
    date: Date;
    description: string;
    author: string;
    url: string;
    tags: string[];
}

function getBlogPosts(srcDir: string): BlogPost[] {
    const blogDir = path.resolve(srcDir, BLOG_DIR);
    const posts: BlogPost[] = [];

    if (!fs.existsSync(blogDir)) {
        return posts;
    }

    const files = fs.readdirSync(blogDir).filter((f) => f.endsWith('.md') && !['index.md', 'archive.md', 'tags.md'].includes(f));

    for (const file of files) {
        const filePath = path.join(blogDir, file);
        const content = fs.readFileSync(filePath, 'utf-8');
        const { data } = matter(content);

        if (!data.title || !data.date) {
            continue;
        }

        const slug = file.replace(/\.md$/, '');
        posts.push({
            title: data.title,
            date: new Date(data.date),
            description: data.description || '',
            author: data.author || 'ADP Team',
            url: `${SITE_URL}/blog/${slug}.html`,
            tags: data.tags || [],
        });
    }

    posts.sort((a, b) => b.date.getTime() - a.date.getTime());
    return posts;
}

export async function generateFeed(siteConfig: SiteConfig): Promise<void> {
    const posts = getBlogPosts(siteConfig.srcDir);

    if (posts.length === 0) {
        return;
    }

    const feed = new Feed({
        title: 'ADP Blog',
        description: 'News, tutorials, and deep dives from the ADP team about PHP debugging, framework adapters, and development tools.',
        id: SITE_URL,
        link: `${SITE_URL}/blog/`,
        language: 'en',
        image: `${SITE_URL}/duck.svg`,
        favicon: `${SITE_URL}/duck.svg`,
        copyright: 'Copyright © 2024-present ADP Contributors',
        feedLinks: {
            rss: `${SITE_URL}/feed.xml`,
            atom: `${SITE_URL}/feed.atom`,
        },
        author: {
            name: 'ADP Team',
            link: SITE_URL,
        },
    });

    for (const post of posts) {
        feed.addItem({
            title: post.title,
            id: post.url,
            link: post.url,
            description: post.description,
            date: post.date,
            author: [{ name: post.author }],
            category: post.tags.map((tag) => ({ name: tag })),
        });
    }

    const outDir = siteConfig.outDir;
    fs.writeFileSync(path.join(outDir, 'feed.xml'), feed.rss2());
    fs.writeFileSync(path.join(outDir, 'feed.atom'), feed.atom1());
}
