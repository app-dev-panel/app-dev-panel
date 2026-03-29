---
layout: page
title: Теги
---

<script setup>
import BlogTags from '../../.vitepress/theme/components/BlogTags.vue';

const posts = [
  { title: 'Представляем ADP', url: '/ru/blog/introducing-adp', date: '2026-03-15', tags: ['announcement', 'release'] },
  { title: 'Создание пользовательских коллекторов', url: '/ru/blog/custom-collectors', date: '2026-03-20', tags: ['tutorial', 'collectors', 'php'] },
  { title: 'Отладка в реальном времени с SSE', url: '/ru/blog/sse-debugging', date: '2026-03-25', tags: ['deep-dive', 'sse', 'architecture'] },
  { title: 'Отладка с помощью ИИ через MCP', url: '/ru/blog/mcp-server-ai', date: '2026-03-27', tags: ['ai', 'mcp', 'deep-dive'] },
];
</script>

<BlogTags :posts="posts" />
