<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Palette,
    PawPrint,
    ShieldCheck,
    UserRound,
} from '@lucide/vue';
import { Card } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as animals } from '@/routes/animals';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Przegląd', href: dashboard() }],
    },
});

const accountLinks = [
    {
        title: 'Twój profil',
        description: 'Sprawdź swoje dane i adres e-mail.',
        href: editProfile(),
        icon: UserRound,
    },
    {
        title: 'Bezpieczeństwo',
        description: 'Zadbaj o bezpieczny dostęp do konta.',
        href: editSecurity(),
        icon: ShieldCheck,
    },
    {
        title: 'Wygląd aplikacji',
        description: 'Wybierz jasny, ciemny lub systemowy motyw.',
        href: editAppearance(),
        icon: Palette,
    },
];
</script>

<template>
    <div
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8 lg:py-10"
    >
        <Head title="Przegląd" />
        <div
            v-if="$page.props.auth.organization"
            class="rounded-xl bg-accent p-4 text-sm"
        >
            <p class="font-semibold">
                {{ $page.props.auth.organization.name }}
            </p>
            <p class="text-muted-foreground">
                {{ $page.props.auth.organization.shelter_name }}
            </p>
            <p
                v-if="!$page.props.auth.organization.is_active"
                class="mt-2 text-destructive"
            >
                Dostęp do organizacji jest wyłączony. Skontaktuj się z
                administratorem platformy.
            </p>
        </div>
        <header class="space-y-2">
            <p class="text-sm font-medium text-primary">
                Twoja przestrzeń w ŁapaRazem
            </p>
            <h1 class="text-3xl font-bold tracking-tight">
                Dobrze Cię widzieć.
            </h1>
            <p class="max-w-2xl leading-relaxed text-muted-foreground">
                Wszystko dla lepszego życia zwierząt zaczyna się od wspólnego
                działania.
            </p>
        </header>

        <section
            aria-labelledby="workspace-title"
            class="flex items-start gap-4 rounded-2xl bg-accent/70 p-5 sm:p-6"
        >
            <span
                class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-card/80 text-primary"
            >
                <PawPrint class="size-6" aria-hidden="true" />
            </span>
            <div class="max-w-2xl space-y-2">
                <h2 id="workspace-title" class="text-lg font-bold">
                    Miejsce na codzienną pomoc
                </h2>
                <template v-if="$page.props.auth.canManageAnimals">
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        Kartoteka zwierząt jest gotowa. Dodawaj podopiecznych i
                        uzupełniaj informacje potrzebne do codziennej opieki.
                    </p>
                    <Link
                        :href="animals()"
                        class="inline-flex items-center gap-2 text-sm font-medium text-primary"
                        >Przejdź do zwierząt
                        <ArrowRight class="size-4" aria-hidden="true"
                    /></Link>
                </template>
                <p v-else class="text-sm leading-relaxed text-muted-foreground">
                    Dostęp do kartoteki zwierząt mają konta przypisane do
                    aktywnego schroniska. Tutaj możesz zadbać o swój profil i
                    ustawienia konta.
                </p>
            </div>
        </section>

        <section aria-labelledby="account-title" class="space-y-4">
            <div class="space-y-1">
                <h2 id="account-title" class="text-xl font-bold">
                    Twoje konto
                </h2>
                <p class="text-sm text-muted-foreground">
                    Dane, bezpieczeństwo i wygoda pracy — w jednym miejscu.
                </p>
            </div>
            <div class="grid gap-4 lg:grid-cols-3">
                <Link
                    v-for="item in accountLinks"
                    :key="item.title"
                    :href="item.href"
                    class="group block rounded-2xl outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:ring-offset-background"
                >
                    <Card
                        class="h-full gap-3 rounded-2xl p-5 shadow-none transition-colors duration-150 group-hover:border-primary/40 group-hover:bg-accent/30 motion-reduce:transition-none"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-accent text-primary"
                            >
                                <component
                                    :is="item.icon"
                                    class="size-5"
                                    aria-hidden="true"
                                />
                            </span>
                            <h3 class="flex-1 text-base font-bold">
                                {{ item.title }}
                            </h3>
                            <ArrowRight
                                class="size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-primary motion-reduce:transition-none"
                                aria-hidden="true"
                            />
                        </div>
                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            {{ item.description }}
                        </p>
                    </Card>
                </Link>
            </div>
        </section>
    </div>
</template>
