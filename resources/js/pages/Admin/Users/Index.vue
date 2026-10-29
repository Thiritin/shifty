<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { store as storeAdminUser, update as updateAdminUser } from '@/routes/admin/users'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Progress } from '@/components/ui/progress'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { 
    Users, 
    UserCheck, 
    Shield, 
    TrendingUp, 
    Edit, 
    Plus,
    KeyRound,
    Loader2,
} from 'lucide-vue-next'

interface User {
    id: number
    name: string
    email: string
    shift_count: number
    shifts_expected: number
    dog_mood: 'happy' | 'mediocre' | 'sad'
    is_admin: boolean
    has_password: boolean
    has_sso: boolean
    created_at: string
}

const props = defineProps<{
    users: User[]
    defaultShiftsExpected: number
}>()

const dialogOpen = ref(false)
const editingUser = ref<User | null>(null)

const userForm = useForm({
    name: '',
    email: '',
    password: '',
    shifts_expected: 0,
    is_admin: false,
})

const activeUsers = computed(() => {
    return props.users.filter(user => user.shift_count > 0).length
})

const adminCount = computed(() => {
    return props.users.filter(user => user.is_admin).length
})

const avgPerformance = computed(() => {
    if (props.users.length === 0) return 0
    const totalPerformance = props.users.reduce((sum, user) => {
        const performance = user.shifts_expected > 0 ? (user.shift_count / user.shifts_expected) * 100 : 0
        return sum + performance
    }, 0)
    return Math.round(totalPerformance / props.users.length)
})

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin', href: '#' },
    { title: 'Users', href: '/admin/users' },
]

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getProgressPercentage = (user: User) => {
    if (user.shifts_expected === 0) return 0
    return Math.min(100, (user.shift_count / user.shifts_expected) * 100)
}

const getMoodVariant = (mood: string) => {
    switch (mood) {
        case 'happy': return 'default' as const
        case 'mediocre': return 'secondary' as const
        case 'sad': return 'destructive' as const
        default: return 'secondary' as const
    }
}

const openCreate = () => {
    editingUser.value = null
    userForm.clearErrors()
    userForm.defaults({ name: '', email: '', password: '', shifts_expected: props.defaultShiftsExpected, is_admin: false })
    userForm.reset()
    dialogOpen.value = true
}

const editUser = (user: User) => {
    editingUser.value = user
    userForm.clearErrors()
    userForm.defaults({ name: user.name, email: user.email, password: '', shifts_expected: user.shifts_expected, is_admin: user.is_admin })
    userForm.reset()
    dialogOpen.value = true
}

const saveUser = () => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) }
    if (editingUser.value) userForm.put(updateAdminUser.url(editingUser.value.id), options)
    else userForm.post(storeAdminUser.url(), options)
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />

        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold">Users</h1>
                <Button @click="openCreate">
                    <Plus class="mr-2 h-4 w-4" />
                    Add user
                </Button>
            </div>

            <!-- Users Overview Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ users.length }}</div>
                        <p class="text-xs text-muted-foreground">
                            registered users
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Users</CardTitle>
                        <UserCheck class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ activeUsers }}</div>
                        <p class="text-xs text-muted-foreground">
                            with assigned shifts
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Admins</CardTitle>
                        <Shield class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ adminCount }}</div>
                        <p class="text-xs text-muted-foreground">
                            admin users
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Avg. Performance</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ avgPerformance }}%</div>
                        <p class="text-xs text-muted-foreground">
                            shift completion
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Users List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Users class="h-5 w-5" />
                        Team Members
                    </CardTitle>
                    <CardDescription>
                        Manage user expectations and view performance
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div 
                            v-for="user in users" 
                            :key="user.id"
                            class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 transition-colors"
                        >
                            <div class="flex items-center gap-4">
                                <Avatar class="h-10 w-10">
                                    <AvatarFallback>{{ getInitials(user.name) }}</AvatarFallback>
                                </Avatar>
                                
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium">{{ user.name }}</p>
                                        <Badge v-if="user.is_admin" variant="secondary" class="text-xs">
                                            <Shield class="mr-1 h-3 w-3" />
                                            Admin
                                        </Badge>
                                        <Badge v-if="user.has_sso" variant="outline" class="text-xs">SSO</Badge>
                                        <Badge v-if="user.has_password" variant="outline" class="text-xs">
                                            <KeyRound class="mr-1 h-3 w-3" />
                                            Password
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-6">
                                <!-- Shift Performance -->
                                <div class="text-center">
                                    <div class="text-sm font-medium">{{ user.shift_count }}/{{ user.shifts_expected }}</div>
                                    <div class="text-xs text-muted-foreground">shifts</div>
                                    <Progress 
                                        :model-value="getProgressPercentage(user)" 
                                        class="w-16 mt-1"
                                    />
                                </div>
                                
                                <!-- Dog Mood -->
                                <div class="text-center">
                                    <Badge :variant="getMoodVariant(user.dog_mood)" class="mb-1">
                                        {{ user.dog_mood }}
                                    </Badge>
                                    <div class="text-xs text-muted-foreground">mood</div>
                                </div>
                                
                                <!-- Edit Button -->
                                <Button 
                                    variant="outline" 
                                    size="sm"
                                    @click="editUser(user)"
                                >
                                    <Edit class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editingUser ? 'Edit user' : 'Add user' }}</DialogTitle>
                    <DialogDescription v-if="editingUser?.has_sso">This user signs in with single sign-on, which updates their name and email on each login.</DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="saveUser">
                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="userForm.name" required />
                        <InputError :message="userForm.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="userForm.email" type="email" required />
                        <InputError :message="userForm.errors.email" />
                    </div>

                    <div class="space-y-2">
                        <Label for="password">{{ editingUser?.has_password ? 'New password' : 'Password' }}</Label>
                        <Input id="password" v-model="userForm.password" type="password" autocomplete="new-password" minlength="8" />
                        <InputError :message="userForm.errors.password" />
                    </div>

                    <div class="space-y-2">
                        <Label for="shifts_expected">Expected shifts</Label>
                        <Input id="shifts_expected" v-model.number="userForm.shifts_expected" type="number" min="0" required />
                        <InputError :message="userForm.errors.shifts_expected" />
                    </div>

                    <div class="flex items-center space-x-2">
                        <input id="is_admin" v-model="userForm.is_admin" type="checkbox" class="rounded border-input" />
                        <Label for="is_admin">Admin</Label>
                    </div>
                    <InputError :message="userForm.errors.is_admin" />

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="userForm.processing">
                            <Loader2 v-if="userForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            {{ editingUser ? 'Save' : 'Add user' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>