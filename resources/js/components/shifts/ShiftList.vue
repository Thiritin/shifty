<script setup lang="ts">
import dayjs from 'dayjs'
import ShiftActions from './ShiftActions.vue'
import ShiftName from './ShiftName.vue'
import { fillBorder, fillOf, timeRange, type ShiftGroup } from './shared'

defineProps<{ groups: ShiftGroup[]; showDate: boolean }>()
</script>

<template>
    <div class="space-y-6">
        <section v-for="group in groups" :key="group.key">
            <h2 class="mb-2 text-sm font-semibold text-muted-foreground">{{ group.label }}</h2>
            <ul class="divide-y border">
                <li
                    v-for="shift in group.shifts"
                    :key="shift.id"
                    class="flex flex-wrap items-center gap-x-6 gap-y-2 border-l-4 bg-card px-4 py-3 md:flex-nowrap"
                    :class="fillBorder[fillOf(shift)]"
                >
                    <div class="w-28 shrink-0 text-sm tabular-nums">
                        <div v-if="showDate" class="text-xs text-muted-foreground">{{ dayjs(shift.date).format('ddd D MMM') }}</div>
                        {{ timeRange(shift) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <ShiftName :shift="shift" />
                        <p v-if="shift.description" class="truncate text-sm text-muted-foreground">{{ shift.description }}</p>
                    </div>
                    <div class="w-20 shrink-0 text-sm tabular-nums">{{ shift.assigned_count }} / {{ shift.required_people }}</div>
                    <div class="hidden w-56 shrink-0 truncate text-sm text-muted-foreground xl:block">
                        {{ shift.users.map((u) => u.name).join(', ') }}
                    </div>
                    <ShiftActions :shift="shift" />
                </li>
            </ul>
        </section>
    </div>
</template>
