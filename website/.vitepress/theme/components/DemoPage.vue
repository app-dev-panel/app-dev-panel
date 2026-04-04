<script setup lang="ts">
import { onMounted } from 'vue';
import { withBase } from 'vitepress';

onMounted(() => {
    const base = withBase('/');

    // Load panel CSS
    for (const href of [`${base}bundle.css`, `${base}bundle3.css`, `${base}bundle2.css`]) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        document.head.appendChild(link);
    }

    // Load panel JS (module)
    const script = document.createElement('script');
    script.type = 'module';
    script.src = `${base}bundle.js`;
    document.head.appendChild(script);
});
</script>

<template>
    <div
        id="root"
        style="display: flex; flex-direction: column; min-height: 100vh; justify-content: space-between; flex: 1"
    ></div>
</template>
