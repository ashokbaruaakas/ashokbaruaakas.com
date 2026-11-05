<script setup lang="ts">
import TextLink from '@/components/TextLink.vue'
import { Button } from '@/components/ui/button'
import { logout } from '@/routes'
import { send } from '@/routes/verification'
import { Form } from '@inertiajs/vue3'
import { LoaderCircle } from '@lucide/vue'
</script>

<template>
    <Form method="post" :action="send.url()" class="flex flex-col gap-4 text-center" v-slot="{ processing }">
        <Button class="h-11 w-full" :disabled="processing">
            <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
            Resend verification email
        </Button>

        <TextLink :href="logout.url()" method="post" as="button" class="mx-auto min-h-11 text-sm"> Log out </TextLink>
    </Form>
</template>
