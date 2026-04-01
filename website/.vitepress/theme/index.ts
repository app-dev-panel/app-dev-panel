import DefaultTheme from 'vitepress/theme';
import type { Theme } from 'vitepress';
import { defineComponent, h, onMounted, watch, nextTick } from 'vue';
import { useRoute } from 'vitepress';
import { enhanceAppWithTabs } from 'vitepress-plugin-tabs/client';
import mediumZoom from 'medium-zoom';
import BlogIndex from './components/BlogIndex.vue';
import BlogPost from './components/BlogPost.vue';
import BlogTags from './components/BlogTags.vue';
import BlogArchive from './components/BlogArchive.vue';
import DuckHero from './components/DuckHero.vue';
import CopyButton from './components/CopyButton.vue';
import GitHubStars from './components/GitHubStars.vue';
import './style.css';

function initZoom() {
    mediumZoom('.vp-doc img:not(.no-zoom):not(.medium-zoom-image)', {
        background: 'rgba(0, 0, 0, 0.85)',
    });
}

export default {
    extends: DefaultTheme,
    Layout: defineComponent({
        setup() {
            const route = useRoute();
            onMounted(() => {
                initZoom();
                watch(
                    () => route.path,
                    () => nextTick(initZoom),
                );
            });
            return () =>
                h(DefaultTheme.Layout, null, {
                    'nav-bar-content-after': () => h(GitHubStars),
                });
        },
    }),
    enhanceApp({ app }) {
        enhanceAppWithTabs(app);
        app.component('BlogIndex', BlogIndex);
        app.component('BlogPost', BlogPost);
        app.component('BlogTags', BlogTags);
        app.component('BlogArchive', BlogArchive);
        app.component('DuckHero', DuckHero);
        app.component('CopyButton', CopyButton);
    },
} satisfies Theme;
