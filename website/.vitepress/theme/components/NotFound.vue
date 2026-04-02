<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, withBase } from 'vitepress';

const route = useRoute();

const isRussian = computed(() => route.path.startsWith('/ru/') || route.path === '/ru');

const t = computed(() =>
    isRussian.value
        ? {
              code: '404',
              title: 'Утка заблудилась...',
              message: 'Похоже, эта страница уплыла в неизвестном направлении.',
              action: 'На главную',
              home: '/ru/',
          }
        : {
              code: '404',
              title: 'Duck not found',
              message: 'This duck wandered off and could not find the page you were looking for.',
              action: 'Go Home',
              home: '/',
          },
);
</script>

<template>
    <div class="not-found">
        <div class="not-found-container">
            <div class="not-found-duck">
                <img :src="withBase('/duck.svg')" alt="Lost duck" class="duck-img" />
            </div>

            <p class="not-found-code">{{ t.code }}</p>

            <h1 class="not-found-title">{{ t.title }}</h1>

            <p class="not-found-message">{{ t.message }}</p>

            <a :href="withBase(t.home)" class="not-found-action">
                {{ t.action }}
            </a>
        </div>
    </div>
</template>

<style scoped>
.not-found {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - var(--vp-nav-height) - 80px);
    padding: 48px 24px;
    text-align: center;
}

.not-found-container {
    max-width: 400px;
}

.not-found-duck {
    display: flex;
    justify-content: center;
    margin-bottom: 24px;
}

.duck-img {
    width: 120px;
    height: 120px;
    filter: drop-shadow(0 4px 12px rgba(37, 99, 235, 0.15));
    animation: duck-wander 4s ease-in-out infinite;
    opacity: 0.8;
}

@keyframes duck-wander {
    0%,
    100% {
        transform: translateX(0) rotate(0deg);
    }
    25% {
        transform: translateX(-12px) rotate(-5deg);
    }
    75% {
        transform: translateX(12px) rotate(5deg);
    }
}

.not-found-code {
    font-size: 72px;
    font-weight: 700;
    letter-spacing: -0.04em;
    line-height: 1;
    margin: 0 0 8px;
    background: linear-gradient(135deg, var(--vp-c-brand-1) 0%, var(--vp-c-brand-3) 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.not-found-title {
    font-size: 24px;
    font-weight: 600;
    letter-spacing: -0.02em;
    color: var(--vp-c-text-1);
    margin: 0 0 12px;
}

.not-found-message {
    font-size: 15px;
    line-height: 1.6;
    color: var(--vp-c-text-2);
    margin: 0 0 32px;
}

.not-found-action {
    display: inline-block;
    padding: 10px 28px;
    font-size: 14px;
    font-weight: 500;
    color: #fff;
    background: var(--vp-c-brand-1);
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.25s ease;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
}

.not-found-action:hover {
    background: var(--vp-c-brand-2);
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
    transform: translateY(-1px);
}

@media (max-width: 640px) {
    .duck-img {
        width: 96px;
        height: 96px;
    }

    .not-found-code {
        font-size: 56px;
    }

    .not-found-title {
        font-size: 20px;
    }

    .not-found-message {
        font-size: 14px;
    }
}
</style>
