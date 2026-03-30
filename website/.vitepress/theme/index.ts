import DefaultTheme from 'vitepress/theme';
import type { Theme } from 'vitepress';
import { enhanceAppWithTabs } from 'vitepress-plugin-tabs/client';
import BlogIndex from './components/BlogIndex.vue';
import BlogPost from './components/BlogPost.vue';
import BlogTags from './components/BlogTags.vue';
import BlogArchive from './components/BlogArchive.vue';
import DuckHero from './components/DuckHero.vue';
import './style.css';

export default {
    extends: DefaultTheme,
    enhanceApp({ app }) {
        enhanceAppWithTabs(app);
        app.component('BlogIndex', BlogIndex);
        app.component('BlogPost', BlogPost);
        app.component('BlogTags', BlogTags);
        app.component('BlogArchive', BlogArchive);
        app.component('DuckHero', DuckHero);
    },
} satisfies Theme;
