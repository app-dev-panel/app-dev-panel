---
layout: page
title: Tags
description: "Browse ADP blog posts by tag — find articles on collectors, adapters, AI debugging, SSE, and more."
---

<script setup>
import BlogTags from '../.vitepress/theme/components/BlogTags.vue';

const posts = [
  { title: 'Introducing ADP', url: '/blog/introducing-adp', date: '2026-03-15', tags: ['announcement', 'release'] },
  { title: 'Building Custom Collectors', url: '/blog/custom-collectors', date: '2026-03-20', tags: ['tutorial', 'collectors', 'php'] },
  { title: 'Real-time Debugging with SSE', url: '/blog/sse-debugging', date: '2026-03-25', tags: ['deep-dive', 'sse', 'architecture'] },
  { title: 'AI-Powered Debugging with MCP', url: '/blog/mcp-server-ai', date: '2026-03-27', tags: ['ai', 'mcp', 'deep-dive'] },
];
</script>

<BlogTags :posts="posts" />
