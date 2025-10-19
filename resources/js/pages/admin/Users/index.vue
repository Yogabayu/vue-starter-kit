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
type Role = {
    id: number;
    name: string;
    display_name?: string;
};

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: Role[]; // ✅ array of Role, bukan string
    created_at: string;
};

const props = defineProps<{ users: UserRow[] }>();
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'User', href: usersIndex().url },
];

// state
const q = ref('');
const type = ref(0);
const sortKey = ref<keyof UserRow>('id');
const sortDir = ref<'asc' | 'desc'>('asc');
const page = ref(1);
const perPage = ref(10);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const roles = ref<{ id: number; name: string; display_name: string }[]>([]);

const formUser = useForm({
    id: null,
    name: '',
    role: [],
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

function editUser(user: any, userType: number) {
    getRoles();
    formUser.reset();
    type.value = userType;
    formUser.id = user.id;
    formUser.name = user.name;
    formUser.email = user.email;
    formUser.role = user.roles && user.roles.length > 0 ? user.roles[0].id : null;
    isEditOpen.value = true;
}

async function getRoles() {
    try {
        const response = await fetch(route('users.getRoles'));
        const data = await response.json();
        roles.value = data;
    } catch (error) {
        console.error('Failed to fetch roles:', error);
        toast.error('Failed to load roles');
    }
}

function updateUser() {
    if (!formUser.id && type.value == 1) {
        formUser.post(route('users.create'), {
            preserveScroll: true,
            onSuccess: () => {
                isEditOpen.value = false;
                toast.success('User created successfully!');
            },
            onError: (errors) => {
                console.error(errors);
                toast.error('Failed to create user');
            },
        });
        formUser.reset();
    }

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
    formUser.reset();
}

const openDeleteDialog = (userId: any) => {
    formUser.id = userId;
    isDeleteOpen.value = true;
};

function confirmDelete() {
    if (!formUser.id) return;

    formUser.delete(route('users.destroy', { id: formUser.id }), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteOpen.value = false;
            toast.success('User deleted successfully!');
        },
        onError: (errors) => {
            console.error(errors);
            toast.error('Failed to delete user');
        },
    });
    formUser.reset();
}
</script>

<template>
    <Head title="User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold">User List</h2>
                <Button
                    size="sm"
                    class="hover:cursor-pointer"
                    @click="editUser({}, 1)"
                >
                    Add User</Button
                >
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
                                @click="setSort('roles')"
                            >
                                Role
                                <span v-if="sortKey === 'roles'"
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
                            <TableCell>{{
                                (page - 1) * perPage + rows.indexOf(u) + 1
                            }}</TableCell>
                            <TableCell>{{ u.name }}</TableCell>
                            <TableCell>{{ u.email }}</TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <Button
                                        v-for="r in u.roles"
                                        :key="r.id"
                                        size="sm"
                                        variant="outline"
                                        class="rounded-full px-2 py-0.5 text-xs"
                                        :class="{
                                            'border-blue-500 text-blue-600':
                                                r.name === 'super_admin',
                                            'border-green-500 text-green-600':
                                                r.name === 'admin_destinasi',
                                            'border-gray-400 text-gray-200':
                                                r.name === 'user',
                                        }"
                                    >
                                        {{ r.display_name ?? r.name }}
                                    </Button>
                                </div>
                            </TableCell>

                            <TableCell>{{
                                new Date(u.created_at).toLocaleDateString()
                            }}</TableCell>
                            <TableCell class="text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="hover:cursor-pointer"
                                        >
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            class="hover:cursor-pointer"
                                            @click="editUser(u, 2)"
                                        >
                                            ✏️ Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="hover:cursor-pointer"
                                            @click="openDeleteDialog(u.id)"
                                        >
                                            🗑️ Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="rows.length === 0">
                            <TableCell
                                colspan="5"
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
                    <Button
                        variant="outline"
                        class="hover:cursor-pointer"
                        @click="isDeleteOpen = false"
                        >Batal</Button
                    >
                    <Button
                        variant="destructive"
                        class="hover:cursor-pointer"
                        @click="confirmDelete"
                        >Hapus</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isEditOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle v-if="!formUser.id">Add User</DialogTitle>
                    <DialogTitle v-else>Edit User</DialogTitle>
                    <!-- <DialogDescription>
                        Form untuk mengedit informasi user.
                    </DialogDescription> -->
                </DialogHeader>

                <form>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <label for="roles" class="font-medium">Roles</label>
                            <div>
                                <select
                                    id="roles"
                                    v-model="formUser.role"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-foreground dark:bg-muted dark:text-foreground"
                                >
                                    <option
                                        v-for="role in roles"
                                        :key="role.id"
                                        :value="role.id"
                                    >
                                        {{ role.display_name }}
                                    </option>
                                </select>
                            </div>
                        </div>
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
                    <Button type="submit" @click="updateUser()">Save</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
