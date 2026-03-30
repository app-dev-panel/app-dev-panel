<script setup lang="ts">
import { ref, onMounted } from 'vue';

const stars = ref<string | null>(null);

function formatStars(count: number): string {
    if (count >= 1000) {
        return (count / 1000).toFixed(count >= 10000 ? 0 : 1) + 'k';
    }
    return count.toString();
}

onMounted(async () => {
    try {
        const res = await fetch('https://api.github.com/repos/app-dev-panel/app-dev-panel');
        if (res.ok) {
            const data = await res.json();
            stars.value = formatStars(data.stargazers_count);
        }
    } catch {
        // silently fail
    }
});
</script>

<template>
    <a
        class="github-stars"
        href="https://github.com/app-dev-panel/app-dev-panel"
        target="_blank"
        rel="noopener"
        title="Star on GitHub"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/>
        </svg>
        <span v-if="stars" class="github-stars-count">{{ stars }}</span>
        <span v-else class="github-stars-count">...</span>
    </a>
</template>

<style scoped>
.github-stars {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border: 1px solid var(--vp-c-divider);
    border-radius: 20px;
    color: var(--vp-c-text-1);
    text-decoration: none !important;
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
    transition: all 0.2s ease;
    margin-left: 8px;
}
.github-stars:hover {
    border-color: var(--vp-c-brand-1);
    color: var(--vp-c-brand-1);
    background: var(--vp-c-brand-soft);
}
.github-stars svg {
    flex-shrink: 0;
}
.github-stars-count::after {
    content: '\2B50';
    margin-left: 2px;
    font-size: 12px;
}
@media (max-width: 768px) {
    .github-stars-count {
        display: none;
    }
}
</style>
