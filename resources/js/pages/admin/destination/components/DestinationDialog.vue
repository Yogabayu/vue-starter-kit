<script setup lang="ts">
import { computed, nextTick, ref, unref, watch } from 'vue';

import { Button } from '@/components/ui/button';
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
    FormDescription,
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
import { route } from 'ziggy-js';

import { Check, Trash2 } from 'lucide-vue-next';
const fileEl = ref<HTMLInputElement | null>(null);

const villages = ref([] as any[]);
const tagSearch = ref('');
const onTagSearchInput = (e: Event) => {
    const target = e.target as HTMLInputElement | null;
    tagSearch.value = (target?.value || '').toString();
};
const canCreateTag = computed(() => {
    const raw = tagSearch.value.trim();
    if (!raw) return false;
    const existsByName = (props.tags || []).some(
        (t) => t.name.toLowerCase() === raw.toLowerCase(),
    );
    const alreadyNew = (localForm.value?.new_tags || []).some(
        (n: string) => n.toLowerCase() === raw.toLowerCase(),
    );
    return !existsByName && !alreadyNew;
});
const createTagFromSearch = () => {
    const raw = tagSearch.value.trim();
    if (!raw) return;
    if (!canCreateTag.value) return;
    if (!Array.isArray((localForm.value as any).new_tags))
        (localForm.value as any).new_tags = [] as any;
    (localForm.value as any).new_tags.push(raw);
    tagSearch.value = '';
};

async function getVillages(idDistricts: string) {
    try {
        const response = await fetch(
            route('villages.update', { code: idDistricts }),
            {
                headers: { Accept: 'application/json' },
            },
        );
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        villages.value = data.data;
    } catch (error) {
        console.error('Error fetching villages data:', error);
    }
}

const props = defineProps<{
    open: boolean;
    form: any;
    categories: Array<{ id: number; name: string }>;
    facilities?: Array<{ id: number; name: string }>;
    tags?: Array<{ id: number; name: string }>;
    images: Array<any>;
    isUploading: boolean;
    uploadFile: File | null;
    uploadCaption: string;
    districts: Array<{ code: string; name: string }>;
}>();

watch(
    () => props.open,
    (val) => {
        if (val && props.form?.detail?.district_id) {
            getVillages(props.form.detail.district_id);
        }
    },
);

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
    (e: 'toggle-facility', id: number): void;
    (e: 'remove-facility', id: number): void;
    (e: 'toggle-tag', id: number): void;
    (e: 'remove-tag', id: number): void;
}>();

const isOpen = computed({
    get: () => props.open,
    set: (val) => emit('update:open', val),
});

const selectedCategoryIds = computed(() => props.form?.categories ?? []);
const selectedCategories = computed(() => {
    const map = new Map(props.categories.map((c) => [c.id, c]));
    return selectedCategoryIds.value
        .map((id: any) => map.get(id))
        .filter(Boolean);
});

const selectedFacilityIds = computed(() => props.form?.facilities ?? []);
const selectedFacilities = computed(() => {
    const list = props.facilities ?? [];
    const map = new Map(list.map((c) => [c.id, c]));
    return selectedFacilityIds.value
        .map((id: any) => map.get(id))
        .filter(Boolean);
});

const selectedTagIds = computed(() => props.form?.tags ?? []);
const selectedTags = computed(() => {
    const list = props.tags ?? [];
    const map = new Map(list.map((c) => [c.id, c]));
    return selectedTagIds.value.map((id: any) => map.get(id)).filter(Boolean);
});

function deepClone<T>(v: T): T {
    return JSON.parse(JSON.stringify(v));
}

const localForm = ref(deepClone(unref(props.form)));

let syncingFromParent = false;
watch(
    () => unref(props.form),
    (newVal) => {
        syncingFromParent = true;
        localForm.value = deepClone(newVal);

        nextTick(() => {
            syncingFromParent = false;
        });
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

const localCaption = ref(props.uploadCaption);

watch(
    () => props.uploadCaption,
    (v) => (localCaption.value = v),
);

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
                    Manage your destination information here. <br />
                    <span class="text-red-500">*</span> indicates required
                    fields.
                </DialogDescription>
            </DialogHeader>

            <div class="max-h-[calc(85vh-120px)] overflow-y-auto px-6 pb-4">
                <FormField v-slot="{ componentField }" name="name">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormItem>
                            <FormLabel
                                >Name
                                <span class="text-red-500">*</span></FormLabel
                            >
                            <FormControl>
                                <Input
                                    v-bind="componentField"
                                    v-model="localForm.name"
                                    type="text"
                                    placeholder="Destination Name"
                                />
                            </FormControl>
                        </FormItem>

                        <FormItem>
                            <label class="text-sm font-medium">
                                Category <span class="text-red-500">*</span>
                            </label>
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

                    <!-- Facilities and Tags -->
                    <div class="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormItem>
                            <label class="text-sm font-medium"
                                >Facilities</label
                            >
                            <Popover>
                                <PopoverTrigger as-child>
                                    <FormControl>
                                        <Button
                                            variant="outline"
                                            class="w-full justify-between"
                                        >
                                            <span
                                                v-if="selectedFacilities.length"
                                            >
                                                {{
                                                    selectedFacilities
                                                        .map((c: any) => c.name)
                                                        .join(', ')
                                                }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                                >Select facilities...</span
                                            >
                                        </Button>
                                    </FormControl>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-[--radix-popover-trigger-width] p-0"
                                    align="start"
                                >
                                    <Command>
                                        <CommandInput
                                            placeholder="Search facility..."
                                        />
                                        <CommandEmpty
                                            >No facility found.</CommandEmpty
                                        >
                                        <CommandGroup>
                                            <CommandItem
                                                v-for="c in props.facilities ||
                                                []"
                                                :key="c.id"
                                                class="flex items-center justify-between"
                                                @select="
                                                    emit(
                                                        'toggle-facility',
                                                        c.id,
                                                    )
                                                "
                                                :value="c.id"
                                            >
                                                <span>{{ c.name }}</span>
                                                <Check
                                                    v-if="
                                                        selectedFacilityIds.includes(
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
                                v-if="selectedFacilities.length"
                                class="mt-2 flex flex-wrap gap-2"
                            >
                                <span
                                    v-for="c in selectedFacilities"
                                    :key="c.id"
                                    class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-1 text-xs"
                                >
                                    {{ c.name }}
                                    <button
                                        type="button"
                                        @click="emit('remove-facility', c.id)"
                                        class="hover:text-destructive"
                                    >
                                        x
                                    </button>
                                </span>
                            </div>
                        </FormItem>

                        <FormItem>
                            <label class="text-sm font-medium">Tags</label>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <FormControl>
                                        <Button
                                            variant="outline"
                                            class="w-full justify-between"
                                        >
                                            <span v-if="selectedTags.length || (localForm.new_tags && localForm.new_tags.length)">
                                                {{
                                                    selectedTags
                                                        .map((c: any) => c.name)
                                                        .concat(localForm.new_tags || [])
                                                        .join(', ')
                                                }}
                                            </span>
                                            <span v-else class="text-muted-foreground">
                                                Select tags...
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
                                            placeholder="Search or create tag..."
                                            @input="onTagSearchInput"
                                            @keydown.enter.prevent="createTagFromSearch"
                                        />
                                        <CommandEmpty>
                                            <div class="p-2 text-sm">
                                                <div>No tag found.</div>
                                                <button
                                                    v-if="canCreateTag"
                                                    class="mt-2 w-full text-left underline"
                                                    @mousedown.prevent="createTagFromSearch"
                                                >
                                                    Create "{{ tagSearch.trim() }}"
                                                </button>
                                            </div>
                                        </CommandEmpty>
                                        <CommandGroup>
                                            <CommandItem
                                                v-for="c in props.tags || []"
                                                :key="c.id"
                                                class="flex items-center justify-between"
                                                @select="emit('toggle-tag', c.id)"
                                                :value="c.name"
                                            >
                                                <span>{{ c.name }}</span>
                                                <Check
                                                    v-if="selectedTagIds.includes(c.id)"
                                                    class="h-4 w-4"
                                                />
                                            </CommandItem>
                                            <CommandItem
                                                v-if="
                                                    tagSearch.trim() &&
                                                    !(props.tags || []).some(t => t.name.toLowerCase() === tagSearch.trim().toLowerCase()) &&
                                                    !(localForm.new_tags || []).some((n: string) => n.toLowerCase() === tagSearch.trim().toLowerCase())
                                                "
                                                :value="tagSearch"
                                                class="flex items-center justify-between text-emerald-600"
                                                @select="
                                                    () => {
                                                        if (!Array.isArray(localForm.new_tags)) localForm.new_tags = [] as any;
                                                        (localForm.new_tags as any).push(tagSearch.trim());
                                                        tagSearch = '' as any;
                                                    }
                                                "
                                            >
                                                <span>Create "{{ tagSearch.trim() }}"</span>
                                            </CommandItem>
                                        </CommandGroup>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <div class="mt-2 flex flex-wrap gap-2" v-if="selectedTags.length || (localForm.new_tags && localForm.new_tags.length)">
                                <span
                                    v-for="c in selectedTags"
                                    :key="'existing-' + c.id"
                                    class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-1 text-xs"
                                >
                                    {{ c.name }}
                                    <button
                                        type="button"
                                        @click="emit('remove-tag', c.id)"
                                        class="hover:text-destructive"
                                    >
                                        x
                                    </button>
                                </span>
                                <span
                                    v-for="(name, idx) in (localForm.new_tags || [])"
                                    :key="'new-' + idx + '-' + name"
                                    class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-1 text-xs"
                                >
                                    {{ name }}
                                    <button
                                        type="button"
                                        @click="localForm.new_tags = (localForm.new_tags || []).filter((n: string, i: number) => i !== idx)"
                                        class="hover:text-destructive"
                                    >
                                        x
                                    </button>
                                </span>
                            </div>

                            
                        </FormItem>
                    </div>

                    <FormItem class="mt-4">
                        <label class="text-sm font-medium">
                            Description <span class="text-red-500">*</span>
                        </label>
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
                            <label class="text-sm font-medium">
                                Address <span class="text-red-500">*</span>
                            </label>
                            <FormControl>
                                <Input
                                    v-model="localForm.detail.address"
                                    type="text"
                                    placeholder="Destination Address"
                                />
                            </FormControl>
                        </FormItem>
                        <FormItem>
                            <label class="text-sm font-medium">
                                District <span class="text-red-500">*</span>
                            </label>
                            <FormControl>
                                <select
                                    v-model="localForm.detail.district_id"
                                    @change="
                                        getVillages(localForm.detail.district_id)
                                    "
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
                            <label class="text-sm font-medium">
                                Village <span class="text-red-500">*</span>
                            </label>
                            <FormControl>
                                <select
                                    v-model="localForm.detail.village_id"
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
                            <label class="text-sm font-medium">Phone</label>
                            <FormControl>
                                <Input
                                    v-model="localForm.detail.phone"
                                    type="tel"
                                    inputmode="tel"
                                    placeholder="+62 812 3456 7890"
                                />
                            </FormControl>
                            <FormDescription>
                                Include country code, e.g. +62 for Indonesia
                            </FormDescription>
                        </FormItem>

                        <!-- Pricing -->
                         <FormItem>
                            <label class="text-sm font-medium">Currency</label>
                            <FormControl>
                                <Input
                                    v-model="localForm.detail.currency"
                                    type="text"
                                    maxlength="3"
                                    placeholder="IDR"
                                />
                            </FormControl>
                        </FormItem>
                        <FormItem>
                            <label class="text-sm font-medium">
                                Ticket Price
                            </label>
                            <FormControl>
                                <Input
                                    v-model="localForm.detail.ticket_price"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                />
                            </FormControl>
                        </FormItem>

                        

                        <FormItem>
                            <label class="text-sm font-medium">Maps URL</label>
                            <FormControl>
                                <Input
                                    v-model="localForm.detail.map_url"
                                    type="text"
                                    placeholder="https://maps.google.com..."
                                />
                            </FormControl>
                        </FormItem>
                    </div>

                    <!-- Open Hours grid -->
                    <div class="mt-4">
                        <label class="text-sm font-medium">Open hours</label>
                        <div class="mt-2 grid grid-cols-1 gap-2">
                            <div
                                v-for="row in localForm.open_hours"
                                :key="row.day_of_week"
                                class="grid grid-cols-12 items-center gap-2"
                            >
                                <div class="col-span-2 text-sm">
                                    {{
                                        [
                                            'Mon',
                                            'Tue',
                                            'Wed',
                                            'Thu',
                                            'Fri',
                                            'Sat',
                                            'Sun',
                                        ][row.day_of_week - 1]
                                    }}
                                </div>
                                <div class="col-span-2">
                                    <label
                                        class="inline-flex items-center gap-2 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="row.is_closed"
                                        />
                                        Closed
                                    </label>
                                </div>
                                <div class="col-span-3">
                                    <Input
                                        :disabled="row.is_closed"
                                        v-model="row.open_time"
                                        type="time"
                                    />
                                </div>
                                <div class="col-span-3">
                                    <Input
                                        :disabled="row.is_closed"
                                        v-model="row.close_time"
                                        type="time"
                                    />
                                </div>
                                <div class="col-span-2">
                                    <Input
                                        :disabled="row.is_closed"
                                        v-model="row.notes"
                                        type="text"
                                        placeholder="notes"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Images -->
                    <FormItem class="mt-4">
                        <label class="text-sm font-medium">Images</label>
                        <FormControl>
                            <div class="flex flex-wrap items-center gap-2">
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    ref="fileEl"
                                    multiple
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
                            </div>
                        </FormControl>
                    </FormItem>

                    <div class="grid gap-3" v-if="!props.form.id">
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
                                            : img.full_url
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
                    <div class="grid gap-3" v-else>
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
                                            : img.full_url
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
