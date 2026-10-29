<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{ iconClass?: string }>();

const branding = computed(() => usePage().props.branding);
</script>

<template>
    <template v-if="branding.logoLight">
        <img :src="branding.logoLight" alt="" class="object-contain dark:hidden" v-bind="$attrs" />
        <img :src="branding.logoDark ?? branding.logoLight" alt="" class="hidden object-contain dark:block" v-bind="$attrs" />
    </template>
    <AppLogoIcon v-else :class="['fill-current', iconClass]" v-bind="$attrs" />
</template>
