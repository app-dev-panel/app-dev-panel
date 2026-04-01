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

function setupClassRefTooltips() {
    document.addEventListener(
        'mouseenter',
        (e) => {
            const ref = (e.target as Element).closest?.('.class-ref, .pkg-ref');
            if (!ref) return;

            const tip = ref.querySelector('.class-ref-tooltip, .pkg-ref-tooltip') as HTMLElement;
            if (!tip) return;

            tip.style.left = '0';
            tip.style.top = '0';
            tip.style.maxWidth = '';
            tip.classList.add('is-visible');

            const rect = ref.getBoundingClientRect();
            const tipRect = tip.getBoundingClientRect();
            const gap = 8;

            let top = rect.top - tipRect.height - gap;
            if (top < 0) top = rect.bottom + gap;
            tip.style.top = top + 'px';

            const refCenter = rect.left + rect.width / 2;
            let left = refCenter - tipRect.width / 2;
            if (left + tipRect.width > window.innerWidth - 8) {
                left = window.innerWidth - tipRect.width - 8;
            }
            if (left < 8) left = 8;
            tip.style.left = left + 'px';
        },
        true,
    );

    document.addEventListener(
        'mouseleave',
        (e) => {
            const ref = (e.target as Element).closest?.('.class-ref, .pkg-ref');
            if (!ref) return;
            const tip = ref.querySelector('.class-ref-tooltip, .pkg-ref-tooltip') as HTMLElement;
            if (tip) tip.classList.remove('is-visible');
        },
        true,
    );
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

        if (typeof window !== 'undefined') {
            setupClassRefTooltips();
        }
    },
} satisfies Theme;
