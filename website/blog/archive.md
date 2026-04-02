---
layout: page
title: Archive
description: "Chronological archive of all ADP blog posts about PHP debugging, collectors, adapters, and development tools."
---

<script setup>
import BlogArchive from '../.vitepress/theme/components/BlogArchive.vue';

const posts = [
  { title: 'AI-Powered Debugging with MCP Server', url: '/blog/mcp-server-ai', date: '2026-03-27' },
  { title: 'Real-time Debugging with SSE', url: '/blog/sse-debugging', date: '2026-03-25' },
  { title: 'Building Custom Collectors', url: '/blog/custom-collectors', date: '2026-03-20' },
  { title: 'Introducing ADP', url: '/blog/introducing-adp', date: '2026-03-15' },
];
</script>

<BlogArchive :posts="posts" />
