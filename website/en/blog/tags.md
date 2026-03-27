---
layout: page
title: Tags
---

<script setup>
import BlogTags from '../../.vitepress/theme/components/BlogTags.vue';

const posts = [
  { title: 'Introducing ADP', url: '/en/blog/introducing-adp', date: '2026-03-15', tags: ['announcement', 'release'] },
  { title: 'Building Custom Collectors', url: '/en/blog/custom-collectors', date: '2026-03-20', tags: ['tutorial', 'collectors', 'php'] },
  { title: 'Real-time Debugging with SSE', url: '/en/blog/sse-debugging', date: '2026-03-25', tags: ['deep-dive', 'sse', 'architecture'] },
  { title: 'AI-Powered Debugging with MCP', url: '/en/blog/mcp-server-ai', date: '2026-03-27', tags: ['ai', 'mcp', 'deep-dive'] },
];
</script>

<BlogTags :posts="posts" />
