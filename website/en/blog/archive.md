---
layout: page
title: Archive
---

<script setup>
import BlogArchive from '../../.vitepress/theme/components/BlogArchive.vue';

const posts = [
  { title: 'AI-Powered Debugging with MCP Server', url: '/en/blog/mcp-server-ai', date: '2026-03-27' },
  { title: 'Real-time Debugging with SSE', url: '/en/blog/sse-debugging', date: '2026-03-25' },
  { title: 'Building Custom Collectors', url: '/en/blog/custom-collectors', date: '2026-03-20' },
  { title: 'Introducing ADP', url: '/en/blog/introducing-adp', date: '2026-03-15' },
];
</script>

<BlogArchive :posts="posts" />
