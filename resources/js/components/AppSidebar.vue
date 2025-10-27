<script setup lang="ts">
// import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, usersIndex } from '@/routes';
import { index as destinationsIndex } from '@/routes/destinations';
import { index as categoriesIndex } from '@/routes/categories';
import { index as facilitiesIndex } from '@/routes/facilities'; 
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
// import { BookOpen, Folder, LayoutGrid } from 'lucide-vue-next';
import { LayoutGrid, UserRoundCog, MapPin, ChartBarStacked, Building } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Destinations',
        href: destinationsIndex(),
        icon: MapPin,
    },
    {
        title: 'Categories',
        href: categoriesIndex(),
        icon: ChartBarStacked ,
    },
    {
        title: 'Facilities',
        href: facilitiesIndex(),
        icon: Building ,
    },
    {}
];

const settingNavItems: NavItem[] = [
    {
        title: 'Users',
        href: usersIndex(),
        icon: UserRoundCog,
    },
];

// Role-based visibility
const page = usePage();
const roleNames = computed<string[]>(() => (page.props.auth?.user as any)?.role_names ?? []);
const isSuperAdmin = computed(() => roleNames.value.includes('super_admin'));

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Github Repo',
//         href: 'https://github.com/laravel/vue-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits#vue',
//         icon: BookOpen,
//     },
// ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain title="Main" :items="mainNavItems" />
            <NavMain v-if="isSuperAdmin" title="Settings" :items="settingNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <!-- <NavFooter :items="footerNavItems" /> -->
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
