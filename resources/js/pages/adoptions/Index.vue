<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { HeartHandshake } from '@lucide/vue';
import AnimalTabs from '@/components/AnimalTabs.vue';
import AdoptionForm from '@/components/AdoptionForm.vue';
import CarePagination from '@/components/CarePagination.vue';
import { index, show } from '@/routes/adoptions';
import { index as animalIndex } from '@/routes/animals/adoptions';
import type { CareAnimal } from '@/types/care-task';
import type { Adoption } from '@/types/adoption';
import type { Paginated } from '@/types/animal';
const props = defineProps<{
    animal: CareAnimal | null;
    applications: Paginated<Adoption>;
    filter: string;
    statuses: Record<string, string>;
    members: { id: number; name: string }[];
    today: string;
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Adopcje', href: index() }] },
});
function filterUrl(status: string) {
    return props.animal
        ? animalIndex.url(props.animal.id, { query: { status } })
        : index.url({ query: { status } });
}
</script>
<template>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
        <Head :title="animal ? `${animal.name} — adopcje` : 'Adopcje'" />
        <header class="space-y-2">
            <p class="text-sm font-medium text-primary">
                {{ $page.props.auth.organization?.shelter_name }}
            </p>
            <h1 class="text-3xl font-bold">
                {{ animal ? animal.name : 'Adopcje' }}
            </h1>
            <p class="text-muted-foreground">
                Zgłoszenia, spotkania i droga do nowego domu. Nowy proces
                rozpoczniesz w kartotece zwierzęcia.
            </p>
        </header>
        <AnimalTabs v-if="animal" :animal-id="animal.id" active="adoptions" />
        <div
            class="grid items-start gap-6"
            :class="animal && 'xl:grid-cols-[minmax(0,1fr)_28rem]'"
        >
            <section class="min-w-0 space-y-4">
                <nav class="flex flex-wrap gap-2" aria-label="Etapy adopcji">
                    <Link
                        v-for="(label, status) in {
                            open: 'Otwarte',
                            ...statuses,
                        }"
                        :key="status"
                        :href="filterUrl(status)"
                        class="rounded-full border px-3 py-2 text-sm"
                        :class="
                            filter === status
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'bg-card hover:bg-accent'
                        "
                        :aria-current="filter === status ? 'page' : undefined"
                        >{{ label }}</Link
                    >
                </nav>
                <div
                    v-if="!applications.data.length"
                    class="settings-card text-center"
                >
                    <HeartHandshake
                        class="mx-auto size-10 text-primary/50"
                        aria-hidden="true"
                    />
                    <h2 class="text-lg font-bold">
                        Brak zgłoszeń w tym widoku
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Dodaj zgłoszenie w karcie zwierzęcia lub zmień filtr.
                    </p>
                </div>
                <article
                    v-for="application in applications.data"
                    :key="application.id"
                    class="settings-card"
                >
                    <div class="flex flex-wrap justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-sm text-primary">
                                {{ application.animal.name }} ·
                                {{ application.animal.registration_number }}
                            </p>
                            <h2 class="text-lg font-bold">
                                <Link
                                    :href="show(application.id)"
                                    class="hover:text-primary"
                                    >{{ application.candidate_name }}</Link
                                >
                            </h2>
                        </div>
                        <span
                            class="h-fit rounded-full bg-accent px-3 py-1 text-xs"
                            >{{ statuses[application.status] }}</span
                        >
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Prowadzi:
                        {{
                            application.coordinator?.name ??
                            'Brak osoby — przypisz ponownie'
                        }}
                    </p>
                    <Link
                        :href="show(application.id)"
                        class="inline-block text-sm font-medium text-primary"
                        >Otwórz zgłoszenie →</Link
                    >
                </article>
                <CarePagination :pages="applications" />
            </section>
            <aside v-if="animal" class="settings-card min-w-0">
                <h2 class="text-xl font-bold">Nowe zgłoszenie</h2>
                <AdoptionForm
                    :animal-id="animal.id"
                    :statuses="statuses"
                    :members="members"
                    :today="today"
                />
            </aside>
        </div>
    </div>
</template>
