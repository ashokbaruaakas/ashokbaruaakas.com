<template>
    <div class="min-w-0 overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm">
        <div aria-hidden="true" class="flex items-center gap-1.5 border-b px-4 py-3">
            <span class="size-2.5 rounded-full bg-border" />
            <span class="size-2.5 rounded-full bg-border" />
            <span class="size-2.5 rounded-full bg-emerald-500" />
            <span class="ml-2 font-mono text-xs text-muted-foreground">~/portfolio</span>
        </div>

        <div class="flex flex-col gap-2 p-4 font-mono text-sm leading-relaxed">
            <slot />
        </div>
    </div>
</template>
