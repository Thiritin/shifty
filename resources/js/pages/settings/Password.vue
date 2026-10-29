<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { type BreadcrumbItem } from '@/types'

defineProps<{ hasPassword: boolean }>()

const page = usePage()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Password', href: '/settings/password' }]

const form = useForm({ current_password: '', password: '', password_confirmation: '' })

const submit = () => form.put('/settings/password', { preserveScroll: true, onSuccess: () => form.reset() })
</script>

<template>
    <Head title="Password" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-xl space-y-6 p-4 md:p-6">
            <h1 class="text-2xl font-bold tracking-tight">Password</h1>

            <Card>
                <CardHeader>
                    <CardTitle>{{ hasPassword ? 'Change password' : 'Set a password' }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-4" @submit.prevent="submit">
                        <div v-if="hasPassword" class="grid gap-2">
                            <Label for="current_password">Current password</Label>
                            <Input id="current_password" v-model="form.current_password" type="password" autocomplete="current-password" required />
                            <InputError :message="form.errors.current_password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="password">New password</Label>
                            <Input id="password" v-model="form.password" type="password" autocomplete="new-password" minlength="8" required />
                            <InputError :message="form.errors.password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm new password</Label>
                            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
                        </div>
                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">Save</Button>
                            <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground" role="status">{{ page.props.flash.success }}</p>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
