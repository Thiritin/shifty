<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Check, Clock, Minus, Plus, X } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { assign, unassign } from '@/routes/admin/shifts'
import { type BreadcrumbItem } from '@/types'

interface Person {
    id: number
    name: string
    email: string
}

interface Candidate extends Person {
    shift_count: number
    shifts_expected: number | null
    status: 'green' | 'red' | 'neutral'
    available_hours: number
    total_hours: number
    presence: 'yes' | 'partly' | 'no' | 'unknown'
    conflict: { id: number; name: string } | null
    blocked: boolean
    suggested: boolean
}

const props = defineProps<{
    shift: {
        id: number
        name: string
        description: string | null
        date: string
        start_time: string
        end_time: string
        required_people: number
        users: Person[]
    }
    candidates: Candidate[]
}>()

const processing = ref(false)
const open = computed(() => Math.max(0, props.shift.required_people - props.shift.users.length))
const eligible = computed(() => props.candidates.filter((c) => !c.blocked))
const blocked = computed(() => props.candidates.filter((c) => c.blocked))

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Shifts', href: '/shifts' },
    { title: props.shift.name, href: `/shifts/${props.shift.id}` },
]

const time = (t: string) => t.slice(0, 5)

const presenceLabel: Record<Candidate['presence'], string> = {
    yes: 'On site',
    partly: 'Partly on site',
    no: 'Not on site',
    unknown: 'Dates not set',
}

const reasons = (c: Candidate) => [c.status === 'red' && 'Marked unavailable', c.presence === 'no' && 'Not on site'].filter((r) => r !== false)

const run = (visit: (opts: object) => void) => {
    processing.value = true
    visit({ preserveScroll: true, onFinish: () => (processing.value = false) })
}

const add = (user: Person) => run((opts) => router.post(assign.url({ shift: props.shift.id, user: user.id }), {}, opts))
const remove = (user: Person) => run((opts) => router.delete(unassign.url({ shift: props.shift.id, user: user.id }), opts))
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="shift.name" />

        <div class="flex flex-col gap-6 p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">{{ shift.name }}</h1>
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground">
                        <Clock class="size-4" />
                        {{ dayjs(shift.date).format('dddd D MMMM YYYY') }}, {{ time(shift.start_time) }} to {{ time(shift.end_time) }}
                    </p>
                    <p v-if="shift.description" class="mt-2 max-w-prose text-sm">{{ shift.description }}</p>
                </div>
                <Badge :variant="open ? 'outline' : 'default'" class="text-sm">
                    {{ shift.users.length }} of {{ shift.required_people }} filled
                </Badge>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Assigned</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul v-if="shift.users.length" class="divide-y">
                        <li v-for="u in shift.users" :key="u.id" class="flex items-center justify-between gap-4 py-2">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ u.name }}</p>
                                <p class="truncate text-sm text-muted-foreground">{{ u.email }}</p>
                            </div>
                            <Button size="sm" variant="outline" :disabled="processing" @click="remove(u)">
                                <Minus class="mr-1 size-4" />
                                Remove
                            </Button>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">Nobody is assigned yet.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Candidates</CardTitle>
                    <CardDescription>People with the fewest shifts come first, then those who marked these hours as available.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-xs text-muted-foreground">
                                <tr>
                                    <th class="py-2 pr-4 font-medium">Name</th>
                                    <th class="py-2 pr-4 font-medium">Shifts</th>
                                    <th class="py-2 pr-4 font-medium">Availability</th>
                                    <th class="py-2 pr-4 font-medium">Presence</th>
                                    <th class="py-2" />
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="c in eligible" :key="c.id">
                                    <td class="py-2 pr-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">{{ c.name }}</span>
                                            <Badge v-if="c.suggested" class="bg-emerald-600 text-white hover:bg-emerald-600">Suggested</Badge>
                                        </div>
                                        <p class="text-xs text-muted-foreground">{{ c.email }}</p>
                                    </td>
                                    <td class="py-2 pr-4 tabular-nums">
                                        {{ c.shift_count }}<span v-if="c.shifts_expected" class="text-muted-foreground"> / {{ c.shifts_expected }}</span>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <span v-if="c.status === 'green'" class="inline-flex items-center gap-1 text-emerald-700 dark:text-emerald-400">
                                            <Check class="size-4" /> Available
                                        </span>
                                        <span v-else-if="c.available_hours" class="text-muted-foreground">
                                            {{ c.available_hours }} of {{ c.total_hours }} hours available
                                        </span>
                                        <span v-else class="text-muted-foreground">No preference</span>
                                    </td>
                                    <td class="py-2 pr-4 text-muted-foreground">{{ presenceLabel[c.presence] }}</td>
                                    <td class="py-2 text-right">
                                        <Button size="sm" :variant="c.suggested ? 'default' : 'outline'" :disabled="processing || !open" @click="add(c)">
                                            <Plus class="mr-1 size-4" />
                                            Assign
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="!eligible.length">
                                    <td colspan="5" class="py-3 text-muted-foreground">Nobody else is free for this shift.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="blocked.length">
                        <h3 class="mb-2 text-sm font-semibold">Not suggested</h3>
                        <ul class="divide-y text-sm">
                            <li v-for="c in blocked" :key="c.id" class="flex flex-wrap items-center justify-between gap-2 py-2">
                                <div class="min-w-0">
                                    <span class="font-medium">{{ c.name }}</span>
                                    <span class="ml-2 text-muted-foreground tabular-nums">{{ c.shift_count }} shifts</span>
                                    <p class="flex flex-wrap items-center gap-x-3 text-xs text-muted-foreground">
                                        <span v-if="c.conflict" class="inline-flex items-center gap-1">
                                            <X class="size-3" /> Already on
                                            <Link :href="`/shifts/${c.conflict.id}`" class="underline">{{ c.conflict.name }}</Link>
                                        </span>
                                        <span v-for="r in reasons(c)" :key="r" class="inline-flex items-center gap-1"><X class="size-3" /> {{ r }}</span>
                                    </p>
                                </div>
                                <Button size="sm" variant="ghost" :disabled="processing || !open" @click="add(c)">Assign anyway</Button>
                            </li>
                        </ul>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
