<script setup lang="ts">
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Calendar, Clock, MapPin } from 'lucide-vue-next'
import dayjs from 'dayjs'

interface User {
    id: number
    name: string
    email: string
    arrival_date?: string
    departure_date?: string
    arrival_time?: string
    departure_time?: string
}

const props = defineProps<{
    user: User
}>()

const processing = ref(false)

const form = useForm({
    arrival_date: props.user.arrival_date ? dayjs(props.user.arrival_date).format('YYYY-MM-DD') : '',
    departure_date: props.user.departure_date ? dayjs(props.user.departure_date).format('YYYY-MM-DD') : '',
    arrival_time: props.user.arrival_time || '',
    departure_time: props.user.departure_time || '',
})

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Settings', href: '/settings' },
    { title: 'Availability', href: '/settings/availability' },
]

const submitForm = () => {
    if (processing.value) return
    
    processing.value = true
    form.patch('/settings/availability', {
        onFinish: () => {
            processing.value = false
        }
    })
}

const formatDisplayDate = (dateStr: string) => {
    if (!dateStr) return 'Not set'
    return dayjs(dateStr).format('MMMM D, YYYY')
}

const formatDisplayTime = (timeStr: string) => {
    if (!timeStr) return 'Not set'
    return dayjs(`2000-01-01 ${timeStr}`).format('h:mm A')
}
</script>

<template>
    <Head title="Availability Settings" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Availability Settings</h1>
                <p class="text-muted-foreground">
                    Let us know when you'll be available for shifts
                </p>
            </div>

            <!-- Current Availability Display -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MapPin class="h-5 w-5" />
                        Current Availability
                    </CardTitle>
                    <CardDescription>
                        Your current availability settings for shift scheduling
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <Calendar class="h-4 w-4" />
                                Arrival
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ formatDisplayDate(form.arrival_date) }}
                            </p>
                            <div class="flex items-center gap-2 text-sm">
                                <Clock class="h-4 w-4" />
                                {{ formatDisplayTime(form.arrival_time) }}
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <Calendar class="h-4 w-4" />
                                Departure
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ formatDisplayDate(form.departure_date) }}
                            </p>
                            <div class="flex items-center gap-2 text-sm">
                                <Clock class="h-4 w-4" />
                                {{ formatDisplayTime(form.departure_time) }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Settings Form -->
            <Card>
                <CardHeader>
                    <CardTitle>Update Availability</CardTitle>
                    <CardDescription>
                        Change your arrival and departure dates and times
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Arrival Section -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium">Arrival</h3>
                                <div class="space-y-2">
                                    <Label for="arrival_date">Arrival Date</Label>
                                    <Input
                                        id="arrival_date"
                                        v-model="form.arrival_date"
                                        type="date"
                                        :class="{ 'border-red-500': form.errors.arrival_date }"
                                    />
                                    <p v-if="form.errors.arrival_date" class="text-sm text-red-500">
                                        {{ form.errors.arrival_date }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="arrival_time">Arrival Time (by hour)</Label>
                                    <Input
                                        id="arrival_time"
                                        v-model="form.arrival_time"
                                        type="time"
                                        :class="{ 'border-red-500': form.errors.arrival_time }"
                                    />
                                    <p v-if="form.errors.arrival_time" class="text-sm text-red-500">
                                        {{ form.errors.arrival_time }}
                                    </p>
                                </div>
                            </div>

                            <!-- Departure Section -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium">Departure</h3>
                                <div class="space-y-2">
                                    <Label for="departure_date">Departure Date</Label>
                                    <Input
                                        id="departure_date"
                                        v-model="form.departure_date"
                                        type="date"
                                        :class="{ 'border-red-500': form.errors.departure_date }"
                                    />
                                    <p v-if="form.errors.departure_date" class="text-sm text-red-500">
                                        {{ form.errors.departure_date }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="departure_time">Departure Time (by hour)</Label>
                                    <Input
                                        id="departure_time"
                                        v-model="form.departure_time"
                                        type="time"
                                        :class="{ 'border-red-500': form.errors.departure_time }"
                                    />
                                    <p v-if="form.errors.departure_time" class="text-sm text-red-500">
                                        {{ form.errors.departure_time }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <Button 
                                type="submit" 
                                :disabled="processing || form.processing"
                                class="min-w-[120px]"
                            >
                                {{ processing || form.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
