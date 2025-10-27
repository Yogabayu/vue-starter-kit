<script setup lang="ts">
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as destinationsIndex } from '@/routes/destinations';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { BadgePlus } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref,h } from 'vue';
import { toast } from 'vue-sonner';


import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import DestinationDialog from './components/DestinationDialog.vue';
import DetailDialog from './components/DetailDialog.vue';

import { route } from 'ziggy-js';
import DestinationTable from './components/DestinationTable.vue';
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
const facilities = ref([] as { id: number; name: string }[]);
const tags = ref([] as { id: number; name: string }[]);

type DetailDestination = {
    id: number;
    destination_id: number;
    description: string;
    address: string;
    village_id: number | null;
    village?: any;
    map_url: string;
    ticket_price: string | number | null;
    currency?: string | null;
    phone: string;
    status: 'draft' | 'published' | 'pending';
    published_at?: string | null;
};
async function updateDistricts() {
    try {
        const res = await fetch(route('districts.update', { code: '35.02' }), {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) console.error('Failed to update districts');
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
    facilities?: { id: number; name: string }[];
    tags?: { id: number; name: string }[];
    open_hours?: any[];
    created_at: string;
};

const props = defineProps<{ destinations: DestinationRow[] }>();
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Destinations', href: destinationsIndex().url },
];


const q = ref('');
const sortKey = ref<keyof DestinationRow>('id');
const sortDir = ref<'asc' | 'desc'>('asc');
const page = ref(1);
const perPage = ref(10);
const isEditOpen = ref(false);
const isDetailOpen = ref(false);
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
    facilities: [] as number[],
    tags: [] as number[],
    images: [] as ImageDestination[],
    detail: {
        description: '',
        address: '',
        village_id: null,
        map_url: '',
        ticket_price: '',
        currency: 'IDR',
        phone: '',
        status: 'draft',

        district: '',
        village: '',
    } as any,
    open_hours: [
        { day_of_week: 1, is_closed: false, open_time: '', close_time: '', notes: '' },
        { day_of_week: 2, is_closed: false, open_time: '', close_time: '', notes: '' },
        { day_of_week: 3, is_closed: false, open_time: '', close_time: '', notes: '' },
        { day_of_week: 4, is_closed: false, open_time: '', close_time: '', notes: '' },
        { day_of_week: 5, is_closed: false, open_time: '', close_time: '', notes: '' },
        { day_of_week: 6, is_closed: false, open_time: '', close_time: '', notes: '' },
        { day_of_week: 7, is_closed: false, open_time: '', close_time: '', notes: '' },
    ],
});

function toggleCategory(id: number) {
    const list = form.categories ?? [];
    const i = list.indexOf(id);
    if (i === -1) list.push(id);
    else list.splice(i, 1);

    form.categories = [...list];
}

function toggleFacility(id: number) {
    const list = form.facilities ?? [];
    const i = list.indexOf(id);
    if (i === -1) list.push(id);
    else list.splice(i, 1);

    form.facilities = [...list];
}

function toggleTag(id: number) {
    const list = form.tags ?? [];
    const i = list.indexOf(id);
    if (i === -1) list.push(id);
    else list.splice(i, 1);

    form.tags = [...list];
}

function removeCategory(id: number) {
    form.categories = (form.categories ?? []).filter((x) => x !== id);
}

function removeFacility(id: number) {
    form.facilities = (form.facilities ?? []).filter((x) => x !== id);
}

function removeTag(id: number) {
    form.tags = (form.tags ?? []).filter((x) => x !== id);
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

async function fetchFacilities() {
    try {
        const res = await fetch(route('facilities.index'), { headers: { Accept: 'application/json' } });
        const data = await res.json();
        facilities.value = Array.isArray(data?.data) ? data.data : [];
    } catch (e) { console.error(e); }
}

async function fetchTags() {
    try {
        const res = await fetch(route('tags.index'), { headers: { Accept: 'application/json' } });
        const data = await res.json();
        tags.value = Array.isArray(data?.data) ? data.data : [];
    } catch (e) { console.error(e); }
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
        if (Array.isArray(data?.facilities)) {
            form.facilities = data.facilities.map((f: any) => f.id);
        }
        if (Array.isArray(data?.tags)) {
            form.tags = data.tags.map((t: any) => t.id);
        }
        if (Array.isArray(data?.open_hours)) {

            const base = [...form.open_hours];
            data.open_hours.forEach((h: any) => {
                const i = h.day_of_week - 1;
                if (i >= 0 && i < base.length) {
                    base[i] = {
                        day_of_week: h.day_of_week,
                        is_closed: !!h.is_closed,
                        open_time: h.open_time ?? '',
                        close_time: h.close_time ?? '',
                        notes: h.notes ?? '',
                    };
                }
            });
            form.open_hours = base;
        }
        if (data?.detail) {
            form.detail = {
                description: data.detail.description ?? '',
                address: data.detail.address ?? '',

                district_id: data.detail.village?.district?.code ?? '',
                village_id: data.detail.village?.code ?? '',
                map_url: data.detail.map_url ?? data.detail.maps_link ?? '',
                ticket_price: data.detail.ticket_price ?? '',
                currency: data.detail.currency ?? 'IDR',
                phone: data.detail.phone ?? '',
                status: data.detail.status ?? 'published',
                published_at: data.detail.published_at ?? null,
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
    form.facilities = [];
    form.tags = [];
    form.detail = {
        description: '',
        address: '',
        village_id: null,
        map_url: '',
        ticket_price: '',
        currency: 'IDR',
        phone: '',
        status: 'pending',
        district_id: '',
    } as any;
    isEditOpen.value = true;
}

async function openEdit(row: DestinationRow) {
    getDistricts();

    selected.value = row;
    form.id = row.id;
    form.name = row.name;
    form.slug = row.slug;
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

            form.categories.forEach((id) =>
                formData.append('categories[]', id.toString()),
            );
            form.facilities.forEach((id) =>
                formData.append('facilities[]', id.toString()),
            );
            form.tags.forEach((id) =>
                formData.append('tags[]', id.toString()),
            );

            formData.append('detail', JSON.stringify(form.detail));
            formData.append('open_hours', JSON.stringify(form.open_hours));

            pendingImages.value.forEach((img, i) => {
                formData.append(`images[${i}][file]`, img.file);
                formData.append(`images[${i}][caption]`, img.caption || '');
                formData.append(
                    `images[${i}][is_cover]`,
                    img.is_cover ? '1' : '0',
                );
            });

            console.log('Form Data Entries:');
            for (const pair of formData.entries()) {
                console.log(`${pair[0]}: ${pair[1]}`);
            }

            const res = await fetch(route('destinations.store'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: csrfHeaders({ Accept: 'application/json' }),
                body: formData,
            });

            if (!res.ok) throw new Error('Failed to create destination');
            toast.success('Destination created');

            pendingImages.value = [];
            isEditOpen.value = false;
            router.reload({ only: ['destinations'] });
        } else {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('name', form.name);
            formData.append('slug', form.slug);

            form.categories.forEach((id) =>
                formData.append('categories[]', id.toString()),
            );
            form.facilities.forEach((id) =>
                formData.append('facilities[]', id.toString()),
            );
            form.tags.forEach((id) =>
                formData.append('tags[]', id.toString()),
            );

            formData.append('detail', JSON.stringify(form.detail));
            formData.append('open_hours', JSON.stringify(form.open_hours));

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
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: csrfHeaders({ Accept: 'application/json' }),
                    body: formData,
                },
            );

            if (!res.ok) throw new Error('Failed to update destination');
            toast.success('Destination updated');

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

function removeDestination(id: number) {
  toast.custom(
    () =>
      h(
        'div',
        {
          class: [
            'pointer-events-auto',
            'rounded-lg border shadow-lg px-4 py-3',
            'bg-zinc-900 border-zinc-700 text-zinc-100',
            'max-w-[300px] text-sm',
          ].join(' '),
        },
        [
          h(
            'p',
            {
              class:
                'font-medium text-base text-zinc-100 mb-3 text-center',
            },
            'Yakin ingin menghapus destinasi ini?'
          ),

          h(
            'div',
            {
              class:
                'flex items-center justify-center gap-2',
            },
            [
              h(
                'button',
                {
                  class: [
                    'px-3 py-1.5 text-sm font-medium rounded',
                    'bg-zinc-200 text-zinc-800 hover:bg-zinc-300',
                    'dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600',
                    'cursor-pointer select-none',
                    'border border-transparent dark:border-zinc-600',
                  ].join(' '),
                  onClick: () => {
                    toast.dismiss()
                  },
                },
                'Batal'
              ),

              h(
                'button',
                {
                  class: [
                    'px-3 py-1.5 text-sm font-medium rounded',
                    'bg-red-600 text-white hover:bg-red-700',
                    'cursor-pointer select-none',
                    'border border-transparent',
                  ].join(' '),
                  onClick: async () => {
                    toast.dismiss()

                    try {
                      const res = await fetch(
                        route('destinations.destroy', {
                          destination: id,
                        }),
                        {
                          method: 'DELETE',
                          credentials: 'same-origin',
                          headers: csrfHeaders({
                            Accept: 'application/json',
                          }),
                        }
                      )

                      if (!res.ok) throw new Error('Failed to delete')

                      toast.success('Destination deleted ✅')
                      router.reload({ only: ['destinations'] })
                    } catch (e) {
                      console.error(e)
                      toast.error('Failed to delete ❌')
                    }
                  },
                },
                'Ya, Hapus'
              ),
            ]
          ),
        ]
      ),
    {
      duration: Infinity,
      class: 'pointer-events-auto bg-transparent shadow-none border-0',
      closeButton: false, 
    }
  )
}

const uploadFile = ref<File | null>(null);
const uploadCaption = ref('');

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
    form.id = v?.id ?? form.id;
    form.name = v?.name ?? form.name;
    form.slug = v?.slug ?? form.slug;
    if (Array.isArray(v?.categories)) form.categories = v.categories;
    if (Array.isArray(v?.facilities)) form.facilities = v.facilities;
    if (Array.isArray(v?.tags)) form.tags = v.tags;
    if (Array.isArray(v?.open_hours)) form.open_hours = v.open_hours;
    if (v?.detail) form.detail = v.detail;
}

function detailsOf(row: DestinationRow) {    
    form.detail = row.detail;
    form.images = row.images || [];
    form.name = row.name;
    form.open_hours = row.open_hours || [];
    isDetailOpen.value = true;
}

onMounted(() => {
    fetchCategories();
    fetchFacilities();
    fetchTags();
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
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger as-child>
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
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Add Destination</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <div class="ml-auto w-full max-w-xs">
                    <Input v-model="q" placeholder="Search destinations." />
                </div>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 p-2 dark:border-sidebar-border"
            >
                <DestinationTable
                    :rows="rows"
                    :total="total"
                    :page="page"
                    :per-page="perPage"
                    :last-page="lastPage"
                    sort-key="id"
                    sort-dir="asc"
                    @edit="openEdit"
                    @delete="removeDestination"
                    @update:sort="setSort"
                    @details="detailsOf"
                />
            </div>
        </div>

        <DestinationDialog
            v-model:open="isEditOpen"
            v-model:uploadCaption="uploadCaption"
            :form="form"
            @update:form="updateFormFromDialog"
            :categories="categories"
            :facilities="facilities"
            :tags="tags"
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
            @toggle-facility="toggleFacility"
            @remove-facility="removeFacility"
            @toggle-tag="toggleTag"
            @remove-tag="removeTag"
            @delete-destination="removeDestination"
        />
        <DetailDialog
            v-model:open="isDetailOpen"
            :detail="form.detail"
            :images="form.images"
            :title="form.name"
            :openHours="form.open_hours"
        />
    </AppLayout>
</template>