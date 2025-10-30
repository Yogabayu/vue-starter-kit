<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import LucideIconPicker from './LucideIconPicker.vue';
import { computed, nextTick, ref, unref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    form: any;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'update:form', value: any): void;
    (e: 'save'): void;
}>();

const isOpen = computed({
    get: () => props.open,
    set: (v) => emit('update:open', v),
});

function deepClone<T>(v: T): T {
    return JSON.parse(JSON.stringify(v));
}

const localForm = ref<any>(deepClone(unref(props.form)));
let syncingFromParent = false;

watch(
    () => unref(props.form),
    (v) => {
        syncingFromParent = true;
        localForm.value = deepClone(v);
        nextTick(() => (syncingFromParent = false));
    },
    { deep: true },
);

watch(
    localForm,
    (v) => {
        if (syncingFromParent) return;
        emit('update:form', deepClone(v));
    },
    { deep: true },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-h-[100vh] overflow-hidden p-0 sm:max-w-md">
            <DialogHeader class="px-6 pt-6 pb-3">
                <DialogTitle>
                    {{ props.form?.id ? 'Edit Category' : 'Add Category' }}
                </DialogTitle>
                <DialogDescription>
                    Manage category information here. <br />
                    <span class="text-red-500">*</span> indicates required
                    fields.
                </DialogDescription>
            </DialogHeader>

            <div class="max-h-[calc(85vh-120px)] overflow-y-auto px-6 pb-4">
                <div class="grid grid-cols-1 gap-4">
                    <FormField v-slot="{ componentField }" name="name">
                        <FormItem>
                            <FormLabel>
                                Name <span class="text-red-500">*</span>
                            </FormLabel>
                            <FormControl>
                                <Input
                                    v-bind="componentField"
                                    v-model="localForm.name"
                                    type="text"
                                    placeholder="Category Name"
                                />
                            </FormControl>
                        </FormItem>
                    </FormField>

                    <!-- <FormField v-slot="{ componentField }" name="icon">
                        <FormItem>
                            <FormLabel> Icon </FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" v-model="localForm.icon" type="text" placeholder="e.g. map-pin" />
                            </FormControl>
                        </FormItem>
                    </FormField> -->
                    <FormField v-slot="{ componentField }" name="icon">
                        <FormItem>
                            <FormLabel>Icon</FormLabel>
                            <FormControl>
                                <LucideIconPicker
                                    v-bind="componentField"
                                    v-model="localForm.icon"
                                />
                            </FormControl>
                        </FormItem>
                    </FormField>
                </div>
            </div>

            <DialogFooter class="px-6 py-4">
                <Button variant="outline" @click="isOpen = false"
                    >Cancel</Button
                >
                <Button @click="emit('save')">Save</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
