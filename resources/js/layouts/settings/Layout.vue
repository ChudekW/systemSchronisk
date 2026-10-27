<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Palette, ShieldCheck, UserRound } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profil',
        icon: UserRound,
        href: editProfile(),
    },
    {
        title: 'Bezpieczeństwo',
        icon: ShieldCheck,
        href: editSecurity(),
    },
    {
        title: 'Wygląd',
        icon: Palette,
        href: editAppearance(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div
        class="mx-auto w-full max-w-7xl space-y-8 px-4 py-6 sm:px-6 lg:px-8 lg:py-10"
    >
        <header class="settings-transition-header space-y-2">
            <p class="text-sm font-medium text-primary">
                Twoja przestrzeń w ŁapaRazem
            </p>
            <h1 class="text-3xl font-bold tracking-tight">Ustawienia</h1>
            <p class="max-w-2xl leading-relaxed text-muted-foreground">
                Zarządzaj swoimi danymi, bezpieczeństwem i wyglądem aplikacji.
            </p>
        </header>
        <div
            class="grid items-start gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-8"
        >
            <aside
                class="settings-transition-navigation rounded-2xl border bg-card p-2 lg:sticky lg:top-6"
            >
                <nav class="flex flex-col gap-1" aria-label="Ustawienia konta">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'h-12 w-full justify-start gap-3 rounded-xl px-3',
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-accent font-semibold text-primary hover:bg-accent'
                                : 'text-muted-foreground',
                        ]"
                        as-child
                    >
                        <Link
                            :href="item.href"
                            :aria-current="
                                isCurrentOrParentUrl(item.href)
                                    ? 'page'
                                    : undefined
                            "
                        >
                            <component
                                :is="item.icon"
                                class="size-5 shrink-0"
                                aria-hidden="true"
                            />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>
            <div class="min-w-0 space-y-6">
                <slot />
            </div>
        </div>
    </div>
</template>
