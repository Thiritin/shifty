<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import AvailabilityGrid from '@/components/AvailabilityGrid.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

const props = defineProps<{
    user: {
        arrival_date?: string | null
        departure_date?: string | null
        arrival_time?: string | null
        departure_time?: string | null
    }
    grid: InstanceType<typeof AvailabilityGrid>['$props']['grid']
}>()

const toDay = (date?: string | null) => (date ? date.slice(0, 10) : '')
const toHour = (time?: string | null) => (time ? time.slice(0, 5) : '')

const form = useForm({
    arrival_date: toDay(props.user.arrival_date),
    departure_date: toDay(props.user.departure_date),
    arrival_time: toHour(props.user.arrival_time),
    departure_time: toHour(props.user.departure_time),
})

const hours = Array.from({ length: 24 }, (_, h) => `${String(h).padStart(2, '0')}:00`)

const sides = [
    { key: 'arrival', label: 'Arrival', timeLabel: 'Arrival time' },
    { key: 'departure', label: 'Departure', timeLabel: 'Departure time' },
] as const

const isDisabled = (side: 'arrival' | 'departure', day: string) =>
    side === 'departure' ? !!form.arrival_date && day < form.arrival_date : !!form.departure_date && day > form.departure_date

const submit = () => form.patch('/settings/availability', { preserveScroll: true, onSuccess: () => form.defaults() })

const breadcrumbs: BreadcrumbItem[] = [{ title: 'My availability', href: '/settings/availability' }]
</script>

<template>
    <Head title="My availability" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <h1 class="text-3xl font-bold tracking-tight">My availability</h1>

            <Card>
                <CardHeader>
                    <CardTitle>Arrival and departure</CardTitle>
                    <CardDescription>Pick the days you arrive and leave.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="submit">
                        <fieldset v-for="side in sides" :key="side.key" class="space-y-3">
                            <legend class="mb-2 text-sm font-medium">{{ side.label }}</legend>

                            <div v-if="grid.days.length" class="flex flex-wrap gap-2">
                                <Button
                                    v-for="day in grid.days"
                                    :key="day"
                                    type="button"
                                    size="sm"
                                    :variant="form[`${side.key}_date`] === day ? 'default' : 'outline'"
                                    :aria-label="dayjs(day).format('dddd D MMMM')"
                                    :aria-pressed="form[`${side.key}_date`] === day"
                                    :disabled="isDisabled(side.key, day)"
                                    class="h-auto flex-col gap-0 px-3 py-1.5"
                                    @click="form[`${side.key}_date`] = form[`${side.key}_date`] === day ? '' : day"
                                >
                                    <span class="text-xs font-normal opacity-80">{{ dayjs(day).format('ddd') }}</span>
                                    <span>{{ dayjs(day).format('D MMM') }}</span>
                                </Button>
                            </div>
                            <Input v-else :id="`${side.key}_date`" v-model="form[`${side.key}_date`]" type="date" class="max-w-xs" />
                            <InputError :message="form.errors[`${side.key}_date`]" />

                            <div class="flex items-center gap-3">
                                <Label :for="`${side.key}_time`" class="text-muted-foreground">{{ side.timeLabel }}</Label>
                                <select
                                    :id="`${side.key}_time`"
                                    v-model="form[`${side.key}_time`]"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    <option value="">Not sure</option>
                                    <option v-if="form[`${side.key}_time`] && !hours.includes(form[`${side.key}_time`])" :value="form[`${side.key}_time`]">
                                        {{ form[`${side.key}_time`] }}
                                    </option>
                                    <option v-for="h in hours" :key="h" :value="h">{{ h }}</option>
                                </select>
                            </div>
                        </fieldset>

                        <div class="flex items-center gap-3">
                            <Button type="submit" :disabled="form.processing || !form.isDirty">Save</Button>
                            <span v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved</span>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Hourly availability</CardTitle>
                    <CardDescription>Mark the hours you can work in green and the hours you cannot in red.</CardDescription>
                </CardHeader>
                <CardContent>
                    <AvailabilityGrid :key="`${grid.arrivesAt}-${grid.departsAt}`" :grid="grid" />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
