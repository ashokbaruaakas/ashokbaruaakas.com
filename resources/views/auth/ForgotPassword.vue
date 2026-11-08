<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import TextLink from '@/components/TextLink.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { login } from '@/routes'
import { email } from '@/routes/password'
import { Form } from '@inertiajs/vue3'
import { LoaderCircle } from '@lucide/vue'

defineProps<{
    status?: string
}>()
</script>

<template>
    <div
        v-if="status"
        role="status"
        class="mb-6 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-400"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form method="post" :action="email.url()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input id="email" type="email" name="email" autocomplete="off" autofocus placeholder="email@example.com" class="h-11" />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button class="h-11 w-full" :disabled="processing">
                    <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                    Email password reset link
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Or, return to</span>
            <TextLink :href="login.url()">log in</TextLink>
        </div>
    </div>
</template>
