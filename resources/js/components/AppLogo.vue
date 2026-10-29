<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const branding = computed(() => page.props.branding);
</script>

<template>
    <div class="flex aspect-square items-center justify-center rounded-md text-sidebar-primary-foreground">
        <template v-if="branding.logoLight">
            <img :src="branding.logoLight" alt="" class="size-8 object-contain dark:hidden" />
            <img :src="branding.logoDark ?? branding.logoLight" alt="" class="hidden size-8 object-contain dark:block" />
        </template>
        <AppLogoIcon v-else class="size-8 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span v-if="branding.organization" class="truncate text-xs text-muted-foreground uppercase">{{ branding.organization }}</span>
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ page.props.name }}</span>
    </div>
</template>
