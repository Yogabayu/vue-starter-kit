<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Toaster } from "@/components/ui/sonner" 
import ThemeFab from '@/components/customizer/ThemeFab.vue'

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
        
        <Toaster position="top-right" rich-colors theme="system" />
        
  <ThemeFab />
    </AppLayout>
</template>

<style>
 button:hover {
    cursor: pointer;
 }
</style>