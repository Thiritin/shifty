<script setup lang="ts">
import { computed, onBeforeUnmount, watch } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLogoIcon from '@/components/AppLogoIcon.vue'
import { testMail, update } from '@/routes/admin/settings'
import { type BreadcrumbItem } from '@/types'

type AssetName = 'logo_light' | 'logo_dark' | 'favicon'

const props = defineProps<{
    settings: {
        app_name: string
        organization: string | null
        default_shifts_expected: number
        self_registration: boolean
        timezone: string
        accent_color: string | null
        mail_mailer: 'smtp' | 'log'
        mail_host: string | null
        mail_port: number | null
        mail_username: string | null
        mail_password_set: boolean
        mail_from_address: string | null
        mail_from_name: string | null
        password_login: boolean
        sso_enabled: boolean
        sso_label: string | null
        sso_discovery_url: string | null
        sso_client_id: string | null
        sso_client_secret_set: boolean
        sso_scopes: string | null
        sso_access_claim: string | null
        sso_access_value: string | null
        sso_admin_claim: string | null
        sso_admin_value: string | null
        sso_auto_create: boolean
        sso_link_by_email: boolean
    }
    ssoCallbackUrl: string
    timezones: string[]
    assets: Record<AssetName, string | null>
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'App Settings', href: '/admin/settings' }]

const page = usePage()

const form = useForm({
    ...props.settings,
    organization: props.settings.organization ?? '',
    mail_host: props.settings.mail_host ?? '',
    mail_port: props.settings.mail_port ?? ('' as number | ''),
    mail_username: props.settings.mail_username ?? '',
    mail_from_address: props.settings.mail_from_address ?? '',
    mail_from_name: props.settings.mail_from_name ?? '',
    mail_password: '',
    sso_label: props.settings.sso_label ?? '',
    sso_discovery_url: props.settings.sso_discovery_url ?? '',
    sso_client_id: props.settings.sso_client_id ?? '',
    sso_client_secret: '',
    sso_scopes: props.settings.sso_scopes ?? '',
    sso_access_claim: props.settings.sso_access_claim ?? '',
    sso_access_value: props.settings.sso_access_value ?? '',
    sso_admin_claim: props.settings.sso_admin_claim ?? '',
    sso_admin_value: props.settings.sso_admin_value ?? '',
    logo_light: null as File | null,
    logo_dark: null as File | null,
    favicon: null as File | null,
    remove: [] as AssetName[],
})

const selectClass =
    'border-input dark:bg-input/30 flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] md:text-sm'

const objectUrls: Partial<Record<AssetName, string>> = {}

const pickFile = (name: AssetName, event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null
    form[name] = file
    form.remove = form.remove.filter((n) => n !== name)
    if (objectUrls[name]) URL.revokeObjectURL(objectUrls[name]!)
    objectUrls[name] = file ? URL.createObjectURL(file) : undefined
}

const removeAsset = (name: AssetName) => {
    form[name] = null
    if (!form.remove.includes(name)) form.remove.push(name)
    if (objectUrls[name]) URL.revokeObjectURL(objectUrls[name]!)
    objectUrls[name] = undefined
}

const preview = computed(() => {
    const url = (name: AssetName) =>
        (form[name] && objectUrls[name]) || (form.remove.includes(name) ? null : props.assets[name])
    const light = url('logo_light')
    return { light, dark: url('logo_dark') ?? light, favicon: url('favicon') }
})

const accentStyle = computed(() => (form.accent_color ? { backgroundColor: form.accent_color } : {}))

watch(
    () => form.mail_mailer,
    (mailer) => {
        if (mailer === 'log') form.clearErrors('mail_host', 'mail_port', 'mail_from_address')
    },
)

const submit = () => {
    const accentChanged = form.accent_color !== props.settings.accent_color
    form.post(update.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('mail_password', 'sso_client_secret', 'logo_light', 'logo_dark', 'favicon', 'remove')
            // The accent color is rendered into the page head, which Inertia visits don't replace.
            if (accentChanged) window.location.reload()
        },
    })
}

const sendTestMail = () => router.post(testMail.url(), {}, { preserveScroll: true })

onBeforeUnmount(() => Object.values(objectUrls).forEach((u) => u && URL.revokeObjectURL(u)))
</script>

<template>
    <Head title="App Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="mx-auto w-full max-w-3xl space-y-6 p-4 md:p-6" @submit.prevent="submit">
            <Heading title="App Settings" />

            <Card>
                <CardHeader>
                    <CardTitle>General</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="app_name">App name</Label>
                        <Input id="app_name" v-model="form.app_name" required maxlength="60" />
                        <InputError :message="form.errors.app_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="organization">Organization</Label>
                        <Input id="organization" v-model="form.organization" maxlength="100" />
                        <InputError :message="form.errors.organization" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="timezone">Event timezone</Label>
                        <Input id="timezone" v-model="form.timezone" list="timezones" required />
                        <datalist id="timezones">
                            <option v-for="tz in timezones" :key="tz" :value="tz" />
                        </datalist>
                        <InputError :message="form.errors.timezone" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Shifts</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="default_shifts_expected">Default shifts per person</Label>
                        <Input id="default_shifts_expected" v-model="form.default_shifts_expected" type="number" min="0" max="1000" required />
                        <InputError :message="form.errors.default_shifts_expected" />
                    </div>
                    <div class="flex items-center gap-3 md:pt-7">
                        <Checkbox
                            id="self_registration"
                            :model-value="form.self_registration"
                            @update:model-value="(v) => (form.self_registration = v === true)"
                        />
                        <Label for="self_registration">Users can sign up for shifts themselves</Label>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Branding</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div v-for="asset in (['logo_light', 'logo_dark', 'favicon'] as AssetName[])" :key="asset" class="grid content-start gap-2">
                            <Label :for="asset">{{ { logo_light: 'Logo (light mode)', logo_dark: 'Logo (dark mode)', favicon: 'Favicon' }[asset] }}</Label>
                            <Input
                                :id="asset"
                                type="file"
                                :accept="asset === 'favicon' ? '.png,.svg,.ico' : '.png,.jpg,.jpeg,.webp,.svg'"
                                @change="pickFile(asset, $event)"
                            />
                            <Button
                                v-if="form[asset] || (assets[asset] && !form.remove.includes(asset))"
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="justify-self-start"
                                @click="removeAsset(asset)"
                            >
                                Remove
                            </Button>
                            <InputError :message="form.errors[asset]" />
                        </div>
                    </div>

                    <div class="grid gap-2 md:w-1/3">
                        <Label for="accent_color">Accent color</Label>
                        <div class="flex items-center gap-2">
                            <input
                                id="accent_color"
                                type="color"
                                class="h-9 w-12 cursor-pointer rounded-md border border-input bg-transparent p-1"
                                :value="form.accent_color ?? '#171717'"
                                @input="form.accent_color = ($event.target as HTMLInputElement).value"
                            />
                            <span class="font-mono text-sm">{{ form.accent_color ?? 'Default' }}</span>
                            <Button v-if="form.accent_color" type="button" variant="ghost" size="sm" @click="form.accent_color = null">Reset</Button>
                        </div>
                        <InputError :message="form.errors.accent_color" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div
                            v-for="mode in (['light', 'dark'] as const)"
                            :key="mode"
                            :class="[mode === 'dark' ? 'dark bg-neutral-950 text-neutral-50' : 'bg-white text-neutral-950', 'rounded-lg border p-4']"
                            :aria-label="`${mode} mode preview`"
                        >
                            <div class="flex items-center gap-2">
                                <img v-if="preview[mode]" :src="preview[mode]!" alt="" class="size-8 object-contain" />
                                <AppLogoIcon v-else :class="['size-8 fill-current', mode === 'dark' ? 'text-white' : 'text-black']" />
                                <div class="grid text-sm leading-tight">
                                    <span v-if="form.organization" class="text-xs uppercase opacity-60">{{ form.organization }}</span>
                                    <span class="font-semibold">{{ form.app_name }}</span>
                                </div>
                                <img v-if="preview.favicon" :src="preview.favicon" alt="" class="ml-auto size-4 object-contain" />
                            </div>
                            <span
                                class="mt-4 inline-flex h-8 items-center rounded-md px-3 text-sm font-medium"
                                :class="form.accent_color ? '' : mode === 'dark' ? 'bg-neutral-50 text-neutral-900' : 'bg-neutral-900 text-neutral-50'"
                                :style="accentStyle"
                            >
                                Sign Up
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Authentication</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="flex items-center gap-3">
                        <Checkbox id="password_login" :model-value="form.password_login" @update:model-value="(v) => (form.password_login = v === true)" />
                        <Label for="password_login">Password login</Label>
                    </div>
                    <div class="flex items-center gap-3">
                        <Checkbox id="sso_enabled" :model-value="form.sso_enabled" @update:model-value="(v) => (form.sso_enabled = v === true)" />
                        <Label for="sso_enabled">Single sign-on (OpenID Connect)</Label>
                    </div>
                    <InputError class="md:col-span-2" :message="form.errors.password_login" />

                    <template v-if="form.sso_enabled">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="sso_callback">Callback URL</Label>
                            <Input id="sso_callback" :model-value="ssoCallbackUrl" readonly class="font-mono" @focus="($event.target as HTMLInputElement).select()" />
                        </div>
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="sso_discovery_url">Discovery URL</Label>
                            <Input id="sso_discovery_url" v-model="form.sso_discovery_url" type="url" required placeholder="https://id.example.org/.well-known/openid-configuration" />
                            <InputError :message="form.errors.sso_discovery_url" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="sso_client_id">Client ID</Label>
                            <Input id="sso_client_id" v-model="form.sso_client_id" required autocomplete="off" />
                            <InputError :message="form.errors.sso_client_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="sso_client_secret">Client secret</Label>
                            <Input
                                id="sso_client_secret"
                                v-model="form.sso_client_secret"
                                type="password"
                                autocomplete="new-password"
                                :placeholder="settings.sso_client_secret_set ? '••••••••' : ''"
                            />
                            <InputError :message="form.errors.sso_client_secret" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="sso_label">Button label</Label>
                            <Input id="sso_label" v-model="form.sso_label" required maxlength="60" />
                            <InputError :message="form.errors.sso_label" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="sso_scopes">Scopes</Label>
                            <Input id="sso_scopes" v-model="form.sso_scopes" required class="font-mono" />
                            <InputError :message="form.errors.sso_scopes" />
                        </div>

                        <fieldset class="grid gap-2 md:col-span-2">
                            <legend class="mb-2 text-sm font-medium">Allow sign-in only when claim contains</legend>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="sso_access_claim">Claim</Label>
                                    <Input id="sso_access_claim" v-model="form.sso_access_claim" class="font-mono" placeholder="groups" />
                                    <InputError :message="form.errors.sso_access_claim" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="sso_access_value">Value</Label>
                                    <Input id="sso_access_value" v-model="form.sso_access_value" class="font-mono" />
                                    <InputError :message="form.errors.sso_access_value" />
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="grid gap-2 md:col-span-2">
                            <legend class="mb-2 text-sm font-medium">Make admin when claim contains</legend>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="sso_admin_claim">Claim</Label>
                                    <Input id="sso_admin_claim" v-model="form.sso_admin_claim" class="font-mono" placeholder="groups" />
                                    <InputError :message="form.errors.sso_admin_claim" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="sso_admin_value">Value</Label>
                                    <Input id="sso_admin_value" v-model="form.sso_admin_value" class="font-mono" />
                                    <InputError :message="form.errors.sso_admin_value" />
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex items-center gap-3">
                            <Checkbox id="sso_auto_create" :model-value="form.sso_auto_create" @update:model-value="(v) => (form.sso_auto_create = v === true)" />
                            <Label for="sso_auto_create">Create accounts on first sign-in</Label>
                        </div>
                        <div class="flex items-center gap-3">
                            <Checkbox id="sso_link_by_email" :model-value="form.sso_link_by_email" @update:model-value="(v) => (form.sso_link_by_email = v === true)" />
                            <Label for="sso_link_by_email">Link to existing accounts by email</Label>
                        </div>
                    </template>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Mail</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2 md:col-span-2 md:w-1/2">
                        <Label for="mail_mailer">Delivery</Label>
                        <select id="mail_mailer" v-model="form.mail_mailer" :class="selectClass">
                            <option value="smtp">SMTP</option>
                            <option value="log">Disabled</option>
                        </select>
                    </div>
                    <template v-if="form.mail_mailer === 'smtp'">
                        <div class="grid gap-2">
                            <Label for="mail_host">Host</Label>
                            <Input id="mail_host" v-model="form.mail_host" required />
                            <InputError :message="form.errors.mail_host" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="mail_port">Port</Label>
                            <Input id="mail_port" v-model="form.mail_port" type="number" min="1" max="65535" required />
                            <InputError :message="form.errors.mail_port" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="mail_username">Username</Label>
                            <Input id="mail_username" v-model="form.mail_username" autocomplete="off" />
                            <InputError :message="form.errors.mail_username" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="mail_password">Password</Label>
                            <Input
                                id="mail_password"
                                v-model="form.mail_password"
                                type="password"
                                autocomplete="new-password"
                                :placeholder="settings.mail_password_set ? '••••••••' : ''"
                            />
                            <InputError :message="form.errors.mail_password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="mail_from_address">From address</Label>
                            <Input id="mail_from_address" v-model="form.mail_from_address" type="email" required />
                            <InputError :message="form.errors.mail_from_address" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="mail_from_name">From name</Label>
                            <Input id="mail_from_name" v-model="form.mail_from_name" />
                            <InputError :message="form.errors.mail_from_name" />
                        </div>
                        <div class="md:col-span-2">
                            <Button type="button" variant="outline" :disabled="form.isDirty" @click="sendTestMail">Send test mail</Button>
                        </div>
                    </template>
                </CardContent>
            </Card>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="form.processing">Save</Button>
                <p v-if="page.props.flash.success && !form.isDirty" class="text-sm text-muted-foreground" role="status">
                    {{ page.props.flash.success }}
                </p>
                <p v-if="page.props.flash.error" class="text-sm text-red-600" role="alert">{{ page.props.flash.error }}</p>
            </div>
        </form>
    </AppLayout>
</template>
