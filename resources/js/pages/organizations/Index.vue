<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Plus, ArrowRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/organizations';
defineProps<{
    organizations: {
        data: {
            id: number;
            name: string;
            shelter_name: string;
            is_active: boolean;
            users_count: number;
        }[];
        prev_page_url: string | null;
        next_page_url: string | null;
        current_page: number;
        last_page: number;
    };
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Organizacje', href: index() }] },
});
</script>
<template>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
        <Head title="Organizacje" />
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div class="space-y-2">
                <p class="text-sm font-medium text-primary">
                    Zarządzanie platformą
                </p>
                <h1 class="text-3xl font-bold">Organizacje</h1>
                <p class="text-muted-foreground">
                    Schroniska, ich dane i konta administratorów.
                </p>
            </div>
            <Button as-child
                ><Link :href="create()"
                    ><Plus aria-hidden="true" />Dodaj organizację</Link
                ></Button
            >
        </header>
        <div
            v-if="!organizations.data.length"
            class="settings-card text-center"
        >
            <Building2 class="mx-auto size-10 text-primary" />
            <h2 class="text-xl font-bold">Dodaj pierwszą organizację</h2>
            <p class="text-muted-foreground">
                Wraz z organizacją utworzysz konto jej administratora.
            </p>
        </div>
        <div v-else class="grid gap-4 md:grid-cols-2">
            <Link
                v-for="organization in organizations.data"
                :key="organization.id"
                :href="edit(organization.id)"
                class="settings-card space-y-3 transition-colors outline-none hover:border-primary/50 focus-visible:ring-2 focus-visible:ring-ring"
            >
                <div class="flex items-center justify-between gap-3">
                    <Building2 class="size-5 text-primary" /><span
                        class="rounded-full bg-accent px-3 py-1 text-xs"
                        >{{
                            organization.is_active ? 'Aktywna' : 'Nieaktywna'
                        }}</span
                    >
                </div>
                <h2 class="text-xl font-bold">{{ organization.name }}</h2>
                <p class="text-sm text-muted-foreground">
                    {{ organization.shelter_name }}
                </p>
                <div class="flex justify-between text-sm">
                    <span>Konta: {{ organization.users_count }}</span
                    ><ArrowRight class="size-4" aria-hidden="true" />
                </div>
            </Link>
        </div>
        <nav
            v-if="organizations.last_page > 1"
            class="flex items-center gap-4"
            aria-label="Strony organizacji"
        >
            <Button
                v-if="organizations.prev_page_url"
                variant="outline"
                as-child
                ><Link :href="organizations.prev_page_url"
                    >Poprzednia</Link
                ></Button
            ><span class="text-sm"
                >Strona {{ organizations.current_page }} z
                {{ organizations.last_page }}</span
            ><Button
                v-if="organizations.next_page_url"
                variant="outline"
                as-child
                ><Link :href="organizations.next_page_url"
                    >Następna</Link
                ></Button
            >
        </nav>
    </div>
</template>
