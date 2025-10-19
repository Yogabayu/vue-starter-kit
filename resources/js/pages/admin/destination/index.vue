<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index as destinationsIndex } from '@/routes/destinations';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { BadgePlus, Check, Trash2, Upload, X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

// shadcn-vue primitives
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
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { route } from 'ziggy-js';
type ImageDestination = {
    id: number;
    destination_id: number;
    image_url: string;
    caption: string;
    is_cover: boolean;
};

type DetailDestination = {
    id: number;
    destination_id: number;
    description: string;
    address: string;
    village: string;
    district: string;
    latitude: string;
    longitude: string;
    ticket_price: string;
    opening_hours: string;
    close_hours: string;
    cover_image: string;
    phone: string;
    status: string;
};

type DestinationRow = {
    id: number;
    user_id: number;
    slug: string;
    name: string;
    detail: DetailDestination;
    images?: ImageDestination[];
    categories?: { id: number; name: string }[];
    created_at: string;
};

const props = defineProps<{ destinations: DestinationRow[] }>();
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Destinations', href: destinationsIndex().url },
];

// state
const q = ref('');
const sortKey = ref<keyof DestinationRow>('id');
const sortDir = ref<'asc' | 'desc'>('asc');
const page = ref(1);
const perPage = ref(10);
const isEditOpen = ref(false);
const isUploading = ref(false);
const categories = ref<Array<{ id: number; name: string }>>([]);
const images = ref<ImageDestination[]>([]);
const fileEl = ref<HTMLInputElement | null>(null);
const selected: any = ref<DestinationRow | null>(null);

const pageCtx = usePage();
const currentUserId = computed(() => pageCtx.props.auth?.user?.id);

const form = ref({
    id: null as number | null,
    name: '',
    slug: '',
    categories: [] as number[],
    detail: {
        description: '',
        address: '',
        village: '',
        district: '',
        latitude: '',
        longitude: '',
        ticket_price: '',
        open_hours: '',
        close_hours: '',
        phone: '',
        status: 'published',
    } as any,
});

// derived: selected category objects
const selectedCategories = computed(() => {
    const map = new Map(categories.value.map((c) => [c.id, c] as const));
    return form.value.categories.map((id) => map.get(id)).filter(Boolean) as {
        id: number;
        name: string;
    }[];
});

function toggleCategory(id: number) {
    if (!form.value) return;
    const list = form.value.categories ?? [];
    const i = list.indexOf(id);
    if (i === -1) list.push(id);
    else list.splice(i, 1);
    // force reactivity in some edge cases
    form.value.categories = [...list];
}

function removeCategory(id: number) {
    if (!form.value) return;
    form.value.categories = (form.value.categories ?? []).filter(
        (x) => x !== id,
    );
}

const previewUrl = computed(() => {
    if (!uploadFile.value) return '';
    return window.URL.createObjectURL(uploadFile.value);
});


// const formUser = useForm({
//     id: null,
//     name: '',
//     role: [],
//     email: '',
//     password: '',
// });

const filteredDestinations = computed(() => {
    if (!q.value) {
        return props.destinations;
    }
    const query = q.value.toLowerCase();
    return props.destinations.filter((destination) =>
        destination.name.toLowerCase().includes(query),
    );
});
const selectedCategoryIds = computed(() => form.value?.categories ?? []);

// sort
const sorted = computed(() => {
    const arr = [...filteredDestinations.value];
    arr.sort((a, b) => {
        const A = String(
            a[sortKey.value as keyof DestinationRow],
        ).toLowerCase();
        const B = String(
            b[sortKey.value as keyof DestinationRow],
        ).toLowerCase();
        if (A === B) return 0;
        const res = A > B ? 1 : -1;
        return sortDir.value === 'asc' ? res : -res;
    });
    return arr;
});

// paginate
const total = computed(() => sorted.value.length);
const lastPage = computed(() =>
    Math.max(1, Math.ceil(total.value / perPage.value)),
);
const rows = computed(() => {
    const start = (page.value - 1) * perPage.value;
    return sorted.value.slice(start, start + perPage.value);
});

function setSort(key: keyof DestinationRow) {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
}

async function fetchCategories() {
    try {
        const res = await fetch(route('categories.index'), {
            headers: { Accept: 'application/json' },
        });
        const data = await res.json();

        categories.value = Array.isArray(data?.data)
            ? data.data
            : Array.isArray(data)
              ? data
              : [];
        console.log(categories.value);
    } catch (e) {
        console.error(e);
    }
}

async function fetchDestinationDetail(id: number) {
    try {
        const res = await fetch(
            route('destinations.show', { destination: id }),
            { headers: { Accept: 'application/json' } },
        );
        const data = await res.json();
        images.value = data?.images ?? [];
        if (Array.isArray(data?.categories)) {
            form.value.categories = data.categories.map((c: any) => c.id);
        }
        if (data?.detail) {
            form.value.detail = {
                description: data.detail.description ?? '',
                address: data.detail.address ?? '',
                village: data.detail.village ?? '',
                district: data.detail.district ?? '',
                latitude: data.detail.latitude ?? '',
                longitude: data.detail.longitude ?? '',
                ticket_price: data.detail.ticket_price ?? '',
                open_hours: data.detail.open_hours ?? '',
                close_hours: data.detail.close_hours ?? '',
                phone: data.detail.phone ?? '',
                status: data.detail.status ?? 'published',
            } as any;
        }
    } catch (e) {
        console.error(e);
    }
}

function openCreate() {
    selected.value = null;
    images.value = [];
    form.value = {
        id: null,
        name: '',
        slug: '',
        categories: [],
        detail: {
            description: '',
            address: '',
            village: '',
            district: '',
            latitude: '',
            longitude: '',
            ticket_price: '',
            open_hours: '',
            close_hours: '',
            phone: '',
            status: 'published',
        },
    } as any;
    isEditOpen.value = true;
}

async function openEdit(row: DestinationRow) {
    await fetchCategories();
    selected.value = row;
    form.value.id = row.id;
    form.value.name = row.name;
    form.value.slug = row.slug;
    form.value.categories = (row.categories || []).map((c: any) => c.id);
    await fetchDestinationDetail(row.id);
    isEditOpen.value = true;
}

async function saveDestination() {
    try {
        const payload: any = {
            user_id: currentUserId.value,
            slug: form.value.slug,
            name: form.value.name,
            categories: form.value.categories,
            detail: form.value.detail,
        };
        if (!form.value.id) {
            const res = await fetch(route('destinations.store'), {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            if (!res.ok) throw new Error('Failed to create');
            toast.success('Destination created');
        } else {
            const res = await fetch(
                route('destinations.update', { destination: form.value.id }),
                {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(payload),
                },
            );
            if (!res.ok) throw new Error('Failed to update');
            toast.success('Destination updated');
        }
        isEditOpen.value = false;
        window.location.reload();
    } catch (e) {
        console.error(e);
        toast.error((e as any).message || 'Failed to save');
    }
}

async function removeDestination(id: number) {
    try {
        const res = await fetch(
            route('destinations.destroy', { destination: id }),
            { method: 'DELETE', headers: { Accept: 'application/json' } },
        );
        if (!res.ok) throw new Error('Failed to delete');
        toast.success('Destination deleted');
        window.location.reload();
    } catch (e) {
        console.error(e);
        toast.error('Failed to delete');
    }
}

// image upload
const uploadFile = ref<File | null>(null);
const uploadCaption = ref('');
const uploadIsCover = ref(false);

// optional: agar URL dilepas dari memori saat file berubah
watch(uploadFile, (newVal, oldVal) => {
    if (oldVal) window.URL.revokeObjectURL(window.URL.createObjectURL(oldVal));
});

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement | null;
    if (!input?.files?.length) {
        uploadFile.value = null;
        return;
    }
    uploadFile.value = input.files[0];
}

async function uploadImage() {
    if (!form.value.id || !uploadFile.value) return;
    try {
        isUploading.value = true;
        const fd = new FormData();
        fd.append('image', uploadFile.value);
        if (uploadCaption.value) fd.append('caption', uploadCaption.value);
        if (uploadIsCover.value) fd.append('is_cover', '1');
        const res = await fetch(
            route('destinations.images.store', { destination: form.value.id }),
            { method: 'POST', body: fd },
        );
        if (!res.ok) throw new Error('Failed to upload');
        const data = await res.json();
        images.value.unshift(data);
        toast.success('Image uploaded');
        uploadFile.value = null;
        uploadCaption.value = '';
        uploadIsCover.value = false;
    } catch (e) {
        console.error(e);
        toast.error('Upload failed');
    } finally {
        isUploading.value = false;
    }
}

async function deleteImage(imageId: number) {
    if (!form.value.id) return;
    try {
        const res = await fetch(
            route('destinations.images.destroy', {
                destination: form.value.id,
                image: imageId,
            }),
            { method: 'DELETE' },
        );
        if (!res.ok) throw new Error('Failed');
        images.value = images.value.filter((i) => i.id !== imageId);
        toast.success('Image deleted');
    } catch (e) {
        console.error(e);
        toast.error('Delete failed');
    }
}

onMounted(() => {
    fetchCategories();
});
</script>

<template>
    <Head title="Destinations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold">Destination List</h2>
                <Button
                    size="sm"
                    class="hover:cursor-pointer"
                    @click="openCreate"
                    ><BadgePlus class="h-5 w-5 text-gray-600" /> add</Button
                >
                <div class="ml-auto w-full max-w-xs">
                    <Input v-model="q" placeholder="Search destinations." />
                </div>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 p-2 dark:border-sidebar-border"
            >
                <Table>
                    <TableCaption>Total: {{ total }}</TableCaption>

                    <TableHeader>
                        <TableRow>
                            <TableHead
                                class="cursor-pointer select-none"
                                @click="setSort('id')"
                            >
                                ID
                                <span v-if="sortKey === 'id'"
                                    >({{ sortDir }})</span
                                >
                            </TableHead>
                            <TableHead
                                class="cursor-pointer select-none"
                                @click="setSort('name')"
                            >
                                Name
                                <span v-if="sortKey === 'name'"
                                    >({{ sortDir }})</span
                                >
                            </TableHead>
                            <TableHead
                                class="cursor-pointer select-none"
                                @click="setSort('created_at')"
                            >
                                Created At
                                <span v-if="sortKey === 'created_at'"
                                    >({{ sortDir }})</span
                                >
                            </TableHead>
                            <TableHead class="text-right"> Action </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="d in rows" :key="d.id">
                            <TableCell>{{
                                (page - 1) * perPage + rows.indexOf(d) + 1
                            }}</TableCell>
                            <TableCell>{{ d.name }}</TableCell>

                            <TableCell>{{
                                new Date(d.created_at).toLocaleDateString()
                            }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="hover:cursor-pointer"
                                        @click="openEdit(d)"
                                        >Edit</Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        class="hover:cursor-pointer"
                                        @click="removeDestination(d.id)"
                                        >Delete</Button
                                    >
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="rows.length === 0">
                            <TableCell
                                colspan="4"
                                class="text-center text-muted-foreground"
                            >
                                No data
                            </TableCell>
                        </TableRow>
                    </TableBody>

                    <TableFooter>
                        <TableRow>
                            <TableCell colspan="6">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div class="text-sm text-muted-foreground">
                                        Page {{ page }} / {{ lastPage }}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            :disabled="page <= 1"
                                            @click="page--"
                                            >Prev</Button
                                        >
                                        <Button
                                            variant="outline"
                                            :disabled="page >= lastPage"
                                            @click="page++"
                                            >Next</Button
                                        >
                                        <select
                                            v-model.number="perPage"
                                            class="rounded-md border bg-background px-2 py-1 text-foreground dark:bg-muted dark:text-foreground"
                                        >
                                            <option :value="5">5</option>
                                            <option :value="10">10</option>
                                            <option :value="25">25</option>
                                        </select>
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
            </div>
        </div>

        <!-- Edit/Create Dialog -->
        <Dialog v-model:open="isEditOpen">
            <DialogContent
                class="max-h-[100vh] overflow-hidden p-0 sm:max-w-3xl"
            >
                <DialogHeader class="px-6 pt-6 pb-3">
                    <DialogTitle>{{
                        form.id ? 'Edit Destination' : 'Add Destination'
                    }}</DialogTitle>
                    <DialogDescription
                        >Kelola destinasi, kategori dan
                        gambar</DialogDescription
                    >
                </DialogHeader>

                <div class="max-h-[calc(85vh-120px)] overflow-y-auto px-6 pb-4">
                    <div class="grid gap-6">
                        <div class="grid gap-2">
                            <label class="font-medium">Name</label>
                            <Input
                                v-model="form.name"
                                placeholder="Destination name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <label class="font-medium">Slug</label>
                            <Input
                                v-model="form.slug"
                                placeholder="unique-slug"
                            />
                        </div>
                        <div class="grid gap-2">
                            <label class="font-medium">Categories</label>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="w-full justify-between"
                                    >
                                        <span v-if="selectedCategories.length">
                                            {{
                                                selectedCategories
                                                    .map((c) => c.name)
                                                    .join(', ')
                                            }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                            >Select categories...</span
                                        >
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-[300px] p-0">
                                    <Command>
                                        <CommandInput
                                            placeholder="Search category..."
                                        />
                                        <CommandEmpty
                                            >No category found.</CommandEmpty
                                        >
                                        <CommandGroup>
                                            <CommandItem
                                                v-for="c in categories"
                                                :key="c.id"
                                                class="flex items-center justify-between"
                                                @select="toggleCategory(c.id)"
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
                                        @click="removeCategory(c.id)"
                                        class="hover:text-destructive"
                                    >
                                        <X class="h-3 w-3" />
                                    </button>
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <label class="font-medium">Description</label>
                            <textarea
                                v-model="form.detail.description"
                                rows="4"
                                class="min-h-24 w-full resize-y rounded-md border bg-background px-3 py-2 text-foreground dark:bg-muted dark:text-foreground"
                            ></textarea>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <label class="font-medium">Address</label>
                                <Input v-model="form.detail.address" />
                            </div>
                            <div class="grid gap-2">
                                <label class="font-medium">Village</label>
                                <Input v-model="form.detail.village" />
                            </div>
                            <div class="grid gap-2">
                                <label class="font-medium">District</label>
                                <Input v-model="form.detail.district" />
                            </div>
                            <div class="grid gap-2">
                                <label class="font-medium">Ticket Price</label>
                                <Input v-model="form.detail.ticket_price" />
                            </div>
                            <div class="grid gap-2">
                                <label class="font-medium">Open Hours</label>
                                <Input
                                    v-model="form.detail.open_hours"
                                    placeholder="08:00"
                                />
                            </div>
                            <div class="grid gap-2">
                                <label class="font-medium">Close Hours</label>
                                <Input
                                    v-model="form.detail.close_hours"
                                    placeholder="17:00"
                                />
                            </div>
                            <div class="grid gap-2">
                                <label class="font-medium">Phone</label>
                                <Input v-model="form.detail.phone" />
                            </div>
                            <div class="grid gap-2">
                                <label class="font-medium">Status</label>
                                <select
                                    v-model="form.detail.status"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-foreground dark:bg-muted dark:text-foreground"
                                >
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        </div>

                        <!-- Images -->
                        <div class="grid gap-3">
                            <label class="font-medium">Images</label>
                            <div class="flex flex-wrap items-center gap-2">
                                <input
                                    ref="fileEl"
                                    type="file"
                                    class="hidden"
                                    @change="onFileChange"
                                />

                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="fileEl?.click()"
                                    >Choose File</Button
                                >
                                <Input
                                    v-model="uploadCaption"
                                    placeholder="Caption (optional)"
                                    class="w-[200px]"
                                />
                                <label
                                    class="flex items-center gap-2 text-sm select-none"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="uploadIsCover"
                                        class="rounded border-gray-300"
                                    />
                                    Cover
                                </label>
                                <Button
                                    size="sm"
                                    :disabled="isUploading || !uploadFile"
                                    @click="uploadImage"
                                >
                                    <Upload class="mr-1 h-4 w-4" /> Upload
                                </Button>
                            </div>

                            <div
                                v-if="uploadFile"
                                class="mt-2 flex items-center gap-2"
                            >
                                <img
                                    :src="previewUrl"
                                    alt="preview"
                                    class="h-24 w-24 rounded-md border object-cover"
                                />
                                <span class="text-xs text-muted-foreground">{{
                                    uploadFile?.name
                                }}</span>
                            </div>

                            <div
                                v-if="images.length"
                                class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-4"
                            >
                                <div
                                    v-for="img in images"
                                    :key="img.id"
                                    class="group relative overflow-hidden rounded-lg border shadow-sm"
                                >
                                    <img
                                        :src="img.image_url"
                                        class="h-28 w-full object-cover transition group-hover:opacity-75"
                                    />
                                    <div
                                        class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-black/50 px-2 py-1 text-xs text-white"
                                    >
                                        <span>{{
                                            img.is_cover ? 'Cover' : img.caption
                                        }}</span>
                                        <button
                                            @click.prevent="deleteImage(img.id)"
                                            class="hover:text-destructive"
                                        >
                                            <Trash2 class="inline h-3 w-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter
                    class="sticky bottom-0 border-t bg-background px-6 py-4"
                >
                    <Button variant="outline" @click="isEditOpen = false"
                        >Cancel</Button
                    >
                    <Button @click="saveDestination">Save</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
