<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { Shield, Lock } from 'lucide-vue-next'

const form = useForm({
    password: '',
})

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset()
        },
    })
}
</script>

<template>
    <Head title="Confirm Password" />

    <AuthLayout>
        <div class="min-h-screen flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="text-center mb-8">
                    <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-red-100 flex items-center justify-center dark:bg-red-900">
                        <Shield class="h-6 w-6 text-red-600 dark:text-red-400" />
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">Confirm password</h1>
                    <p class="text-muted-foreground">Please confirm your password before continuing</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Security confirmation</CardTitle>
                        <CardDescription>
                            This is a secure area of the application. Please confirm your password before continuing.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="password">Password</Label>
                                <div class="relative">
                                    <Lock class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        placeholder="Enter your password"
                                        class="pl-10"
                                        required
                                        autofocus
                                        autocomplete="current-password"
                                    />
                                </div>
                                <InputError :message="form.errors.password" />
                            </div>

                            <Button type="submit" class="w-full" :disabled="form.processing">
                                <Shield class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Confirming...' : 'Confirm' }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthLayout>
</template>