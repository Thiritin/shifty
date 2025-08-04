<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { Lock, Mail } from 'lucide-vue-next'

const props = defineProps<{
    email: string
    token: string
}>()

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation')
        },
    })
}
</script>

<template>
    <Head title="Reset Password" />

    <AuthLayout>
        <div class="min-h-screen flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="text-center mb-8">
                    <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-green-100 flex items-center justify-center dark:bg-green-900">
                        <Lock class="h-6 w-6 text-green-600 dark:text-green-400" />
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">Reset password</h1>
                    <p class="text-muted-foreground">Enter your new password below</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Set new password</CardTitle>
                        <CardDescription>Choose a strong password for your account</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <div class="relative">
                                    <Mail class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="pl-10"
                                        readonly
                                    />
                                </div>
                                <InputError :message="form.errors.email" />
                            </div>

                            <div class="space-y-2">
                                <Label for="password">New Password</Label>
                                <div class="relative">
                                    <Lock class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        class="pl-10"
                                        required
                                        autofocus
                                        autocomplete="new-password"
                                    />
                                </div>
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="space-y-2">
                                <Label for="password_confirmation">Confirm Password</Label>
                                <div class="relative">
                                    <Lock class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        class="pl-10"
                                        required
                                        autocomplete="new-password"
                                    />
                                </div>
                                <InputError :message="form.errors.password_confirmation" />
                            </div>

                            <Button type="submit" class="w-full" :disabled="form.processing">
                                {{ form.processing ? 'Resetting...' : 'Reset password' }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthLayout>
</template>