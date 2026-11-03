<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { vAutoAnimate } from '@formkit/auto-animate';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/animals/adoptions';
import { update } from '@/routes/adoptions';
import type { Adoption } from '@/types/adoption';
const props = defineProps<{
    animalId: number;
    application?: Adoption;
    statuses: Record<string, string>;
    transitions?: string[];
    members: { id: number; name: string }[];
    today: string;
}>();
const files = ref<HTMLInputElement | null>(null);
const form = useForm({
    candidate_name: props.application?.candidate_name ?? '',
    email: props.application?.email ?? '',
    phone: props.application?.phone ?? '',
    housing: props.application?.housing ?? '',
    candidate_notes: props.application?.candidate_notes ?? '',
    coordinator_id: props.application?.coordinator_id ?? '',
    status: props.application?.status ?? 'new',
    note: '',
    adopted_on: '',
    version: props.application?.version ?? 1,
    attachments: [] as File[],
    task_title: '',
    task_due_date: '',
});
watch(
    () => form.status,
    (status) => {
        if (
            !['new', 'reviewing', 'meeting', 'approved', 'completed'].includes(
                status,
            )
        ) {
            form.task_title = '';
            form.task_due_date = '';
        }
    },
);
const fieldClass =
    'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus-visible:outline-2 focus-visible:outline-ring';
function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('note', 'attachments', 'task_title', 'task_due_date');
            if (files.value) files.value.value = '';
        },
    };
    if (props.application)
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            update.url(props.application.id),
            options,
        );
    else form.post(store.url(props.animalId), options);
}
</script>
<template>
    <form
        class="space-y-5"
        @submit.prevent="submit"
        v-auto-animate="{ duration: 180 }"
    >
        <InputError :message="form.errors.version" role="alert" />
        <div class="space-y-2">
            <Label for="adoption-name">Imię i nazwisko kandydata</Label
            ><Input
                id="adoption-name"
                v-model="form.candidate_name"
                required
                maxlength="160"
            /><InputError :message="form.errors.candidate_name" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
                <Label for="adoption-email">E-mail</Label
                ><Input
                    id="adoption-email"
                    v-model="form.email"
                    type="email"
                    maxlength="255"
                /><InputError :message="form.errors.email" />
            </div>
            <div class="space-y-2">
                <Label for="adoption-phone">Telefon</Label
                ><Input
                    id="adoption-phone"
                    v-model="form.phone"
                    type="tel"
                    maxlength="50"
                /><InputError :message="form.errors.phone" />
            </div>
        </div>
        <p class="text-xs text-muted-foreground">
            Podaj co najmniej jeden sposób kontaktu.
        </p>
        <div class="space-y-2">
            <Label for="adoption-housing"
                >Warunki i przygotowanie do adopcji</Label
            ><textarea
                id="adoption-housing"
                v-model="form.housing"
                :class="fieldClass"
                rows="3"
                required
                maxlength="5000"
                placeholder="Warunki mieszkaniowe, domownicy, inne zwierzęta, doświadczenie…"
            /><InputError :message="form.errors.housing" />
        </div>
        <div class="space-y-2">
            <Label for="adoption-notes">Notatki z rozmów</Label
            ><textarea
                id="adoption-notes"
                v-model="form.candidate_notes"
                :class="fieldClass"
                rows="3"
                maxlength="5000"
            /><InputError :message="form.errors.candidate_notes" />
        </div>
        <div class="space-y-2">
            <Label for="adoption-coordinator">Osoba prowadząca</Label
            ><select
                id="adoption-coordinator"
                v-model="form.coordinator_id"
                :class="fieldClass"
                required
            >
                <option value="" disabled>Wybierz pracownika</option>
                <option
                    v-if="
                        application?.coordinator_id &&
                        !members.some(
                            (member) =>
                                member.id === application?.coordinator_id,
                        )
                    "
                    :value="application.coordinator_id"
                    disabled
                >
                    {{ application.coordinator?.name ?? 'Usunięte konto' }} —
                    nieaktywne
                </option>
                <option
                    v-for="member in members"
                    :key="member.id"
                    :value="member.id"
                >
                    {{ member.name }}
                </option></select
            ><InputError :message="form.errors.coordinator_id" />
        </div>
        <div v-if="application" class="space-y-2">
            <Label for="adoption-status">Etap procesu</Label
            ><select
                id="adoption-status"
                v-model="form.status"
                :class="fieldClass"
            >
                <option :value="application.status">
                    {{ statuses[application.status] }}
                </option>
                <option
                    v-for="status in transitions"
                    :key="status"
                    :value="status"
                >
                    {{ statuses[status] }}
                </option>
            </select>
        </div>
        <InputError :message="form.errors.status" />
        <div v-if="application" class="space-y-2">
            <Label for="adoption-note">Notatka / uzasadnienie decyzji</Label
            ><textarea
                id="adoption-note"
                v-model="form.note"
                :class="fieldClass"
                rows="3"
                maxlength="5000"
                :required="
                    ['approved', 'completed', 'rejected', 'withdrawn'].includes(
                        form.status,
                    )
                "
            /><InputError :message="form.errors.note" />
        </div>
        <div
            v-if="form.status === 'completed'"
            class="space-y-3 rounded-xl border border-primary/30 bg-primary/5 p-4"
        >
            <p class="text-sm">
                Zapis oznaczy zwierzę jako adoptowane i zamknie pozostałe
                otwarte zgłoszenia. Przed finalizacją dołącz dokument adopcji.
            </p>
            <Label for="adoption-date">Data wydania zwierzęcia</Label
            ><Input
                id="adoption-date"
                v-model="form.adopted_on"
                type="date"
                :max="today"
                required
            /><InputError :message="form.errors.adopted_on" />
        </div>
        <fieldset
            v-if="
                [
                    'new',
                    'reviewing',
                    'meeting',
                    'approved',
                    'completed',
                ].includes(form.status)
            "
            class="space-y-3 rounded-xl border p-4"
        >
            <legend class="px-1 text-sm font-semibold">
                {{
                    form.status === 'completed'
                        ? 'Kontakt poadopcyjny dla osoby prowadzącej (opcjonalnie)'
                        : 'Zadanie dla osoby prowadzącej (opcjonalnie)'
                }}
            </legend>
            <Label for="adoption-task">Czynność do wykonania</Label
            ><Input
                id="adoption-task"
                v-model="form.task_title"
                maxlength="160"
                placeholder="Np. umówić spotkanie zapoznawcze"
            /><InputError :message="form.errors.task_title" /><template
                v-if="form.task_title"
                ><Label for="adoption-task-date">Termin zadania</Label
                ><Input
                    id="adoption-task-date"
                    v-model="form.task_due_date"
                    type="date"
                    :min="today"
                    required /><InputError :message="form.errors.task_due_date"
            /></template>
        </fieldset>
        <div class="space-y-2">
            <Label for="adoption-files">Dokumenty</Label
            ><input
                id="adoption-files"
                ref="files"
                type="file"
                multiple
                accept=".pdf,.jpg,.jpeg,.png"
                :class="fieldClass"
                @change="
                    form.attachments = Array.from(
                        ($event.target as HTMLInputElement).files ?? [],
                    )
                "
            />
            <p class="text-xs text-muted-foreground">
                Do 5 plików na zapis, PDF/JPG/PNG do 10 MB. Dokumenty pozostają
                prywatne dla schroniska.
            </p>
            <InputError
                v-for="(message, key) in form.errors"
                :key="key"
                :message="
                    String(key).startsWith('attachments') ? message : undefined
                "
            />
        </div>
        <progress
            v-if="form.progress"
            :value="form.progress.percentage"
            max="100"
            class="w-full"
            aria-label="Wysyłanie dokumentów"
        />
        <Button :disabled="form.processing">{{
            form.processing
                ? 'Zapisywanie…'
                : form.status === 'completed'
                  ? 'Zatwierdź wydanie i zakończ adopcję'
                  : application
                    ? 'Zapisz zmiany'
                    : 'Dodaj zgłoszenie'
        }}</Button>
    </form>
</template>
