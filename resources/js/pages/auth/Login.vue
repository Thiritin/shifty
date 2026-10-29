<script setup lang="ts">
import { ref } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { login as ssoLogin } from '@/routes/auth'
import { debug, store } from '@/routes/login'

const props = defineProps<{
    passwordLogin: boolean
    sso: { label: string } | null
    debugUsers: { id: number; name: string; email: string; is_admin: boolean }[] | null
}>()

const page = usePage()

const form = useForm({ email: '', password: '', remember: false })

const submit = () => form.post(store.url(), { onFinish: () => form.reset('password') })

const debugUserId = ref<number | ''>(props.debugUsers?.[0]?.id ?? '')
const debugLogin = () => router.post(debug.url(), debugUserId.value ? { user_id: debugUserId.value } : {})
</script>

<template>
    <AuthLayout :title="`Sign in to ${page.props.name}`">
        <Head title="Sign in" />

        <div class="flex flex-col gap-6">
            <p v-if="page.props.errors.sso" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300" role="alert">
                {{ page.props.errors.sso }}
            </p>

            <form v-if="passwordLogin" class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" v-model="form.email" type="email" required autofocus autocomplete="username" />
                    <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input id="password" v-model="form.password" type="password" required autocomplete="current-password" />
                    <InputError :message="form.errors.password" />
                </div>
                <div class="flex items-center gap-3">
                    <Checkbox id="remember" :model-value="form.remember" @update:model-value="(v) => (form.remember = v === true)" />
                    <Label for="remember">Remember me</Label>
                </div>
                <Button type="submit" class="w-full" :disabled="form.processing">Sign in</Button>
            </form>

            <div v-if="passwordLogin && sso" class="flex items-center gap-3 text-xs text-muted-foreground uppercase">
                <span class="h-px flex-1 bg-border" />or<span class="h-px flex-1 bg-border" />
            </div>

            <Button v-if="sso" as-child :variant="passwordLogin ? 'outline' : 'default'" class="w-full">
                <a :href="ssoLogin.url()">{{ sso.label }}</a>
            </Button>

            <div v-if="debugUsers" class="grid gap-2 rounded-md border border-dashed border-amber-400 p-4">
                <Label for="debug_user">Debug login</Label>
                <select
                    v-if="debugUsers.length"
                    id="debug_user"
                    v-model="debugUserId"
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm dark:bg-input/30"
                >
                    <option v-for="u in debugUsers" :key="u.id" :value="u.id">
                        {{ u.name }} ({{ u.email }}){{ u.is_admin ? ' · admin' : '' }}
                    </option>
                </select>
                <Button type="button" variant="secondary" @click="debugLogin">
                    {{ debugUsers.length ? 'Sign in as this user' : 'Create debug admin and sign in' }}
                </Button>
            </div>
        </div>
    </AuthLayout>
</template>
