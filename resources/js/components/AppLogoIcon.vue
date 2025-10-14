<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        :src="'/exploreponorogormbg.png'"
        alt="Logo"
        class="min-w-64 h-auto mb-8"
        v-bind="$attrs"
    />
</template>
