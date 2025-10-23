<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index as destinationsIndex } from '@/routes/destinations';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { BadgePlus } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref } from 'vue';
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
function getCookie(name: string) {
    const m = document.cookie.match(
        new RegExp(
            '(?:^|; )' +
                name.replace(/([.$?*|{}()\[\]\\/+^])/g, '\\$1') +
                '=([^;]*)',
        ),
    );
    return m ? decodeURIComponent(m[1]) : '';
}
function csrfHeaders(extra: Record<string, string> = {}) {
    const token = getCookie('XSRF-TOKEN');
    return { 'X-XSRF-TOKEN': token, ...extra } as Record<string, string>;
}
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

type ApiStyle = {
    code: string;
    name: string;
};

const districts = ref([] as ApiStyle[]);

type DetailDestination = {
    id: number;
    destination_id: number;
    description: string;
    address: string;
    village: string;
    district: string;
    maps_link: string;
    ticket_price: string;
    opening_hours: string;
    close_hours: string;
    cover_image: string;
    phone: string;
    status: string;
};
async function updateDistricts() {
    try {
        const res = await fetch(route('districts.update', { code: '35.02' }), {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) throw new Error('Failed to update districts');
    } catch (e) {
        console.error(e);
        toast.error((e as any).message || 'Failed to update districts');
    }
}

async function getDistricts() {
    try {
        const response = await fetch(route('districts.index'), {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        districts.value = data.data;
    } catch (error) {
        console.error('Error fetching districts data:', error);
    }
}

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

const form = useForm({
    id: null as number | null,
    name: '',
    slug: '',
    categories: [] as number[],
    detail: {
        description: '',
        address: '',
        village: '',
        district: '',
        maps_link: '',
        ticket_price: '',
        open_hours: '',
        close_hours: '',
        phone: '',
        status: 'draft',
    } as any,
});

function toggleCategory(id: number) {
    const list = form.categories ?? [];
    const i = list.indexOf(id);
    if (i === -1) list.push(id);
    else list.splice(i, 1);
    // force reactivity in some edge cases
    form.categories = [...list];
}

function removeCategory(id: number) {
    form.categories = (form.categories ?? []).filter((x) => x !== id);
}

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
            form.categories = data.categories.map((c: any) => c.id);
        }
        if (data?.detail) {
            form.detail = {
                description: data.detail.description ?? '',
                address: data.detail.address ?? '',
                village: data.detail.village ?? '',
                district: data.detail.district ?? '',
                maps_link: data.detail.maps_link ?? '',
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
    updateDistricts();
    nextTick(() => {
        getDistricts();
    });
    selected.value = null;
    images.value = [];
    pendingImages.value = [];
    form.id = null;
    form.name = '';
    form.slug = '';
    form.categories = [];
    form.detail = {
        description: '',
        address: '',
        village: '',
        district: '',
        maps_link: '',
        ticket_price: '',
        open_hours: '',
        close_hours: '',
        phone: '',
        status: 'published',
    } as any;
    isEditOpen.value = true;
}

async function openEdit(row: DestinationRow) {
    await fetchCategories();
    await updateDistricts();
    await nextTick(() => {
        getDistricts();
    });
    selected.value = row;
    form.id = row.id;
    form.name = row.name;
    form.slug = row.slug;
    form.categories = (row.categories || []).map((c: any) => c.id);
    await fetchDestinationDetail(row.id);
    isEditOpen.value = true;
}

async function saveDestination() {
    try {
        isUploading.value = true;
        if (!form.id) {
            const formData = new FormData();
            formData.append('user_id', String(currentUserId.value));
            formData.append('slug', form.slug);
            formData.append('name', form.name);

            // categories
            form.categories.forEach((id) =>
                formData.append('categories[]', id.toString()),
            );

            // detail
            formData.append('detail', JSON.stringify(form.detail));

            // images (pending)
            pendingImages.value.forEach((img, i) => {
                formData.append(`images[${i}][file]`, img.file);
                formData.append(`images[${i}][caption]`, img.caption || '');
                formData.append(
                    `images[${i}][is_cover]`,
                    img.is_cover ? '1' : '0',
                );
            });

            const res = await fetch(route('destinations.store'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: csrfHeaders({ Accept: 'application/json' }),
                body: formData,
            });

            if (!res.ok) throw new Error('Failed to create destination');
            toast.success('Destination created');

            // bersihkan form
            pendingImages.value = [];
            isEditOpen.value = false;
            router.reload({ only: ['destinations'] });
        } else {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('name', form.name);
            formData.append('slug', form.slug);

            // categories
            form.categories.forEach((id) =>
                formData.append('categories[]', id.toString()),
            );

            // detail
            formData.append('detail', JSON.stringify(form.detail));

            // images (pending)
            if (pendingImages.value.length > 0) {
                pendingImages.value.forEach((img, i) => {
                    formData.append(`images[${i}][file]`, img.file);
                    formData.append(`images[${i}][caption]`, img.caption || '');
                    formData.append(
                        `images[${i}][is_cover]`,
                        img.is_cover ? '1' : '0',
                    );
                });
            }

            const res = await fetch(
                route('destinations.update', { destination: form.id }),
                {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: csrfHeaders({ Accept: 'application/json' }),
                    body: formData,
                },
            );

            if (!res.ok) throw new Error('Failed to update destination');
            toast.success('Destination updated');

            // bersihkan form
            pendingImages.value = [];
            isEditOpen.value = false;
            router.reload({ only: ['destinations'] });
        }
    } catch (e) {
        console.error(e);
        toast.error((e as any).message || 'Failed to save');
    } finally {
        isUploading.value = false;
    }
}

async function removeDestination(id: number) {
    try {
        const res = await fetch(
            route('destinations.destroy', { destination: id }),
            {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: csrfHeaders({ Accept: 'application/json' }),
            },
        );
        if (!res.ok) throw new Error('Failed to delete');
        toast.success('Destination deleted');
        router.reload({ only: ['destinations'] });
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
    if (!input?.files?.length) return;

    for (const file of Array.from(input.files)) {
        const uid =
            globalThis.crypto?.randomUUID?.() ??
            `${Date.now()}-${Math.floor(Math.random() * 1000)}`;
        const preview = URL.createObjectURL(file);

        pendingImages.value.push({
            kind: 'pending',
            uid,
            file,
            preview_url: preview,
            caption: uploadCaption.value || '',
            is_cover: false,
        });
    }

    // Reset field agar bisa pilih file yang sama lagi
    input.value = '';
    uploadCaption.value = '';
}

async function setImageAsCover(imageId: number) {
    if (!form.id) return;
    try {
        const res = await fetch(
            route('destinations.images.cover', {
                destination: form.id,
                image: imageId,
            }),
            {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: csrfHeaders({ Accept: 'application/json' }),
            },
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
    if (!form.id) return;
    try {
        const res = await fetch(
            route('destinations.images.destroy', {
                destination: form.id,
                image: imageId,
            }),
            {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: csrfHeaders({ Accept: 'application/json' }),
            },
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
        try {
            window.URL.revokeObjectURL(item.preview_url);
        } catch {}
        pendingImages.value = pendingImages.value.filter(
            (p) => p.uid !== item.uid,
        );
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
    pendingImages.value = pendingImages.value.map((p) => ({
        ...p,
        is_cover: false,
    }));
}

function updateFormFromDialog(v: any) {
    // Merge emitted form state into useForm without replacing the instance
    form.id = v?.id ?? form.id;
    form.name = v?.name ?? form.name;
    form.slug = v?.slug ?? form.slug;
    if (Array.isArray(v?.categories)) form.categories = v.categories;
    if (v?.detail) form.detail = v.detail;
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
                    class="border border-gray-300 bg-white text-gray-900 hover:cursor-pointer dark:border-gray-700 dark:bg-muted dark:text-gray-100"
                    @click="openCreate"
                >
                    <BadgePlus
                        class="h-5 w-5 text-gray-900 dark:text-gray-100"
                    />
                    add
                </Button>
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
            :form="form"
            @update:form="updateFormFromDialog"
            :categories="categories"
            :images="displayImages"
            :isUploading="isUploading"
            :uploadFile="uploadFile"
            :districts="districts"
            @save="saveDestination"
            @file-change="onFileChange"
            @delete-image="removeImage"
            @set-cover="setCover"
            @toggle-category="toggleCategory"
            @remove-category="removeCategory"
        />
    </AppLayout>
</template>
