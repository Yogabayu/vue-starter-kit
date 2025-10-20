<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index as destinationsIndex } from '@/routes/destinations';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { BadgePlus } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

// shadcn-vue primitives
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import DestinationDialog from './components/DestinationDialog.vue';

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

type PendingImage = {
    kind: 'pending';
    uid: string;
    file: File;
    preview_url: string;
    caption: string;
    is_cover: boolean;
};

type DisplayImage = PendingImage | ({ kind: 'persisted' } & ImageDestination);

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
const pendingImages = ref<PendingImage[]>([]);
const displayImages = computed<DisplayImage[]>(() => [
    ...pendingImages.value,
    ...images.value.map((i) => ({ kind: 'persisted' as const, ...i })),
]);
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
        pendingImages.value = [];
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
    pendingImages.value = [];
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
        console.log(payload);
        
        // if (!form.value.id) {
        //     const res = await fetch(route('destinations.store'), {
        //         method: 'POST',
        //         body: JSON.stringify(payload),
        //     });
        // if (!res.ok) throw new Error('Failed to create');
        // const created = await res.json();
        // // ensure we have the new id for subsequent image upload
        // form.value.id = created.id;
        // toast.success('Destination created');
        // } else {
        //     const res = await fetch(
        //         route('destinations.update', { destination: form.value.id }),
        //         {
        //             method: 'PUT',
        //             headers: {
        //                 'Content-Type': 'application/json',
        //                 Accept: 'application/json',
        //             },
        //             body: JSON.stringify(payload),
        //         },
        //     );
        //     if (!res.ok) throw new Error('Failed to update');
        //     toast.success('Destination updated');
        // }
        // // Upload all staged pending images (if any)
        // if (pendingImages.value.length && form.value.id) {
        //     for (const p of [...pendingImages.value]) {
        //         try {
        //             isUploading.value = true;
        //             const fd = new FormData();
        //             fd.append('image', p.file);
        //             if (p.caption) fd.append('caption', p.caption);
        //             if (p.is_cover) fd.append('is_cover', '1');
        //             const resUp = await fetch(
        //                 route('destinations.images.store', { destination: form.value.id }),
        //                 { method: 'POST', body: fd },
        //             );
        //             if (!resUp.ok) throw new Error('Failed to upload');
        //             const data = await resUp.json();
        //             images.value.unshift(data);
        //         } catch (err) {
        //             console.error(err);
        //             toast.error('Upload failed');
        //         } finally {
        //             try { window.URL.revokeObjectURL(p.preview_url); } catch {}
        //             isUploading.value = false;
        //         }
        //     }
        //     pendingImages.value = [];
        // }
        // isEditOpen.value = false;
        // window.location.reload();
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

// uploadFile kept only for compatibility; pending items manage their own blob URLs

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement | null;
    if (!input?.files?.length) {
        uploadFile.value = null;
        return;
    }
    const file = input.files[0];
    const uid = (globalThis.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random()}`);
    const preview = window.URL.createObjectURL(file);
    pendingImages.value.push({
        kind: 'pending',
        uid,
        file,
        preview_url: preview,
        caption: uploadCaption.value || '',
        is_cover: false,
    });
    // clear single use fields
    uploadFile.value = null;
    uploadCaption.value = '';
    if (input) input.value = '';
}

async function uploadImage() {
    if (!form.value.id || !uploadFile.value) return;
    try {
        isUploading.value = true;
        const fd = new FormData();
        fd.append('image', uploadFile.value);
        if (uploadCaption.value) fd.append('caption', uploadCaption.value);
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
    } catch (e) {
        console.error(e);
        toast.error('Upload failed');
    } finally {
        isUploading.value = false;
    }
}

async function setImageAsCover(imageId: number) {
    if (!form.value.id) return;
    try {
        const res = await fetch(
            route('destinations.images.cover', {
                destination: form.value.id,
                image: imageId,
            }),
            { method: 'PATCH', headers: { Accept: 'application/json' } },
        );
        if (!res.ok) throw new Error('Failed to set cover');
        images.value = images.value.map((img) => ({
            ...img,
            is_cover: img.id === imageId,
        }));
        toast.success('Cover updated');
    } catch (e) {
        console.error(e);
        toast.error('Failed to set cover');
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

function removeImage(item: DisplayImage) {
    if (item.kind === 'pending') {
        try { window.URL.revokeObjectURL(item.preview_url); } catch {}
        pendingImages.value = pendingImages.value.filter((p) => p.uid !== item.uid);
        return;
    }
    return deleteImage(item.id);
}

async function setCover(item: DisplayImage) {
    if (item.kind === 'pending') {
        pendingImages.value = pendingImages.value.map((p) => ({
            ...p,
            is_cover: p.uid === item.uid,
        }));
        return;
    }
    await setImageAsCover(item.id);
    pendingImages.value = pendingImages.value.map((p) => ({ ...p, is_cover: false }));
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
        <DestinationDialog
            v-model:open="isEditOpen"
            v-model:uploadCaption="uploadCaption"
            v-model:form="form"
            :categories="categories"
            :images="displayImages"
            :isUploading="isUploading"
            :uploadFile="uploadFile"
            @save="saveDestination"
            @file-change="onFileChange"
            @delete-image="removeImage"
            @set-cover="setCover"
            @toggle-category="toggleCategory"
            @remove-category="removeCategory"
        />
    </AppLayout>
</template>
