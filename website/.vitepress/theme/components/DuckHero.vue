<script setup lang="ts">
import { withBase } from 'vitepress';
</script>

<template>
    <div class="duck-hero">
        <img :src="withBase('/duck.svg')" alt="ADP Duck mascot" />
    </div>
</template>
