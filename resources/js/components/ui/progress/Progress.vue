<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

interface ProgressProps {
  modelValue?: number
  max?: number
  class?: string
}

const props = withDefaults(defineProps<ProgressProps>(), {
  modelValue: 0,
  max: 100,
})

const progressValue = computed(() => {
  if (props.modelValue == null) return 0
  return Math.min(Math.max(props.modelValue, 0), props.max)
})

const progressPercentage = computed(() => {
  return (progressValue.value / props.max) * 100
})
</script>

<template>
  <div
    :class="cn('relative h-4 w-full overflow-hidden rounded-full bg-secondary', props.class)"
  >
    <div
      class="h-full w-full flex-1 bg-primary transition-all"
      :style="{ transform: `translateX(-${100 - progressPercentage}%)` }"
    />
  </div>
</template>