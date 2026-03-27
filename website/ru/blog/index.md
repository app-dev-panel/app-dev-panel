---
layout: page
title: Блог
---

<script setup>
import BlogIndex from '../../.vitepress/theme/components/BlogIndex.vue';

const posts = [
  {
    title: 'Представляем ADP — Универсальная панель отладки PHP',
    url: '/ru/blog/introducing-adp',
    date: '2026-03-15',
    excerpt: 'Знакомьтесь с ADP — фреймворк-независимой панелью отладки, которая работает с Yii, Symfony, Laravel и другими. Узнайте, зачем мы её создали и как она ускорит вашу отладку.',
    tags: ['announcement', 'release'],
    author: 'ADP Team',
    readingTime: '5 мин',
  },
  {
    title: 'Создание пользовательских коллекторов для вашего домена',
    url: '/ru/blog/custom-collectors',
    date: '2026-03-20',
    excerpt: 'Узнайте, как расширить ADP, написав собственные коллекторы, которые захватывают данные, специфичные для вашего приложения.',
    tags: ['tutorial', 'collectors', 'php'],
    author: 'ADP Team',
    readingTime: '8 мин',
  },
  {
    title: 'Отладка в реальном времени с Server-Sent Events',
    url: '/ru/blog/sse-debugging',
    date: '2026-03-25',
    excerpt: 'Глубокое погружение в то, как ADP использует SSE для передачи отладочных данных в браузер в реальном времени. Больше никаких обновлений страницы.',
    tags: ['deep-dive', 'sse', 'architecture'],
    author: 'ADP Team',
    readingTime: '6 мин',
  },
  {
    title: 'Отладка с помощью ИИ через MCP-сервер',
    url: '/ru/blog/mcp-server-ai',
    date: '2026-03-27',
    excerpt: 'Как ADP интегрируется с ИИ-ассистентами через MCP (Model Context Protocol) для интеллектуальной помощи в отладке.',
    tags: ['ai', 'mcp', 'deep-dive'],
    author: 'ADP Team',
    readingTime: '7 мин',
  },
];
</script>

<BlogIndex :posts="posts" description="Новости, руководства и подробные разборы от команды ADP." rssUrl="/app-dev-panel/feed.xml" />
