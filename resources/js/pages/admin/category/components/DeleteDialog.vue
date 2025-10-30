<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps<{ open: boolean; name: string }>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
}>();

const isOpen = computed({
    get: () => props.open,
    set: (v) => emit('update:open', v),
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Delete Category</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete <b>{{ name }}</b>? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button variant="outline" @click="isOpen = false">Cancel</Button>
                <Button variant="destructive" @click="emit('confirm')">Delete</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

