<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Shield, ExternalLink } from 'lucide-vue-next'

defineProps<{
    canLogin: boolean
    canRegister: boolean
}>()
</script>

<template>
    <Head title="Access Restricted - Shifty" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <div class="mx-auto mb-6 h-16 w-16 rounded-full bg-red-100 flex items-center justify-center dark:bg-red-900">
                    <Shield class="h-8 w-8 text-red-600 dark:text-red-400" />
                </div>
                <CardTitle class="text-2xl font-bold">Access Restricted</CardTitle>
            </CardHeader>
            <CardContent class="text-center space-y-6">
                <CardDescription class="text-base">
                    You need to login with a Eurofurence Identity account to gain access here. 
                    Access is currently only available to the fursuit badge team.
                </CardDescription>
                
                <div class="space-y-3">
                    <Button 
                        asChild 
                        class="w-full text-white hover:opacity-90" 
                        style="background-color: #004e4c;"
                        size="lg"
                    >
                        <a href="/auth/eurofurence" class="flex items-center justify-center">
                            Login with Eurofurence Identity
                        </a>
                    </Button>
                    
                    <Button asChild class="w-full" size="lg" v-if="canLogin">
                        <a :href="route('login')">
                            Login with Eurofurence Identity
                        </a>
                    </Button>
                    
                    <Button asChild variant="outline" class="w-full" size="lg">
                        <a href="https://identity.eurofurence.org" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center">
                            Return to Identity Portal
                            <ExternalLink class="ml-2 h-4 w-4" />
                        </a>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>