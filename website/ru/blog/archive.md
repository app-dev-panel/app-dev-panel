---
layout: page
title: Архив
---

<script setup>
import BlogArchive from '../../.vitepress/theme/components/BlogArchive.vue';

const posts = [
  { title: 'Отладка с помощью ИИ через MCP-сервер', url: '/ru/blog/mcp-server-ai', date: '2026-03-27' },
  { title: 'Отладка в реальном времени с SSE', url: '/ru/blog/sse-debugging', date: '2026-03-25' },
  { title: 'Создание пользовательских коллекторов', url: '/ru/blog/custom-collectors', date: '2026-03-20' },
  { title: 'Представляем ADP', url: '/ru/blog/introducing-adp', date: '2026-03-15' },
];
</script>

<BlogArchive :posts="posts" />
