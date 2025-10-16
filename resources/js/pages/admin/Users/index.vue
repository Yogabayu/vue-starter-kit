<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { usersIndex } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

// shadcn-vue primitives
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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
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
import { useForm } from '@inertiajs/vue3';
import { MoreHorizontal } from 'lucide-vue-next';
import { route } from 'ziggy-js';

type UserRow = { id: number; name: string; email: string; created_at: string };

const props = defineProps<{ users: UserRow[] }>();
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'User', href: usersIndex().url },
];

// state
const q = ref('');
const sortKey = ref<keyof UserRow>('id');
const sortDir = ref<'asc' | 'desc'>('asc');
const page = ref(1);
const perPage = ref(10);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);

const formUser = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
});

// filter
const filtered = computed(() => {
    if (!q.value) return props.users;
    const s = q.value.toLowerCase();
    return props.users.filter(
        (u) =>
            String(u.id).includes(s) ||
            u.name.toLowerCase().includes(s) ||
            u.email.toLowerCase().includes(s),
    );
});

// sort
const sorted = computed(() => {
    const arr = [...filtered.value];
    arr.sort((a, b) => {
        const A = String(a[sortKey.value]).toLowerCase();
        const B = String(b[sortKey.value]).toLowerCase();
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

function setSort(key: keyof UserRow) {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
}

function editUser(user: any) {
    formUser.id = user.id;
    formUser.name = user.name;
    formUser.email = user.email;
    isEditOpen.value = true;
}

function updateUser() {
    if (!formUser.id) return;
    
    formUser.put(route('users.update', { id: formUser.id }), {
        preserveScroll: true,
        onSuccess: () => {
            isEditOpen.value = false;
            toast.success('User updated successfully!');
        },
        onError: (errors) => {
            console.error(errors);
            toast.error('Failed to update user');
        },
    });
}

const openDeleteDialog = () => {
    isDeleteOpen.value = true;
};

const confirmDelete = () => {
    isDeleteOpen.value = false;
    toast.success('User deleted');
};
</script>

<template>
    <Head title="User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold">User List</h2>
                <div class="ml-auto w-full max-w-xs">
                    <Input v-model="q" placeholder="Search users…" />
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
                                @click="setSort('email')"
                            >
                                Email
                                <span v-if="sortKey === 'email'"
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
                        <TableRow v-for="u in rows" :key="u.id">
                            <TableCell>{{ u.id }}</TableCell>
                            <TableCell>{{ u.name }}</TableCell>
                            <TableCell>{{ u.email }}</TableCell>
                            <TableCell>{{
                                new Date(u.created_at).toLocaleDateString()
                            }}</TableCell>
                            <TableCell class="text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="editUser(u)">
                                            ✏️ Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="openDeleteDialog()"
                                        >
                                            🗑️ Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
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
                            <TableCell colspan="5">
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

        <Dialog v-model:open="isDeleteOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Hapus User?</DialogTitle>
                    <DialogDescription>
                        Tindakan ini tidak dapat dibatalkan. Apakah kamu yakin
                        ingin menghapus user ini?
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button variant="outline" @click="isDeleteOpen = false"
                        >Batal</Button
                    >
                    <Button variant="destructive" @click="confirmDelete"
                        >Hapus</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isEditOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Edit User</DialogTitle>
                    <DialogDescription>
                        Form untuk mengedit informasi user.
                    </DialogDescription>
                </DialogHeader>

                <form>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <label for="name" class="font-medium">Name</label>
                            <Input
                                id="name"
                                v-model="formUser.name"
                                type="text"
                                placeholder="Name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <label for="email" class="font-medium">Email</label>
                            <Input
                                id="email"
                                v-model="formUser.email"
                                type="email"
                                placeholder="Email"
                            />
                        </div>
                        <div class="grid gap-2">
                            <label for="password" class="font-medium"
                                >Password</label
                            >
                            <Input
                                id="password"
                                v-model="formUser.password"
                                type="password"
                                placeholder="leave blank to keep current password"
                            />
                        </div>
                    </div>
                </form>

                <DialogFooter>
                    <Button variant="outline" @click="isEditOpen = false"
                        >Cancel</Button
                    >
                    <Button type="submit" @click="updateUser()"
                        >Save</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
