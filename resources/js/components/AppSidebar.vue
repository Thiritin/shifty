<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { CalendarCheck, CalendarDays, LayoutGrid, Settings, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();

const items = computed<NavItem[]>(() => [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'My availability', href: '/settings/availability', icon: CalendarCheck },
    { title: 'Shifts', href: '/shifts', icon: CalendarDays },
    ...(page.props.auth.user?.is_admin
        ? [
              { title: 'Users', href: '/admin/users', icon: Users },
              { title: 'Settings', href: '/admin/settings', icon: Settings },
          ]
        : []),
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child tooltip="Dashboard">
                        <Link :href="dashboard().url">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="items" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
