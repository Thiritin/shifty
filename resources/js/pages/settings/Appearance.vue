<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppearanceTabs from '@/components/AppearanceTabs.vue'
import { Palette } from 'lucide-vue-next'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Settings', href: '#' },
    { title: 'Appearance', href: '/settings/appearance' },
]
</script>

<template>
    <Head title="Appearance Settings" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Appearance Settings</h1>
                <p class="text-muted-foreground">Customize the look and feel of the application</p>
            </div>

            <!-- Appearance Settings -->
            <Card>
                <CardHeader>
                    <div class="flex items-center space-x-2">
                        <Palette class="h-5 w-5" />
                        <CardTitle>Theme</CardTitle>
                    </div>
                    <CardDescription>
                        Choose how Shifty looks and feels for you. Your preference will be saved to your account.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <AppearanceTabs />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>