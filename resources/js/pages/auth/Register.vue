<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { UserPlus, Mail, Lock, User } from 'lucide-vue-next'

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation')
        },
    })
}
</script>

<template>
    <Head title="Sign Up" />

    <AuthLayout>
        <div class="min-h-screen flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-green-100 flex items-center justify-center dark:bg-green-900">
                        <UserPlus class="h-6 w-6 text-green-600 dark:text-green-400" />
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">Create account</h1>
                    <p class="text-muted-foreground">Join Shifty to start managing your volunteer shifts</p>
                </div>

                <!-- Registration Form -->
                <Card>
                    <CardHeader class="space-y-1">
                        <CardTitle class="text-xl">Sign up</CardTitle>
                        <CardDescription>
                            Create your account to get started with shift planning
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <!-- Name -->
                            <div class="space-y-2">
                                <Label for="name">Full Name</Label>
                                <div class="relative">
                                    <User class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Enter your full name"
                                        required
                                        autofocus
                                        autocomplete="name"
                                        class="pl-10"
                                    />
                                </div>
                                <InputError :message="form.errors.name" />
                            </div>

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
                                        autocomplete="username"
                                        class="pl-10"
                                    />
                                </div>
                                <InputError :message="form.errors.email" />
                            </div>

                            <!-- Password -->
                            <div class="space-y-2">
                                <Label for="password">Password</Label>
                                <div class="relative">
                                    <Lock class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        placeholder="Create a strong password"
                                        required
                                        autocomplete="new-password"
                                        class="pl-10"
                                    />
                                </div>
                                <InputError :message="form.errors.password" />
                            </div>

                            <!-- Confirm Password -->
                            <div class="space-y-2">
                                <Label for="password_confirmation">Confirm Password</Label>
                                <div class="relative">
                                    <Lock class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        placeholder="Confirm your password"
                                        required
                                        autocomplete="new-password"
                                        class="pl-10"
                                    />
                                </div>
                                <InputError :message="form.errors.password_confirmation" />
                            </div>

                            <!-- Submit Button -->
                            <Button
                                type="submit"
                                class="w-full"
                                :disabled="form.processing"
                            >
                                <UserPlus class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Creating account...' : 'Create account' }}
                            </Button>
                        </form>

                        <!-- Login Link -->
                        <div class="mt-6 text-center">
                            <p class="text-sm text-muted-foreground">
                                Already have an account?
                                <Link
                                    :href="route('login')"
                                    class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400"
                                >
                                    Sign in
                                </Link>
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Terms -->
                <p class="mt-6 text-center text-xs text-muted-foreground">
                    By creating an account, you agree to our terms of service and privacy policy.
                </p>
            </div>
        </div>
    </AuthLayout>
</template>