<script setup lang="ts">
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md dark:bg-sidebar-primary  text-sidebar-primary-foreground"
    >
        <img
            :src="'/exploreponorogormbg.png'"
            alt="Logo"
            class="size-10 fill-current text-white dark:text-black"
            v-bind="$attrs"
        />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold"
            >exploreponorogo</span
        >
    </div>
</template>
