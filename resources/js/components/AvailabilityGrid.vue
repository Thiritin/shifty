<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { Check, Eraser, X } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { hours as hoursRoute } from '@/routes/availability'

type Status = 'available' | 'unavailable'
type Brush = Status | null

const props = defineProps<{
    grid: {
        days: string[]
        cells: Record<string, Status>
        arrivesAt: string | null
        departsAt: string | null
    }
}>()

const hours = Array.from({ length: 24 }, (_, h) => String(h).padStart(2, '0'))
const cells = reactive<Record<string, Status | undefined>>({ ...props.grid.cells })
const changes = reactive<Record<string, Brush>>({})
const brush = ref<Brush>('available')
const activeDay = ref(props.grid.days[0])
const saving = ref(false)

const brushes: { value: Brush; label: string; icon: typeof Check }[] = [
    { value: 'available', label: 'Available', icon: Check },
    { value: 'unavailable', label: 'Unavailable', icon: X },
    { value: null, label: 'No preference', icon: Eraser },
]

const dirty = computed(() => Object.keys(changes).length > 0)

const keyOf = (day: string, hour: string) => `${day}T${hour}`

const isAway = (day: string, hour: string) => {
    const start = `${day}T${hour}:00`
    const end = dayjs(start).add(1, 'hour').format('YYYY-MM-DDTHH:mm')
    return (!!props.grid.arrivesAt && end <= props.grid.arrivesAt) || (!!props.grid.departsAt && start >= props.grid.departsAt)
}

const paint = (key: string) => {
    if ((cells[key] ?? null) === brush.value) return
    cells[key] = brush.value ?? undefined
    changes[key] = brush.value
}

const cellFromPoint = (event: PointerEvent) =>
    (document.elementFromPoint(event.clientX, event.clientY) as HTMLElement | null)?.closest<HTMLElement>('[data-cell]')?.dataset.cell

let lastKey: string | null = null

const onPointerDown = (event: PointerEvent) => {
    const key = cellFromPoint(event)
    if (!key) return
    event.preventDefault()
    lastKey = key
    paint(key)
}

// Fast drags skip cells between move events, so fill the hours in between.
const onPointerMove = (event: PointerEvent) => {
    const key = cellFromPoint(event)
    if (!lastKey || !key || key === lastKey) return
    const [day, from] = [lastKey.slice(0, 10), Number(lastKey.slice(11))]
    const to = Number(key.slice(11))
    if (key.startsWith(day)) {
        for (let h = Math.min(from, to); h <= Math.max(from, to); h++) paint(keyOf(day, String(h).padStart(2, '0')))
    } else {
        paint(key)
    }
    lastKey = key
}

const stopPainting = () => (lastKey = null)
window.addEventListener('pointerup', stopPainting)
onBeforeUnmount(() => window.removeEventListener('pointerup', stopPainting))

const describe = (day: string, hour: string) => {
    const status = cells[keyOf(day, hour)]
    const label = status === 'available' ? 'available' : status === 'unavailable' ? 'unavailable' : 'no preference'
    return `${dayjs(day).format('ddd D MMM')} ${hour}:00, ${label}`
}

const save = () => {
    saving.value = true
    router.put(hoursRoute.url(), { cells: { ...changes } }, {
        preserveScroll: true,
        onSuccess: () => Object.keys(changes).forEach((k) => delete changes[k]),
        onFinish: () => (saving.value = false),
    })
}
</script>

<template>
    <div v-if="grid.days.length" class="space-y-4">
        <div class="flex flex-wrap items-center gap-2" role="radiogroup" aria-label="Brush">
            <Button
                v-for="b in brushes"
                :key="String(b.value)"
                type="button"
                size="sm"
                :variant="brush === b.value ? 'default' : 'outline'"
                role="radio"
                :aria-checked="brush === b.value"
                @click="brush = b.value"
            >
                <span
                    class="mr-1.5 inline-flex size-4 items-center justify-center rounded-sm border"
                    :class="{
                        'border-emerald-600 bg-emerald-500 text-white': b.value === 'available',
                        'cell-unavailable border-red-600 text-white': b.value === 'unavailable',
                        'border-border bg-background': b.value === null,
                    }"
                >
                    <component :is="b.icon" class="size-3" />
                </span>
                {{ b.label }}
            </Button>
            <Button type="button" size="sm" class="ml-auto" :disabled="!dirty || saving" @click="save">Save</Button>
        </div>

        <div class="flex gap-1 overflow-x-auto md:hidden" role="tablist" aria-label="Day">
            <Button
                v-for="day in grid.days"
                :key="day"
                type="button"
                size="sm"
                role="tab"
                :aria-selected="activeDay === day"
                :variant="activeDay === day ? 'default' : 'ghost'"
                @click="activeDay = day"
            >
                {{ dayjs(day).format('ddd D') }}
            </Button>
        </div>

        <div class="overflow-x-auto">
            <div
                class="grid min-w-full select-none gap-px md:min-w-0"
                :style="{ gridTemplateColumns: `3rem repeat(${grid.days.length}, minmax(3.25rem, 1fr))` }"
                role="grid"
                aria-label="Hourly availability"
                @pointerdown="onPointerDown"
                @pointermove="onPointerMove"
            >
                <div />
                <div
                    v-for="day in grid.days"
                    :key="day"
                    class="pb-1 text-center text-xs font-medium"
                    :class="activeDay === day ? '' : 'hidden md:block'"
                    role="columnheader"
                >
                    {{ dayjs(day).format('ddd D MMM') }}
                </div>

                <template v-for="hour in hours" :key="hour">
                    <div class="pr-2 text-right font-mono text-xs leading-7 text-muted-foreground" role="rowheader">{{ hour }}:00</div>
                    <button
                        v-for="day in grid.days"
                        :key="keyOf(day, hour)"
                        type="button"
                        :data-cell="keyOf(day, hour)"
                        role="gridcell"
                        :aria-label="describe(day, hour)"
                        class="flex h-7 touch-none items-center justify-center rounded-sm border text-white transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        :class="[
                            activeDay === day ? 'flex' : 'hidden md:flex',
                            cells[keyOf(day, hour)] === 'available'
                                ? 'border-emerald-600 bg-emerald-500'
                                : cells[keyOf(day, hour)] === 'unavailable'
                                  ? 'cell-unavailable border-red-600'
                                  : isAway(day, hour)
                                    ? 'border-transparent bg-muted/60'
                                    : 'border-border bg-background hover:bg-muted',
                        ]"
                        @keydown.enter.prevent="paint(keyOf(day, hour))"
                        @keydown.space.prevent="paint(keyOf(day, hour))"
                    >
                        <Check v-if="cells[keyOf(day, hour)] === 'available'" class="size-3.5" aria-hidden="true" />
                        <X v-else-if="cells[keyOf(day, hour)] === 'unavailable'" class="size-3.5" aria-hidden="true" />
                    </button>
                </template>
            </div>
        </div>
    </div>
    <p v-else class="text-sm text-muted-foreground">Hourly availability opens once shifts are scheduled or your arrival and departure dates are set.</p>
</template>

<style scoped>
.cell-unavailable {
    background: repeating-linear-gradient(135deg, rgb(239 68 68), rgb(239 68 68) 4px, rgb(220 38 38) 4px, rgb(220 38 38) 8px);
}
</style>
