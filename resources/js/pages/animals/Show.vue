<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PawPrint, Pencil, ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { index, edit, photo } from '@/routes/animals';
import type { Animal, AnimalOptions, Paginated } from '@/types/animal';
const props = defineProps<{
    animal: Animal;
    options: AnimalOptions;
    history: Paginated<{
        id: number;
        from_status: string | null;
        to_status: string;
        note: string | null;
        created_at: string;
        user: { name: string } | null;
    }>;
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Zwierzęta', href: index() },
            { title: 'Karta zwierzęcia' },
        ],
    },
});
function date(value: string | null) {
    return value
        ? new Intl.DateTimeFormat('pl-PL', { dateStyle: 'medium' }).format(
              new Date(`${value.slice(0, 10)}T12:00:00`),
          )
        : 'Brak danych';
}
const details = computed(() => [
    ['Gatunek', props.options.species[props.animal.species]],
    ['Rasa / typ', props.animal.breed],
    ['Płeć', props.options.sexes[props.animal.sex]],
    [
        'Data urodzenia',
        `${date(props.animal.birth_date)}${props.animal.birth_date && props.animal.birth_date_estimated ? ' (szacunkowa)' : ''}`,
    ],
    ['Umaszczenie', props.animal.color],
    [
        'Masa ciała',
        props.animal.weight_kg ? `${props.animal.weight_kg} kg` : null,
    ],
    ['Mikroczip', props.animal.microchip],
    [
        'Kastracja / sterylizacja',
        props.options.sterilization[props.animal.sterilization_status],
    ],
    ['Data przyjęcia', date(props.animal.admitted_at)],
    [
        'Powód przyjęcia',
        props.options.admissionReasons[props.animal.admission_reason],
    ],
    ['Miejsce znalezienia', props.animal.found_location],
    ['Lokalizacja / boks', props.animal.location],
]);
const notes = computed(() => [
    ['Zdrowie', props.animal.health_notes],
    ['Charakter i zachowanie', props.animal.behavior_notes],
    ['Zalecenia opieki', props.animal.care_notes],
    ['Dodatkowy opis', props.animal.description],
]);
</script>
<template>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-8">
        <Head :title="animal.name" />
        <Link
            :href="index()"
            class="inline-flex items-center gap-2 text-sm text-primary"
            ><ArrowLeft class="size-4" aria-hidden="true" />Wróć do
            kartoteki</Link
        >
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div class="space-y-2">
                <p class="text-sm font-medium text-primary">
                    {{ $page.props.auth.organization?.shelter_name }} ·
                    {{ animal.registration_number }}
                </p>
                <h1 class="text-3xl font-bold">{{ animal.name }}</h1>
                <span
                    class="inline-block rounded-full bg-accent px-3 py-1 text-sm font-medium text-accent-foreground"
                    >{{ options.statuses[animal.status] }}</span
                >
            </div>
            <Button as-child
                ><Link :href="edit(animal.id)"
                    ><Pencil class="size-4" aria-hidden="true" />Edytuj
                    kartę</Link
                ></Button
            >
        </header>
        <div class="grid items-start gap-6 lg:grid-cols-[1fr_2fr]">
            <div
                class="flex aspect-square items-center justify-center overflow-hidden rounded-2xl border bg-accent/40"
            >
                <img
                    v-if="animal.has_photo"
                    :src="photo.url(animal.id)"
                    :alt="animal.name"
                    class="animal-photo-transition h-full w-full object-cover"
                />
                <div v-else class="space-y-3 text-center text-muted-foreground">
                    <PawPrint
                        class="mx-auto size-16 text-primary/40"
                        aria-hidden="true"
                    />
                    <p>Zdjęcie jeszcze przed nami</p>
                </div>
            </div>
            <section class="settings-card">
                <h2 class="text-lg font-bold">Dane zwierzęcia</h2>
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div
                        v-for="[label, value] in details"
                        :key="label ?? ''"
                        class="space-y-1"
                    >
                        <dt class="text-sm text-muted-foreground">
                            {{ label }}
                        </dt>
                        <dd class="font-medium break-words">
                            {{ value || 'Brak danych' }}
                        </dd>
                    </div>
                </dl>
            </section>
        </div>
        <div class="grid gap-6 md:grid-cols-2">
            <section
                v-for="[title, text] in notes"
                :key="title ?? ''"
                class="settings-card"
            >
                <h2 class="text-lg font-bold">{{ title }}</h2>
                <p
                    class="text-sm leading-relaxed break-words whitespace-pre-wrap"
                    :class="!text && 'text-muted-foreground'"
                >
                    {{ text || 'Nie uzupełniono jeszcze informacji.' }}
                </p>
            </section>
        </div>
        <section class="settings-card">
            <h2 class="text-lg font-bold">Historia statusu</h2>
            <p
                v-if="!history.data.length"
                class="text-sm text-muted-foreground"
            >
                Brak zarejestrowanych zmian.
            </p>
            <ol class="divide-y">
                <li
                    v-for="entry in history.data"
                    :key="entry.id"
                    class="space-y-2 py-4 first:pt-0"
                >
                    <div class="flex flex-wrap justify-between gap-2">
                        <p class="font-medium">
                            {{
                                entry.from_status
                                    ? `${options.statuses[entry.from_status]} → `
                                    : 'Utworzenie karty → '
                            }}{{ options.statuses[entry.to_status] }}
                        </p>
                        <time
                            :datetime="entry.created_at"
                            class="text-sm text-muted-foreground"
                            >{{
                                new Intl.DateTimeFormat('pl-PL', {
                                    dateStyle: 'medium',
                                    timeStyle: 'short',
                                }).format(new Date(entry.created_at))
                            }}</time
                        >
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ entry.user?.name ?? 'Usunięte konto' }}
                    </p>
                    <p
                        v-if="entry.note"
                        class="text-sm break-words whitespace-pre-wrap"
                    >
                        {{ entry.note }}
                    </p>
                </li>
            </ol>
            <nav
                v-if="history.last_page > 1"
                class="flex items-center gap-4"
                aria-label="Strony historii"
            >
                <Button v-if="history.prev_page_url" variant="outline" as-child
                    ><Link :href="history.prev_page_url" preserve-scroll
                        >Poprzednia</Link
                    ></Button
                ><span class="text-sm"
                    >{{ history.current_page }} / {{ history.last_page }}</span
                ><Button v-if="history.next_page_url" variant="outline" as-child
                    ><Link :href="history.next_page_url" preserve-scroll
                        >Następna</Link
                    ></Button
                >
            </nav>
        </section>
    </div>
</template>
