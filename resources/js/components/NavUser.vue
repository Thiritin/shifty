<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import { logout } from '@/routes';
import { type User } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { KeyRound, LogOut, Monitor, Moon, Sun } from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth.user as User;
const { appearance, updateAppearance } = useAppearance();

const themes = [
    { value: 'light', icon: Sun, label: 'Light' },
    { value: 'dark', icon: Moon, label: 'Dark' },
    { value: 'system', icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem class="group-data-[collapsible=icon]:hidden">
            <div class="flex gap-1 rounded-md bg-sidebar-accent/60 p-1" role="radiogroup" aria-label="Appearance">
                <button
                    v-for="t in themes"
                    :key="t.value"
                    type="button"
                    role="radio"
                    :aria-checked="appearance === t.value"
                    :aria-label="t.label"
                    :title="t.label"
                    class="flex flex-1 items-center justify-center rounded-sm py-1.5 text-muted-foreground transition-colors hover:text-foreground"
                    :class="appearance === t.value ? 'bg-background text-foreground shadow-xs' : ''"
                    @click="updateAppearance(t.value)"
                >
                    <component :is="t.icon" class="size-4" />
                </button>
            </div>
        </SidebarMenuItem>
        <SidebarMenuItem>
            <SidebarMenuButton as-child :is-active="page.url.startsWith('/settings/password')" tooltip="Password">
                <Link href="/settings/password">
                    <KeyRound />
                    <span>Password</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
        <SidebarMenuItem>
            <SidebarMenuButton as-child tooltip="Log out">
                <Link :href="logout().url" method="post" as="button" class="w-full" @click="router.flushAll()">
                    <LogOut />
                    <span>Log out</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
        <SidebarMenuItem class="flex items-center gap-2 px-2 pt-2 group-data-[collapsible=icon]:hidden">
            <UserInfo :user="user" :show-email="true" />
        </SidebarMenuItem>
    </SidebarMenu>
</template>
