<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { Mail, ArrowLeft } from 'lucide-vue-next'

defineProps<{
    status?: string
}>()

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <Head title="Forgot Password" />

    <AuthLayout>
        <div class="min-h-screen flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center dark:bg-blue-900">
                        <Mail class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">Forgot password?</h1>
                    <p class="text-muted-foreground">No problem. Enter your email and we'll send you a reset link.</p>
                </div>

                <!-- Status Message -->
                <div v-if="status" class="mb-4 text-sm text-green-600 bg-green-50 border border-green-200 rounded-lg p-3 dark:bg-green-950 dark:border-green-800 dark:text-green-400">
                    {{ status }}
                </div>

                <!-- Reset Form -->
                <Card>
                    <CardHeader class="space-y-1">
                        <CardTitle class="text-xl">Reset password</CardTitle>
                        <CardDescription>
                            Enter your email address and we'll send you a link to reset your password
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
                                        class="pl-10"
                                    />
                                </div>
                                <InputError :message="form.errors.email" />
                            </div>

                            <!-- Submit Button -->
                            <Button
                                type="submit"
                                class="w-full"
                                :disabled="form.processing"
                            >
                                <Mail class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Sending...' : 'Send reset link' }}
                            </Button>
                        </form>

                        <!-- Back to Login -->
                        <div class="mt-6 text-center">
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400"
                            >
                                <ArrowLeft class="mr-1 h-4 w-4" />
                                Back to sign in
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthLayout>
</template>