<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { 
    Calendar, 
    Users, 
    ChevronLeft, 
    ChevronRight, 
    Settings,
    UserCheck,
    UserX
} from 'lucide-vue-next'

interface User {
    id: number
    name: string
    email: string
    shift_count: number
    shifts_expected: number
}

interface ShiftUser {
    id: number
    name: string
    email: string
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
    spots_available: number
    is_full: boolean
    users: ShiftUser[]
}

const props = defineProps<{
    shifts: Shift[]
    currentWeek: string
    users: User[]
}>()

const processing = ref(false)

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin', href: '#' },
    { title: 'Shifts', href: '/admin/shifts' },
]

const groupedShifts = computed(() => {
    const groups: Record<string, Shift[]> = {}
    
    const startDate = new Date(props.currentWeek)
    for (let i = 0; i < 7; i++) {
        const date = new Date(startDate)
        date.setDate(startDate.getDate() + i)
        const dateStr = date.toISOString().split('T')[0]
        groups[dateStr] = []
    }
    
    props.shifts.forEach(shift => {
        if (groups[shift.date]) {
            groups[shift.date].push(shift)
        }
    })
    
    return groups
})

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatDateHeader = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'short',
        day: 'numeric'
    })
}

const navigateWeek = (direction: number) => {
    const currentDate = new Date(props.currentWeek)
    currentDate.setDate(currentDate.getDate() + (direction * 7))
    const newWeek = currentDate.toISOString().split('T')[0]
    
    router.get(route('admin.shifts.index'), { week: newWeek })
}

const assignUserToShift = (shiftId: number, userId: number) => {
    processing.value = true
    router.post(route('admin.shifts.assign', shiftId), { user_id: userId }, {
        onFinish: () => processing.value = false
    })
}

const removeUserFromShift = (shiftId: number, userId: number) => {
    processing.value = true
    router.delete(route('admin.shifts.unassign', shiftId), { 
        data: { user_id: userId },
        onFinish: () => processing.value = false
    })
}

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getShiftStatus = (shift: Shift) => {
    if (shift.assigned_count === 0) return { text: 'No volunteers', variant: 'destructive' as const }
    if (shift.is_full) return { text: 'Full', variant: 'default' as const }
    if (shift.assigned_count < shift.required_people) return { text: 'Needs volunteers', variant: 'secondary' as const }
    return { text: 'Staffed', variant: 'default' as const }
}
</script>

<template>
    <Head title="Admin - Shifts" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Admin - Shift Management</h1>
                    <p class="text-muted-foreground">Week of {{ formatDate(currentWeek) }}</p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <Button asChild variant="outline">
                        <Link :href="route('admin.users.index')">
                            <Users class="mr-2 h-4 w-4" />
                            Manage Users
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Week Navigation -->
            <Card>
                <CardContent class="flex items-center justify-between p-4">
                    <div class="flex items-center space-x-2">
                        <Button @click="navigateWeek(-1)" variant="outline" size="sm">
                            <ChevronLeft class="mr-1 h-4 w-4" />
                            Previous Week
                        </Button>
                        <Button @click="navigateWeek(1)" variant="outline" size="sm">
                            Next Week
                            <ChevronRight class="ml-1 h-4 w-4" />
                        </Button>
                    </div>
                    
                    <Button asChild variant="outline" size="sm">
                        <Link :href="route('shifts.index', { week: currentWeek })">
                            <Settings class="mr-2 h-4 w-4" />
                            User View
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <!-- Shifts Grid -->
            <div class="space-y-6">
                <div v-for="(dayShifts, date) in groupedShifts" :key="date">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <Calendar class="h-5 w-5" />
                                <span>{{ formatDateHeader(date) }}</span>
                            </CardTitle>
                        </CardHeader>
                        
                        <CardContent>
                            <div v-if="dayShifts.length === 0" class="py-12 text-center">
                                <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-muted flex items-center justify-center">
                                    <Calendar class="h-6 w-6 text-muted-foreground" />
                                </div>
                                <p class="text-muted-foreground">No shifts scheduled for this day</p>
                            </div>
                            
                            <div v-else class="space-y-4">
                                <Card v-for="shift in dayShifts" :key="shift.id" class="relative">
                                    <CardHeader>
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <CardTitle class="text-lg">{{ shift.name }}</CardTitle>
                                                <CardDescription>
                                                    {{ shift.start_time }} - {{ shift.end_time }}
                                                </CardDescription>
                                            </div>
                                            <Badge :variant="getShiftStatus(shift).variant">
                                                {{ getShiftStatus(shift).text }}
                                            </Badge>
                                        </div>
                                        
                                        <div v-if="shift.description" class="text-sm text-muted-foreground">
                                            {{ shift.description }}
                                        </div>
                                    </CardHeader>
                                    
                                    <CardContent class="space-y-4">
                                        <!-- Capacity Info -->
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="flex items-center">
                                                <Users class="mr-1 h-4 w-4" />
                                                Volunteers needed
                                            </span>
                                            <span class="font-medium">
                                                {{ shift.assigned_count }} / {{ shift.required_people }}
                                            </span>
                                        </div>
                                        
                                        <!-- Assigned Users -->
                                        <div>
                                            <h4 class="font-medium mb-3">Currently Assigned</h4>
                                            <div v-if="shift.users.length === 0" class="text-sm text-muted-foreground italic">
                                                No volunteers assigned
                                            </div>
                                            <div v-else class="space-y-2">
                                                <div 
                                                    v-for="user in shift.users" 
                                                    :key="user.id"
                                                    class="flex items-center justify-between p-3 bg-muted rounded-lg"
                                                >
                                                    <div class="flex items-center space-x-3">
                                                        <Avatar class="h-8 w-8">
                                                            <AvatarFallback class="text-xs">
                                                                {{ getInitials(user.name) }}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <div>
                                                            <p class="font-medium text-sm">{{ user.name }}</p>
                                                            <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                                                        </div>
                                                    </div>
                                                    <Button
                                                        @click="removeUserFromShift(shift.id, user.id)"
                                                        :disabled="processing"
                                                        size="sm"
                                                        variant="destructive"
                                                    >
                                                        <UserX class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Available Users -->                                        
                                        <div v-if="!shift.is_full">
                                            <h4 class="font-medium mb-3">Available Volunteers</h4>
                                            <div class="grid gap-2 max-h-40 overflow-y-auto">
                                                <div 
                                                    v-for="user in users.filter(u => !shift.users.some(su => su.id === u.id))" 
                                                    :key="user.id"
                                                    class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50"
                                                >
                                                    <div class="flex items-center space-x-3">
                                                        <Avatar class="h-8 w-8">
                                                            <AvatarFallback class="text-xs">
                                                                {{ getInitials(user.name) }}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <div>
                                                            <p class="font-medium text-sm">{{ user.name }}</p>
                                                            <p class="text-xs text-muted-foreground">
                                                                {{ user.shift_count }}/{{ user.shifts_expected }} shifts
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <Button
                                                        @click="assignUserToShift(shift.id, user.id)"
                                                        :disabled="processing"
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <UserCheck class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>