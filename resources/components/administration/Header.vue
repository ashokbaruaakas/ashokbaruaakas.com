<script setup lang="ts">
import AppBrand from '@/components/AppBrand.vue'
import AppearanceToggle from '@/components/AppearanceToggle.vue'
import { Button } from '@/components/ui/button'
import { Kbd, KbdGroup } from '@/components/ui/kbd'
import { home, logout } from '@/routes'
import { dashboard as administrationDashboard } from '@/routes/administration'
import { Link, router } from '@inertiajs/vue3'
import { ArrowUpRight, LogOut, Search } from '@lucide/vue'
import { onMounted, ref } from 'vue'

const emit = defineEmits<{
    search: []
}>()

const isMac = ref(false)

onMounted(() => {
    isMac.value = /Mac|iPhone|iPad/.test(navigator.userAgent)
})
</script>

<template>
    <header class="flex h-14 shrink-0 items-center justify-between gap-2 px-2 sm:px-4">
        <Link :href="administrationDashboard.url()" aria-label="Dashboard" class="min-w-0 rounded-md">
            <AppBrand />
        </Link>

        <div class="flex shrink-0 items-center gap-1 text-muted-foreground">
            <Button variant="ghost" size="sm" class="hidden font-normal text-inherit sm:inline-flex" as-child>
                <Link :href="home.url()">
                    View site
                    <ArrowUpRight />
                </Link>
            </Button>

            <Button
                variant="ghost"
                size="sm"
                class="size-8 px-0 font-normal text-inherit sm:w-auto sm:px-2"
                aria-label="Search"
                @click="emit('search')"
            >
                <Search />
                <KbdGroup class="hidden sm:inline-flex">
                    <Kbd>{{ isMac ? '⌘' : 'Ctrl' }}</Kbd>
                    <Kbd>K</Kbd>
                </KbdGroup>
            </Button>

            <AppearanceToggle />

            <Button variant="ghost" size="icon" class="size-8 text-inherit" as-child>
                <Link :href="logout.url()" method="post" as="button" @click="router.flushAll()">
                    <LogOut />
                    <span class="sr-only">Log out</span>
                </Link>
            </Button>
        </div>
    </header>
</template>
