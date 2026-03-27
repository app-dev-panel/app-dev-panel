<script setup lang="ts">
import { computed } from 'vue';
import { useData, withBase } from 'vitepress';

interface Post {
    title: string;
    url: string;
    date: string;
}

const props = defineProps<{
    posts: Post[];
}>();

const { lang } = useData();

const grouped = computed(() => {
    const sorted = [...props.posts].sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
    const groups = new Map<number, Post[]>();
    for (const post of sorted) {
        const year = new Date(post.date).getFullYear();
        if (!groups.has(year)) groups.set(year, []);
        groups.get(year)!.push(post);
    }
    return groups;
});

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString(lang.value === 'ru' ? 'ru-RU' : 'en-US', {
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <div class="blog-archive">
        <h1>{{ lang === 'ru' ? 'Архив' : 'Archive' }}</h1>

        <div v-for="[year, posts] in grouped" :key="year">
            <h2 class="blog-archive-year">{{ year }}</h2>
            <div v-for="post in posts" :key="post.url" class="blog-archive-item">
                <span class="blog-archive-date">{{ formatDate(post.date) }}</span>
                <span class="blog-archive-title">
                    <a :href="withBase(post.url)">{{ post.title }}</a>
                </span>
            </div>
        </div>
    </div>
</template>
