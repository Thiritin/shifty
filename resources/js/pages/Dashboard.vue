<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { 
    Calendar, 
    Users, 
    AlertTriangle, 
    CheckCircle, 
    Clock, 
    FileText, 
    Download,
    TrendingUp,
    Target,
    User,
    Heart,
    Sparkles,
    Zap
} from 'lucide-vue-next';
import dayjs from 'dayjs';

interface UserShift {
    id: number;
    name: string;
    description?: string;
    date: string;
    start_time: string;
    end_time: string;
    required_people: number;
    assigned_count: number;
}

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    shift_count: number;
    shifts_expected: number;
    dog_mood: string;
    has_seen_intro: boolean;
    arrival_date?: string;
    departure_date?: string;
    arrival_time?: string;
    departure_time?: string;
}

interface Shift {
    id: number;
    required_people: number;
    assigned_count: number;
    spots_available: number;
}

interface Props {
    shiftStats: {
        totalShifts: number;
        totalSpots: number;
        filledSpots: number;
        unfilledShifts: Shift[];
    };
    user: User;
    userShifts: UserShift[];
}

const props = defineProps<Props>();

const showIntroDialog = ref(!props.user.has_seen_intro);

const introForm = useForm({
    arrival_date: '',
    departure_date: '',
    arrival_time: '',
    departure_time: '',
});

const coveragePercentage = computed(() => {
    if (props.shiftStats.totalSpots === 0) return 0;
    return Math.round((props.shiftStats.filledSpots / props.shiftStats.totalSpots) * 100);
});

const userProgress = computed(() => {
    if (props.user.shifts_expected === 0) return 0;
    return Math.round((props.user.shift_count / props.user.shifts_expected) * 100);
});

const shiftyMood = computed(() => {
    const progress = userProgress.value;
    if (progress >= 100) return { emoji: '😸', mood: 'Ecstatic', description: 'Shifty is purring with joy! You\'ve exceeded expectations!' };
    if (progress >= 80) return { emoji: '😺', mood: 'Very Happy', description: 'Shifty is very pleased and relaxed!' };
    if (progress >= 60) return { emoji: '😊', mood: 'Content', description: 'Shifty is feeling quite satisfied.' };
    if (progress >= 40) return { emoji: '😐', mood: 'Neutral', description: 'Shifty is starting to worry about relaxation time.' };
    if (progress >= 20) return { emoji: '😟', mood: 'Concerned', description: 'Shifty is getting anxious about having to work.' };
    return { emoji: '😿', mood: 'Stressed', description: 'Shifty is very worried about having to do extra work!' };
});

const upcomingShifts = computed(() => {
    const now = dayjs();
    
    return props.userShifts
        .filter(shift => {
            // Create a proper datetime by combining date and start_time
            // Handle both "HH:mm" and "HH:mm:ss" formats
            const shiftDateTime = dayjs(`${shift.date} ${shift.start_time}`);
            
            // Show shifts that haven't started yet
            return shiftDateTime.isAfter(now);
        })
        .sort((a, b) => {
            // Sort by date and time
            const aDateTime = dayjs(`${a.date} ${a.start_time}`);
            const bDateTime = dayjs(`${b.date} ${b.start_time}`);
            return aDateTime.isBefore(bDateTime) ? -1 : 1;
        })
        .slice(0, 3);
});

const formatDate = (dateString: string) => {
    return dayjs(dateString).format('dddd, MMM D');
};

const formatTime = (timeString: string) => {
    // Handle time strings like "09:00:00" or "09:00" - use 24-hour format
    return dayjs(`2000-01-01 ${timeString}`).format('HH:mm');
};

const completeIntro = () => {
    introForm.post('/settings/availability/intro', {
        onSuccess: () => {
            showIntroDialog.value = false;
        }
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <!-- Header with Shifty -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="space-y-2">
                        <h1 class="text-3xl font-bold tracking-tight">Welcome back, {{ user.name }}!</h1>
                        <p class="text-muted-foreground">
                            Let's see how Shifty is feeling today
                        </p>
                    </div>
                </div>
                
                <!-- Shifty Mood Card -->
                <Card class="border-2 border-dashed border-primary/20 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-950/20 dark:to-purple-950/20">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="text-4xl animate-bounce">{{ shiftyMood.emoji }}</div>
                                <div>
                                    <CardTitle class="text-xl">Shifty is {{ shiftyMood.mood }}</CardTitle>
                                    <CardDescription class="text-base">{{ shiftyMood.description }}</CardDescription>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold">{{ userProgress }}%</div>
                                <p class="text-sm text-muted-foreground">Progress</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span>{{ user.shift_count }} of {{ user.shifts_expected }} shifts completed</span>
                                <span>{{ Math.max(0, user.shifts_expected - user.shift_count) }} remaining</span>
                            </div>
                            <Progress 
                                :model-value="userProgress" 
                                class="h-3 transition-all duration-500 ease-in-out"
                            />
                        </div>
                        <div class="mt-4 flex gap-2">
                            <Button as-child size="sm">
                                <Link href="/shifts">
                                    <Sparkles class="h-4 w-4 mr-2" />
                                    Make Shifty Happy!
                                </Link>
                            </Button>
                            <Button variant="outline" size="sm" as-child>
                                <Link href="/settings/availability">
                                    <Calendar class="h-4 w-4 mr-2" />
                                    Update Availability
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Stats Overview -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card class="hover:shadow-md transition-shadow">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Coverage</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ coveragePercentage }}%</div>
                        <p class="text-xs text-muted-foreground">
                            {{ shiftStats.filledSpots }} of {{ shiftStats.totalSpots }} spots filled
                        </p>
                        <Progress 
                            :model-value="coveragePercentage" 
                            class="mt-3"
                        />
                    </CardContent>
                </Card>

                <Card class="hover:shadow-md transition-shadow">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Open Spots</CardTitle>
                        <AlertTriangle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ shiftStats.totalSpots - shiftStats.filledSpots }}</div>
                        <p class="text-xs text-muted-foreground">
                            spots need volunteers
                        </p>
                        <div class="mt-3">
                            <Badge 
                                v-if="(shiftStats.totalSpots - shiftStats.filledSpots) > 0"
                                variant="destructive"
                                class="animate-pulse"
                            >
                                Help Needed!
                            </Badge>
                            <Badge v-else variant="default" class="bg-green-100 text-green-800">
                                All Set!
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card class="hover:shadow-md transition-shadow">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Your Shifts</CardTitle>
                        <User class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ user.shift_count }}</div>
                        <p class="text-xs text-muted-foreground">
                            shifts assigned
                        </p>
                        <div class="mt-3">
                            <Badge 
                                v-if="userProgress >= 100"
                                class="bg-gold-100 text-gold-800 animate-pulse"
                            >
                                <Heart class="h-3 w-3 mr-1" />
                                Amazing!
                            </Badge>
                            <Badge 
                                v-else-if="userProgress >= 80"
                                class="bg-green-100 text-green-800"
                            >
                                Great Job!
                            </Badge>
                            <Badge 
                                v-else-if="userProgress >= 60"
                                class="bg-blue-100 text-blue-800"
                            >
                                Good Progress
                            </Badge>
                            <Badge v-else variant="outline">
                                Keep Going!
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card class="hover:shadow-md transition-shadow">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Shifts</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ shiftStats.totalShifts }}</div>
                        <p class="text-xs text-muted-foreground">
                            shifts available
                        </p>
                        <div class="mt-3">
                            <Button variant="outline" size="sm" as-child>
                                <Link href="/shifts">View All</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Upcoming Shifts -->
            <Card v-if="upcomingShifts.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Your Upcoming Shifts
                    </CardTitle>
                    <CardDescription>
                        Here are your next scheduled shifts
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div 
                            v-for="shift in upcomingShifts" 
                            :key="shift.id"
                            class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ shift.name }}</span>
                                    <span class="text-sm text-muted-foreground">{{ formatDate(shift.date) }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium">{{ formatTime(shift.start_time) }} - {{ formatTime(shift.end_time) }}</div>
                                <div class="text-sm text-muted-foreground">{{ shift.assigned_count }}/{{ shift.required_people }} people</div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- No upcoming shifts message -->
            <Card v-else>
                <CardContent class="pt-6">
                    <div class="text-center py-8">
                        <Calendar class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-muted-foreground mb-2">No upcoming shifts</h3>
                        <p class="text-sm text-muted-foreground mb-4">
                            Ready to help make Shifty happy? Sign up for some shifts!
                        </p>
                        <Button as-child>
                            <Link href="/shifts">
                                <Zap class="h-4 w-4 mr-2" />
                                Browse Available Shifts
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Introduction Dialog -->
        <Dialog v-model:open="showIntroDialog">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle class="text-xl flex items-center gap-2">
                        <span class="text-3xl">😸</span>
                        Welcome to Shifty!
                    </DialogTitle>
                    <DialogDescription class="text-base space-y-3">
                        <p>
                            Meet <strong>Shifty</strong>, your lazy cat companion! Shifty doesn't want to work, 
                            so your goal is to make Shifty happy by signing yourself up for shifts.
                        </p>
                        <p>
                            When Shifty is happy, it means they can relax all day while you handle the work! 
                            The more shifts you complete, the happier Shifty becomes.
                        </p>
                        <p>
                            First, let us know when you'll be available so we can schedule your shifts accordingly:
                        </p>
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="intro_arrival_date">Arrival Date</Label>
                            <Input
                                id="intro_arrival_date"
                                v-model="introForm.arrival_date"
                                type="date"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="intro_arrival_time">Arrival Time</Label>
                            <Input
                                id="intro_arrival_time"
                                v-model="introForm.arrival_time"
                                type="time"
                                required
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="intro_departure_date">Departure Date</Label>
                            <Input
                                id="intro_departure_date"
                                v-model="introForm.departure_date"
                                type="date"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="intro_departure_time">Departure Time</Label>
                            <Input
                                id="intro_departure_time"
                                v-model="introForm.departure_time"
                                type="time"
                                required
                            />
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button 
                        @click="completeIntro" 
                        :disabled="introForm.processing"
                        class="w-full"
                    >
                        <Sparkles class="h-4 w-4 mr-2" />
                        {{ introForm.processing ? 'Setting up...' : 'Start Making Shifty Happy!' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
