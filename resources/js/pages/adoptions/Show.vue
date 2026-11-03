<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdoptionAftercare from '@/components/AdoptionAftercare.vue';
import AdoptionForm from '@/components/AdoptionForm.vue';
import CarePagination from '@/components/CarePagination.vue';
import { index, download } from '@/routes/adoptions';
import { index as animalIndex } from '@/routes/animals/adoptions';
import { show as taskShow } from '@/routes/animals/tasks';
import type { Adoption, AdoptionHistory } from '@/types/adoption';
import type { Paginated } from '@/types/animal';
import type { CareTask } from '@/types/care-task';
defineProps<{
    application: Adoption;
    history: Paginated<AdoptionHistory>;
    tasks: Paginated<CareTask>;
    statuses: Record<string, string>;
    transitions: string[];
    editable: boolean;
    canRegisterReturn: boolean;
    members: { id: number; name: string }[];
    today: string;
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Adopcje', href: index() },
            { title: 'Zgłoszenie adopcyjne' },
        ],
    },
});
const taskStatuses: Record<string, string> = {
    todo: 'Do wykonania',
    in_progress: 'W trakcie',
    done: 'Wykonane',
};
function date(value: string) {
    return new Intl.DateTimeFormat('pl-PL', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
</script>
<template>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
        <Head :title="`Adopcja — ${application.candidate_name}`" /><Link
            :href="animalIndex(application.animal_id)"
            class="text-sm font-medium text-primary"
            >← {{ application.animal.name }} · zgłoszenia adopcyjne</Link
        >
        <header class="space-y-2">
            <p class="text-sm text-muted-foreground">
                Zgłoszenie #{{ application.id }} · {{ application.animal.name }}
            </p>
            <h1 class="text-3xl font-bold">{{ application.candidate_name }}</h1>
            <p class="text-primary">
                {{ statuses[application.status]
                }}{{
                    application.adopted_on
                        ? ` · wydanie ${application.adopted_on}`
                        : ''
                }}
            </p>
        </header>
        <section v-if="application.returned_on" class="settings-card">
            <h2 class="text-lg font-bold">
                Powrót z adopcji · {{ application.returned_on }}
            </h2>
            <p class="text-sm">Przyjął(a): {{ application.received_by }}</p>
            <p class="text-sm whitespace-pre-wrap">
                {{ application.return_reason }}
            </p>
            <Link
                :href="animalIndex(application.animal_id)"
                class="inline-block text-sm font-medium text-primary"
                >Przejdź do zgłoszeń zwierzęcia →</Link
            >
        </section>
        <div class="grid items-start gap-6 lg:grid-cols-2">
            <div class="min-w-0 space-y-6">
                <section class="settings-card">
                    <h2 class="text-xl font-bold">Kandydat i kontakt</h2>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                E-mail / telefon
                            </dt>
                            <dd class="break-words">
                                {{ application.email || 'Brak e-maila' }} ·
                                {{ application.phone || 'Brak telefonu' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Warunki i przygotowanie
                            </dt>
                            <dd class="break-words whitespace-pre-wrap">
                                {{ application.housing }}
                            </dd>
                        </div>
                        <div v-if="application.candidate_notes">
                            <dt class="text-sm text-muted-foreground">
                                Notatki z rozmów
                            </dt>
                            <dd class="break-words whitespace-pre-wrap">
                                {{ application.candidate_notes }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Osoba prowadząca
                            </dt>
                            <dd>
                                {{
                                    application.coordinator?.name ??
                                    'Brak osoby prowadzącej'
                                }}
                            </dd>
                        </div>
                    </dl>
                </section>
                <section class="settings-card">
                    <h2 class="text-xl font-bold">Dokumenty</h2>
                    <p
                        v-if="!application.attachments.length"
                        class="text-sm text-muted-foreground"
                    >
                        Nie dodano dokumentów.
                    </p>
                    <ul class="space-y-3">
                        <li
                            v-for="attachment in application.attachments"
                            :key="attachment.id"
                        >
                            <a
                                :href="download.url(attachment.id)"
                                class="text-sm break-all text-primary underline"
                                >{{ attachment.name }}</a
                            >
                        </li>
                    </ul>
                </section>
                <section class="settings-card">
                    <h2 class="text-xl font-bold">Zadania procesu</h2>
                    <p
                        v-if="!tasks.data.length"
                        class="text-sm text-muted-foreground"
                    >
                        Brak powiązanych zadań.
                    </p>
                    <p
                        v-if="!editable && tasks.data.length"
                        class="text-sm text-muted-foreground"
                    >
                        Sprawdź pozostałe obowiązki — zamknięcie zgłoszenia nie
                        oznacza wykonania jego zadań.
                    </p>
                    <Link
                        v-for="task in tasks.data"
                        :key="task.id"
                        :href="
                            taskShow({ animal: task.animal_id, task: task.id })
                        "
                        class="block rounded-xl border p-3 text-sm hover:bg-accent"
                        ><strong>{{ task.title }}</strong
                        ><span class="mt-1 block text-muted-foreground"
                            >{{ task.due_date }} ·
                            {{ taskStatuses[task.status] }}</span
                        ></Link
                    ><CarePagination :pages="tasks" />
                </section>
                <section class="settings-card">
                    <h2 class="text-xl font-bold">Historia procesu</h2>
                    <ol class="divide-y">
                        <li
                            v-for="entry in history.data"
                            :key="entry.id"
                            class="space-y-3 py-4 first:pt-0"
                        >
                            <div class="flex flex-wrap justify-between gap-2">
                                <strong class="text-sm">{{
                                    entry.author_name
                                }}</strong
                                ><time
                                    :datetime="entry.created_at"
                                    class="text-xs text-muted-foreground"
                                    >{{ date(entry.created_at) }}</time
                                >
                            </div>
                            <p class="text-sm font-medium text-primary">
                                {{ statuses[entry.snapshot.status] }}
                            </p>
                            <p
                                v-if="entry.note"
                                class="text-sm break-words whitespace-pre-wrap"
                            >
                                {{ entry.note }}
                            </p>
                            <p
                                v-if="entry.snapshot.event_type === 'aftercare'"
                                class="text-sm text-primary"
                            >
                                Kontakt poadopcyjny ·
                                {{ entry.snapshot.contact_on }}
                            </p>
                            <p
                                v-if="entry.snapshot.event_type === 'return'"
                                class="text-sm text-primary"
                            >
                                Powrót · {{ entry.snapshot.returned_on }} ·
                                przyjął(a): {{ entry.snapshot.received_by }}
                            </p>
                            <details class="text-sm">
                                <summary
                                    class="cursor-pointer text-muted-foreground"
                                >
                                    Dane w chwili zapisu
                                </summary>
                                <div
                                    class="mt-3 space-y-2 rounded-lg bg-muted/50 p-3"
                                >
                                    <p>
                                        {{ entry.snapshot.candidate_name }} ·
                                        {{ entry.snapshot.email }} ·
                                        {{ entry.snapshot.phone }}
                                    </p>
                                    <p class="whitespace-pre-wrap">
                                        {{ entry.snapshot.housing }}
                                    </p>
                                    <p class="whitespace-pre-wrap">
                                        {{ entry.snapshot.candidate_notes }}
                                    </p>
                                    <p>
                                        Prowadzi:
                                        {{
                                            entry.snapshot.coordinator_name ??
                                            'Brak osoby'
                                        }}
                                    </p>
                                    <p v-if="entry.snapshot.adopted_on">
                                        Wydanie: {{ entry.snapshot.adopted_on }}
                                    </p>
                                </div>
                            </details>
                        </li>
                    </ol>
                    <CarePagination :pages="history" />
                </section>
            </div>
            <aside class="settings-card min-w-0">
                <h2
                    v-if="application.status !== 'completed'"
                    class="text-xl font-bold"
                >
                    {{
                        editable
                            ? 'Prowadzenie zgłoszenia'
                            : 'Status zgłoszenia'
                    }}
                </h2>
                <template v-if="editable"
                    ><p class="text-sm text-muted-foreground">
                        Decyzję o zgodzie, odmowie i wydaniu zatwierdza
                        administrator schroniska. Każdy zapis pozostaje w
                        historii.
                    </p>
                    <AdoptionForm
                        :key="`${application.id}-${application.version}`"
                        :animal-id="application.animal_id"
                        :application="application"
                        :statuses="statuses"
                        :transitions="transitions"
                        :members="members"
                        :today="today"
                /></template>
                <AdoptionAftercare
                    v-else-if="application.status === 'completed'"
                    :key="`${application.id}-${application.version}`"
                    :application="application"
                    :can-register-return="canRegisterReturn"
                    :members="members"
                    :today="today"
                />
                <p v-else class="text-sm text-muted-foreground">
                    {{
                        application.status === 'approved'
                            ? 'Zgłoszenie czeka na wydanie zwierzęcia przez administratora.'
                            : 'Proces jest zamknięty. Dane, dokumenty i historia pozostają dostępne do wglądu.'
                    }}
                </p>
            </aside>
        </div>
    </div>
</template>
