<script setup lang="ts">
import { computed } from 'vue';
import { useData, withBase } from 'vitepress';

interface Post {
    title: string;
    url: string;
    date: string;
    excerpt?: string;
    tags?: string[];
    author?: string;
    readingTime?: string;
    cover?: string;
}

const props = defineProps<{
    posts: Post[];
    title?: string;
    description?: string;
    rssUrl?: string;
}>();

const { lang } = useData();

const sortedPosts = computed(() =>
    [...props.posts].sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime()),
);

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString(lang.value === 'ru' ? 'ru-RU' : 'en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
</script>

<template>
    <div class="blog-index">
        <div style="display: flex; align-items: center; justify-content: space-between">
            <h1>{{ title || (lang === 'ru' ? 'Блог' : 'Blog') }}</h1>
            <a v-if="rssUrl" :href="rssUrl" class="blog-rss" target="_blank" rel="noopener">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="6.18" cy="17.82" r="2.18" />
                    <path d="M4 4.44v2.83c7.03 0 12.73 5.7 12.73 12.73h2.83c0-8.59-6.97-15.56-15.56-15.56zm0 5.66v2.83c3.9 0 7.07 3.17 7.07 7.07h2.83c0-5.47-4.43-9.9-9.9-9.9z" />
                </svg>
                RSS
            </a>
        </div>
        <p v-if="description" class="blog-description">{{ description }}</p>

        <div v-if="sortedPosts.length === 0" style="text-align: center; padding: 48px 0; color: var(--vp-c-text-3)">
            <p>{{ lang === 'ru' ? 'Пока нет записей. Скоро появятся!' : 'No posts yet. Stay tuned!' }}</p>
        </div>

        <a v-for="post in sortedPosts" :key="post.url" :href="withBase(post.url)" class="blog-post-card">
            <div class="blog-meta">
                <span>{{ formatDate(post.date) }}</span>
                <span v-if="post.author">{{ lang === 'ru' ? 'от' : 'by' }} {{ post.author }}</span>
                <span v-if="post.readingTime" class="reading-time">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    {{ post.readingTime }}
                </span>
            </div>
            <h2>{{ post.title }}</h2>
            <p v-if="post.excerpt" class="blog-excerpt">{{ post.excerpt }}</p>
            <div v-if="post.tags?.length" class="blog-tags">
                <span v-for="tag in post.tags" :key="tag" class="blog-tag">{{ tag }}</span>
            </div>
        </a>
    </div>
</template>
