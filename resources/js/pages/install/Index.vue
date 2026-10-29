<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { store } from '@/routes/install'

const props = defineProps<{ appName: string; token: string | null }>()

const form = useForm({
    token: props.token ?? '',
    app_name: props.appName,
    organization: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

const submit = () => form.post(store.url(), { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<template>
    <AuthLayout title="Set up your workspace">
        <Head title="Setup" />

        <form class="grid gap-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="token">Setup token</Label>
                <Input id="token" v-model="form.token" required autocomplete="off" class="font-mono" />
                <p class="text-sm text-muted-foreground">
                    Run <code class="font-mono">php artisan shifty:setup-token</code> or check the container log to find it.
                </p>
                <InputError :message="form.errors.token" />
            </div>

            <section class="grid gap-4 border-t pt-6">
                <h2 class="text-sm font-semibold">App</h2>
                <div class="grid gap-2">
                    <Label for="app_name">App name</Label>
                    <Input id="app_name" v-model="form.app_name" required maxlength="60" />
                    <InputError :message="form.errors.app_name" />
                </div>
                <div class="grid gap-2">
                    <Label for="organization">Organization</Label>
                    <Input id="organization" v-model="form.organization" maxlength="100" />
                    <InputError :message="form.errors.organization" />
                </div>
            </section>

            <section class="grid gap-4 border-t pt-6">
                <h2 class="text-sm font-semibold">Admin account</h2>
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" required autocomplete="name" />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" v-model="form.email" type="email" required autocomplete="username" />
                    <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input id="password" v-model="form.password" type="password" required minlength="8" autocomplete="new-password" />
                    <InputError :message="form.errors.password" />
                </div>
                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input id="password_confirmation" v-model="form.password_confirmation" type="password" required autocomplete="new-password" />
                </div>
            </section>

            <Button type="submit" class="w-full" :disabled="form.processing">Finish setup</Button>
        </form>
    </AuthLayout>
</template>
