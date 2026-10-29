<script setup lang="ts">
import { inject } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Badge } from '@/components/ui/badge'
import { shiftActionsKey, type Shift } from './shared'

defineProps<{ shift: Shift }>()

const actions = inject(shiftActionsKey)!
</script>

<template>
    <span class="inline-flex flex-wrap items-center gap-2">
        <Link v-if="actions.isAdmin" :href="`/shifts/${shift.id}`" class="font-medium hover:underline">{{ shift.name }}</Link>
        <span v-else class="font-medium">{{ shift.name }}</span>
        <Badge v-if="shift.is_assigned" variant="secondary">You</Badge>
        <Badge v-if="shift.my_status === 'green'" variant="outline" class="border-emerald-600 text-emerald-700 dark:text-emerald-400">Available</Badge>
        <Badge v-else-if="shift.my_status === 'red'" variant="outline" class="border-red-600 text-red-700 dark:text-red-400">Unavailable</Badge>
    </span>
</template>
