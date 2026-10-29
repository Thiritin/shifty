<script setup lang="ts">
import { inject } from 'vue'
import { Edit, Minus, Plus, Settings, Trash2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { shiftActionsKey, type Shift } from './shared'

defineProps<{ shift: Shift }>()

const actions = inject(shiftActionsKey)!
</script>

<template>
    <div class="flex items-center justify-end gap-1">
        <template v-if="actions.canSelfAssign.value">
            <Button v-if="shift.is_assigned" size="sm" variant="outline" :disabled="actions.processing.value" @click="actions.cancel(shift.id)">
                <Minus class="size-4" />
                Cancel
            </Button>
            <Button v-else-if="!shift.is_full" size="sm" :disabled="actions.processing.value" @click="actions.signUp(shift)">
                <Plus class="size-4" />
                Sign up
            </Button>
        </template>
        <template v-if="actions.isAdmin">
            <Button size="icon" variant="ghost" class="size-8" title="Assign people" aria-label="Assign people" @click="actions.assign(shift)">
                <Settings class="size-4" />
            </Button>
            <Button size="icon" variant="ghost" class="size-8" title="Edit shift" aria-label="Edit shift" @click="actions.edit(shift)">
                <Edit class="size-4" />
            </Button>
            <Button size="icon" variant="ghost" class="size-8 text-destructive hover:text-destructive" title="Delete shift" aria-label="Delete shift" @click="actions.remove(shift)">
                <Trash2 class="size-4" />
            </Button>
        </template>
    </div>
</template>
