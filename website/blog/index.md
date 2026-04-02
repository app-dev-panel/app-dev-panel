---
layout: page
title: Blog
description: "News, tutorials, and deep dives from the ADP team about PHP debugging, framework adapters, and development tools."
---

<script setup>
import BlogIndex from '../.vitepress/theme/components/BlogIndex.vue';

const posts = [
  {
    title: 'Introducing ADP — A Universal PHP Debugging Panel',
    url: '/blog/introducing-adp',
    date: '2026-03-15',
    excerpt: 'Meet ADP, a framework-agnostic debugging panel that works with Yii, Symfony, Laravel, and more. Learn why we built it and how it can supercharge your debugging workflow.',
    tags: ['announcement', 'release'],
    author: 'ADP Team',
    readingTime: '5 min',
  },
  {
    title: 'Building Custom Collectors for Your Domain',
    url: '/blog/custom-collectors',
    date: '2026-03-20',
    excerpt: 'Learn how to extend ADP by writing custom collectors that capture domain-specific data from your application.',
    tags: ['tutorial', 'collectors', 'php'],
    author: 'ADP Team',
    readingTime: '8 min',
  },
  {
    title: 'Real-time Debugging with Server-Sent Events',
    url: '/blog/sse-debugging',
    date: '2026-03-25',
    excerpt: 'Deep dive into how ADP uses SSE to push debug data to your browser in real-time. No more page refreshes.',
    tags: ['deep-dive', 'sse', 'architecture'],
    author: 'ADP Team',
    readingTime: '6 min',
  },
  {
    title: 'AI-Powered Debugging with MCP Server',
    url: '/blog/mcp-server-ai',
    date: '2026-03-27',
    excerpt: 'How ADP integrates with AI assistants through MCP (Model Context Protocol) to provide intelligent debugging assistance.',
    tags: ['ai', 'mcp', 'deep-dive'],
    author: 'ADP Team',
    readingTime: '7 min',
  },
];
</script>

<BlogIndex :posts="posts" description="News, tutorials, and deep dives from the ADP team." rssUrl="/app-dev-panel/feed.xml" />
