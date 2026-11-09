<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAuth } from '@/composables/useAuth'
import SettingsLayout from '@/layouts/SettingsLayout.vue'
import settingsRoutes from '@/routes/settings'
import type { MailSettings } from '@/types/data'
import { Form } from '@inertiajs/vue3'
import { MailX, Send } from '@lucide/vue'

interface Props {
    settings: MailSettings
    defaults: MailSettings
    mailer: string
    enabled: boolean
}

defineProps<Props>()

const { requireUser } = useAuth()
const user = requireUser()
</script>

<template>
    <SettingsLayout>
        <div class="space-y-6">
            <Card v-if="!enabled">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MailX class="size-4 text-muted-foreground" aria-hidden="true" />
                        Mail Is Off
                    </CardTitle>
                    <CardDescription>
                        The application sends no email, so password reset links, email verification and test emails are unavailable.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3 text-sm text-muted-foreground">
                    <p>
                        To turn mail on, set <code class="text-xs">MAIL_MAILER</code> and its connection details (such as
                        <code class="text-xs">MAIL_HOST</code>, <code class="text-xs">MAIL_PORT</code>, <code class="text-xs">MAIL_USERNAME</code> and
                        <code class="text-xs">MAIL_PASSWORD</code>) in <code class="text-xs">.env</code>, then reload the application.
                    </p>
                    <p>Locked out without mail? Run <code class="text-xs">php artisan owner:reset-password</code> on the server.</p>
                </CardContent>
            </Card>

            <Form
                v-else
                method="put"
                :action="settingsRoutes.mail.update.url()"
                :options="{ preserveScroll: true }"
                set-defaults-on-success
                v-slot="{ errors, processing, isDirty }"
            >
                <Card>
                    <CardHeader>
                        <CardTitle>Sender</CardTitle>
                        <CardDescription>
                            Who the application's emails come from. Leave a field empty to use its value from <code class="text-xs">.env</code>.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="fromName">Sender Name</Label>
                                <Input
                                    id="fromName"
                                    name="fromName"
                                    :default-value="settings.fromName ?? ''"
                                    :placeholder="defaults.fromName ?? ''"
                                    maxlength="100"
                                />
                                <InputError :message="errors.fromName" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="fromAddress">Sender Address</Label>
                                <Input
                                    id="fromAddress"
                                    name="fromAddress"
                                    type="email"
                                    :default-value="settings.fromAddress ?? ''"
                                    :placeholder="defaults.fromAddress ?? ''"
                                />
                                <InputError :message="errors.fromAddress" />
                            </div>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            Emails are delivered with the
                            <code class="text-xs">{{ mailer }}</code> mailer. Change how mail is delivered in <code class="text-xs">.env</code>.
                        </p>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button type="submit" size="sm" :disabled="processing || !isDirty">Save Changes</Button>
                    </CardFooter>
                </Card>
            </Form>

            <Form
                v-if="enabled"
                method="post"
                :action="settingsRoutes.mail.test.store.url()"
                :options="{ preserveScroll: true }"
                v-slot="{ processing }"
            >
                <Card>
                    <CardHeader>
                        <CardTitle>Test Email</CardTitle>
                        <CardDescription>Send an email to {{ user.email }} to check that delivery works with the saved settings.</CardDescription>
                    </CardHeader>
                    <CardFooter class="justify-end">
                        <Button type="submit" size="sm" variant="outline" :disabled="processing">
                            <Send />
                            Send Test Email
                        </Button>
                    </CardFooter>
                </Card>
            </Form>
        </div>
    </SettingsLayout>
</template>
