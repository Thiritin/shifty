<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import InputError from '@/components/InputError.vue'
import { LogIn, Mail, Lock } from 'lucide-vue-next'

defineProps<{
    canResetPassword?: boolean
    status?: string
}>()

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const showPassword = ref(false)

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password')
        },
    })
}
</script>

<template>
    <Head title="Sign In" />

    <AuthLayout>
        <div class="min-h-screen flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center dark:bg-blue-900">
                        <LogIn class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">Welcome back</h1>
                    <p class="text-muted-foreground">Sign in to your Shifty account</p>
                </div>

                <!-- Status Message -->
                <div v-if="status" class="mb-4 text-sm text-green-600 bg-green-50 border border-green-200 rounded-lg p-3 dark:bg-green-950 dark:border-green-800 dark:text-green-400">
                    {{ status }}
                </div>

                <!-- Login Form -->
                <Card>
                    <CardHeader class="space-y-1">
                        <CardTitle class="text-xl">Sign in</CardTitle>
                        <CardDescription>
                            Enter your email and password to access your account
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <!-- Email -->
                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <div class="relative">
                                    <Mail class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="name@example.com"
                                        required
                                        autofocus
                                        autocomplete="username"
                                        class="pl-10"
                                    />
                                </div>
                                <InputError :message="form.errors.email" />
                            </div>

                            <!-- Password -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <Label for="password">Password</Label>
                                    <Link
                                        v-if="canResetPassword"
                                        :href="route('password.request')"
                                        class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400"
                                    >
                                        Forgot password?
                                    </Link>
                                </div>
                                <div class="relative">
                                    <Lock class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Enter your password"
                                        required
                                        autocomplete="current-password"
                                        class="pl-10"
                                    />
                                </div>
                                <InputError :message="form.errors.password" />
                            </div>

                            <!-- Remember Me -->
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="remember"
                                    v-model:checked="form.remember"
                                />
                                <Label for="remember" class="text-sm">
                                    Remember me for 30 days
                                </Label>
                            </div>

                            <!-- Submit Button -->
                            <Button
                                type="submit"
                                class="w-full"
                                :disabled="form.processing"
                            >
                                <LogIn class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Signing in...' : 'Sign in' }}
                            </Button>
                        </form>

                        <!-- Register Link -->
                        <div class="mt-6 text-center">
                            <p class="text-sm text-muted-foreground">
                                Don't have an account?
                                <Link
                                    :href="route('register')"
                                    class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400"
                                >
                                    Sign up
                                </Link>
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthLayout>
</template>