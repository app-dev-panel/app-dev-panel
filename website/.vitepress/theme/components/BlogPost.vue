<script setup lang="ts">
import { useData, withBase } from 'vitepress';

interface NavLink {
    title: string;
    url: string;
}

const props = defineProps<{
    title: string;
    date: string;
    author?: string;
    authorAvatar?: string;
    tags?: string[];
    readingTime?: string;
    prev?: NavLink;
    next?: NavLink;
}>();

const { lang } = useData();

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString(lang.value === 'ru' ? 'ru-RU' : 'en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
</script>

<template>
    <div class="blog-post-header">
        <h1>{{ title }}</h1>
        <div class="blog-post-meta">
            <div v-if="author" class="author">
                <img v-if="authorAvatar" :src="authorAvatar" :alt="author" />
                <span>{{ author }}</span>
            </div>
            <span>{{ formatDate(date) }}</span>
            <span v-if="readingTime" class="reading-time">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                </svg>
                {{ readingTime }}
            </span>
        </div>
        <div v-if="tags?.length" class="blog-tags" style="margin-top: 12px">
            <span v-for="tag in tags" :key="tag" class="blog-tag">{{ tag }}</span>
        </div>
    </div>

    <slot />

    <div v-if="prev || next" class="blog-post-nav">
        <a v-if="prev" :href="withBase(prev.url)">
            <span class="label">{{ lang === 'ru' ? 'Предыдущая' : 'Previous' }}</span>
            <span class="title">{{ prev.title }}</span>
        </a>
        <span v-else />
        <a v-if="next" :href="withBase(next.url)" style="text-align: right">
            <span class="label">{{ lang === 'ru' ? 'Следующая' : 'Next' }}</span>
            <span class="title">{{ next.title }}</span>
        </a>
    </div>
</template>
