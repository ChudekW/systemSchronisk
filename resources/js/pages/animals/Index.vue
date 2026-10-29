<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PawPrint, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { create, index, show, photo } from '@/routes/animals';
import type { AnimalOptions, AnimalSummary, Paginated } from '@/types/animal';
const props = defineProps<{
    animals: Paginated<AnimalSummary>;
    filters: { search?: string; species?: string; status?: string };
    options: AnimalOptions;
    searchUnavailable: boolean;
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Zwierzęta', href: index() }] },
});
const search = ref(props.filters.search ?? '');
const species = ref(props.filters.species ?? '');
const status = ref(props.filters.status ?? '');
function filter() {
    router.get(
        index.url(),
        { search: search.value, species: species.value, status: status.value },
        { preserveState: true, preserveScroll: true },
    );
}
function reset() {
    search.value = '';
    species.value = '';
    status.value = '';
    filter();
}
</script>
<template>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
        <Head title="Zwierzęta" />
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div class="space-y-2">
                <p class="text-sm font-medium text-primary">
                    {{ $page.props.auth.organization?.shelter_name }}
                </p>
                <h1 class="text-3xl font-bold">Zwierzęta</h1>
                <p class="text-muted-foreground">
                    Każda historia zaczyna się od troski. Kartoteka Twojego
                    schroniska.
                </p>
            </div>
            <Button as-child
                ><Link :href="create()"
                    ><Plus aria-hidden="true" />Dodaj zwierzę</Link
                ></Button
            >
        </header>
        <form class="settings-card" @submit.prevent="filter">
            <div
                class="grid items-end gap-4 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_auto]"
            >
                <div class="space-y-2">
                    <Label for="search">Szukaj w kartotece</Label
                    ><Input
                        id="search"
                        v-model="search"
                        placeholder="Imię, numer, chip, rasa lub boks"
                        maxlength="120"
                    /><InputError :message="$page.props.errors.search" />
                </div>
                <div class="space-y-2">
                    <Label for="species">Gatunek</Label
                    ><select
                        id="species"
                        v-model="species"
                        class="animal-select"
                    >
                        <option value="">Wszystkie gatunki</option>
                        <option
                            v-for="(label, value) in options.species"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="status">Status</Label
                    ><select id="status" v-model="status" class="animal-select">
                        <option value="">Wszystkie statusy</option>
                        <option
                            v-for="(label, value) in options.statuses"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                </div>
                <Button type="submit" variant="outline"
                    ><Search aria-hidden="true" />Szukaj</Button
                >
            </div>
            <div
                class="flex items-center justify-between text-sm text-muted-foreground"
            >
                <span>Liczba wyników: {{ animals.total }}</span
                ><button
                    v-if="search || species || status"
                    type="button"
                    class="text-primary underline underline-offset-4"
                    @click="reset"
                >
                    Wyczyść filtry
                </button>
            </div>
        </form>
        <div v-if="searchUnavailable" role="alert" class="settings-card">
            <h2 class="text-lg font-bold">
                Wyszukiwarka jest chwilowo niedostępna
            </h2>
            <p class="text-sm text-muted-foreground">
                Spróbuj ponownie za chwilę lub wyczyść wyszukiwanie, aby
                przeglądać kartotekę.
            </p>
            <Button variant="outline" @click="reset">Pokaż kartotekę</Button>
        </div>
        <div
            v-else-if="!animals.data.length"
            class="settings-card py-12 text-center"
        >
            <PawPrint class="mx-auto size-10 text-primary" aria-hidden="true" />
            <h2 class="text-xl font-bold">
                {{
                    filters.search || filters.species || filters.status
                        ? 'Brak pasujących zwierząt'
                        : 'Miejsce na pierwszą historię'
                }}
            </h2>
            <p class="text-muted-foreground">
                {{
                    filters.search || filters.species || filters.status
                        ? 'Zmień kryteria lub wyczyść filtry.'
                        : 'Dodaj pierwsze zwierzę, aby rozpocząć prowadzenie kartoteki.'
                }}
            </p>
        </div>
        <div v-else class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="animal in animals.data"
                :key="animal.id"
                :href="show(animal.id)"
                class="group overflow-hidden rounded-2xl border bg-card transition-colors hover:border-primary/50 focus-visible:ring-2 focus-visible:ring-ring"
            >
                <div
                    class="flex aspect-[16/9] items-center justify-center bg-accent/50"
                >
                    <img
                        v-if="animal.has_photo"
                        :src="photo.url(animal.id)"
                        :alt="animal.name"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                    <PawPrint
                        v-else
                        class="size-14 text-primary/40"
                        aria-hidden="true"
                    />
                </div>
                <div class="space-y-3 p-5">
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <span class="text-xs text-muted-foreground">{{
                            animal.registration_number
                        }}</span
                        ><span
                            class="rounded-full bg-accent px-3 py-1 text-xs font-medium text-accent-foreground"
                            >{{ options.statuses[animal.status] }}</span
                        >
                    </div>
                    <h2 class="text-xl font-bold group-hover:text-primary">
                        {{ animal.name }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ options.species[animal.species] }} ·
                        {{ options.sexes[animal.sex] }}
                    </p>
                    <p class="border-t pt-3 text-sm">
                        {{ animal.location || 'Lokalizacja nieuzupełniona' }}
                    </p>
                </div>
            </Link>
        </div>
        <nav
            v-if="animals.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-4"
            aria-label="Strony kartoteki"
        >
            <Button v-if="animals.prev_page_url" variant="outline" as-child
                ><Link :href="animals.prev_page_url">Poprzednia</Link></Button
            >
            <span class="text-sm"
                >Strona {{ animals.current_page }} z
                {{ animals.last_page }}</span
            >
            <Button v-if="animals.next_page_url" variant="outline" as-child
                ><Link :href="animals.next_page_url">Następna</Link></Button
            >
        </nav>
    </div>
</template>
