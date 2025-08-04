<script setup lang="ts">
import { computed } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Mail, LogOut, RefreshCw } from 'lucide-vue-next'

defineProps<{
    status?: string
}>()

const form = useForm({})
const verificationLinkSent = computed(() => form.recentlySuccessful)

const submit = () => {
    form.post(route('verification.send'))
}

const logout = () => {
    form.post(route('logout'))
}
</script>

<template>
    <Head title="Email Verification" />

    <AuthLayout>
        <div class="min-h-screen flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="text-center mb-8">
                    <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center dark:bg-yellow-900">
                        <Mail class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">Verify your email</h1>
                    <p class="text-muted-foreground">We've sent a verification link to your email address</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Email verification required</CardTitle>
                        <CardDescription>
                            Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="verificationLinkSent" class="text-sm text-green-600 bg-green-50 border border-green-200 rounded-lg p-3 dark:bg-green-950 dark:border-green-800 dark:text-green-400">
                            A new verification link has been sent to your email address.
                        </div>

                        <form @submit.prevent="submit">
                            <Button type="submit" class="w-full" :disabled="form.processing">
                                <RefreshCw class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Sending...' : 'Resend verification email' }}
                            </Button>
                        </form>

                        <Button @click="logout" variant="outline" class="w-full">
                            <LogOut class="mr-2 h-4 w-4" />
                            Log out
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthLayout>
</template>