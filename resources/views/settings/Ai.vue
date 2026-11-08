<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import SettingsLayout from '@/layouts/SettingsLayout.vue'
import settingsRoutes from '@/routes/settings'
import type { AiApiKeyStatus, AiProviderOption, AiProviderValue, AiSettings } from '@/types/data'
import { Form, useForm } from '@inertiajs/vue3'
import { ExternalLink, PlugZap } from '@lucide/vue'
import { computed } from 'vue'

interface Props {
    settings: AiSettings
    apiKeys: Record<AiProviderValue, AiApiKeyStatus>
    providers: AiProviderOption[]
    isReady: boolean
}

const props = defineProps<Props>()

const keyFields = {
    anthropic: { key: 'anthropicApiKey', remove: 'removeAnthropicApiKey' },
    openrouter: { key: 'openRouterApiKey', remove: 'removeOpenRouterApiKey' },
    'opencode-go': { key: 'openCodeGoApiKey', remove: 'removeOpenCodeGoApiKey' },
} as const

const form = useForm({
    provider: props.settings.provider,
    chatModel: props.settings.chatModel ?? '',
    fastModel: props.settings.fastModel ?? '',
    anthropicApiKey: '',
    openRouterApiKey: '',
    openCodeGoApiKey: '',
    removeAnthropicApiKey: false,
    removeOpenRouterApiKey: false,
    removeOpenCodeGoApiKey: false,
})

const provider = computed(() => props.providers.find((option) => option.value === form.provider) ?? props.providers[0])
const fields = computed(() => keyFields[form.provider])
const keyStatus = computed(() => props.apiKeys[form.provider])

const keyBadge = computed(() => {
    if (keyStatus.value.isSaved && !form[fields.value.remove]) {
        return { label: 'Saved', variant: 'secondary' as const }
    }

    if (keyStatus.value.isInEnv) {
        return { label: 'From .env', variant: 'outline' as const }
    }

    return { label: 'Not Set', variant: 'destructive' as const }
})

const changeProvider = (value: unknown) => {
    if (value === form.provider) {
        return
    }

    form.provider = value as AiProviderValue
    form.chatModel = ''
    form.fastModel = ''
}

const submit = () =>
    form
        .transform((data) => ({
            ...data,
            anthropicApiKey: data.anthropicApiKey || null,
            openRouterApiKey: data.openRouterApiKey || null,
            openCodeGoApiKey: data.openCodeGoApiKey || null,
            chatModel: data.chatModel || null,
            fastModel: data.fastModel || null,
        }))
        .put(settingsRoutes.ai.update.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.anthropicApiKey = ''
                form.openRouterApiKey = ''
                form.openCodeGoApiKey = ''
                form.removeAnthropicApiKey = false
                form.removeOpenRouterApiKey = false
                form.removeOpenCodeGoApiKey = false
                form.defaults()
            },
        })
</script>

<template>
    <SettingsLayout>
        <div class="space-y-6">
            <form @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle>Provider</CardTitle>
                        <CardDescription>
                            Which service the assistant uses. Your portfolio and job hunt details are sent to it when you use the assistant.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid gap-2 sm:max-w-xs">
                            <Label for="provider">Provider</Label>
                            <Select :model-value="form.provider" @update:model-value="changeProvider">
                                <SelectTrigger id="provider">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in providers" :key="option.value" :value="option.value">{{ option.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.provider" />
                        </div>

                        <div class="grid gap-2">
                            <div class="flex items-center gap-2">
                                <Label :for="fields.key">{{ provider.label }} API Key</Label>
                                <Badge :variant="keyBadge.variant">{{ keyBadge.label }}</Badge>
                            </div>
                            <Input
                                :id="fields.key"
                                v-model="form[fields.key]"
                                type="password"
                                autocomplete="off"
                                spellcheck="false"
                                :placeholder="keyStatus.isSaved ? 'Leave empty to keep the saved key' : 'Paste a new key'"
                                maxlength="500"
                            />
                            <InputError :message="form.errors[fields.key]" />
                            <p class="text-sm text-muted-foreground">
                                Saved keys are encrypted and never shown again. Without one, the key from <code class="text-xs">.env</code> is used.
                                <a
                                    :href="provider.keysUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 underline underline-offset-4"
                                >
                                    Get a key
                                    <ExternalLink class="size-3" />
                                </a>
                            </p>
                            <div v-if="keyStatus.isSaved" class="flex items-center gap-2">
                                <Checkbox
                                    :id="fields.remove"
                                    :model-value="form[fields.remove]"
                                    @update:model-value="(checked) => (form[fields.remove] = checked === true)"
                                />
                                <Label :for="fields.remove" class="font-normal">Remove the saved key</Label>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="chatModel">Chat Model</Label>
                                <Input id="chatModel" v-model="form.chatModel" :placeholder="provider.chatModel" maxlength="100" spellcheck="false" />
                                <InputError :message="form.errors.chatModel" />
                                <p class="text-sm text-muted-foreground">For conversations, actions and writing.</p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="fastModel">Fast Model</Label>
                                <Input id="fastModel" v-model="form.fastModel" :placeholder="provider.fastModel" maxlength="100" spellcheck="false" />
                                <InputError :message="form.errors.fastModel" />
                                <p class="text-sm text-muted-foreground">For quick work, such as reading a job post.</p>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button type="submit" size="sm" :disabled="form.processing || !form.isDirty">Save Changes</Button>
                    </CardFooter>
                </Card>
            </form>

            <Form method="post" :action="settingsRoutes.ai.check.store.url()" :options="{ preserveScroll: true }" v-slot="{ processing }">
                <Card>
                    <CardHeader>
                        <CardTitle>Test Connection</CardTitle>
                        <CardDescription>
                            Send a short prompt with the saved settings to check that the key and fast model work.
                            <template v-if="form.isDirty"> Save your changes first.</template>
                        </CardDescription>
                    </CardHeader>
                    <CardFooter class="justify-end">
                        <Button type="submit" size="sm" variant="outline" :disabled="processing || !isReady || form.isDirty">
                            <PlugZap />
                            Test Connection
                        </Button>
                    </CardFooter>
                </Card>
            </Form>
        </div>
    </SettingsLayout>
</template>
