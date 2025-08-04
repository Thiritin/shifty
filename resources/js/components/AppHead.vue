<template>
    <Head :title="title" />
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

defineProps<{
    title: string
}>()
</script>
