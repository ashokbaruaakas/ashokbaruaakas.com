<script setup lang="ts">
import ConfirmDialog from '@/components/app/portfolio/ConfirmDialog.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import SettingsLayout from '@/layouts/SettingsLayout.vue'
import settingsRoutes from '@/routes/settings'
import { useForm } from '@inertiajs/vue3'
import { Download, Upload } from '@lucide/vue'
import { computed, ref, useTemplateRef } from 'vue'

const input = useTemplateRef<HTMLInputElement>('input')
const isConfirming = ref(false)

const form = useForm<{ file: File | null }>({ file: null })

const confirmDescription = computed(
    () =>
        `This replaces your profile, portfolio content, job hunts, images and settings with the contents of ${form.file?.name}. Your account and sign-in details stay as they are. This can't be undone, so download a backup first if you may want the current content back.`,
)

const clearFile = () => {
    form.file = null

    if (input.value) {
        input.value.value = ''
    }
}

const chooseFile = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0]

    if (!file) {
        return
    }

    form.clearErrors()
    form.file = file
    isConfirming.value = true
}

const closeConfirmation = (open: boolean) => {
    isConfirming.value = open

    if (!open && !form.processing) {
        clearFile()
    }
}

const importBackup = () => {
    form.post(settingsRoutes.backup.store.url(), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            isConfirming.value = false
            clearFile()
        },
    })
}
</script>

<template>
    <SettingsLayout>
        <div class="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Export</CardTitle>
                    <CardDescription>
                        Download a zip file with your profile, portfolio content, job hunts, images, resume PDF and settings. Keep it somewhere
                        private, because it includes your contact details.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">Your account, password, two-factor and passkey details are not included.</p>
                </CardContent>
                <CardFooter class="justify-end">
                    <Button as-child size="sm" variant="outline">
                        <a :href="settingsRoutes.backup.download.url()"><Download /> Download backup</a>
                    </Button>
                </CardFooter>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Import</CardTitle>
                    <CardDescription>
                        Restore a backup downloaded from this page, up to 50 MB. It's checked in full first, and nothing changes if any part of it is
                        invalid.
                    </CardDescription>
                </CardHeader>
                <CardContent v-if="form.errors.file">
                    <InputError :message="form.errors.file" />
                </CardContent>
                <CardFooter class="justify-end">
                    <input ref="input" type="file" class="sr-only" accept=".zip" aria-label="Choose a backup file" @change="chooseFile" />
                    <Button type="button" size="sm" variant="outline" :disabled="form.processing" @click="input?.click()">
                        <Upload /> {{ form.processing ? 'Importing…' : 'Choose backup file' }}
                    </Button>
                </CardFooter>
            </Card>

            <ConfirmDialog
                :open="isConfirming"
                title="Replace your content with this backup?"
                :description="confirmDescription"
                confirm-label="Replace and import"
                :processing="form.processing"
                @update:open="closeConfirmation"
                @confirm="importBackup"
            />
        </div>
    </SettingsLayout>
</template>
