<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'

interface ShiftUser {
    id: number
    name: string
}

interface Shift {
    id: number
    name: string
    date: string
    start_time: string
    end_time: string
    required_people: number
    description: string | null
    assigned_count: number
    users: ShiftUser[]
}

const props = defineProps<{
    shifts: Shift[]
    weekStart: string
}>()

const groupedShifts = computed(() => {
    const groups: Record<string, Shift[]> = {}
    
    // Create entries for all 7 days of the week
    const startDate = new Date(props.weekStart)
    for (let i = 0; i < 7; i++) {
        const date = new Date(startDate)
        date.setDate(startDate.getDate() + i)
        const dateStr = date.toISOString().split('T')[0]
        groups[dateStr] = []
    }
    
    // Add shifts to their respective dates
    props.shifts.forEach(shift => {
        if (groups[shift.date]) {
            groups[shift.date].push(shift)
        }
    })
    
    return groups
})

const formatDateHeader = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric'
    })
}

const formatWeekRange = (weekStart: string) => {
    const start = new Date(weekStart)
    const end = new Date(start)
    end.setDate(start.getDate() + 6)
    
    return `${start.toLocaleDateString('en-US', { month: 'long', day: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}`
}

onMounted(() => {
    // Auto-print when the page loads
    window.print()
})
</script>

<template>
    <Head title="Print Schedule" />
    
    <div class="min-h-screen bg-white p-8 text-black">
        <!-- Header -->
        <div class="mb-8 text-center border-b-2 border-gray-800 pb-4">
            <h1 class="text-3xl font-bold mb-2">Volunteer Shift Schedule</h1>
            <p class="text-lg text-gray-600">{{ formatWeekRange(weekStart) }}</p>
        </div>

        <!-- Schedule Grid -->
        <div class="space-y-8">
            <div 
                v-for="(dayShifts, date) in groupedShifts" 
                :key="date"
                class="break-inside-avoid"
            >
                <h2 class="text-xl font-bold mb-4 border-b border-gray-400 pb-2">
                    {{ formatDateHeader(date) }}
                </h2>
                
                <div v-if="dayShifts.length === 0" class="text-gray-500 italic ml-4">
                    No shifts scheduled
                </div>
                
                <div v-else class="space-y-4">
                    <div 
                        v-for="shift in dayShifts" 
                        :key="shift.id"
                        class="ml-4 border border-gray-300 rounded p-4 break-inside-avoid"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-semibold text-lg">{{ shift.name }}</h3>
                                <p class="text-gray-600">{{ shift.start_time }} - {{ shift.end_time }}</p>
                            </div>
                            <div class="text-right text-sm">
                                <p class="font-medium">{{ shift.assigned_count }} / {{ shift.required_people }} volunteers</p>
                                <p class="text-gray-500">
                                    {{ shift.required_people - shift.assigned_count }} spots {{ shift.required_people - shift.assigned_count === 1 ? 'remaining' : 'remaining' }}
                                </p>
                            </div>
                        </div>
                        
                        <div v-if="shift.description" class="text-gray-600 text-sm mb-3">
                            {{ shift.description }}
                        </div>
                        
                        <div v-if="shift.users.length > 0" class="border-t border-gray-200 pt-3">
                            <p class="font-medium text-sm mb-2">Assigned Volunteers:</p>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                <div 
                                    v-for="user in shift.users" 
                                    :key="user.id"
                                    class="text-sm bg-gray-100 rounded px-2 py-1"
                                >
                                    {{ user.name }}
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="border-t border-gray-200 pt-3">
                            <p class="text-gray-500 text-sm italic">No volunteers assigned yet</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-12 pt-4 border-t border-gray-400 text-center text-sm text-gray-600">
            <p>Generated on {{ new Date().toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            }) }}</p>
        </div>
    </div>
</template>

<style>
@media print {
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .break-inside-avoid {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>