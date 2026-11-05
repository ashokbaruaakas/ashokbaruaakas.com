<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader } from '@/components/ui/card'
import AppearanceTabs from './partial/AppearanceTabs.vue'

import SettingsLayout from '@/layouts/AccountLayout.vue'
</script>

<template>
    <SettingsLayout>
        <div class="space-y-6">
            <Card>
                <CardHeader class="pb-4">
                    <CardDescription class="text-sm text-muted-foreground"> Adjust your preferences for a personalized experience. </CardDescription>
                </CardHeader>
                <CardContent>
                    <AppearanceTabs />
                </CardContent>
            </Card>
        </div>
    </SettingsLayout>
</template>
