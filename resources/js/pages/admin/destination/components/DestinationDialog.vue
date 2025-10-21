<script setup lang="ts">
import { computed, nextTick, onMounted, ref, unref, watch } from 'vue';
// import { toast } from "vue-sonner"
// import { route } from "ziggy-js"

// Shadcn components
import { Button } from '@/components/ui/button';
import { route } from 'ziggy-js';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
} from '@/components/ui/command';
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
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Textarea } from '@/components/ui/textarea';

// Lucide icons
import { Check, Trash2 } from 'lucide-vue-next';
const fileEl = ref<HTMLInputElement | null>(null);


type ApiStyle = {
    code: string;
    name: string;
};

const districts = ref([] as ApiStyle[]);
const selectedDistrict = ref();
const villages = ref([] as ApiStyle[]);

//mounted
onMounted(() => {
    fetch(route('districts.index'))
        .then((res) => res.json())
        .then((data) => {
            districts.value = data.data;
            console.log(districts.value);
            
        })
        .catch((err) => {
            console.error('Error fetching districts data:', err);
        });
});

//district
async function getVillages(idDistricts: string) {
    try {
        const response = await fetch(`/proxy/villages/${idDistricts}`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        villages.value = data.data;
    } catch (error) {
        console.error('Error fetching villages data:', error);
    }
}

// Props & emits
const props = defineProps<{
    open: boolean;
    form: any;
    categories: Array<{ id: number; name: string }>;
    images: Array<any>;
    isUploading: boolean;
    uploadFile: File | null;
    uploadCaption: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'update:form', value: any): void;
    (e: 'save'): void;
    (e: 'file-change', event: Event): void;
    (e: 'delete-image', item: any): void;
    (e: 'set-cover', item: any): void;
    (e: 'toggle-category', id: number): void;
    (e: 'remove-category', id: number): void;
    (e: 'update:uploadCaption', value: string): void;
}>();

// two-way binding untuk dialog
const isOpen = computed({
    get: () => props.open,
    set: (val) => emit('update:open', val),
});

// computed helper
const selectedCategoryIds = computed(() => props.form?.categories ?? []);
const selectedCategories = computed(() => {
    const map = new Map(props.categories.map((c) => [c.id, c]));
    return selectedCategoryIds.value
        .map((id: any) => map.get(id))
        .filter(Boolean);
});

// no single-file preview: images list shows pending and persisted

// Create a local, editable copy of the incoming form.
// Unwrap refs to avoid cloning Vue Ref/Proxy objects.
function deepClone<T>(v: T): T {
    // Form only contains plain data; JSON clone is safe here
    return JSON.parse(JSON.stringify(v));
}

const localForm = ref(deepClone(unref(props.form)));
let syncingFromParent = false;
watch(
    () => unref(props.form),
    (newVal) => {
        syncingFromParent = true;
        localForm.value = deepClone(newVal);
        // release the lock on next tick to avoid echoing back
        nextTick(() => {
            syncingFromParent = false;
        });
    },
    { deep: true },
);

// propagate local form edits back to parent so parent payload stays in sync
watch(
    localForm,
    (v) => {
        if (syncingFromParent) return;
        emit('update:form', deepClone(v));
    },
    { deep: true },
);

const localCaption = ref(props.uploadCaption);

// kalau parent kirim prop baru, sinkronkan ulang
watch(
    () => props.uploadCaption,
    (v) => (localCaption.value = v),
);

// kirim balik perubahan ke parent
watch(localCaption, (v) => emit('update:uploadCaption', v));
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-h-[100vh] overflow-hidden p-0 sm:max-w-3xl">
            <DialogHeader class="px-6 pt-6 pb-3">
                <DialogTitle>
                    {{ props.form.id ? 'Edit Destination' : 'Add Destination' }}
                </DialogTitle>
                <DialogDescription>
                    Kelola destinasi, kategori dan gambar
                </DialogDescription>
            </DialogHeader>

            <div class="max-h-[calc(85vh-120px)] overflow-y-auto px-6 pb-4">
                <FormField name="name">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormItem>
                            <FormLabel>Name</FormLabel>
                            <FormControl>
                                <Input
                                    v-model="localForm.name"
                                    type="text"
                                    placeholder="Destination Name"
                                />
                            </FormControl>
                        </FormItem>

                        <FormItem>
                            <FormLabel>Category</FormLabel>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <FormControl>
                                        <Button
                                            variant="outline"
                                            class="w-full justify-between"
                                        >
                                            <span
                                                v-if="selectedCategories.length"
                                            >
                                                {{
                                                    selectedCategories
                                                        .map((c: any) => c.name)
                                                        .join(', ')
                                                }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                            >
                                                Select categories...
                                            </span>
                                        </Button>
                                    </FormControl>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-[--radix-popover-trigger-width] p-0"
                                    align="start"
                                >
                                    <Command>
                                        <CommandInput
                                            placeholder="Search category..."
                                        />
                                        <CommandEmpty
                                            >No category found.</CommandEmpty
                                        >
                                        <CommandGroup>
                                            <CommandItem
                                                v-for="c in props.categories"
                                                :key="c.id"
                                                class="flex items-center justify-between"
                                                @select="
                                                    emit(
                                                        'toggle-category',
                                                        c.id,
                                                    )
                                                "
                                                :value="c.id"
                                            >
                                                <span>{{ c.name }}</span>
                                                <Check
                                                    v-if="
                                                        selectedCategoryIds.includes(
                                                            c.id,
                                                        )
                                                    "
                                                    class="h-4 w-4"
                                                />
                                            </CommandItem>
                                        </CommandGroup>
                                    </Command>
                                </PopoverContent>
                            </Popover>

                            <div
                                v-if="selectedCategories.length"
                                class="mt-2 flex flex-wrap gap-2"
                            >
                                <span
                                    v-for="c in selectedCategories"
                                    :key="c.id"
                                    class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-1 text-xs"
                                >
                                    {{ c.name }}
                                    <button
                                        type="button"
                                        @click="emit('remove-category', c.id)"
                                        class="hover:text-destructive"
                                    >
                                        x
                                    </button>
                                </span>
                            </div>
                        </FormItem>
                    </div>

                    <FormItem class="mt-4">
                        <FormLabel>Description</FormLabel>
                        <FormControl>
                            <Textarea
                                v-model="localForm.detail.description"
                                rows="4"
                                placeholder="Type your message here."
                            />
                        </FormControl>
                    </FormItem>

                    <!-- 2 Kolom Grid -->
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormItem>
                            <FormLabel>Address</FormLabel>
                            <FormControl>
                                <Input
                                    v-model="localForm.detail.address"
                                    type="text"
                                    placeholder="Destination Address"
                                />
                            </FormControl>
                        </FormItem>
                        <FormItem>
                            <FormLabel>District</FormLabel>
                            <FormControl>
                                <select
                                    v-model="localForm.detail.district"
                                    @change="getVillages(localForm.detail.district)"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-foreground dark:bg-muted dark:text-foreground"
                                >
                                    <option value="">Select District</option>
                                    <option
                                        v-for="district in districts"
                                        :key="district.code"
                                        :value="district.code"
                                    >
                                        {{ district.name }}
                                    </option>
                                </select>
                            </FormControl>
                        </FormItem>

                        <FormItem>
                            <FormLabel>Village</FormLabel>
                            <FormControl>
                                <select
                                    v-model="localForm.detail.village"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-foreground dark:bg-muted dark:text-foreground"
                                >
                                    <option value="">Select Village</option>
                                    <option
                                        v-for="village in villages"
                                        :key="village.code"
                                        :value="village.code"
                                    >
                                        {{ village.name }}
                                    </option>
                                </select>
                            </FormControl>
                        </FormItem>

                        <FormItem>
                            <FormLabel>Ticket Price</FormLabel>
                            <FormControl>
                                <Input
                                    v-model="localForm.detail.ticket_price"
                                    type="text"
                                    placeholder="Destination Ticket Price"
                                />
                            </FormControl>
                        </FormItem>

                        <FormItem>
                            <FormLabel>Open Hours</FormLabel>
                            <FormControl>
                                <Input
                                    v-model="localForm.detail.open_hours"
                                    type="time"
                                    placeholder="Open Hours"
                                />
                            </FormControl>
                        </FormItem>

                        <FormItem>
                            <FormLabel>Close Hours</FormLabel>
                            <FormControl>
                                <Input
                                    v-model="localForm.detail.close_hours"
                                    type="time"
                                    placeholder="Close Hours"
                                />
                            </FormControl>
                        </FormItem>

                        <FormItem>
                            <FormLabel>Phone</FormLabel>
                            <FormControl>
                                <Input
                                    v-model="localForm.detail.phone"
                                    type="tel"
                                    inputmode="tel"
                                    placeholder="+62 812 3456 7890"
                                />
                            </FormControl>
                        </FormItem>

                        <FormItem>
                            <FormLabel>Status</FormLabel>
                            <FormControl>
                                <select
                                    v-model="localForm.detail.status"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-foreground dark:bg-muted dark:text-foreground"
                                >
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </FormControl>
                        </FormItem>
                    </div>

                    <!-- Images -->
                    <FormItem class="mt-4">
                        <FormLabel>Images</FormLabel>
                        <FormControl>
                            <div class="flex flex-wrap items-center gap-2">
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    ref="fileEl"
                                    @change="emit('file-change', $event)"
                                />

                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="props.isUploading"
                                    @click="fileEl?.click()"
                                >
                                    Choose File
                                </Button>

                                <Input
                                    v-model="localCaption"
                                    placeholder="Caption (optional)"
                                    class="w-[200px]"
                                />
                            </div>
                        </FormControl>
                    </FormItem>

                    <div class="grid gap-3">
                        <div
                            v-if="props.images.length"
                            class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-4"
                        >
                            <div
                                v-for="img in props.images"
                                :key="
                                    img.kind === 'pending'
                                        ? 'p-' + img.uid
                                        : 'i-' + img.id
                                "
                                class="group relative overflow-hidden rounded-lg border shadow-sm"
                            >
                                <img
                                    :src="
                                        img.kind === 'pending'
                                            ? img.preview_url
                                            : img.image_url
                                    "
                                    class="h-28 w-full object-cover transition group-hover:opacity-75"
                                />
                                <div
                                    class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-black/50 px-2 py-1 text-xs"
                                >
                                    <span>
                                        <template v-if="img.is_cover"
                                            >Cover</template
                                        >
                                        <template v-else>{{
                                            img.caption || '\u00A0'
                                        }}</template>
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <button
                                            v-if="!img.is_cover"
                                            @click.prevent="
                                                emit('set-cover', img)
                                            "
                                            class="underline hover:opacity-80"
                                        >
                                            Set cover
                                        </button>
                                        <button
                                            @click.prevent="
                                                emit('delete-image', img)
                                            "
                                            class="hover:text-destructive"
                                            title="Delete image"
                                        >
                                            <Trash2 class="inline h-3 w-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </FormField>
            </div>

            <DialogFooter class="px-6 pt-3 pb-6">
                <Button variant="outline" class="mr-2" @click="isOpen = false">
                    Cancel
                </Button>
                <Button @click="emit('save')">
                    {{ props.form.id ? 'Update' : 'Create' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
