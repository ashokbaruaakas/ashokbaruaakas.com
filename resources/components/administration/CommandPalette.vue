<script setup lang="ts">
import { CommandDialog, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command'
import { useAppearance } from '@/composables/useAppearance'
import { isNavItemActive } from '@/lib/helpers'
import { account, home, logout } from '@/routes'
import { router } from '@inertiajs/vue3'
import { House, LogOut, Monitor, Moon, Sun, User } from '@lucide/vue'
import { useEventListener } from '@vueuse/core'
import { useAdministrationNavigation } from './navigation'

const open = defineModel<boolean>('open', { required: true })

const { mainNavGroups } = useAdministrationNavigation()
const { updateAppearance } = useAppearance()

useEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key.toLowerCase() === 'k' && (event.metaKey || event.ctrlKey)) {
        event.preventDefault()
        open.value = !open.value
    }
})

const run = (action: () => void) => {
    open.value = false
    action()
}

const visit = (href: string) => run(() => router.visit(href))

const logOut = () =>
    run(() => {
        router.flushAll()
        router.post(logout.url())
    })
</script>

<template>
    <CommandDialog v-model:open="open" title="Command palette" description="Go to a page or run an action.">
        <CommandInput placeholder="Search pages and actions…" />
        <CommandList>
            <template v-for="(group, index) in mainNavGroups" :key="group.label ?? index">
                <CommandGroup :heading="group.label">
                    <CommandItem v-for="item in group.items" :key="item.href" :value="item.href" @select="visit(item.href)">
                        <component :is="item.icon" />
                        {{ item.title }}
                        <span v-if="isNavItemActive(item)" class="ml-auto text-xs text-muted-foreground">Current</span>
                    </CommandItem>
                </CommandGroup>
            </template>

            <CommandGroup heading="General">
                <CommandItem value="account" @select="visit(account.url())">
                    <User />
                    Account
                </CommandItem>
                <CommandItem value="view-site" @select="visit(home.url())">
                    <House />
                    View site
                </CommandItem>
            </CommandGroup>

            <CommandGroup heading="Appearance">
                <CommandItem value="appearance-light" @select="run(() => updateAppearance('light'))">
                    <Sun />
                    Light theme
                </CommandItem>
                <CommandItem value="appearance-dark" @select="run(() => updateAppearance('dark'))">
                    <Moon />
                    Dark theme
                </CommandItem>
                <CommandItem value="appearance-system" @select="run(() => updateAppearance('system'))">
                    <Monitor />
                    System theme
                </CommandItem>
            </CommandGroup>

            <CommandGroup>
                <CommandItem value="log-out" @select="logOut">
                    <LogOut />
                    Log out
                </CommandItem>
            </CommandGroup>
        </CommandList>
    </CommandDialog>
</template>
