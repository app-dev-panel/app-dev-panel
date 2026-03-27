<script setup lang="ts">
import { computed } from 'vue';
import { useData, withBase } from 'vitepress';

interface Post {
    title: string;
    url: string;
    date: string;
    tags?: string[];
}

const props = defineProps<{
    posts: Post[];
}>();

const { lang } = useData();

const tagMap = computed(() => {
    const map = new Map<string, Post[]>();
    for (const post of props.posts) {
        for (const tag of post.tags ?? []) {
            if (!map.has(tag)) map.set(tag, []);
            map.get(tag)!.push(post);
        }
    }
    return new Map([...map.entries()].sort((a, b) => b[1].length - a[1].length));
});

const allTags = computed(() => [...tagMap.value.keys()]);

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString(lang.value === 'ru' ? 'ru-RU' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <div class="blog-tags-page">
        <h1>{{ lang === 'ru' ? 'Теги' : 'Tags' }}</h1>

        <div class="blog-tags-cloud">
            <a v-for="tag in allTags" :key="tag" :href="'#' + tag" class="blog-tag">
                {{ tag }} ({{ tagMap.get(tag)!.length }})
            </a>
        </div>

        <div v-for="[tag, posts] in tagMap" :key="tag" class="blog-tags-section">
            <h2 :id="tag">{{ tag }}</h2>
            <div v-for="post in posts" :key="post.url" class="blog-archive-item">
                <span class="blog-archive-date">{{ formatDate(post.date) }}</span>
                <span class="blog-archive-title">
                    <a :href="withBase(post.url)">{{ post.title }}</a>
                </span>
            </div>
        </div>
    </div>
</template>
