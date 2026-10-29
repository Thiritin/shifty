<script setup lang="ts">
import { computed, provide, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Progress } from '@/components/ui/progress'
import { 
    Calendar, 
    Users, 
    Clock,
    UserCheck,
    UserX,
    Plus,
    Minus,
    Printer,
    CalendarPlus,
    CalendarRange,
    LayoutGrid,
    List,
    Table2,
    Edit,
    Trash2,
    UserPlus,
    UserMinus,
    Settings
} from 'lucide-vue-next'
import dayjs from 'dayjs'
import { useStorage } from '@vueuse/core'
import ShiftCalendar from '@/components/shifts/ShiftCalendar.vue'
import ShiftList from '@/components/shifts/ShiftList.vue'
import ShiftTable from '@/components/shifts/ShiftTable.vue'
import { day, fillLabel, fillOf, shiftActionsKey, type Fill, type Shift, type ShiftGroup, type ShiftUser } from '@/components/shifts/shared'

interface User {
    id: number
    name: string
    email: string
    is_admin: boolean
    shift_count: number
    shifts_expected: number
    dog_mood: string
}

const props = defineProps<{
    shifts: Shift[]
    user: User
    allUsers: ShiftUser[]
}>()

const processing = ref(false)
const editingShift = ref<Shift | null>(null)
const showCreateDialog = ref(false)
const showEditDialog = ref(false)
const showDeleteDialog = ref(false)
const showAssignDialog = ref(false)
const selectedShift = ref<Shift | null>(null)

// Form data for creating/editing shifts
const shiftForm = ref({
    name: '',
    date: '',
    start_time: '',
    end_time: '',
    required_people: 1,
    description: ''
})

const resetForm = () => {
    shiftForm.value = {
        name: '',
        date: '',
        start_time: '',
        end_time: '',
        required_people: 1,
        description: ''
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Shifts', href: '/shifts' },
]

type View = 'grid' | 'list' | 'table' | 'calendar'
type GroupBy = 'day' | 'status' | 'shift' | 'mine'

const view = useStorage<View>('shifts.view', 'grid')
const groupBy = useStorage<GroupBy>('shifts.groupBy', 'day')

const views: { value: View; label: string; icon: typeof LayoutGrid }[] = [
    { value: 'grid', label: 'Grid', icon: LayoutGrid },
    { value: 'list', label: 'List', icon: List },
    { value: 'table', label: 'Table', icon: Table2 },
    { value: 'calendar', label: 'Calendar', icon: CalendarRange },
]

const fillOrder: Fill[] = ['empty', 'partial', 'full']

const groups = computed<ShiftGroup[]>(() => {
    const sorted = [...props.shifts].sort((a, b) => `${day(a)} ${a.start_time}`.localeCompare(`${day(b)} ${b.start_time}`))
    const keyOf: Record<GroupBy, (s: Shift) => string> = {
        day,
        status: fillOf,
        shift: (s) => s.name,
        mine: (s) => (s.is_assigned ? 'mine' : 'other'),
    }
    const map = new Map<string, Shift[]>()
    for (const shift of sorted) {
        const key = keyOf[groupBy.value](shift)
        map.set(key, [...(map.get(key) ?? []), shift])
    }
    const keys = [...map.keys()]
    if (groupBy.value === 'status') keys.sort((a, b) => fillOrder.indexOf(a as Fill) - fillOrder.indexOf(b as Fill))
    if (groupBy.value === 'shift') keys.sort((a, b) => a.localeCompare(b))
    if (groupBy.value === 'mine') keys.sort((a) => (a === 'mine' ? -1 : 1))
    const label = (key: string) =>
        ({ day: formatDate(key), status: fillLabel[key as Fill], shift: key, mine: key === 'mine' ? 'Your shifts' : 'Other shifts' })[groupBy.value]
    return keys.map((key) => ({ key, label: label(key), shifts: map.get(key)! }))
})

const formatDate = (dateStr: string) => {
    return dayjs(dateStr).format('dddd, MMMM D, YYYY')
}


const formatTime = (timeStr: string) => {
    if (!timeStr) return '';
    // Handle time strings like "09:00:00" or "09:00" - use 24-hour format
    return dayjs(`2000-01-01 ${timeStr}`).format('HH:mm');
}

const formatTimeForInput = (timeStr: string) => {
    if (!timeStr) return '';
    // Parse time and return in HH:mm format for HTML time inputs
    return dayjs(`2000-01-01 ${timeStr}`).format('HH:mm');
}

// Shifty mood logic (copied from Dashboard)
const userProgress = computed(() => {
    if (!props.user.shifts_expected) return 0;
    return Math.round((props.user.shift_count / props.user.shifts_expected) * 100);
});

const shiftyMood = computed(() => {
    const progress = userProgress.value;
    if (progress >= 100) return { emoji: '😸', mood: 'Ecstatic', description: "Shifty is purring with joy! You've exceeded expectations!" };
    if (progress >= 80) return { emoji: '😺', mood: 'Very Happy', description: 'Shifty is very pleased and relaxed!' };
    if (progress >= 60) return { emoji: '😊', mood: 'Content', description: 'Shifty is feeling quite satisfied.' };
    if (progress >= 40) return { emoji: '😐', mood: 'Neutral', description: 'Shifty is starting to worry about relaxation time.' };
    if (progress >= 20) return { emoji: '😟', mood: 'Concerned', description: 'Shifty is getting anxious about having to work.' };
    return { emoji: '😿', mood: 'Stressed', description: 'Shifty is very worried about having to do extra work!' };
});

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const page = usePage()
const canSelfAssign = computed(() => page.props.selfRegistration || props.user.is_admin)

const assignToShift = (shift: Shift) => {
    if (processing.value) return
    if (shift.my_status === 'red' && !confirm('You marked yourself unavailable for part of this shift. Sign up anyway?')) return
    
    processing.value = true
    router.post(`/shifts/${shift.id}/assign`, {}, {
        onFinish: () => {
            processing.value = false
        }
    })
}

const unassignFromShift = (shiftId: number) => {
    if (processing.value) return
    
    processing.value = true
    router.delete(`/shifts/${shiftId}/unassign`, {
        onFinish: () => {
            processing.value = false
        }
    })
}

const getShiftStatusColor = (shift: Shift) => {
    if (shift.is_full) return 'bg-green-100 text-green-800'
    if (shift.assigned_count > 0) return 'bg-yellow-100 text-yellow-800'
    return 'bg-red-100 text-red-800'
}

const getShiftStatusText = (shift: Shift) => {
    if (shift.is_full) return 'Full'
    if (shift.assigned_count > 0) return 'Partially Filled'
    return 'Empty'
}

const spotsAvailable = (shift: Shift) => {
    return shift.required_people - shift.assigned_count
}

// Admin functions
const openCreateDialog = () => {
    resetForm()
    showCreateDialog.value = true
}

const openEditDialog = (shift: Shift) => {
    editingShift.value = shift
    shiftForm.value = {
        name: shift.name,
        date: dayjs(shift.date).format('YYYY-MM-DD'),
        start_time: formatTimeForInput(shift.start_time),
        end_time: formatTimeForInput(shift.end_time),
        required_people: shift.required_people,
        description: shift.description || ''
    }
    showEditDialog.value = true
}

const openDeleteDialog = (shift: Shift) => {
    selectedShift.value = shift
    showDeleteDialog.value = true
}

const openAssignDialog = (shift: Shift) => {
    selectedShift.value = shift
    showAssignDialog.value = true
}

const createShift = () => {
    if (processing.value) return
    
    processing.value = true
    router.post('/shifts', shiftForm.value, {
        onSuccess: () => {
            showCreateDialog.value = false
            resetForm()
        },
        onFinish: () => {
            processing.value = false
        }
    })
}

const updateShift = () => {
    if (processing.value || !editingShift.value) return
    
    processing.value = true
    router.put(`/shifts/${editingShift.value.id}`, shiftForm.value, {
        onSuccess: () => {
            showEditDialog.value = false
            editingShift.value = null
            resetForm()
        },
        onFinish: () => {
            processing.value = false
        }
    })
}

const deleteShift = () => {
    if (processing.value || !selectedShift.value) return
    
    processing.value = true
    router.delete(`/shifts/${selectedShift.value.id}`, {
        onSuccess: () => {
            showDeleteDialog.value = false
            selectedShift.value = null
        },
        onFinish: () => {
            processing.value = false
        }
    })
}

const adminAssignUser = (userId: number) => {
    if (processing.value || !selectedShift.value) return
    
    processing.value = true
    router.post(`/shifts/${selectedShift.value.id}/assign/${userId}`, {}, {
        onFinish: () => {
            processing.value = false
        }
    })
}

const adminUnassignUser = (userId: number) => {
    if (processing.value || !selectedShift.value) return
    
    processing.value = true
    router.delete(`/shifts/${selectedShift.value.id}/unassign/${userId}`, {
        onFinish: () => {
            processing.value = false
        }
    })
}
provide(shiftActionsKey, {
    isAdmin: props.user.is_admin,
    canSelfAssign,
    processing,
    signUp: assignToShift,
    cancel: unassignFromShift,
    assign: openAssignDialog,
    edit: openEditDialog,
    remove: openDeleteDialog,
})
</script>

<template>
    <Head title="Shifts" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Team Shifts</h1>
                    <p class="text-muted-foreground">
                        Manage your shift assignments and view team schedule
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="icon" as-child title="Print schedule">
                        <a href="/shifts/print" target="_blank" rel="noopener" aria-label="Print schedule">
                            <Printer class="h-4 w-4" />
                        </a>
                    </Button>
                    <Button variant="outline" size="icon" as-child title="Export to calendar">
                        <a href="/shifts/calendar" aria-label="Export to calendar">
                            <CalendarPlus class="h-4 w-4" />
                        </a>
                    </Button>
                    <Button v-if="user.is_admin" @click="openCreateDialog">
                        <Plus class="h-4 w-4 mr-2" />
                        Create Shift
                    </Button>
                </div>
            </div>

            <!-- User Stats -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Your Shifts</CardTitle>
                        <UserCheck class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ user.shift_count }}</div>
                        <p class="text-xs text-muted-foreground">
                            of {{ user.shifts_expected }} expected
                        </p>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Shifts</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ shifts.length }}</div>
                        <p class="text-xs text-muted-foreground">
                            Available shifts
                        </p>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Cat Mood</CardTitle>
                        <span
                            class="text-lg"
                            :class="{
                                'animate-bounce': ['Very Happy', 'Ecstatic'].includes(shiftyMood.mood),
                                'animate-pulse': shiftyMood.mood === 'Content'
                            }"
                        >
                            {{ shiftyMood.emoji }}
                        </span>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ shiftyMood.mood }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ shiftyMood.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div v-if="shifts.length" class="flex flex-wrap items-center justify-between gap-3">
                <div class="inline-flex rounded-md border p-0.5" role="radiogroup" aria-label="View">
                    <button
                        v-for="v in views"
                        :key="v.value"
                        type="button"
                        role="radio"
                        :aria-checked="view === v.value"
                        class="inline-flex items-center gap-1.5 rounded-sm px-3 py-1.5 text-sm transition-colors"
                        :class="view === v.value ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
                        @click="view = v.value"
                    >
                        <component :is="v.icon" class="size-4" />
                        {{ v.label }}
                    </button>
                </div>
                <label v-if="view !== 'calendar'" class="flex items-center gap-2 text-sm text-muted-foreground">
                    Group by
                    <select
                        v-model="groupBy"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option value="day">Day</option>
                        <option value="status">Status</option>
                        <option value="shift">Shift</option>
                        <option value="mine">Your shifts</option>
                    </select>
                </label>
            </div>

            <ShiftList v-if="view === 'list'" :groups="groups" :show-date="groupBy !== 'day'" />
            <ShiftTable v-else-if="view === 'table'" :groups="groups" :show-date="groupBy !== 'day'" />
            <ShiftCalendar v-else-if="view === 'calendar'" :shifts="shifts" />
            <div v-else class="space-y-6">
                <div v-for="group in groups" :key="group.key" class="space-y-4">
                    <h2 class="text-xl font-semibold flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        {{ group.label }}
                    </h2>
                    
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Card 
                            v-for="shift in group.shifts" 
                            :key="shift.id" 
                            class="hover:shadow-lg transition-all duration-300 hover:scale-105 border-l-4"
                            :class="{
                                'border-l-green-500': shift.is_full,
                                'border-l-yellow-500': shift.assigned_count > 0 && !shift.is_full,
                                'border-l-red-500': shift.assigned_count === 0
                            }"
                        >
                            <CardHeader>
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <CardTitle class="text-lg">
                                                <Link v-if="user.is_admin" :href="`/shifts/${shift.id}`" class="hover:underline">{{ shift.name }}</Link>
                                                <template v-else>{{ shift.name }}</template>
                                            </CardTitle>
                                            <!-- Admin Action Icons -->
                                            <div v-if="user.is_admin" class="flex gap-1 ml-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-6 w-6 p-0 hover:bg-blue-100 transition-colors"
                                                    @click="openAssignDialog(shift)"
                                                    title="Manage Assignments"
                                                >
                                                    <Settings class="h-3 w-3" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-6 w-6 p-0 hover:bg-blue-100 transition-colors"
                                                    @click="openEditDialog(shift)"
                                                    title="Edit Shift"
                                                >
                                                    <Edit class="h-3 w-3" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-6 w-6 p-0 text-destructive hover:text-destructive hover:bg-red-100 transition-colors"
                                                    @click="openDeleteDialog(shift)"
                                                    title="Delete Shift"
                                                >
                                                    <Trash2 class="h-3 w-3" />
                                                </Button>
                                            </div>
                                        </div>
                                        <CardDescription v-if="shift.description">
                                            {{ shift.description }}
                                        </CardDescription>
                                    </div>
                                    <Badge :class="getShiftStatusColor(shift)" class="animate-pulse">
                                        {{ getShiftStatusText(shift) }}
                                    </Badge>
                                </div>
                            </CardHeader>
                            
                            <CardContent class="space-y-4">
                                <!-- Time and Capacity -->
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Clock class="h-4 w-4" />
                                        <span v-if="groupBy !== 'day'">{{ dayjs(shift.date).format('ddd D MMM') }},</span>
                                        {{ formatTime(shift.start_time) }} - {{ formatTime(shift.end_time) }}
                                        <Badge v-if="shift.my_status === 'green'" variant="outline" class="ml-auto border-emerald-600 text-emerald-700 dark:text-emerald-400">Available</Badge>
                                        <Badge v-else-if="shift.my_status === 'red'" variant="outline" class="ml-auto border-red-600 text-red-700 dark:text-red-400">Unavailable</Badge>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <div class="flex items-center gap-2">
                                                <Users class="h-4 w-4 text-muted-foreground" />
                                                <span>{{ shift.assigned_count }} / {{ shift.required_people }} people</span>
                                            </div>
                                            <span 
                                                v-if="spotsAvailable(shift) > 0" 
                                                class="text-orange-600 animate-pulse"
                                            >
                                                {{ spotsAvailable(shift) }} needed
                                            </span>
                                            <span 
                                                v-else
                                                class="text-green-600"
                                            >
                                                ✓ Full
                                            </span>
                                        </div>
                                        <Progress 
                                            :model-value="(shift.assigned_count / shift.required_people) * 100" 
                                            class="h-2 transition-all duration-500"
                                        />
                                    </div>
                                </div>

                                <!-- Assigned Users -->
                                <div v-if="shift.users.length > 0" class="space-y-2">
                                    <h4 class="text-sm font-medium">Assigned:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <div 
                                            v-for="shiftUser in shift.users" 
                                            :key="shiftUser.id"
                                            class="flex items-center gap-2"
                                        >
                                            <Avatar class="h-6 w-6">
                                                <AvatarFallback class="text-xs">
                                                    {{ getInitials(shiftUser.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <span class="text-sm" :class="{ 'font-medium text-blue-600': shiftUser.id === user.id }">
                                                {{ shiftUser.name }}
                                                <span v-if="shiftUser.id === user.id" class="text-xs text-blue-500">(You)</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div v-if="canSelfAssign" class="pt-2">
                                    <Button 
                                        v-if="!shift.is_assigned && !shift.is_full"
                                        @click="assignToShift(shift)"
                                        :disabled="processing"
                                        class="w-full"
                                    >
                                        <Plus class="h-4 w-4 mr-2" />
                                        Sign Up
                                    </Button>
                                    
                                    <Button 
                                        v-else-if="shift.is_assigned"
                                        @click="unassignFromShift(shift.id)"
                                        :disabled="processing"
                                        variant="outline"
                                        class="w-full"
                                    >
                                        <Minus class="h-4 w-4 mr-2" />
                                        Cancel Assignment
                                    </Button>
                                    
                                    <Button 
                                        v-else
                                        disabled
                                        variant="secondary"
                                        class="w-full"
                                    >
                                        <UserX class="h-4 w-4 mr-2" />
                                        Shift Full
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
                
            </div>

            <div v-if="!shifts.length" class="text-center py-12">
                    <Calendar class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                    <h3 class="text-lg font-medium text-muted-foreground mb-2">No shifts scheduled</h3>
                    <p class="text-sm text-muted-foreground">
                        Check back later for new shift opportunities.
                    </p>
            </div>
        </div>

        <!-- Create Shift Dialog -->
        <Dialog v-model:open="showCreateDialog">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Create New Shift</DialogTitle>
                    <DialogDescription>
                        Add a new shift to the schedule.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="name" class="text-right">Name</Label>
                        <Input
                            id="name"
                            v-model="shiftForm.name"
                            class="col-span-3"
                            placeholder="Shift name"
                        />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="date" class="text-right">Date</Label>
                        <Input
                            id="date"
                            v-model="shiftForm.date"
                            type="date"
                            class="col-span-3"
                        />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="start_time" class="text-right">Start</Label>
                        <Input
                            id="start_time"
                            v-model="shiftForm.start_time"
                            type="time"
                            class="col-span-3"
                        />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="end_time" class="text-right">End</Label>
                        <Input
                            id="end_time"
                            v-model="shiftForm.end_time"
                            type="time"
                            class="col-span-3"
                        />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="required_people" class="text-right">People</Label>
                        <Input
                            id="required_people"
                            v-model="shiftForm.required_people"
                            type="number"
                            min="1"
                            class="col-span-3"
                        />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="description" class="text-right">Description</Label>
                        <Input
                            id="description"
                            v-model="shiftForm.description"
                            class="col-span-3"
                            placeholder="Optional description"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button type="submit" @click="createShift" :disabled="processing">
                        Create Shift
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Shift Dialog -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Edit Shift</DialogTitle>
                    <DialogDescription>
                        Update the shift details.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_name" class="text-right">Name</Label>
                        <Input
                            id="edit_name"
                            v-model="shiftForm.name"
                            class="col-span-3"
                        />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_date" class="text-right">Date</Label>
                        <Input
                            id="edit_date"
                            v-model="shiftForm.date"
                            type="date"
                            class="col-span-3"
                        />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_start_time" class="text-right">Start</Label>
                        <Input
                            id="edit_start_time"
                            v-model="shiftForm.start_time"
                            type="time"
                            class="col-span-3"
                        />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_end_time" class="text-right">End</Label>
                        <Input
                            id="edit_end_time"
                            v-model="shiftForm.end_time"
                            type="time"
                            class="col-span-3"
                        />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_required_people" class="text-right">People</Label>
                        <Input
                            id="edit_required_people"
                            v-model="shiftForm.required_people"
                            type="number"
                            min="1"
                            class="col-span-3"
                        />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="edit_description" class="text-right">Description</Label>
                        <Input
                            id="edit_description"
                            v-model="shiftForm.description"
                            class="col-span-3"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button type="submit" @click="updateShift" :disabled="processing">
                        Update Shift
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Shift Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Delete Shift</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ selectedShift?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteShift" :disabled="processing">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Assign Users Dialog -->
        <Dialog v-model:open="showAssignDialog">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Manage Assignments</DialogTitle>
                    <DialogDescription>
                        Assign or remove users from "{{ selectedShift?.name }}"
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="space-y-2">
                        <h4 class="text-sm font-medium">Currently Assigned:</h4>
                        <div v-if="selectedShift?.users.length === 0" class="text-sm text-muted-foreground">
                            No users assigned
                        </div>
                        <div v-else class="space-y-2">
                            <div 
                                v-for="shiftUser in selectedShift?.users" 
                                :key="shiftUser.id"
                                class="flex items-center justify-between p-2 border rounded"
                            >
                                <div class="flex items-center gap-2">
                                    <Avatar class="h-6 w-6">
                                        <AvatarFallback class="text-xs">
                                            {{ getInitials(shiftUser.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span class="text-sm">{{ shiftUser.name }}</span>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="adminUnassignUser(shiftUser.id)"
                                    :disabled="processing"
                                    class="text-destructive hover:text-destructive"
                                >
                                    <UserMinus class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <h4 class="text-sm font-medium">Available Users:</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <div 
                                v-for="user in allUsers.filter(u => !selectedShift?.users.some(su => su.id === u.id))" 
                                :key="user.id"
                                class="flex items-center justify-between p-2 border rounded"
                            >
                                <div class="flex items-center gap-2">
                                    <Avatar class="h-6 w-6">
                                        <AvatarFallback class="text-xs">
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span class="text-sm">{{ user.name }}</span>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="adminAssignUser(user.id)"
                                    :disabled="processing || (selectedShift?.assigned_count || 0) >= (selectedShift?.required_people || 0)"
                                >
                                    <UserPlus class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button @click="showAssignDialog = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
