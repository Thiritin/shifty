<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { User, Save } from 'lucide-vue-next'

interface User {
    id: number
    name: string
    email: string
    email_verified_at?: string
}

const props = defineProps<{
    user: User
    mustVerifyEmail: boolean
    status?: string
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Settings', href: '#' },
    { title: 'Profile', href: '/settings/profile' },
]

const form = useForm({
    name: props.user.name,
    email: props.user.email,
})

const submit = () => {
    form.patch(route('profile.update'))
}
</script>

<template>
    <Head title="Profile Settings" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Profile Settings</h1>
                <p class="text-muted-foreground">Manage your account information</p>
            </div>

            <!-- Status Message -->
            <div v-if="status === 'profile-updated'" class="rounded-lg bg-green-50 border border-green-200 p-4 dark:bg-green-950 dark:border-green-800">
                <p class="text-sm text-green-600 dark:text-green-400">Profile updated successfully.</p>
            </div>

            <!-- Profile Form -->
            <Card>
                <CardHeader>
                    <div class="flex items-center space-x-2">
                        <User class="h-5 w-5" />
                        <CardTitle>Profile Information</CardTitle>
                    </div>
                    <CardDescription>
                        Update your account's profile information and email address.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                autofocus
                                autocomplete="name"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="username"
                            />
                            <InputError :message="form.errors.email" />
                            
                            <!-- Email Verification Warning -->
                            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="mt-2">
                                <p class="text-sm text-amber-600 dark:text-amber-400">
                                    Your email address is unverified.
                                    <Link
                                        :href="route('verification.send')"
                                        method="post"
                                        as="button"
                                        class="underline text-amber-600 hover:text-amber-500 dark:text-amber-400"
                                    >
                                        Click here to re-send the verification email.
                                    </Link>
                                </p>
                                
                                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm text-green-600 dark:text-green-400">
                                    A new verification link has been sent to your email address.
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center gap-4">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <Save class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                            
                            <div v-show="form.recentlySuccessful" class="text-sm text-green-600 dark:text-green-400">
                                Saved.
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>