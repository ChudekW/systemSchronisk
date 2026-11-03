<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, ArrowRight } from '@lucide/vue';
import CareTaskForm from '@/components/CareTaskForm.vue';
import CarePagination from '@/components/CarePagination.vue';
import { index } from '@/routes/tasks';
import AnimalTabs from '@/components/AnimalTabs.vue';
import { index as animalTasks, show } from '@/routes/animals/tasks';
import type { CareAnimal, CareTask, CareOptions } from '@/types/care-task';
import type { Paginated } from '@/types/animal';
const props = defineProps<{
    animal: CareAnimal | null;
    tasks: Paginated<CareTask>;
    options: CareOptions;
    filter: string;
    today: string;
    overdueCount: number;
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Moje zadania', href: index() },
            { title: 'Opieka i zadania' },
        ],
    },
});
const filters = {
    open: 'Otwarte',
    overdue: 'Zaległe',
    done: 'Wykonane',
    all: 'Wszystkie',
};
function filterUrl(filter: string) {
    return props.animal
        ? animalTasks.url(props.animal.id, { query: { filter } })
        : index.url({ query: { filter } });
}
function date(value: string) {
    return new Intl.DateTimeFormat('pl-PL', { dateStyle: 'medium' }).format(
        new Date(`${value}T12:00:00`),
    );
}
</script>
<template>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
        <Head
            :title="
                animal ? `${animal.name} — opieka i zadania` : 'Moje zadania'
            "
        />
        <header class="space-y-2">
            <p class="text-sm font-medium text-primary">
                {{ $page.props.auth.organization?.shelter_name }}
            </p>
            <h1 class="text-3xl font-bold">
                {{ animal ? animal.name : 'Moje zadania' }}
            </h1>
            <p class="text-muted-foreground">
                {{
                    animal
                        ? 'Plan opieki, obowiązki i historia ich wykonania.'
                        : 'Twoje codzienne obowiązki. Nowe zadanie dodasz z kartoteki zwierzęcia.'
                }}
            </p>
        </header>
        <AnimalTabs v-if="animal" :animal-id="animal.id" active="tasks" />
        <div
            v-if="overdueCount"
            class="rounded-xl border border-destructive/20 bg-destructive/5 p-4 text-sm"
        >
            <strong>{{ overdueCount }}</strong> — liczba zaległych zadań.
            <Link :href="filterUrl('overdue')" class="font-medium underline"
                >Pokaż zaległe</Link
            >
        </div>
        <div
            class="grid items-start gap-6"
            :class="animal && 'xl:grid-cols-[minmax(0,1fr)_26rem]'"
        >
            <section class="space-y-4">
                <nav class="flex flex-wrap gap-2" aria-label="Filtr zadań">
                    <Link
                        v-for="(label, key) in filters"
                        :key="key"
                        :href="filterUrl(key)"
                        class="rounded-full border px-4 py-2 text-sm transition-colors"
                        :class="
                            filter === key
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'bg-card hover:bg-accent'
                        "
                        :aria-current="filter === key ? 'page' : undefined"
                        >{{ label }}</Link
                    >
                </nav>
                <div
                    v-if="!tasks.data.length"
                    class="settings-card items-center py-12 text-center"
                >
                    <ClipboardList
                        class="size-10 text-primary/50"
                        aria-hidden="true"
                    />
                    <h2 class="text-lg font-bold">Brak zadań w tym widoku</h2>
                    <p class="text-sm text-muted-foreground">
                        {{
                            animal
                                ? 'Dodaj pierwsze zadanie lub zmień filtr.'
                                : 'Nie masz przypisanych zadań pasujących do tego filtra.'
                        }}
                    </p>
                </div>
                <article
                    v-for="task in tasks.data"
                    :key="task.id"
                    class="settings-card"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div class="space-y-1">
                            <p v-if="!animal" class="text-sm text-primary">
                                {{ task.animal?.name }} ·
                                {{ task.animal?.registration_number }}
                            </p>
                            <h2 class="text-lg font-bold">
                                <Link
                                    :href="
                                        show({
                                            animal: task.animal_id,
                                            task: task.id,
                                        })
                                    "
                                    class="hover:text-primary"
                                    >{{ task.title }}</Link
                                >
                            </h2>
                        </div>
                        <span
                            class="rounded-full bg-accent px-3 py-1 text-xs font-medium"
                            >{{ options.statuses[task.status] }}</span
                        >
                    </div>
                    <p
                        v-if="task.description"
                        class="line-clamp-2 text-sm text-muted-foreground"
                    >
                        {{ task.description }}
                    </p>
                    <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                        <span
                            :class="
                                task.status !== 'done' && task.due_date < today
                                    ? 'font-semibold text-destructive'
                                    : 'text-muted-foreground'
                            "
                            >{{
                                task.status !== 'done' && task.due_date < today
                                    ? 'Po terminie · '
                                    : 'Termin · '
                            }}{{ date(task.due_date) }}</span
                        ><span
                            >Priorytet:
                            {{ options.priorities[task.priority] }}</span
                        ><span
                            >{{
                                task.assignee?.name ??
                                'Brak osoby — przypisz ponownie'
                            }}{{
                                task.assignee && !task.assignee.is_active
                                    ? ' (konto nieaktywne)'
                                    : ''
                            }}</span
                        >
                    </div>
                    <Link
                        :href="show({ animal: task.animal_id, task: task.id })"
                        class="inline-flex items-center gap-2 text-sm font-medium text-primary"
                        >Szczegóły i historia
                        <ArrowRight class="size-4" aria-hidden="true"
                    /></Link>
                </article>
                <CarePagination :pages="tasks" />
            </section>
            <aside v-if="animal" class="settings-card">
                <h2 class="text-xl font-bold">Nowe zadanie</h2>
                <CareTaskForm
                    :animal-id="animal.id"
                    :options="options"
                    :today="today"
                />
            </aside>
        </div>
    </div>
</template>
