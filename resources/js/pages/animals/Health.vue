<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { HeartPulse, Paperclip } from '@lucide/vue';
import { ref, nextTick } from 'vue';
import AnimalTabs from '@/components/AnimalTabs.vue';
import CarePagination from '@/components/CarePagination.vue';
import MedicalRecordForm from '@/components/MedicalRecordForm.vue';
import { Button } from '@/components/ui/button';
import { index as animals } from '@/routes/animals';
import { index, download } from '@/routes/animals/health';
import { show as taskShow } from '@/routes/animals/tasks';
import type { CareAnimal } from '@/types/care-task';
import type { Paginated } from '@/types/animal';
import type { MedicalRecord } from '@/types/medical';
defineProps<{
    animal: CareAnimal;
    records: Paginated<MedicalRecord>;
    kinds: Record<string, string>;
    filter: string;
    canManageMedical: boolean;
    members: { id: number; name: string }[];
    today: string;
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Zwierzęta', href: animals() },
            { title: 'Zdrowie' },
        ],
    },
});
const correction = ref<MedicalRecord | null>(null);
const editor = ref<HTMLElement | null>(null);
async function correct(record: MedicalRecord) {
    correction.value = record;
    await nextTick();
    editor.value?.scrollIntoView({ block: 'start' });
    editor.value?.focus({ preventScroll: true });
}
function date(value: string, time = false) {
    return new Intl.DateTimeFormat('pl-PL', {
        dateStyle: 'medium',
        ...(time ? { timeStyle: 'short' as const } : {}),
    }).format(new Date(time ? value : `${value}T12:00:00`));
}
const taskStatuses: Record<string, string> = {
    todo: 'Do wykonania',
    in_progress: 'W trakcie',
    done: 'Wykonane',
};
</script>
<template>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
        <Head :title="`${animal.name} — zdrowie`" />
        <header class="space-y-2">
            <p class="text-sm font-medium text-primary">
                {{ $page.props.auth.organization?.shelter_name }} ·
                {{ animal.registration_number }}
            </p>
            <h1 class="text-3xl font-bold">{{ animal.name }}</h1>
            <p class="text-muted-foreground">
                Zdrowie, zalecenia i obserwacje — wspólna historia opieki.
            </p>
        </header>
        <AnimalTabs :animal-id="animal.id" active="health" />
        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_28rem]">
            <section class="space-y-4">
                <h2 class="text-xl font-bold">Historia zdrowia</h2>
                <nav
                    class="flex flex-wrap gap-2"
                    aria-label="Rodzaj dokumentacji"
                >
                    <Link
                        v-for="(label, kind) in { '': 'Wszystkie', ...kinds }"
                        :key="kind"
                        :href="index(animal.id, { query: { kind } })"
                        class="rounded-full border px-3 py-1.5 text-sm"
                        :class="
                            filter === kind
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'bg-card hover:bg-accent'
                        "
                        :aria-current="filter === kind ? 'page' : undefined"
                        >{{ label }}</Link
                    >
                </nav>
                <div
                    v-if="!records.data.length"
                    class="settings-card items-center py-12 text-center"
                >
                    <HeartPulse
                        class="size-10 text-primary/50"
                        aria-hidden="true"
                    />
                    <h3 class="text-lg font-bold">Brak wpisów w tym widoku</h3>
                    <p class="text-sm text-muted-foreground">
                        Dodaj pierwszy wpis lub wybierz inny rodzaj
                        dokumentacji.
                    </p>
                </div>
                <article
                    v-for="record in records.data"
                    :key="record.id"
                    :id="`record-${record.id}`"
                    class="settings-card min-w-0 scroll-mt-8"
                >
                    <div class="flex flex-wrap justify-between gap-2">
                        <span
                            class="rounded-full bg-accent px-3 py-1 text-xs font-medium"
                            >{{ kinds[record.kind] }}</span
                        ><time
                            :datetime="record.occurred_on"
                            class="text-sm text-muted-foreground"
                            >{{ date(record.occurred_on) }}</time
                        >
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-lg font-bold break-words">
                            {{ record.title }}
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Wpis #{{ record.id }} · {{ record.author_name }} ·
                            zapisano {{ date(record.created_at, true) }}
                        </p>
                        <p
                            v-if="record.performed_by"
                            class="text-sm text-muted-foreground"
                        >
                            Wykonał(a): {{ record.performed_by }}
                        </p>
                    </div>
                    <p
                        v-if="record.corrections_count"
                        class="rounded-lg bg-muted p-3 text-sm"
                    >
                        Ten wpis ma korekty ({{ record.corrections_count }}).
                        Oryginalna treść pozostaje w historii.
                    </p>
                    <div
                        v-if="record.correction_of_id"
                        class="rounded-lg border border-primary/20 bg-primary/5 p-3 text-sm"
                    >
                        <strong
                            >Korekta wpisu #{{
                                record.correction_of_id
                            }}</strong
                        >
                        <p class="mt-1 whitespace-pre-wrap">
                            {{ record.correction_reason }}
                        </p>
                    </div>
                    <p
                        class="text-sm leading-relaxed break-words whitespace-pre-wrap"
                    >
                        {{ record.description }}
                    </p>
                    <p v-if="record.weight_kg" class="text-sm font-medium">
                        Masa ciała:
                        {{
                            new Intl.NumberFormat('pl-PL', {
                                maximumFractionDigits: 3,
                            }).format(Number(record.weight_kg))
                        }}
                        kg
                    </p>
                    <div
                        v-if="record.recommendations"
                        class="space-y-2 rounded-xl bg-accent/40 p-4"
                    >
                        <h4 class="text-sm font-semibold">
                            Zalecenia dla zespołu
                        </h4>
                        <p class="text-sm break-words whitespace-pre-wrap">
                            {{ record.recommendations }}
                        </p>
                    </div>
                    <Link
                        v-if="record.care_task"
                        :href="
                            taskShow({
                                animal: animal.id,
                                task: record.care_task.id,
                            })
                        "
                        class="block w-full min-w-0 rounded-xl border p-4 text-sm break-words transition-colors hover:bg-accent/30 focus-visible:outline-2 focus-visible:outline-ring"
                        ><span class="font-semibold"
                            >Powiązane zadanie ·
                            {{ date(record.care_task.due_date) }}</span
                        ><span class="mt-1 block"
                            >{{ record.care_task.title }} ·
                            {{ taskStatuses[record.care_task.status] }}</span
                        ></Link
                    >
                    <ul v-if="record.attachments.length" class="space-y-2">
                        <li
                            v-for="attachment in record.attachments"
                            :key="attachment.id"
                        >
                            <a
                                :href="
                                    download.url({
                                        animal: animal.id,
                                        attachment: attachment.id,
                                    })
                                "
                                class="inline-flex items-center gap-2 text-sm text-primary underline"
                                ><Paperclip
                                    class="size-4 shrink-0"
                                    aria-hidden="true"
                                /><span class="break-all">{{
                                    attachment.name
                                }}</span
                                ><span
                                    class="shrink-0 text-xs text-muted-foreground"
                                    >({{
                                        Math.max(
                                            1,
                                            Math.round(attachment.size / 1024),
                                        )
                                    }}
                                    KB)</span
                                ></a
                            >
                        </li>
                    </ul>
                    <Button
                        v-if="
                            canManageMedical ||
                            (record.kind === 'observation' &&
                                record.user_id === $page.props.auth.user.id)
                        "
                        variant="outline"
                        size="sm"
                        class="self-start"
                        @click="correct(record)"
                        >Dodaj korektę</Button
                    >
                </article>
                <CarePagination :pages="records" />
            </section>
            <aside
                ref="editor"
                tabindex="-1"
                class="settings-card scroll-mt-8 focus:outline-none"
            >
                <h2 class="text-xl font-bold">
                    {{
                        correction
                            ? 'Korekta wpisu'
                            : canManageMedical
                              ? 'Nowy wpis'
                              : 'Nowa obserwacja'
                    }}
                </h2>
                <p
                    v-if="!canManageMedical"
                    class="text-sm text-muted-foreground"
                >
                    Możesz zapisać obserwacje. Wpisy medyczne i zalecenia
                    uzupełnia weterynarz lub administrator.
                </p>
                <MedicalRecordForm
                    :key="correction?.id ?? 'new'"
                    :animal-id="animal.id"
                    :kinds="kinds"
                    :can-manage-medical="canManageMedical"
                    :members="members"
                    :today="today"
                    :correction="correction"
                    @cancel="correction = null"
                    @saved="correction = null"
                />
            </aside>
        </div>
    </div>
</template>
