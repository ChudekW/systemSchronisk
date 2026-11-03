<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CareTaskForm from '@/components/CareTaskForm.vue';
import CarePagination from '@/components/CarePagination.vue';
import { index } from '@/routes/tasks';
import { index as animalTasks } from '@/routes/animals/tasks';
import type {
    CareAnimal,
    CareTask,
    CareOptions,
    CareHistory,
} from '@/types/care-task';
import type { Paginated } from '@/types/animal';
defineProps<{
    animal: CareAnimal;
    task: CareTask;
    options: CareOptions;
    history: Paginated<CareHistory>;
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Moje zadania', href: index() },
            { title: 'Szczegóły zadania' },
        ],
    },
});
function date(value: string) {
    return new Intl.DateTimeFormat('pl-PL', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
</script>
<template>
    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-8">
        <Head :title="task.title" />
        <Link
            :href="animalTasks(animal.id)"
            class="text-sm font-medium text-primary"
            >← {{ animal.name }} · opieka i zadania</Link
        >
        <header class="space-y-2">
            <p class="text-sm text-muted-foreground">
                {{ animal.registration_number }} · {{ animal.name }}
            </p>
            <h1 class="text-3xl font-bold break-words">{{ task.title }}</h1>
            <p class="text-muted-foreground">
                {{ options.statuses[task.status] }} · priorytet
                {{ options.priorities[task.priority].toLowerCase() }}
            </p>
        </header>
        <div class="grid items-start gap-6 lg:grid-cols-2">
            <section class="settings-card">
                <h2 class="text-xl font-bold">Szczegóły i realizacja</h2>
                <CareTaskForm
                    :key="`${task.id}-${task.version}`"
                    :animal-id="animal.id"
                    :task="task"
                    :options="options"
                />
            </section>
            <section class="settings-card">
                <h2 class="text-xl font-bold">Historia opieki</h2>
                <p class="text-sm text-muted-foreground">
                    Zapisane czynności i kolejne wersje zadania — od najnowszej.
                </p>
                <ol class="divide-y">
                    <li
                        v-for="entry in history.data"
                        :key="entry.id"
                        class="space-y-3 py-5 first:pt-0"
                    >
                        <div class="flex flex-wrap justify-between gap-2">
                            <strong>{{ entry.author_name }}</strong
                            ><time
                                :datetime="entry.created_at"
                                class="text-xs text-muted-foreground"
                                >{{ date(entry.created_at) }}</time
                            >
                        </div>
                        <p class="text-sm font-medium text-primary">
                            {{ options.statuses[entry.snapshot.status] }}
                        </p>
                        <p
                            v-if="entry.note"
                            class="text-sm break-words whitespace-pre-wrap"
                        >
                            {{ entry.note }}
                        </p>
                        <details class="text-sm">
                            <summary
                                class="cursor-pointer text-muted-foreground"
                            >
                                Dane zadania w chwili zapisu
                            </summary>
                            <dl
                                class="mt-3 space-y-2 rounded-lg bg-muted/50 p-3"
                            >
                                <div>
                                    <dt class="text-muted-foreground">
                                        Zadanie
                                    </dt>
                                    <dd>{{ entry.snapshot.title }}</dd>
                                </div>
                                <div>
                                    <dt class="text-muted-foreground">
                                        Osoba odpowiedzialna
                                    </dt>
                                    <dd>{{ entry.snapshot.assignee_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-muted-foreground">
                                        Termin / priorytet
                                    </dt>
                                    <dd>
                                        {{ entry.snapshot.due_date }} ·
                                        {{
                                            options.priorities[
                                                entry.snapshot.priority
                                            ]
                                        }}
                                    </dd>
                                </div>
                                <div v-if="entry.snapshot.description">
                                    <dt class="text-muted-foreground">
                                        Wskazówki
                                    </dt>
                                    <dd class="whitespace-pre-wrap">
                                        {{ entry.snapshot.description }}
                                    </dd>
                                </div>
                            </dl>
                        </details>
                    </li>
                </ol>
                <CarePagination :pages="history" />
            </section>
        </div>
    </div>
</template>
