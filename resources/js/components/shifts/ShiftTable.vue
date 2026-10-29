<script setup lang="ts">
import dayjs from 'dayjs'
import ShiftActions from './ShiftActions.vue'
import ShiftName from './ShiftName.vue'
import { fillLabel, fillOf, timeRange, type ShiftGroup } from './shared'

defineProps<{ groups: ShiftGroup[]; showDate: boolean }>()

const fillText: Record<string, string> = {
    empty: 'text-red-700 dark:text-red-400',
    partial: 'text-yellow-700 dark:text-yellow-400',
    full: 'text-green-700 dark:text-green-400',
}
</script>

<template>
    <div class="overflow-x-auto rounded-md border">
        <table class="w-full text-sm">
            <thead class="bg-muted/50 text-left text-xs text-muted-foreground">
                <tr>
                    <th v-if="showDate" class="px-3 py-2 font-medium">Date</th>
                    <th class="px-3 py-2 font-medium">Time</th>
                    <th class="px-3 py-2 font-medium">Shift</th>
                    <th class="px-3 py-2 font-medium">Status</th>
                    <th class="px-3 py-2 font-medium">People</th>
                    <th class="hidden px-3 py-2 font-medium xl:table-cell">Assigned</th>
                    <th class="px-3 py-2" />
                </tr>
            </thead>
            <tbody v-for="group in groups" :key="group.key" class="divide-y border-t">
                <tr class="bg-muted/30">
                    <th :colspan="7" class="px-3 py-2 text-left font-semibold">{{ group.label }}</th>
                </tr>
                <tr v-for="shift in group.shifts" :key="shift.id">
                    <td v-if="showDate" class="px-3 py-2 whitespace-nowrap">{{ dayjs(shift.date).format('ddd D MMM') }}</td>
                    <td class="px-3 py-2 whitespace-nowrap tabular-nums">{{ timeRange(shift) }}</td>
                    <td class="min-w-48 px-3 py-2"><ShiftName :shift="shift" /></td>
                    <td class="px-3 py-2 whitespace-nowrap" :class="fillText[fillOf(shift)]">{{ fillLabel[fillOf(shift)] }}</td>
                    <td class="px-3 py-2 tabular-nums">{{ shift.assigned_count }} / {{ shift.required_people }}</td>
                    <td class="hidden max-w-64 truncate px-3 py-2 text-muted-foreground xl:table-cell">{{ shift.users.map((u) => u.name).join(', ') }}</td>
                    <td class="px-3 py-2"><ShiftActions :shift="shift" /></td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
