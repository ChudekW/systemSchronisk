<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Heart, House, PawPrint } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <Head title="ŁapaRazem — od opieki do domu" />
        <header
            class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-6"
        >
            <AppLogo class="public-logo-transition" />
            <nav aria-label="Konto" class="flex items-center gap-2">
                <Button v-if="$page.props.auth.user" as-child>
                    <Link :href="dashboard()"
                        >Przejdź do panelu <ArrowRight aria-hidden="true"
                    /></Link>
                </Button>
                <template v-else>
                    <Button variant="ghost" as-child
                        ><Link :href="login()">Zaloguj się</Link></Button
                    >
                    <Button variant="outline" as-child
                        ><Link :href="register()">Załóż konto</Link></Button
                    >
                </template>
            </nav>
        </header>
        <main
            class="page-transition-content mx-auto grid w-full max-w-6xl flex-1 items-center gap-12 px-6 py-12 lg:grid-cols-[1.2fr_1fr] lg:py-20"
        >
            <section class="flex flex-col items-start gap-7">
                <span
                    class="rounded-full bg-secondary px-4 py-2 text-sm font-medium text-secondary-foreground"
                    >Od opieki do domu</span
                >
                <h1
                    class="max-w-2xl font-heading text-4xl leading-tight font-bold tracking-tight sm:text-5xl lg:text-6xl"
                >
                    Wszystko dla lepszego życia
                    <span class="text-primary">zwierząt.</span>
                </h1>
                <p
                    class="max-w-lg text-lg leading-relaxed text-muted-foreground"
                >
                    ŁapaRazem powstaje z myślą o ludziach, którzy pomagają
                    zwierzętom. Wspólna przestrzeń dla schronisk, opiekunów i
                    dobrych historii.
                </p>
                <Button size="lg" as-child>
                    <Link :href="$page.props.auth.user ? dashboard() : login()"
                        >{{
                            $page.props.auth.user
                                ? 'Otwórz swoją przestrzeń'
                                : 'Zaloguj się do ŁapaRazem'
                        }}
                        <ArrowRight aria-hidden="true"
                    /></Link>
                </Button>
            </section>
            <aside
                class="relative overflow-hidden rounded-2xl border bg-card p-8 shadow-sm sm:p-12"
            >
                <div class="mb-10 flex items-center gap-3" aria-hidden="true">
                    <span
                        class="flex size-16 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground"
                        ><PawPrint class="size-8" :stroke-width="1.5"
                    /></span>
                    <span class="h-px flex-1 bg-border"></span>
                    <span
                        class="flex size-12 items-center justify-center rounded-full bg-adoption text-adoption-foreground"
                        ><Heart class="size-6" :stroke-width="1.5"
                    /></span>
                    <span class="h-px flex-1 bg-border"></span>
                    <span
                        class="flex size-16 items-center justify-center rounded-2xl bg-accent text-primary"
                        ><House class="size-8" :stroke-width="1.5"
                    /></span>
                </div>
                <h2 class="text-2xl font-bold">Razem łatwiej pomagać.</h2>
                <p class="mt-4 leading-relaxed text-muted-foreground">
                    Za każdą adopcją stoi codzienna troska. Budujemy miejsce,
                    które pomoże połączyć opiekę, ludzi i drogę do nowego domu.
                </p>
                <div class="mt-8 border-t pt-6 text-sm text-muted-foreground">
                    Platforma jest w rozwoju. Obecnie dostępne są konto
                    użytkownika i jego ustawienia.
                </div>
            </aside>
        </main>
        <footer
            class="mx-auto w-full max-w-6xl px-6 py-6 text-sm text-muted-foreground"
        >
            ŁapaRazem · Łączymy ludzi i zwierzęta.
        </footer>
    </div>
</template>
