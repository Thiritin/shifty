import dayjs from 'dayjs'
import type { InjectionKey, Ref } from 'vue'

export interface ShiftUser {
    id: number
    name: string
}

export interface Shift {
    id: number
    name: string
    date: string
    start_time: string
    end_time: string
    required_people: number
    description: string | null
    assigned_count: number
    is_assigned: boolean
    is_full: boolean
    my_status: 'green' | 'red' | 'neutral'
    users: ShiftUser[]
}

export interface ShiftGroup {
    key: string
    label: string
    shifts: Shift[]
}

export type Fill = 'empty' | 'partial' | 'full'

export const fillOf = (shift: Shift): Fill => (shift.is_full ? 'full' : shift.assigned_count > 0 ? 'partial' : 'empty')

export const fillLabel: Record<Fill, string> = { empty: 'Open', partial: 'Partially filled', full: 'Full' }

export const fillBorder: Record<Fill, string> = {
    empty: 'border-l-red-500',
    partial: 'border-l-yellow-500',
    full: 'border-l-green-500',
}

export const day = (shift: Shift) => dayjs(shift.date).format('YYYY-MM-DD')
export const time = (t: string) => t.slice(0, 5)
export const timeRange = (shift: Shift) => `${time(shift.start_time)} - ${time(shift.end_time)}`

export interface ShiftActions {
    isAdmin: boolean
    canSelfAssign: Ref<boolean>
    processing: Ref<boolean>
    signUp: (shift: Shift) => void
    cancel: (shiftId: number) => void
    assign: (shift: Shift) => void
    edit: (shift: Shift) => void
    remove: (shift: Shift) => void
}

export const shiftActionsKey: InjectionKey<ShiftActions> = Symbol('shiftActions')
