<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard, usersIndex } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

// shadcn-vue primitives
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import PlaceholderPattern from '../../components/PlaceholderPattern.vue';

type DashboardStats = {
    totalUsers: number;
    newUsersWeek: number;
    verifiedUsers: number;
    unverifiedUsers: number;
};

type UserRow = { id: number; name: string; email: string; created_at: string };

const props = defineProps<{ stats: DashboardStats; recentUsers: UserRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-hidden p-4">
            <!-- KPI Cards -->
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card>
                    <CardHeader class="px-6 pt-4 pb-0">
                        <CardTitle class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Icon name="users" class="h-4 w-4" /> Total Users
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-6 pb-6 pt-2">
                        <div class="text-3xl font-semibold tracking-tight">{{ props.stats.totalUsers }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="px-6 pt-4 pb-0">
                        <CardTitle class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Icon name="userPlus" class="h-4 w-4" /> New This Week
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-6 pb-6 pt-2">
                        <div class="text-3xl font-semibold tracking-tight">{{ props.stats.newUsersWeek }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="px-6 pt-4 pb-0">
                        <CardTitle class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Icon name="mailCheck" class="h-4 w-4" /> Verified Users
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-6 pb-6 pt-2">
                        <div class="text-3xl font-semibold tracking-tight">{{ props.stats.verifiedUsers }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="px-6 pt-4 pb-0">
                        <CardTitle class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Icon name="userX" class="h-4 w-4" /> Unverified Users
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-6 pb-6 pt-2">
                        <div class="text-3xl font-semibold tracking-tight">{{ props.stats.unverifiedUsers }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Chart + Quick Actions -->
            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader class="px-6 pt-4 pb-0">
                        <CardTitle class="text-sm text-muted-foreground">Users Growth</CardTitle>
                    </CardHeader>
                    <CardContent class="px-0 pb-0">
                        <div class="relative h-[260px] w-full overflow-hidden rounded-b-xl border-t">
                            <PlaceholderPattern />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="px-6 pt-4 pb-0">
                        <CardTitle class="text-sm text-muted-foreground">Quick Actions</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 px-6 pb-6 pt-2">
                        <Link :href="usersIndex().url">
                            <Button class="w-full justify-start" variant="secondary">
                                <Icon name="users" class="mr-2" /> Manage Users
                            </Button>
                        </Link>
                        <Link :href="usersIndex().url">
                            <Button class="w-full justify-start">
                                <Icon name="userPlus" class="mr-2" /> Create User
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Users -->
            <Card>
                <CardHeader class="px-6 pt-4 pb-0">
                    <CardTitle class="text-sm text-muted-foreground">Recent Users</CardTitle>
                </CardHeader>
                <CardContent class="px-0 pb-4 pt-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[90px]">ID</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead class="text-right">Created</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="u in props.recentUsers" :key="u.id">
                                <TableCell class="font-medium">#{{ u.id }}</TableCell>
                                <TableCell>{{ u.name }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ u.email }}</TableCell>
                                <TableCell class="text-right text-muted-foreground">
                                    {{ new Date(u.created_at).toLocaleString() }}
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="props.recentUsers.length === 0">
                                <TableCell colspan="4" class="text-center text-muted-foreground">No recent users</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
