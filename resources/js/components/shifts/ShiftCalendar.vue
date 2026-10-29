<script setup lang="ts">
import { computed, inject } from 'vue'
import { Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { day, fillBorder, fillOf, shiftActionsKey, timeRange, type Shift } from './shared'

const props = defineProps<{ shifts: Shift[] }>()

const actions = inject(shiftActionsKey)!
const ROW = 40

const hourOf = (t: string) => Number(t.slice(0, 2)) + Number(t.slice(3, 5)) / 60
// ponytail: overnight shifts are clipped at midnight on their start day.
const endOf = (s: Shift) => (hourOf(s.end_time) > hourOf(s.start_time) ? hourOf(s.end_time) : 24)

const range = computed(() => {
    const starts = props.shifts.map((s) => Math.floor(hourOf(s.start_time)))
    const ends = props.shifts.map((s) => Math.ceil(endOf(s)))
    return { from: Math.min(...starts, 8), to: Math.max(...ends, 18) }
})

const hours = computed(() => Array.from({ length: range.value.to - range.value.from }, (_, i) => range.value.from + i))

const columns = computed(() => {
    const byDay = new Map<string, Shift[]>()
    for (const s of [...props.shifts].sort((a, b) => a.start_time.localeCompare(b.start_time))) {
        byDay.set(day(s), [...(byDay.get(day(s)) ?? []), s])
    }
    return [...byDay.keys()].sort().map((date) => {
        const laneEnds: number[] = []
        const blocks = byDay.get(date)!.map((shift) => {
            let lane = laneEnds.findIndex((end) => end <= hourOf(shift.start_time))
            if (lane === -1) lane = laneEnds.push(0) - 1
            laneEnds[lane] = endOf(shift)
            return { shift, lane }
        })
        return { date, blocks, lanes: Math.max(laneEnds.length, 1) }
    })
})

const style = (shift: Shift, lane: number, lanes: number) => ({
    top: `${(hourOf(shift.start_time) - range.value.from) * ROW}px`,
    height: `${(endOf(shift) - hourOf(shift.start_time)) * ROW - 2}px`,
    left: `calc(${(lane / lanes) * 100}% + 2px)`,
    width: `calc(${100 / lanes}% - 4px)`,
})
</script>

<template>
    <div class="overflow-x-auto rounded-md border bg-card">
        <div
            class="grid min-w-full"
            :style="{ gridTemplateColumns: `3.5rem repeat(${columns.length}, minmax(12rem, 1fr))` }"
        >
            <div class="sticky left-0 z-10 border-b bg-card" />
            <div v-for="col in columns" :key="col.date" class="border-b border-l px-2 py-2 text-center text-sm font-medium">
                {{ dayjs(col.date).format('ddd D MMM') }}
            </div>

            <div class="sticky left-0 z-10 bg-card">
                <div v-for="h in hours" :key="h" class="pr-2 text-right font-mono text-xs text-muted-foreground" :style="{ height: `${ROW}px` }">
                    {{ String(h).padStart(2, '0') }}:00
                </div>
            </div>
            <div v-for="col in columns" :key="col.date" class="relative border-l" :style="{ height: `${hours.length * ROW}px` }">
                <div v-for="h in hours" :key="h" class="border-t border-dashed border-border/60" :style="{ height: `${ROW}px` }" />
                <component
                    :is="actions.isAdmin ? Link : 'div'"
                    v-for="{ shift, lane } in col.blocks"
                    :key="shift.id"
                    :href="actions.isAdmin ? `/shifts/${shift.id}` : undefined"
                    class="absolute overflow-hidden rounded-sm border border-l-4 bg-muted px-2 py-1 text-xs leading-tight shadow-xs"
                    :class="[fillBorder[fillOf(shift)], actions.isAdmin ? 'hover:bg-accent' : '', shift.is_assigned ? 'ring-2 ring-primary' : '']"
                    :style="style(shift, lane, col.lanes)"
                    :title="`${shift.name}, ${timeRange(shift)}`"
                >
                    <div class="truncate font-medium">{{ shift.name }}</div>
                    <div class="truncate text-muted-foreground tabular-nums">{{ timeRange(shift) }}</div>
                    <div class="truncate text-muted-foreground tabular-nums">{{ shift.assigned_count }} / {{ shift.required_people }} people</div>
                </component>
            </div>
        </div>
    </div>
</template>
