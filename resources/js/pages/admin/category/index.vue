<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index as categoriesIndex } from '@/routes/categories';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { BadgePlus } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

import CategoryTable from './components/CategoryTable.vue';
import CategoryDialog from './components/CategoryDialog.vue';
import DeleteDialog from './components/DeleteDialog.vue';

type Category = {
    id: number;
    name: string;
    icon?: string | null;
    created_at: string;
};

const props = defineProps<{ categories: Category[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Categories', href: categoriesIndex().url },
];

const list = ref<Category[]>([...(props.categories || [])]);
const q = ref('');
const page = ref(1);
const perPage = ref(10);
const sortKey = ref<keyof Category>('id');
const sortDir = ref<'asc' | 'desc'>('asc');

const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const selected = ref<Category | null>(null);

const form = ref<Partial<Category>>({ id: null, name: '', icon: '' });

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

const filtered = computed(() => {
    if (!q.value) return list.value;
    const x = q.value.toLowerCase();
    return list.value.filter(
        (c) => c.name.toLowerCase().includes(x),
    );
});

const sorted = computed(() => {
    const arr = [...filtered.value];
    arr.sort((a, b) => {
        const A = String(a[sortKey.value] ?? '').toLowerCase();
        const B = String(b[sortKey.value] ?? '').toLowerCase();
        if (A === B) return 0;
        const res = A > B ? 1 : -1;
        return sortDir.value === 'asc' ? res : -res;
    });
    return arr;
});

const total = computed(() => sorted.value.length);
const lastPage = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)));
const rows = computed(() => {
    const start = (page.value - 1) * perPage.value;
    return sorted.value.slice(start, start + perPage.value);
});

function setSort({ key, dir }: { key: keyof Category; dir: 'asc' | 'desc' }) {
    sortKey.value = key;
    sortDir.value = dir;
}

async function refresh() {
    try {
        const res = await fetch(route('categories.index'), {
            headers: { Accept: 'application/json' },
        });
        const data = await res.json();
        list.value = Array.isArray(data?.data) ? data.data : [];
    } catch (e) {
        console.error(e);
    }
}

function openCreate() {
    selected.value = null;
    form.value = { id: null, name: '', icon: '' };
    isEditOpen.value = true;
}

function openEdit(row: Category) {
    selected.value = row;
    form.value = { id: row.id, name: row.name, icon: row.icon || '' };
    isEditOpen.value = true;
}

async function saveCategory() {
    try {
        const payload = { name: form.value.name || '', icon: form.value.icon || '' } as any;

        if (form.value.id) {
            const targetKey = selected.value?.id || form.value.id || '';
            const res = await fetch(route('categories.update', { category: targetKey }), {
                method: 'PUT',
                credentials: 'same-origin',
                headers: csrfHeaders({ 'Content-Type': 'application/json', Accept: 'application/json' }),
                body: JSON.stringify(payload),
            });
            if (!res.ok) {
                const errData = await res.json();
                throw new Error(errData?.message || 'Update failed');
            }
            toast.success('Category updated');
        } else {
            const res = await fetch(route('categories.store'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: csrfHeaders({ 'Content-Type': 'application/json', Accept: 'application/json' }),
                body: JSON.stringify(payload),
            });
            if (!res.ok) {
                const errData = await res.json();
                throw new Error(errData?.message || 'Create failed');
            }
            toast.success('Category created');
        }
        isEditOpen.value = false;
        await refresh();
    } catch (e: any) {
        toast.error(e?.message || 'Save failed');
    }
}

function askDelete(row: Category) {
    selected.value = row;
    isDeleteOpen.value = true;
}

async function confirmDelete() {
    if (!selected.value) return;
    try {
        const res = await fetch(route('categories.destroy', { category: selected.value.id }), {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: csrfHeaders({ Accept: 'application/json' }),
        });
        if (!res.ok) throw new Error('Delete failed');
        toast.success('Category deleted');
        isDeleteOpen.value = false;
        await refresh();
    } catch (e) {
        console.error(e);
        toast.error('Delete failed');
    }
}

function updateFormFromDialog(v: any) {
    form.value = { ...form.value, ...v };
}
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold">Category List</h2>
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                size="sm"
                                class="border border-gray-300 bg-white text-gray-900 hover:cursor-pointer dark:border-gray-700 dark:bg-muted dark:text-gray-100"
                                @click="openCreate"
                            >
                                <BadgePlus class="h-5 w-5 text-gray-900 dark:text-gray-100" />
                                add
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Add Category</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <div class="ml-auto w-full max-w-xs">
                    <Input v-model="q" placeholder="Search categories" />
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 p-2 dark:border-sidebar-border">
                <CategoryTable
                    :rows="rows"
                    :total="total"
                    :page="page"
                    :per-page="perPage"
                    :last-page="lastPage"
                    :sort-key="sortKey"
                    :sort-dir="sortDir"
                    @edit="openEdit"
                    @delete="askDelete"
                    @update:page="(v:number)=> (page = v)"
                    @update:perPage="(v:number)=> (perPage = v)"
                    @update:sort="setSort"
                />
            </div>
        </div>

        <CategoryDialog
            v-model:open="isEditOpen"
            :form="form"
            @update:form="updateFormFromDialog"
            @save="saveCategory"
        />

        <DeleteDialog
            v-model:open="isDeleteOpen"
            :name="selected?.name || ''"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
